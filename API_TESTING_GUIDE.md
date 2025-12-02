# AI Chatbot Conversation API - Testing Guide

## 📋 Overview

This guide provides complete instructions for testing the Conversation API using Postman, curl, and manual testing. Includes sample data, test scenarios, and error cases.

---

## 🔑 Prerequisites

1. **Authentication Token** - Get from login endpoint
2. **User ID** - Existing user in database (use ID: 1)
3. **Session ID** - Existing session (use: test_session_123)
4. **Postman/cURL** - For making requests
5. **Running API** - Laravel server running

---

## 🚀 Step 1: Get Authentication Token

### Request
```bash
curl -X POST "http://localhost:8000/api/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

### Expected Response
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Test Student",
      "email": "test@example.com",
      "phone": null,
      "address": null,
      "city": null,
      "state": null,
      "country": null,
      "zip_code": null,
      "active": true,
      "is_verified": false,
      "avatar": null,
      "user_type": 1,
      "created_at": "2025-08-13T18:09:33.000000Z",
      "updated_at": "2025-08-13T18:09:33.000000Z"
    },
    "token": "1|YOUR_AUTH_TOKEN_HERE"
  },
  "message": "Login successful"
}
```

**⚠️ Important**: Save the token from response. You'll need it for all subsequent requests.

---

## 📦 Postman Collection Setup

### Import Collection

Create a new Postman Collection:
1. Click **Collections** → **Create New**
2. Name it: `AI Chatbot Conversation API`
3. Add the following requests

---

## 🧪 Test Cases

### Test Case 1: Get All User Conversations

**Request Name**: Get All Conversations

**Method**: `GET`

**URL**: 
```
http://localhost:8000/api/conversations/user?user_id=1
```

**Headers**:
```
Authorization: Bearer 1|YOUR_AUTH_TOKEN_HERE
Content-Type: application/json
```

**Body**: None (GET request)

**Postman Setup**:
```
Tab: Params
Key: user_id
Value: 1

Tab: Headers
Authorization | Bearer {{auth_token}}
Content-Type | application/json
```

**Expected Response (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "session": {
        "id": "test_session_123",
        "user_id": 1,
        "ip_address": "127.0.0.1",
        "user_agent": "Mozilla/5.0 Test Browser",
        "last_activity": "2025-08-13T18:09:33Z",
        "created_at": "2025-08-13T18:09:33Z"
      },
      "events": [
        {
          "id": 1,
          "event_type": "chat",
          "event_name": "user_message",
          "data": {
            "message": "Hello, I need legal advice",
            "timestamp": "2025-08-13T18:00:00Z"
          },
          "occurred_at": "2025-08-13T18:09:33Z",
          "created_at": "2025-08-13T18:09:33Z"
        },
        {
          "id": 2,
          "event_type": "chat",
          "event_name": "ai_response",
          "data": {
            "message": "Hello! How can I help you with your legal question?",
            "timestamp": "2025-08-13T18:00:05Z"
          },
          "occurred_at": "2025-08-13T18:09:33Z",
          "created_at": "2025-08-13T18:09:33Z"
        }
      ],
      "event_count": 2
    }
  ],
  "message": "Conversations retrieved successfully",
  "total_sessions": 1,
  "total_events": 2
}
```

**Validation**:
- ✅ Status code is 200
- ✅ `success` is `true`
- ✅ `data` is an array
- ✅ Each item has `session` and `events`
- ✅ Events are ordered by `occurred_at`

---

### Test Case 2: Get Paginated Conversations

**Request Name**: Get Paginated Conversations

**Method**: `GET`

**URL**: 
```
http://localhost:8000/api/conversations/user/paginated?user_id=1&per_page=5&sort_by=last_activity&sort_order=desc
```

**Headers**:
```
Authorization: Bearer 1|YOUR_AUTH_TOKEN_HERE
Content-Type: application/json
```

**Postman Params**:
```
user_id: 1
per_page: 5
sort_by: last_activity
sort_order: desc
```

**Expected Response (200 OK)**:
```json
{
  "success": true,
  "data": [
    {
      "session": { ... },
      "events": [ ... ],
      "event_count": 2
    }
  ],
  "pagination": {
    "total": 1,
    "per_page": 5,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 1
  },
  "message": "Paginated conversations retrieved successfully"
}
```

**Test Variations**:

**Variation 2a: Different per_page**
```
?user_id=1&per_page=10
```

**Variation 2b: Sort ascending**
```
?user_id=1&per_page=5&sort_order=asc
```

**Variation 2c: Page 2**
```
?user_id=1&per_page=5&page=2
```

**Validation**:
- ✅ Pagination object contains all fields
- ✅ `total` matches actual count
- ✅ `per_page` matches request
- ✅ Results are sorted correctly

---

### Test Case 3: Get Single Session

**Request Name**: Get Session Details

**Method**: `GET`

**URL**: 
```
http://localhost:8000/api/conversations/session?session_id=test_session_123
```

**Headers**:
```
Authorization: Bearer 1|YOUR_AUTH_TOKEN_HERE
Content-Type: application/json
```

**Postman Params**:
```
session_id: test_session_123
```

**Expected Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "session": {
      "id": "test_session_123",
      "user_id": 1,
      "ip_address": "127.0.0.1",
      "user_agent": "Mozilla/5.0 Test Browser",
      "last_activity": "2025-08-13T18:09:33Z",
      "created_at": "2025-08-13T18:09:33Z"
    },
    "events": [
      {
        "id": 1,
        "event_type": "chat",
        "event_name": "user_message",
        "data": {
          "message": "Hello, I need legal advice",
          "timestamp": "2025-08-13T18:00:00Z"
        },
        "occurred_at": "2025-08-13T18:09:33Z",
        "created_at": "2025-08-13T18:09:33Z"
      },
      {
        "id": 2,
        "event_type": "chat",
        "event_name": "ai_response",
        "data": {
          "message": "Hello! How can I help you with your legal question?",
          "timestamp": "2025-08-13T18:00:05Z"
        },
        "occurred_at": "2025-08-13T18:09:33Z",
        "created_at": "2025-08-13T18:09:33Z"
      }
    ],
    "event_count": 2
  },
  "message": "Session conversation retrieved successfully"
}
```

