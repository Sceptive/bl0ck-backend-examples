# Browser Fingerprint Integration Examples

A comprehensive collection of backend service examples demonstrating browser fingerprint validation and fraud prevention using the bl0ck API across multiple programming languages and frameworks.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Architecture](#architecture)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Language-Specific Examples](#language-specific-examples)
- [Security Considerations](#security-considerations)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

## 🎯 Overview

This project provides production-ready examples of integrating browser fingerprint validation into web applications using the bl0ck API. It demonstrates how to detect and prevent fraudulent form submissions, bot attacks, and suspicious user behavior across five popular backend technologies.

### What is Browser Fingerprinting?

Browser fingerprinting is a technique that collects information about a user's browser and device to create a unique identifier. This helps detect:
- Bot traffic and automated attacks
- Multiple account creation attempts
- Fraudulent transactions
- Suspicious user patterns

### Why Use bl0ck API?

The bl0ck API provides:
- Real-time fingerprint validation
- Fraud detection scoring
- Suspicious activity reporting
- Cross-platform fingerprint tracking
- GDPR-compliant data handling

## ✨ Features

- **Multi-Language Support**: Examples in PHP, Python, Node.js, Java, and .NET
- **Docker Integration**: Run all services with a single command
- **Form Validation**: Server-side validation with fingerprint checking
- **Fraud Detection**: Automatic blocking of suspicious fingerprints
- **Reporting System**: Report invalid submissions to improve detection
- **Comprehensive Testing**: Automated test suite for all services
- **Production Ready**: Error handling, logging, and best practices

## 🏗️ Architecture

```
┌─────────────────┐     ┌──────────────────┐
│   Client App    │────▶│  Backend Service │
│  (Browser)      │     │  (PHP/Python/    │
└─────────────────┘     │   Node/Java/.NET)│
                        └──────────────────┘
                                 │
                                 ▼
                        ┌──────────────────┐
                        │   bl0ck API      │
                        │  - Query          │
                        │  - Report         │
                        └──────────────────┘
```

### Service Ports

| Service | Port | Endpoint |
|---------|------|----------|
| Python (Flask) | 8000 | `/submit` |
| PHP | 8001 | `/form_handler.php` |
| Node.js (Express) | 8003 | `/submit` |
| Java (Spring Boot) | 8080 | `/api/submit` |
| .NET (ASP.NET Core) | 8005 | `/form/submit` |

## 📦 Prerequisites

### Required Software

- Docker Desktop 20.10+ or Docker Engine with Docker Compose
- API token from bl0ck (obtain from [https://bl0ck.sceptive.com](https://bl0ck.sceptive.com))
- Git for cloning the repository
- curl or Postman for testing (optional)

### System Requirements

- **Memory**: Minimum 4GB RAM (8GB recommended)
- **Storage**: 2GB free disk space
- **OS**: Linux, macOS, or Windows with WSL2

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/bl0ck-examples.git
cd bl0ck-examples
```

### 2. Set Environment Variables

Create a `.env` file in the project root:

```bash
# .env
API_TOKEN=your_actual_api_token_here

# Optional: Configure service-specific settings
PYTHON_PORT=8000
PHP_PORT=8001
NODE_PORT=8003
JAVA_PORT=8080
DOTNET_PORT=8005

# Optional: Enable debug logging
DEBUG_MODE=true
LOG_LEVEL=info
```

Or export directly in your shell:

```bash
export API_TOKEN="your_actual_api_token_here"
```

### 3. Build and Start Services

```bash
# Build all services
docker-compose build

# Start all services in detached mode
docker-compose up -d

# Or build and start in one command
docker-compose up --build -d
```

### 4. Verify Services

Check that all services are running:

```bash
# Check container status
docker-compose ps

# View logs
docker-compose logs -f

# Test individual service health
curl http://localhost:8000/health  # Python
curl http://localhost:8003/health  # Node.js
```

## ⚙️ Configuration

### API Token Configuration

Each service reads the API token from the `API_TOKEN` environment variable. If not set, it falls back to a placeholder value. Never commit real API tokens to version control.

### Fingerprint Validation Rules

All services validate fingerprints with these criteria:
- **Length**: Exactly 32 characters
- **Format**: Hexadecimal (a-f, 0-9)
- **Pattern**: Lowercase only

### Form Validation Rules

Standard validation across all services:
- **Required Fields**: name, email, fingerprint
- **Email Format**: Must contain @ and domain
- **Non-empty Values**: All fields must have content

## 📖 Usage

### Basic Form Submission

Submit a form with valid data:

```bash
curl -X POST http://localhost:8000/submit \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "fingerprint": "a1b2c3d4e5f6789012345678901234567"
  }'
```

### Expected Responses

#### Success Response (200 OK)
```json
{
  "status": "success",
  "message": "Form submitted successfully"
}
```

#### Invalid Form Data (400 Bad Request)
```json
{
  "status": "error",
  "message": "Invalid form data",
  "report_status": "reported"
}
```

#### Blocked Fingerprint (403 Forbidden)
```json
{
  "status": "error",
  "message": "Suspicious activity detected"
}
```

### Workflow Example

1. **Client collects fingerprint** using JavaScript fingerprinting library
2. **Form submission** includes fingerprint with other data
3. **Backend validates** form fields
4. **Fingerprint check** against bl0ck API
5. **Decision made**: Accept, reject, or report
6. **Response sent** to client

## 📚 API Documentation

### Form Submission Endpoint

All services implement the same API contract:

**Endpoint**: `POST /submit` (varies by service)

**Request Body**:
```json
{
  "name": "string (required)",
  "email": "string (required, valid email)",
  "fingerprint": "string (required, 32 char hex)"
}
```

**Response Codes**:
- `200`: Successful submission
- `400`: Invalid form data
- `403`: Blocked due to suspicious fingerprint
- `500`: Server error

### bl0ck API Integration

Services interact with two bl0ck API endpoints:

#### Query Fingerprint
```
GET https://api.bl0ck.sceptive.com/bfp/query/{fingerprint}
Headers: x-api-token: YOUR_TOKEN
```

#### Report Fingerprint
```
POST https://api.bl0ck.sceptive.com/bfp/report/{fingerprint}
Headers: x-api-token: YOUR_TOKEN
Body: { "details": { ... } }
```

## 🧪 Testing

### Run Automated Tests

```bash
# Make test script executable
chmod +x test_services.sh

# Run all tests
./test_services.sh

# Test individual service
curl -X POST http://localhost:8000/submit \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "email": "test@example.com", "fingerprint": "abcdef1234567890abcdef1234567890"}'
```

### Test Invalid Data

Test form validation:

```bash
# Missing email
curl -X POST http://localhost:8003/submit \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "fingerprint": "abcdef1234567890abcdef1234567890"}'

# Invalid email format
curl -X POST http://localhost:8000/submit \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "email": "invalid-email", "fingerprint": "abcdef1234567890abcdef1234567890"}'

# Invalid fingerprint format
curl -X POST http://localhost:8080/api/submit \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "email": "test@example.com", "fingerprint": "invalid"}'
```

## 🗂️ Language-Specific Examples

### PHP Implementation

```php
// Key features:
- Native PHP with cURL for API calls
- Detailed logging with api_logs.json
- Form data validation
- Supports both form-encoded and JSON requests
```

### Python (Flask) Implementation

```python
# Key features:
- Flask web framework
- Requests library for API calls
- Clean error handling
- File logging for submissions
```

### Node.js (Express) Implementation

```javascript
// Key features:
- Express.js framework
- Axios for HTTP requests
- Async/await pattern
- Timeout handling
```

### Java (Spring Boot) Implementation

```java
// Key features:
- Spring Boot framework
- RestTemplate for API calls
- Strong typing with POJOs
- Maven build system
```

### .NET (ASP.NET Core) Implementation

```csharp
// Key features:
- ASP.NET Core Web API
- HttpClient for API calls
- Async controller actions
- Swagger/OpenAPI support
```

## 🔒 Security Considerations

### Best Practices

1. **API Token Security**
   - Never commit tokens to version control
   - Use environment variables or secrets management
   - Rotate tokens regularly
   - Implement rate limiting

2. **Input Validation**
   - Validate all input server-side
   - Sanitize data before processing
   - Use parameterized queries for databases
   - Implement CSRF protection

3. **Fingerprint Handling**
   - Store fingerprints securely (hashed if persisted)
   - Implement privacy controls
   - Follow GDPR/CCPA guidelines
   - Provide opt-out mechanisms

4. **Error Handling**
   - Don't expose sensitive information in errors
   - Log security events
   - Implement proper monitoring
   - Set up alerts for suspicious patterns

## 🔧 Troubleshooting

### Common Issues

#### Services Won't Start
```bash
# Check for port conflicts
lsof -i :8000  # Linux/macOS
netstat -an | findstr :8000  # Windows

# Stop existing containers
docker-compose down

# Rebuild and start fresh
docker-compose up --build --force-recreate
```

#### API Token Issues
```bash
# Verify token is set
echo $API_TOKEN

# Check container environment
docker-compose exec fingerprint-services env | grep API_TOKEN
```

#### Connection Refused Errors
```bash
# Ensure services are running
docker-compose ps

# Check service logs
docker-compose logs [service-name]

# Test internal connectivity
docker-compose exec fingerprint-services curl http://localhost:8000/health
```

#### Build Failures
```bash
# Clean Docker cache
docker system prune -a

# Rebuild without cache
docker-compose build --no-cache
```

### Debug Mode

Enable detailed logging:

```bash
# In docker-compose.yml
environment:
  - API_TOKEN=${API_TOKEN}
  - DEBUG_MODE=true
  - LOG_LEVEL=debug
```

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards

- Follow language-specific conventions
- Add tests for new features
- Update documentation
- Ensure all tests pass
- Add meaningful commit messages

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.


## 📞 Support

- **Documentation**: [https://docs.bl0ck.sceptive.com](https://docs.bl0ck.sceptive.com)
- **API Support**: support@sceptive.com
- **Issues**: GitHub Issues page

---
