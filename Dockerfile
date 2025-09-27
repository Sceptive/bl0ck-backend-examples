# Dockerfile
FROM node:16-alpine

WORKDIR /app

# Install dependencies for all languages (simplified example)
RUN apk add --no-cache \
    php php-curl \
    python3 py3-pip \
    openjdk11 maven \
    dotnet7-sdk

# Copy all example code
COPY php/ /app/php/
COPY python/ /app/python/
COPY nodejs/ /app/nodejs/
COPY java/ /app/java/
COPY dotnet/ /app/dotnet/

# Install Python dependencies
RUN pip3 install -r /app/python/requirements.txt

# Install Node.js dependencies
RUN cd /app/nodejs && npm install

# Build Java project
RUN cd /app/java && mvn clean package -DskipTests

# Expose ports for each service
EXPOSE 8000 8001 8003 8080 8005

# Start script
COPY start_services.sh /app/
RUN chmod +x /app/start_services.sh

CMD ["/app/start_services.sh"]