**Validation**:
- ✅ Session ID matches request
- ✅ All events belong to the session
- ✅ Event count is accurate
- ✅ Data structure is nested correctly

---

### Test Case 4: Get Conversation Statistics

**Request Name**: Get Conversation Stats

**Method**: `GET`

**URL**: 
```
http://localhost:8000/api/conversations/stats?user_id=1
```

**Headers**:
```
Authorization: Bearer 1|YOUR_AUTH_TOKEN_HERE
Content-Type: application/json
```

**Postman Params**:
```
user_id: 1
```

**Expected Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "total_sessions": 1,
    "total_events": 3,
    "event_types": {
      "user_message": 1,
      "ai_response": 1,
      "legal_services_page": 1
    },
    "oldest_interaction": "2025-08-13T18:09:33Z",
    "newest_interaction": "2025-08-13T18:09:33Z"
  },
  "message": "Conversation statistics retrieved successfully"
}
```

**Validation**:
- ✅ `total_sessions` is a number > 0
- ✅ `total_events` is a number > 0
- ✅ `event_types` is an object with event names as keys
- ✅ Oldest is before or equal to newest
- ✅ Dates are in ISO8601 format

---

### Test Case 5: Export Conversations

**Request Name**: Export Conversations

**Method**: `GET`

**URL**: 
```
http://localhost:8000/api/conversations/export?user_id=1
```

**Headers**:
```
Authorization: Bearer 1|YOUR_AUTH_TOKEN_HERE
Content-Type: application/json
```

**Postman Params**:
```
user_id: 1
```

**Expected Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Test Student",
      "email": "test@example.com"
    },
    "exported_at": "2025-12-03T19:00:00Z",
    "conversations": [
      {
        "session": {
          "id": "test_session_123",
          "user_id": 1,
          "ip_address": "127.0.0.1",
          "user_agent": "Mozilla/5.0 Test Browser",
          "last_activity": "2025-08-13T18:09:33Z",
          "created_at": "2025-08-13T18:09:33Z"
        },
        "events": [
          {
            "id": 1,
            "event_type": "chat",
            "event_name": "user_message",
            "data": {
              "message": "Hello, I need legal advice",
              "timestamp": "2025-08-13T18:00:00Z"
            },
            "occurred_at": "2025-08-13T18:09:33Z",
            "created_at": "2025-08-13T18:09:33Z"
          }
        ],
        "event_count": 1
      }
    ]
  },
  "message": "Conversations exported successfully"
}
```

