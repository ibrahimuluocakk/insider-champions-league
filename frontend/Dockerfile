FROM node:20

WORKDIR /app

COPY . .

RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
