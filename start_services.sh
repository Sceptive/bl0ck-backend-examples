# start_services.sh
#!/bin/sh


# Start Python service
cd /app/python && python3 app.py &

# Start PHP service
php -S 0.0.0.0:8001 -t /app/php/ &



# Start Node.js service
cd /app/nodejs && npm start &

# Start Java service using the built JAR
cd /app/java && java -jar target/fingerprint-api-0.0.1-SNAPSHOT.jar &

# Start .NET service - explicitly specify the project path
cd /app/dotnet && dotnet run --project /app/dotnet/dotnet.csproj &

# Keep container running
wait