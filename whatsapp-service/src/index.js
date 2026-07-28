import 'dotenv/config'

import makeWASocket, {
  Browsers,
  DisconnectReason,
  fetchLatestBaileysVersion,
  useMultiFileAuthState,
} from '@whiskeysockets/baileys'
import express from 'express'
import fs from 'node:fs/promises'
import path from 'node:path'
import Pino from 'pino'
import QRCode from 'qrcode'
import qrcode from 'qrcode-terminal'

const port = Number(process.env.PORT ?? 3001)
const apiToken = process.env.WHATSAPP_API_TOKEN
const authRoot = process.env.WHATSAPP_AUTH_DIR ?? './auth_info'
const qrTimeoutMs = Number(process.env.WHATSAPP_QR_TIMEOUT_MS ?? 60000)
const logger = Pino({ level: process.env.LOG_LEVEL ?? 'info' })
const sessions = new Map()

function getSession(tenantId) {
  if (!sessions.has(tenantId)) {
    sessions.set(tenantId, {
      socket: null,
      state: 'disconnected',
      qr: null,
      lastError: null,
      qrTimer: null,
    })
  }

  return sessions.get(tenantId)
}

function tenantKey(request) {
  return String(request.header('x-tenant-id') || 'default').replace(/[^a-zA-Z0-9_-]/g, '_')
}

function authPath(tenantId) {
  return path.join(authRoot, tenantId)
}

function requireApiToken(request, response, next) {
  if (!apiToken || request.header('x-api-key') !== apiToken) {
    return response.status(401).json({ message: 'Unauthorized' })
  }

  next()
}

function normalizeJid(value) {
  if (value.endsWith('@g.us') || value.endsWith('@s.whatsapp.net')) return value
  return `${value.replace(/\D/g, '')}@s.whatsapp.net`
}

async function connectWhatsApp(tenantId) {
  const session = getSession(tenantId)

  if (session.socket || session.state === 'connecting' || session.state === 'qr_required') return

  session.state = 'connecting'
  const { state, saveCreds } = await useMultiFileAuthState(authPath(tenantId))
  const { version } = await fetchLatestBaileysVersion()

  const socket = makeWASocket({
    version,
    auth: state,
    browser: Browsers.ubuntu('Wabot'),
    logger,
    printQRInTerminal: false,
    markOnlineOnConnect: false,
  })

  session.socket = socket
  socket.ev.on('creds.update', saveCreds)
  socket.ev.on('connection.update', async ({ connection, lastDisconnect, qr }) => {
    if (qr) {
      if (session.qrTimer) clearTimeout(session.qrTimer)
      session.qr = await QRCode.toDataURL(qr, { margin: 1, width: 320 })
      session.state = 'qr_required'
      qrcode.generate(qr, { small: true })

      session.qrTimer = setTimeout(() => {
        if (session.socket === socket && session.state === 'qr_required') {
          logger.info({ tenantId }, 'QR Code expired; restarting WhatsApp connection')
          socket.end(new Error('QR Code expired'))
        }
      }, qrTimeoutMs)
    }

    if (connection === 'open') {
      if (session.qrTimer) clearTimeout(session.qrTimer)
      session.qrTimer = null
      session.state = 'connected'
      session.qr = null
      session.lastError = null
      logger.info({ tenantId }, 'WhatsApp connected')
    }

    if (connection === 'close') {
      if (session.qrTimer) clearTimeout(session.qrTimer)
      session.qrTimer = null
      session.socket = null
      session.state = 'disconnected'
      const statusCode = lastDisconnect?.error?.output?.statusCode
      session.lastError = lastDisconnect?.error?.message ?? 'Connection closed'

      if (statusCode !== DisconnectReason.loggedOut) {
        setTimeout(() => connectWhatsApp(tenantId).catch((error) => {
          session.state = 'error'
          session.lastError = error.message ?? 'Failed to reconnect WhatsApp'
        }), 3000)
      } else {
        logger.warn({ tenantId }, 'WhatsApp session logged out; QR scan is required')
      }
    }
  })
}

async function disconnectWhatsApp(tenantId) {
  const session = getSession(tenantId)

  if (session.socket) {
    try { await session.socket.logout() } catch (error) { logger.warn(error, 'WhatsApp logout returned an error') }
  }

  if (session.qrTimer) clearTimeout(session.qrTimer)
  session.socket = null
  session.state = 'disconnected'
  session.qr = null
  session.lastError = null
  await fs.rm(authPath(tenantId), { recursive: true, force: true })
}

async function waitForConnectionState(tenantId, timeoutMs = 10000) {
  const startedAt = Date.now()
  while (Date.now() - startedAt < timeoutMs) {
    const session = getSession(tenantId)
    if (['connected', 'error'].includes(session.state) || (session.state === 'qr_required' && session.qr)) return
    await new Promise((resolve) => setTimeout(resolve, 100))
  }
}

const app = express()
app.use(express.json({ limit: '1mb' }))

app.get('/health', (_request, response) => response.json({ ok: true, service: 'whatsapp-service' }))
app.use(requireApiToken)

app.get('/status', (request, response) => {
  const tenantId = tenantKey(request)
  const session = getSession(tenantId)
  response.json({ state: session.state, qr: session.qr, last_error: session.lastError, phone: session.socket?.user?.id ?? null })
})

app.post('/connect', async (request, response) => {
  const tenantId = tenantKey(request)
  const session = getSession(tenantId)
  try {
    await connectWhatsApp(tenantId)
    await waitForConnectionState(tenantId)
    response.json({ ok: true, state: session.state, qr: session.qr })
  } catch (error) {
    session.state = 'error'
    session.lastError = error.message ?? 'Failed to connect WhatsApp'
    response.status(502).json({ message: session.lastError })
  }
})

app.post('/logout', async (request, response) => {
  try {
    await disconnectWhatsApp(tenantKey(request))
    response.json({ ok: true, state: 'disconnected' })
  } catch (error) {
    response.status(502).json({ message: error.message ?? 'Failed to logout WhatsApp' })
  }
})

app.get('/groups', async (request, response) => {
  const session = getSession(tenantKey(request))
  if (!session.socket || session.state !== 'connected') return response.status(409).json({ message: 'WhatsApp is not connected' })
  const groups = await session.socket.groupFetchAllParticipating()
  response.json(Object.values(groups).map((group) => ({
    jid: group.id,
    name: group.subject ?? group.name ?? group.id,
    participants_count: group.participants?.length ?? 0,
  })))
})

app.post('/send', async (request, response) => {
  const { to, text } = request.body
  const session = getSession(tenantKey(request))
  if (!to || !text) return response.status(422).json({ message: 'The to and text fields are required' })
  if (!session.socket || session.state !== 'connected') return response.status(409).json({ message: 'WhatsApp is not connected' })
  try {
    const jid = normalizeJid(to)
    const result = await session.socket.sendMessage(jid, { text: String(text) })
    response.json({ ok: true, jid, message_id: result?.key?.id ?? null })
  } catch (error) {
    logger.error(error, 'Failed to send WhatsApp message')
    response.status(502).json({ message: error.message ?? 'Failed to send message' })
  }
})

app.listen(port, () => logger.info(`WhatsApp service listening on http://127.0.0.1:${port}`))
