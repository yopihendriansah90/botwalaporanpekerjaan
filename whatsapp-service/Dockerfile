FROM node:22-alpine

WORKDIR /usr/src/app

COPY package*.json ./
RUN npm ci --omit=dev && npm cache clean --force

COPY src ./src

RUN mkdir -p /usr/src/app/auth_info \
    && chown -R node:node /usr/src/app

USER node

EXPOSE 3001

CMD ["node", "src/index.js"]
