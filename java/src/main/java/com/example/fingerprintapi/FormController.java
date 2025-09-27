package com.example.fingerprintapi;

import org.springframework.web.bind.annotation.*;
import org.springframework.http.*;
import org.springframework.web.client.RestTemplate;
import java.util.*;

@RestController
@RequestMapping("/api")
public class FormController {
    
    private final String API_TOKEN = System.getenv("API_TOKEN") != null ? 
                                    System.getenv("API_TOKEN") : "YOUR_API_TOKEN";
    private final String API_BASE = "https://api.bl0ck.sceptive.com";
    private final RestTemplate restTemplate = new RestTemplate();
    
    @PostMapping("/submit")
    public ResponseEntity<Map<String, Object>> handleForm(@RequestBody Map<String, String> formData) {
        String fingerprint = formData.get("fingerprint");
        
        if (!validateForm(formData)) {
            // Report suspicious fingerprint
            Map<String, Object> reportDetails = new HashMap<>();
            reportDetails.put("ip", "client-ip"); // Get actual IP in real implementation
            reportDetails.put("reason", "Invalid form submission");
            reportDetails.put("form_data", formData);
            
            Map<String, Object> reportResult = reportFingerprint(fingerprint, reportDetails);
            
            Map<String, Object> response = new HashMap<>();
            response.put("status", "error");
            response.put("message", "Invalid form data");
            response.put("report_status", reportResult.getOrDefault("status", "failed"));
            
            return ResponseEntity.status(HttpStatus.BAD_REQUEST).body(response);
        }
        
        // Check fingerprint status
        Map<String, Object> fpStatus = checkFingerprint(fingerprint);
        
        if ("block".equals(fpStatus.get("recommended_action"))) {
            Map<String, Object> response = new HashMap<>();
            response.put("status", "error");
            response.put("message", "Suspicious activity detected");
            
            return ResponseEntity.status(HttpStatus.FORBIDDEN).body(response);
        }
        
        // Process valid form
        // Save to database or perform other actions
        System.out.println("Form submitted by: " + formData.get("email"));
        
        Map<String, Object> response = new HashMap<>();
        response.put("status", "success");
        response.put("message", "Form submitted successfully");
        
        return ResponseEntity.ok(response);
    }
    
    private boolean validateForm(Map<String, String> data) {
        String[] required = {"name", "email", "fingerprint"};
        for (String field : required) {
            if (!data.containsKey(field) || data.get(field).trim().isEmpty()) {
                return false;
            }
        }
        
        // Basic email validation
        String email = data.get("email");
        if (!email.contains("@") || !email.split("@")[1].contains(".")) {
            return false;
        }
        
        return true;
    }
    
    private Map<String, Object> checkFingerprint(String fp) {
        if (fp == null || fp.length() != 32 || !fp.matches("^[a-f0-9]+$")) {
            Map<String, Object> result = new HashMap<>();
            result.put("status", "invalid");
            return result;
        }
        
        try {
            HttpHeaders headers = new HttpHeaders();
            headers.set("x-api-token", API_TOKEN);
            
            ResponseEntity<Map> response = restTemplate.exchange(
                API_BASE + "/bfp/query/" + fp,
                HttpMethod.GET,
                new HttpEntity<>(headers),
                Map.class
            );
            
            if (response.getStatusCodeValue() == 200) 
                return response.getBody();
        } catch (Exception e) {
           e.printStackTrace();
        }

        Map<String, Object> result = new HashMap<>();
        result.put("status", "error");
        return result;
    }
    
    private Map<String, Object> reportFingerprint(String fp, Map<String, Object> details) {
        if (fp == null || fp.length() != 32 || !fp.matches("^[a-f0-9]+$")) {
            Map<String, Object> result = new HashMap<>();
            result.put("status", "invalid_fingerprint");
            return result;
        }
        
        try {
            HttpHeaders headers = new HttpHeaders();
            headers.set("x-api-token", API_TOKEN);
            headers.setContentType(MediaType.APPLICATION_JSON);
            
            Map<String, Object> requestBody = new HashMap<>();
            requestBody.put("details", details);
            
            ResponseEntity<Map> response = restTemplate.exchange(
                API_BASE + "/bfp/report/" + fp,
                HttpMethod.POST,
                new HttpEntity<>(requestBody, headers),
                Map.class
            );
            
            return response.getBody();
        } catch (Exception e) {
            Map<String, Object> result = new HashMap<>();
            result.put("status", "error");
            return result;
        }
    }
}
