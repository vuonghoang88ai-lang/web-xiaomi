FROM node:22-alpine
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
# Nếu có lỗi start, bạn có thể kiểm tra package.json của repo này để đổi lệnh
CMD ["npm", "start"]
