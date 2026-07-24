import 'dotenv/config'

import makeWASocket, {
  Browsers,
  DisconnectReason,
  fetchLatestBaileysVersion,
  useMultiFileAuthState,
} from '@whiskeysockets/baileys'
import express from 'express'
import fs from 'node:fs/promises'
import Pino from 'pino'
import QRCode from 'qrcode'
import qrcode from 'qrcode-terminal'

const port = Number(process.env.PORT ?? 3001)
const apiToken = process.env.WHATSAPP_API_TOKEN
const authDir = process.env.WHATSAPP_AUTH_DIR ?? './auth_info'
const qrTimeoutMs = Number(process.env.WHATSAPP_QR_TIMEOUT_MS ?? 60000)
const logger = Pino({ level: process.env.LOG_LEVEL ?? 'info' })

let socket = null
let connectionState = 'disconnected'
let latestQr = null
let lastError = null
let qrRetryTimer = null

function requireApiToken(request, response, next) {
  if (!apiToken || request.header('x-api-key') !== apiToken) {
    return response.status(401).json({ message: 'Unauthorized' })
  }

  next()
}

function normalizeJid(value) {
  if (value.endsWith('@g.us') || value.endsWith('@s.whatsapp.net')) {
    return value
  }

  return `${value.replace(/\D/g, '')}@s.whatsapp.net`
}

async function connectWhatsApp() {
  if (socket || connectionState === 'connecting' || connectionState === 'qr_required') {
    return
  }

  connectionState = 'connecting'

  const { state, saveCreds } = await useMultiFileAuthState(authDir)
  const { version } = await fetchLatestBaileysVersion()

  socket = makeWASocket({
    version,
    auth: state,
    browser: Browsers.ubuntu('Wabot'),
    logger,
    printQRInTerminal: false,
    markOnlineOnConnect: false,
  })

  socket.ev.on('creds.update', saveCreds)

  socket.ev.on('connection.update', async ({ connection, lastDisconnect, qr }) => {
    if (qr) {
      if (qrRetryTimer) {
        clearTimeout(qrRetryTimer)
      }

      latestQr = await QRCode.toDataURL(qr, { margin: 1, width: 320 })
      connectionState = 'qr_required'
      qrcode.generate(qr, { small: true })

      const currentSocket = socket
      qrRetryTimer = setTimeout(() => {
        if (socket === currentSocket && connectionState === 'qr_required') {
          logger.info('QR Code expired; restarting WhatsApp connection')
          currentSocket?.end(new Error('QR Code expired'))
        }
      }, qrTimeoutMs)
    }

    if (connection === 'open') {
      if (qrRetryTimer) {
        clearTimeout(qrRetryTimer)
        qrRetryTimer = null
      }

      connectionState = 'connected'
      latestQr = null
      lastError = null
      logger.info('WhatsApp connected')
    }

    if (connection === 'close') {
      if (qrRetryTimer) {
        clearTimeout(qrRetryTimer)
        qrRetryTimer = null
      }

      socket = null
      connectionState = 'disconnected'
      const statusCode = lastDisconnect?.error?.output?.statusCode
      lastError = lastDisconnect?.error?.message ?? 'Connection closed'

      if (statusCode !== DisconnectReason.loggedOut) {
        setTimeout(connectWhatsApp, 3000)
      } else {
        logger.warn('WhatsApp session logged out; QR scan is required')
      }
    }
  })
}

async function disconnectWhatsApp() {
  if (socket) {
    try {
      await socket.logout()
    } catch (error) {
      logger.warn(error, 'WhatsApp logout returned an error')
    }
  }

  socket = null
  connectionState = 'disconnected'
  latestQr = null
  lastError = null

  await fs.rm(authDir, { recursive: true, force: true })
}

async function waitForConnectionState(timeoutMs = 10000) {
  const startedAt = Date.now()

  while (Date.now() - startedAt < timeoutMs) {
    if (connectionState === 'connected' || connectionState === 'error' || (connectionState === 'qr_required' && latestQr)) {
      return
    }

    await new Promise((resolve) => setTimeout(resolve, 100))
  }
}

const app = express()
app.use(express.json({ limit: '1mb' }))

app.get('/health', (_request, response) => {
  response.json({ ok: true, service: 'whatsapp-service' })
})

app.use(requireApiToken)

app.get('/status', (_request, response) => {
  response.json({
    state: connectionState,
    qr: latestQr,
    last_error: lastError,
    phone: socket?.user?.id ?? null,
  })
})

app.post('/connect', async (_request, response) => {
  try {
    await connectWhatsApp()
    await waitForConnectionState()
    response.json({ ok: true, state: connectionState, qr: latestQr })
  } catch (error) {
    connectionState = 'error'
    lastError = error.message ?? 'Failed to connect WhatsApp'
    response.status(502).json({ message: lastError })
  }
})

app.post('/logout', async (_request, response) => {
  try {
    await disconnectWhatsApp()
    response.json({ ok: true, state: connectionState })
  } catch (error) {
    lastError = error.message ?? 'Failed to logout WhatsApp'
    response.status(502).json({ message: lastError })
  }
})

app.get('/groups', async (_request, response) => {
  if (!socket || connectionState !== 'connected') {
    return response.status(409).json({ message: 'WhatsApp is not connected' })
  }

  const groups = await socket.groupFetchAllParticipating()
  response.json(Object.values(groups).map((group) => ({
    jid: group.id,
    name: group.subject,
    participants_count: group.participants?.length ?? 0,
  })))
})

app.post('/send', async (request, response) => {
  const { to, text } = request.body

  if (!to || !text) {
    return response.status(422).json({ message: 'The to and text fields are required' })
  }

  if (!socket || connectionState !== 'connected') {
    return response.status(409).json({ message: 'WhatsApp is not connected' })
  }

  try {
    const jid = normalizeJid(to)
    const result = await socket.sendMessage(jid, { text: String(text) })

    response.json({
      ok: true,
      jid,
      message_id: result?.key?.id ?? null,
    })
  } catch (error) {
    logger.error(error, 'Failed to send WhatsApp message')
    response.status(502).json({ message: error.message ?? 'Failed to send message' })
  }
})

app.listen(port, () => {
  logger.info(`WhatsApp service listening on http://127.0.0.1:${port}`)
})

connectWhatsApp().catch((error) => {
  connectionState = 'error'
  lastError = error.message
  logger.error(error, 'Failed to initialize WhatsApp')
})
