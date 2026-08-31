````markdown
# Wave Integration Specification

**Version:** 1.0

**Purpose**

Integrate the existing Wave Diameter Gateway into our application.

Our application acts as a gateway/facade over the external Wave API. Clients will interact only with our REST API, while our backend will translate requests into the Wave protocol and forward them to the Wave server.

This document describes **what must be implemented**. It does **not** describe the Wave API itself.

---

# Goals

- Integrate with the existing Wave server.
- Hide Wave implementation details from API consumers.
- Expose RESTful endpoints.
- Centralize authentication.
- Translate request/response payloads.
- Provide consistent error handling.
- Keep the integration isolated from business logic.

---

# Architecture

```
Client
   │
   ▼
Our REST API
   │
   ▼
WaveController
   │
   ▼
WaveService
   │
   ▼
WaveClient
   │
   ▼
Wave Diameter Gateway
```

The application is responsible for:

- validating incoming requests
- constructing Wave request payloads
- authenticating with Wave
- forwarding requests
- mapping responses
- handling network failures

---

# New Components

## Service

```
app/
└── Services/
    └── Wave/
        ├── WaveClient.php
        ├── WaveRequestFactory.php
        ├── WaveResponseMapper.php
        └── DTO/
```

---

## Controller

```
app/
└── Http/
    └── Controllers/
        └── WaveController.php
```

---

## Configuration

```
config/wave.php
```

---

## Routes

```
routes/api.php
```

---

# Environment Variables

Add the following configuration.

```
WAVE_BASE_URL=
WAVE_USERNAME=
WAVE_PASSWORD=
WAVE_TIMEOUT=10
```

Never hardcode credentials.

---

# Public API

Expose the following endpoints.

---

## 1. Get Balance

```
POST /api/v1/wave/balance
```

### Request

```json
{
    "msisdn": "249912345678",
    "content_id": "1000"
}
```

### Internal Wave Request

```json
{
    "originNodeType": "API",
    "originHostName": "<application_name>",
    "originTransactionID": "<generated_uuid>",
    "originTimeStamp": "<ISO8601>",
    "module": "DSC",
    "command": {
        "function": "GetBalance",
        "request": {
            "MSISDN": "249912345678",
            "ContentID": "1000"
        }
    }
}
```

---

## 2. Charge

```
POST /api/v1/wave/charge
```

### Request

```json
{
    "msisdn": "249912345678",
    "amount": "10",
    "content_id": "1000"
}
```

### Internal Wave Request

```
command.function = Charging
```

---

## 3. Location

```
POST /api/v1/wave/location
```

### Request

```json
{
    "msisdn": "249912345678",
    "content_id": "1000"
}
```

### Internal Wave Request

```
command.function = Location
```

---

# HTTP Client

Use Laravel HTTP Client.

```
Http::withBasicAuth(...)
    ->acceptJson()
    ->timeout(config('wave.timeout'))
    ->post(...)
```

The Wave endpoint is always:

```
POST {WAVE_BASE_URL}/DiameterEventCharging
```

---

# Request Factory

Create a dedicated request factory responsible for constructing valid Wave requests.

Responsibilities:

- Generate originTransactionID
- Generate originTimeStamp
- Set module = DSC
- Populate originHostName
- Populate originNodeType
- Map application payload to Wave payload

Controllers must never manually construct Wave JSON.

---

# Response Mapper

Create a response mapper that converts Wave responses into the application's standard response format.

Example success response:

```json
{
    "success": true,
    "code": 2001,
    "message": "Success",
    "data": {
        "balance": "150.50"
    }
}
```

Example business failure:

```json
{
    "success": false,
    "code": 4547,
    "message": "Insufficient balance"
}
```

The original Wave response should not be returned directly unless explicitly required.

---

# Error Handling

## Authentication Failure

Wave returns HTTP 401.

Return:

```
HTTP 401
```

---

## Authorization Failure

Wave returns HTTP 403.

Return:

```
HTTP 403
```

---

## Invalid Request

If request validation fails:

```
HTTP 422
```

Do not call Wave.

---

## Wave Server Error

If Wave returns HTTP 500:

Return

```
HTTP 502 Bad Gateway
```

---

## Timeout

If Wave does not respond within timeout:

Return

```
HTTP 503 Service Unavailable
```

---

## Network Failure

Return

```
HTTP 503
```

---

## Business Errors

Business failures returned by Wave (for example insufficient balance) should **not** become HTTP errors.

Return:

```
HTTP 200
```

with

```json
{
    "success": false,
    "code": 4547,
    "message": "Insufficient balance"
}
```

---

# Validation

## Balance

Required:

- msisdn
- content_id

---

## Charge

Required:

- msisdn
- amount
- content_id

---

## Location

Required:

- msisdn
- content_id

---

# Logging

Log:

- Request ID
- Endpoint
- Function
- Duration
- HTTP status
- Wave responseCode

Do NOT log:

- Authorization header
- Password
- Full MSISDN (mask where appropriate)

---

# Security

- Read credentials only from environment variables.
- Never expose Wave credentials.
- Never expose the Wave endpoint to API consumers.
- Use HTTPS for all outbound requests.

---

# Testing

Create:

## Feature Tests

- Balance success
- Charge success
- Location success

---

## Validation Tests

- Missing MSISDN
- Missing amount
- Missing content_id

---

## HTTP Client Tests

Use

```
Http::fake()
```

to verify:

- Correct endpoint
- Correct authentication
- Correct JSON body
- Correct response mapping

---

# Acceptance Criteria

The implementation is complete when:

- Three REST endpoints are available.
- Requests are validated before calling Wave.
- Wave requests are built only through the request factory.
- Credentials are loaded from configuration.
- Laravel HTTP Client is used for all outbound requests.
- Responses are mapped to the application's standard format.
- Network failures are handled gracefully.
- Sensitive information is never logged.
- Automated tests cover both success and failure scenarios.
- No controller contains Wave-specific request construction logic.

---

# Future Extensibility

The integration should allow new Wave operations to be added with minimal changes.

Adding a new operation should only require:

1. A new public endpoint.
2. A new request DTO.
3. A new request mapping.
4. A new response mapping.

No changes should be required to the HTTP client implementation.
````