**Validation**:
- ✅ User data is included
- ✅ Export timestamp is current
- ✅ All conversations are included
- ✅ Proper format for JSON download

---

## ❌ Error Test Cases

### Error Case 1: Missing Required Parameter

**Request Name**: Missing user_id

**Method**: `GET`

**URL**: 
```
http://localhost:8000/api/conversations/user
```

**Headers**:
```
Authorization: Bearer 1|YOUR_AUTH_TOKEN_HERE
Content-Type: application/json
```

**Expected Response (422 Unprocessable Entity)**:
```json
{
  "success": false,
  "message": "user_id parameter is required"
}
```

**Test**: ✅ Verify error message is clear

---

### Error Case 2: Invalid User ID

**Request Name**: Non-existent User

**Method**: `GET`

**URL**: 
```
http://localhost:8000/api/conversations/user?user_id=99999
```

**Headers**:
```
Authorization: Bearer 1|YOUR_AUTH_TOKEN_HERE
Content-Type: application/json
```

**Expected Response (404 Not Found)**:
```json
{
  "success": false,
  "message": "User not found"
}
```

**Test**: ✅ Verify 404 status

---

### Error Case 3: Invalid Session ID

**Request Name**: Non-existent Session

**Method**: `GET`

**URL**: 
```
http://localhost:8000/api/conversations/session?session_id=invalid_session_xyz
```

**Headers**:
```
Authorization: Bearer 1|YOUR_AUTH_TOKEN_HERE
Content-Type: application/json
```

**Expected Response (404 Not Found)**:
```json
{
  "success": false,
  "message": "Session not found"
}
```

**Test**: ✅ Verify 404 status

---

### Error Case 4: Missing Authentication Token

**Request Name**: No Auth Token

**Method**: `GET`

**URL**: 
```
http://localhost:8000/api/conversations/user?user_id=1
```

**Headers**:
```
Content-Type: application/json
```

**Expected Response (401 Unauthorized)**:
```json
{
  "message": "Unauthenticated."
}
```

**Test**: ✅ Verify 401 status

---

### Error Case 5: Invalid Authentication Token

**Request Name**: Invalid Token

**Method**: `GET`

**URL**: 
```
http://localhost:8000/api/conversations/user?user_id=1
```

**Headers**:
```
Authorization: Bearer invalid_token_here
Content-Type: application/json
```

**Expected Response (401 Unauthorized)**:
```json
{
  "message": "Unauthenticated."
}
```

**Test**: ✅ Verify 401 status

---

## 🔗 cURL Commands - Full Test Suite

### 1. Get All Conversations
```bash
curl -X GET "http://localhost:8000/api/conversations/user?user_id=1" \
  -H "Authorization: Bearer 1|YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### 2. Get Paginated Conversations
```bash
curl -X GET "http://localhost:8000/api/conversations/user/paginated?user_id=1&per_page=5" \
  -H "Authorization: Bearer 1|YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### 3. Get Single Session
```bash
curl -X GET "http://localhost:8000/api/conversations/session?session_id=test_session_123" \
  -H "Authorization: Bearer 1|YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### 4. Get Statistics
```bash
curl -X GET "http://localhost:8000/api/conversations/stats?user_id=1" \
  -H "Authorization: Bearer 1|YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### 5. Export Conversations
```bash
curl -X GET "http://localhost:8000/api/conversations/export?user_id=1" \
  -H "Authorization: Bearer 1|YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### 6. Login to Get Token
```bash
curl -X POST "http://localhost:8000/api/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

---

## 📊 Test Results Table

