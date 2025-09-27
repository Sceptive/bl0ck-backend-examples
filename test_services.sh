#!/bin/bash
# test_services.sh

API_TOKEN="${API_TOKEN:-YOUR_API_TOKEN}"
TEST_FP="abcdef1234567890abcdef1234567890"  # Example fingerprint

echo "Testing all fingerprint services..."

# Test PHP service
echo -e "\nTesting PHP service:"
curl -X POST http://localhost:8001/form_handler.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=Test&email=test@example.com&fingerprint=$TEST_FP"

# Test Python service
echo -e "\nTesting Python service:"
curl -X POST http://localhost:8000/submit \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "email": "test@example.com", "fingerprint": "'$TEST_FP'"}'

# Test Node.js service
echo -e "\nTesting Node.js service:"
curl -X POST http://localhost:8003/submit \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "email": "test@example.com", "fingerprint": "'$TEST_FP'"}'

# Test Java service
echo -e "\nTesting Java service:"
curl -X POST http://localhost:8080/api/submit \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "email": "test@example.com", "fingerprint": "'$TEST_FP'"}'

# # Test .NET service
echo -e "\nTesting .NET service:"
curl -X POST http://localhost:8005/form/submit \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "email": "test@example.com", "fingerprint": "'$TEST_FP'"}'

echo -e "\nTesting completed."