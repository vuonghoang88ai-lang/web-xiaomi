FROM python:3.10-slim

WORKDIR /app

# Cài đặt các gói hệ thống cơ bản và docker cli để tương tác với WordPress container
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    ca-certificates \
    docker.io \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt thư viện Python
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE 8085

CMD ["python3", "server.py"]