| Test Case | Method | URL | Status | Response |
|-----------|--------|-----|--------|----------|
| Get All Conversations | GET | /api/conversations/user | 200 | ✅ Pass |
| Get Paginated | GET | /api/conversations/user/paginated | 200 | ✅ Pass |
| Get Session | GET | /api/conversations/session | 200 | ✅ Pass |
| Get Stats | GET | /api/conversations/stats | 200 | ✅ Pass |
| Export | GET | /api/conversations/export | 200 | ✅ Pass |
| Missing user_id | GET | /api/conversations/user | 422 | ✅ Pass |
| Invalid user_id | GET | /api/conversations/user | 404 | ✅ Pass |
| Invalid session_id | GET | /api/conversations/session | 404 | ✅ Pass |
| No auth token | GET | /api/conversations/user | 401 | ✅ Pass |
| Invalid token | GET | /api/conversations/user | 401 | ✅ Pass |

---

## 🔐 Postman Environment Setup

Create a Postman Environment named `Conversation API`:

**Variables**:
```
Variable Name: base_url
Value: http://localhost:8000
Scope: Global

Variable Name: auth_token
Value: 1|YOUR_TOKEN_HERE
Scope: Global

Variable Name: user_id
Value: 1
Scope: Global

Variable Name: session_id
Value: test_session_123
Scope: Global
```

**Usage in Requests**:
```
GET {{base_url}}/api/conversations/user?user_id={{user_id}}

Headers:
Authorization | Bearer {{auth_token}}
Content-Type | application/json
```

---

## 🧩 Postman Collection JSON

Copy this entire JSON into Postman (Import → Paste Raw) to create collection:

```json
{
  "info": {
    "name": "AI Chatbot Conversation API",
    "description": "Complete test suite for conversation endpoints",
    "version": "1.0"
  },
  "item": [
    {
      "name": "1. Get All Conversations",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{auth_token}}",
            "type": "text"
          },
          {
            "key": "Content-Type",
            "value": "application/json",
            "type": "text"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/conversations/user?user_id={{user_id}}",
          "host": ["{{base_url}}"],
          "path": ["api", "conversations", "user"],
          "query": [
            {
              "key": "user_id",
              "value": "{{user_id}}"
            }
          ]
        }
      }
    },
    {
      "name": "2. Get Paginated Conversations",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{auth_token}}",
            "type": "text"
          },
          {
            "key": "Content-Type",
            "value": "application/json",
            "type": "text"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/conversations/user/paginated?user_id={{user_id}}&per_page=5&sort_by=last_activity&sort_order=desc",
          "host": ["{{base_url}}"],
          "path": ["api", "conversations", "user", "paginated"],
          "query": [
            {
              "key": "user_id",
              "value": "{{user_id}}"
            },
            {
              "key": "per_page",
              "value": "5"
            },
            {
              "key": "sort_by",
              "value": "last_activity"
            },
            {
              "key": "sort_order",
              "value": "desc"
            }
          ]
        }
      }
    },
    {
      "name": "3. Get Session Details",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{auth_token}}",
            "type": "text"
          },
          {
            "key": "Content-Type",
            "value": "application/json",
            "type": "text"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/conversations/session?session_id={{session_id}}",
          "host": ["{{base_url}}"],
          "path": ["api", "conversations", "session"],
          "query": [
            {
              "key": "session_id",
              "value": "{{session_id}}"
            }
          ]
        }
      }
    },
    {
      "name": "4. Get Statistics",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{auth_token}}",
            "type": "text"
          },
          {
            "key": "Content-Type",
            "value": "application/json",
            "type": "text"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/conversations/stats?user_id={{user_id}}",
          "host": ["{{base_url}}"],
          "path": ["api", "conversations", "stats"],
          "query": [
            {
              "key": "user_id",
              "value": "{{user_id}}"
            }
          ]
        }
      }
    },
    {
      "name": "5. Export Conversations",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{auth_token}}",
            "type": "text"
          },
          {
            "key": "Content-Type",
            "value": "application/json",
            "type": "text"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/conversations/export?user_id={{user_id}}",
          "host": ["{{base_url}}"],
          "path": ["api", "conversations", "export"],
          "query": [
            {
              "key": "user_id",
              "value": "{{user_id}}"
            }
          ]
        }
      }
    }
  ]
}
```

---

## 📋 Manual Testing Checklist

### Setup Phase
- [ ] Laravel server running (`php artisan serve`)
- [ ] Database accessible with sample data
- [ ] User with ID 1 exists in database
- [ ] Session `test_session_123` exists
- [ ] Events exist for the session

### Authentication Phase
- [ ] Successfully login and get token
- [ ] Save token to environment variable
- [ ] Token is valid and not expired

