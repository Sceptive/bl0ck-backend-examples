// app.js
const express = require('express');
const axios = require('axios');
const app = express();

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const API_TOKEN = process.env.API_TOKEN || 'YOUR_API_TOKEN';
const API_BASE = 'https://api.bl0ck.sceptive.com';

function validateForm(data) {
    const required = ['name', 'email', 'fingerprint'];
    for (const field of required) {
        if (!data[field] || !data[field].trim()) {
            return false;
        }
    }
    
    // Basic email validation
    if (!data.email.includes('@') || !data.email.split('@')[1].includes('.')) {
        return false;
    }
    
    return true;
}

async function checkFingerprint(fp) {
    if (!fp || fp.length !== 32 || !/^[a-f0-9]+$/.test(fp)) {
        return { status: 'invalid' };
    }
    
    try {
        const response = await axios.get(`${API_BASE}/bfp/query/${fp}`, {
            headers: { 'x-api-token': API_TOKEN },
            timeout: 5000
        });

        if (response.status == 200) {
            return response.data;
        } 

        return { 
            status: 'error', 
            http_code: response.status 
        };

    } catch (error) {
        return { 
            status: 'error', 
            http_code: error.response?.status || 0 
        };
    }
}

async function reportFingerprint(fp, details) {
    if (!fp || fp.length !== 32 || !/^[a-f0-9]+$/.test(fp)) {
        return { status: 'invalid_fingerprint' };
    }
    
    try {
        const response = await axios.post(`${API_BASE}/bfp/report/${fp}`, 
            { details },
            {
                headers: { 
                    'x-api-token': API_TOKEN,
                    'Content-Type': 'application/json'
                },
                timeout: 5000
            }
        );
          if (response.status == 200) {
            return response.data;
        } 

        return { 
            status: 'error', 
            http_code: response.status 
        };
    } catch (error) {
        return { 
            status: 'error', 
            http_code: error.response?.status || 0 
        };
    }
}

app.post('/submit', async (req, res) => {
    const data = req.body;
    const fingerprint = data.fingerprint || '';
    
    if (!validateForm(data)) {
        // Report suspicious fingerprint
        const reportResult = await reportFingerprint(fingerprint, {
            ip: req.ip,
            reason: 'Invalid form submission',
            form_data: data
        });
        
        return res.status(400).json({
            status: 'error',
            message: 'Invalid form data',
            report_status: reportResult.status || 'failed'
        });
    }
    
    // Check fingerprint status
    const fpStatus = await checkFingerprint(fingerprint);
    
    if (fpStatus.recommended_action === 'block') {
        return res.status(403).json({
            status: 'error',
            message: 'Suspicious activity detected'
        });
    }
    
    // Process valid form
    // Save to database or perform other actions
    console.log(`Form submitted by ${data.email}`);
    
    res.json({
        status: 'success', 
        message: 'Form submitted successfully'
    });
});

app.listen(8003, () => {
    console.log('NodeJS Server running on port 8003');
});