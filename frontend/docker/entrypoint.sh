#!/bin/bash

cd /app

# Eğer node_modules yoksa otomatik yükle
if [ ! -d "node_modules" ]; then
  echo "node_modules bulunamadı, yükleniyor..."
  npm install
fi

# Production build yap
echo "Building Next.js app for production..."
npm run build

# Production modda başlat
echo "Starting Next.js app in production mode..."
npm start