### Endpoint Testing Phase

**Get All Conversations**
- [ ] Response status is 200
- [ ] `success` field is `true`
- [ ] `data` array is returned
- [ ] Sessions contain events
- [ ] Events are ordered chronologically
- [ ] `total_sessions` and `total_events` present

**Get Paginated Conversations**
- [ ] Response status is 200
- [ ] `pagination` object present
- [ ] Pagination values are correct
- [ ] Per page limit respected
- [ ] Sorting works correctly

**Get Session Details**
- [ ] Response status is 200
- [ ] Single session returned (not array)
- [ ] All events for session included
- [ ] Event count matches actual

**Get Statistics**
- [ ] Response status is 200
- [ ] `total_sessions` > 0
- [ ] `total_events` > 0
- [ ] `event_types` breakdown present
- [ ] Date range is valid

**Export Conversations**
- [ ] Response status is 200
- [ ] User data included
- [ ] Export timestamp present
- [ ] All conversations included

### Error Handling Phase
- [ ] Missing user_id returns 422
- [ ] Invalid user returns 404
- [ ] Invalid session returns 404
- [ ] No token returns 401
- [ ] Invalid token returns 401

### Edge Cases Phase
- [ ] Empty results handled correctly
- [ ] Large pagination values work
- [ ] Special characters in data handled
- [ ] Null values in event_data handled

---

## 📊 Performance Testing

### Response Time Targets
```
Get All Conversations: < 500ms
Get Paginated: < 300ms
Get Session: < 200ms
Get Statistics: < 400ms
Export: < 1000ms
```

### Load Testing
```
Sequential requests: 100 requests
Expected time: < 30 seconds
Error rate: 0%
```

---

## 🔄 Regression Testing

After any code changes, run this test sequence:

1. ✅ All 5 endpoints return 200
2. ✅ Response structure unchanged
3. ✅ All error cases still work
4. ✅ Performance meets targets
5. ✅ No new errors introduced

---

## 🐛 Debugging Tips

### Response is empty
- Check if user_id exists in database
- Check if sessions/events exist for user
- Run: `sqlite3 database.sqlite "SELECT * FROM events WHERE user_id = 1;"`

### 401 Unauthorized
- Token may be expired - get new one
- Check Authorization header format: `Bearer TOKEN`
- Verify token in environment variable

### 500 Server Error
- Check Laravel logs: `tail -f storage/logs/laravel.log`
- Verify database connection
- Check model relationships

### Slow responses
- Run: `php artisan optimize`
- Clear cache: `php artisan cache:clear`
- Check database indexes

---

## 📝 Test Report Template

```
API Testing Report
Date: ___________
Tester: _________

Test Results:
✅ Get All Conversations: PASS
✅ Get Paginated: PASS
✅ Get Session: PASS
✅ Get Stats: PASS
✅ Export: PASS

Error Cases:
✅ Missing user_id: PASS
✅ Invalid user: PASS
✅ Invalid session: PASS
✅ No auth: PASS
✅ Invalid token: PASS

Performance:
Average Response Time: ___ ms
Slowest Endpoint: ___________
Status: PASS/FAIL

Notes:
_________________________
_________________________

Signed: ________________
```

---

## 🚀 Quick Start for Testing

1. **Start Laravel**:
   ```bash
   cd /path/to/chamberapis
   php artisan serve
   ```

2. **Get Token**:
   ```bash
   curl -X POST "http://localhost:8000/api/login" \
     -H "Content-Type: application/json" \
     -d '{"email":"test@example.com","password":"password123"}'
   ```

3. **Save Token**:
   Set `TOKEN=your_token_here`

4. **Test Endpoint**:
   ```bash
   curl -X GET "http://localhost:8000/api/conversations/user?user_id=1" \
     -H "Authorization: Bearer $TOKEN"
   ```

5. **Verify Response**:
   Check for `"success": true` in response

---

## ✨ Summary

This testing guide covers:
- ✅ All 5 API endpoints
- ✅ All error scenarios
- ✅ Postman setup & collection
- ✅ cURL commands
- ✅ Expected responses
- ✅ Validation criteria
- ✅ Performance benchmarks
- ✅ Manual checklist
- ✅ Debugging tips
