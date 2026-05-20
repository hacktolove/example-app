# Xceed Callback Notification API

## Overview
Xceed pushes subscription lifecycle events to a partner-provided HTTP endpoint via GET request with query parameters. Partner must respond with `OK` to acknowledge receipt.

## Endpoint Format
```
GET http://<PARTNER_URL>/?ChannelID={int}&OperatorID={int}&RequestID={int}&MSISDN={string}&Status={char5}&Price={float}
```

## Parameters

| Parameter   | Type       | Required | Description |
|-------------|------------|----------|-------------|
| ChannelID   | int        | yes      | Unique channel ID assigned by Xceed |
| OperatorID  | int        | yes      | MCC+MNC combo of end-user's mobile operator |
| RequestID   | int        | yes      | Unique request identifier |
| MSISDN      | string(25) | yes      | End-user phone number with country code (e.g. `249999900046`) |
| Status      | char(5)    | yes      | Event type code (see status codes below) |
| Price       | float      | yes      | Deducted amount — meaningful only on success billing events, else `0` |

## Status Codes

| Code   | Event                  | Price Relevant |
|--------|------------------------|----------------|
| ACT-SB | User subscribed        | no             |
| BLD-SB | User unsubscribed      | no             |
| FSC-BL | First billing success  | yes            |
| FFL-BL | First billing failed   | no             |
| RSC-BL | Renewal billing success| yes            |
| RCL-SB | User recycled          | no             |

## Response
Partner endpoint must return plain text `OK` on success.

## Example Payloads (as JSON mapping of query params)

```json
// Subscription
{ "ChannelID": 101, "OperatorID": 63401, "RequestID": 987654, "MSISDN": "249999900046", "Status": "ACT-SB", "Price": 0.00 }

// First billing success
{ "ChannelID": 101, "OperatorID": 63401, "RequestID": 987655, "MSISDN": "249999900046", "Status": "FSC-BL", "Price": 5.99 }

// Renewal success
{ "ChannelID": 101, "OperatorID": 63401, "RequestID": 987656, "MSISDN": "249999900046", "Status": "RSC-BL", "Price": 5.99 }

// First billing failed
{ "ChannelID": 101, "OperatorID": 63401, "RequestID": 987657, "MSISDN": "249999900046", "Status": "FFL-BL", "Price": 0.00 }

// Unsubscription
{ "ChannelID": 101, "OperatorID": 63401, "RequestID": 987658, "MSISDN": "249999900046", "Status": "BLD-SB", "Price": 0.00 }

// Recycled
{ "ChannelID": 101, "OperatorID": 63401, "RequestID": 987659, "MSISDN": "249999900046", "Status": "RCL-SB", "Price": 0.00 }
```

## Business Rules
- `Price` must only be trusted/stored on `FSC-BL` and `RSC-BL` events.
- `MSISDN` always includes country calling code — no leading `+`.
- Each `RequestID` is unique per event; use it for idempotency checks.
- Xceed retries if partner does not respond with `OK`.

## Support
- Email: support@xceed-sd.com
- Connectivity issues: TELNET Xceed server IPs first to isolate VPN/internet problems.

