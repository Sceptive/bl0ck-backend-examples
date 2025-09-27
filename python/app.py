# app.py
from flask import Flask, request, jsonify
import requests
import os
import re

app = Flask(__name__)

API_TOKEN = os.getenv('API_TOKEN', 'YOUR_API_TOKEN')
API_BASE = 'https://api.bl0ck.sceptive.com'

def validate_form(data):
    required = ['name', 'email', 'fingerprint']
    for field in required:
        if field not in data or not data[field].strip():
            return False
    
    # Basic email validation
    if '@' not in data['email'] or '.' not in data['email'].split('@')[-1]:
        return False
    
    return True

def check_fingerprint(fp):
    if not fp or len(fp) != 32 or not re.match(r'^[a-f0-9]+$', fp):
        return {'status': 'invalid'}
    
    try:
        response = requests.get(
            f'{API_BASE}/bfp/query/{fp}',
            headers={'x-api-token': API_TOKEN},
            timeout=5
        )
        if response.status_code == 200:
            return response.json()
        return {'status': 'error', 'http_code': response.status_code}
    except requests.RequestException:
        return {'status': 'request_error'}

def report_fingerprint(fp, details):
    if not fp or len(fp) != 32 or not re.match(r'^[a-f0-9]+$', fp):
        return {'status': 'invalid_fingerprint'}
    
    try:
        response = requests.post(
            f'{API_BASE}/bfp/report/{fp}',
            headers={'x-api-token': API_TOKEN},
            json={'details': details},
            timeout=5
        )
        if response.status_code == 200:
            return response.json()
        return {'status': 'error', 'http_code': response.status_code}
    except requests.RequestException:
        return {'status': 'request_error'}

@app.route('/submit', methods=['POST'])
def handle_form():
    data = request.form if request.form else request.get_json()
    fingerprint = data.get('fingerprint', '')
    
    if not validate_form(data):
        # Report suspicious fingerprint
        report_result = report_fingerprint(fingerprint, {
            'ip': request.remote_addr,
            'reason': 'Invalid form submission',
            'form_data': dict(data)
        })
        
        return jsonify({
            'status': 'error',
            'message': 'Invalid form data',
            'report_status': report_result.get('status', 'failed')
        }), 400
    
    # Check fingerprint status
    fp_status = check_fingerprint(fingerprint)
    
    if fp_status.get('recommended_action') == 'block':
        return jsonify({
            'status': 'error',
            'message': 'Suspicious activity detected'
        }), 403
    
    # Process valid form
    # Save to database or perform other actions
    with open('submissions.log', 'a') as f:
        f.write(f"{data.get('email')} submitted at {datetime.now()}\n")
    
    return jsonify({
        'status': 'success', 
        'message': 'Form submitted successfully'
    })

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8000)