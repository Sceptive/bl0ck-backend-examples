# Browser Fingerprint Integration Examples

## Usage Instructions

1. Set your API token as an environment variable:
   ```bash
   export API_TOKEN="your_api_token_here"
   ```

2. Build and start the services:
   ```bash
   docker-compose up --build
   ```

3. Run the test script:
   ```bash
   chmod +x test_services.sh
   ./test_services.sh
   ```

4. For testing with invalid form data:
   ```bash
   curl -X POST http://localhost:8001/form_handler.php \
     -d "name=Test&email=invalid-email&fingerprint=abcdef1234567890abcdef1234567890"
   ```

These examples demonstrate how to integrate browser fingerprint validation and reporting into form submission workflows across different backend technologies. Each implementation checks fingerprints against the bl0ck API and reports suspicious fingerprints when form validation fails.