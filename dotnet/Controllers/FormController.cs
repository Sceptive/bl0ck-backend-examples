// FormController.cs
using Microsoft.AspNetCore.Mvc;
using System.Net.Http;
using System.Text.Json;
using System.Threading.Tasks;

[ApiController]
[Route("[controller]")]
public class FormController : ControllerBase
{
    private readonly string _apiToken = Environment.GetEnvironmentVariable("API_TOKEN") ?? "YOUR_API_TOKEN";
    private readonly string _apiBase = "https://api.bl0ck.sceptive.com";
    private readonly HttpClient _httpClient = new HttpClient();
    
    [HttpPost("submit")]
    public async Task<IActionResult> HandleForm([FromBody] FormData formData)
    {
        if (!ValidateForm(formData))
        {
            // Report suspicious fingerprint
            var reportDetails = new
            {
                ip = HttpContext.Connection.RemoteIpAddress?.ToString(),
                reason = "Invalid form submission",
                form_data = formData
            };
            
            var reportResult = await ReportFingerprint(formData.Fingerprint, reportDetails);
            
            return BadRequest(new
            {
                status = "error",
                message = "Invalid form data",
                report_status = reportResult?.recommended_action ?? "failed"
            });
        }
        
        // Check fingerprint status
        var fpStatus = await CheckFingerprint(formData.Fingerprint);
        
        if (fpStatus?.recommended_action == "block")
        {
            return StatusCode(403, new
            {
                status = "error",
                message = "Suspicious activity detected"
            });
        }
        
        // Process valid form
        // Save to database or perform other actions
        Console.WriteLine($"Form submitted by: {formData.Email}");
        
        return Ok(new
        {
            status = "success",
            message = "Form submitted successfully"
        });
    }
    
    private bool ValidateForm(FormData data)
    {
        if (string.IsNullOrEmpty(data.Name) || 
            string.IsNullOrEmpty(data.Email) || 
            string.IsNullOrEmpty(data.Fingerprint))
        {
            return false;
        }
        
        // Basic email validation
        if (!data.Email.Contains("@") || !data.Email.Split('@')[1].Contains("."))
        {
            return false;
        }
        
        return true;
    }
    
    private async Task<FingerprintStatus> CheckFingerprint(string fingerprint)
    {
        if (string.IsNullOrEmpty(fingerprint) || fingerprint.Length != 32 || 
            !System.Text.RegularExpressions.Regex.IsMatch(fingerprint, "^[a-f0-9]+$"))
        {
            return new FingerprintStatus { recommended_action = "block" };
        }
        
        try
        {
            var request = new HttpRequestMessage(HttpMethod.Get, $"{_apiBase}/bfp/query/{fingerprint}");
            request.Headers.Add("x-api-token", _apiToken);
            
            var response = await _httpClient.SendAsync(request);
            if (response.IsSuccessStatusCode)
            {
                var content = await response.Content.ReadAsStringAsync();
                return JsonSerializer.Deserialize<FingerprintStatus>(content);
            }
            
            return new FingerprintStatus { recommended_action = "block" };
        }
        catch
        {
            return new FingerprintStatus { recommended_action = "block" };
        }
    }
    
    private async Task<ReportResult> ReportFingerprint(string fingerprint, object details)
    {
        if (string.IsNullOrEmpty(fingerprint) || fingerprint.Length != 32 || 
            !System.Text.RegularExpressions.Regex.IsMatch(fingerprint, "^[a-f0-9]+$"))
        {
            return new ReportResult { recommended_action = "invalid_fingerprint" };
        }
        
        try
        {
            var request = new HttpRequestMessage(HttpMethod.Post, $"{_apiBase}/bfp/report/{fingerprint}");
            request.Headers.Add("x-api-token", _apiToken);
            
            var json = JsonSerializer.Serialize(new { details });
            request.Content = new StringContent(json, System.Text.Encoding.UTF8, "application/json");
            
            var response = await _httpClient.SendAsync(request);
            if (response.IsSuccessStatusCode)
            {
                var content = await response.Content.ReadAsStringAsync();
                return JsonSerializer.Deserialize<ReportResult>(content);
            }
            
            return new ReportResult { recommended_action = "error" };
        }
        catch
        {
            return new ReportResult { recommended_action = "request_error" };
        }
    }
}

public class FormData
{
    public string Name { get; set; }
    public string Email { get; set; }
    public string Fingerprint { get; set; }
}

public class FingerprintStatus
{
    public string recommended_action { get; set; }
}

public class ReportResult
{
    public string recommended_action { get; set; }
}