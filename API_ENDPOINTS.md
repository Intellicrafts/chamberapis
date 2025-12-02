# API Endpoints - Complete Reference

## Base Information
- **Base URL**: `/api/conversations`
- **Authentication**: Required (Bearer token)
- **Content-Type**: application/json
- **Database**: SQLite (sessions & events tables)

## Endpoint 1: Get All User Conversations

### Request
```
GET /api/conversations/user
```

### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| user_id | integer | Yes | - | ID of the user |

### Example
```bash
curl -X GET "http://localhost/api/conversations/user?user_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### Success Response (200)
```json
{
  "success": true,
  "data": [
    {
      "session": {
        "id": "test_session_123",
        "user_id": 1,
        "ip_address": "127.0.0.1",
        "user_agent": "Mozilla/5.0...",
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

### Error Response (422)
```json
{
  "success": false,
  "message": "user_id parameter is required"
}
```

### Error Response (404)
```json
{
  "success": false,
  "message": "User not found"
}
```

---

## Endpoint 2: Get Paginated User Conversations

### Request
```
GET /api/conversations/user/paginated
```

### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| user_id | integer | Yes | - | ID of the user |
| per_page | integer | No | 10 | Records per page |
| sort_by | string | No | last_activity | Field to sort by |
| sort_order | string | No | desc | asc or desc |

### Example
```bash
curl -X GET "http://localhost/api/conversations/user/paginated?user_id=1&per_page=5&sort_by=last_activity&sort_order=desc" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### Success Response (200)
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
    "total": 15,
    "per_page": 5,
    "current_page": 1,
    "last_page": 3,
    "from": 1,
    "to": 5
  },
  "message": "Paginated conversations retrieved successfully"
}
```

---

## Endpoint 3: Get Specific Session Conversation

### Request
```
GET /api/conversations/session
```

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| session_id | string | Yes | ID of the session |

### Example
```bash
curl -X GET "http://localhost/api/conversations/session?session_id=test_session_123" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### Success Response (200)
```json
{
  "success": true,
  "data": {
    "session": {
      "id": "test_session_123",
      "user_id": 1,
      "ip_address": "127.0.0.1",
      "user_agent": "Mozilla/5.0...",
      "last_activity": "2025-08-13T18:09:33Z",
      "created_at": "2025-08-13T18:09:33Z"
    },
    "events": [
      {
        "id": 1,
        "event_type": "chat",
        "event_name": "user_message",
        "data": { ... },
        "occurred_at": "2025-08-13T18:09:33Z",
        "created_at": "2025-08-13T18:09:33Z"
      }
    ],
    "event_count": 1
  },
  "message": "Session conversation retrieved successfully"
}
```

### Error Response (404)
```json
{
  "success": false,
  "message": "Session not found"
}
```

---

## Endpoint 4: Get Conversation Statistics

### Request
```
GET /api/conversations/stats
```

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | integer | Yes | ID of the user |

### Example
```bash
curl -X GET "http://localhost/api/conversations/stats?user_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### Success Response (200)
```json
{
  "success": true,
  "data": {
    "total_sessions": 5,
    "total_events": 47,
    "event_types": {
      "user_message": 23,
      "ai_response": 24
    },
    "oldest_interaction": "2025-08-01T10:30:00Z",
    "newest_interaction": "2025-08-13T18:09:33Z"
  },
  "message": "Conversation statistics retrieved successfully"
}
```

---

## Endpoint 5: Export User Conversations

### Request
```
GET /api/conversations/export
```

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | integer | Yes | ID of the user |

### Example
```bash
curl -X GET "http://localhost/api/conversations/export?user_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### Success Response (200)
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
        "session": { ... },
        "events": [ ... ],
        "event_count": 2
      }
    ]
  },
  "message": "Conversations exported successfully"
}
```

---

## HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 404 | Not Found | User or session not found |
| 422 | Unprocessable Entity | Missing required parameters |
| 500 | Server Error | Internal server error |
| 401 | Unauthorized | Missing or invalid authentication token |

---

## Authentication

### Adding Token to Requests

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

### Getting a Token

1. First, log in to get authentication token:
```bash
curl -X POST "http://localhost/api/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

2. Use the returned token in subsequent requests:
```json
{
  "success": true,
  "data": {
    "user": { ... },
    "token": "1|YOUR_TOKEN_HERE"
  }
}
```

---

## JavaScript/Fetch Examples

### Example 1: Get All Conversations
```javascript
async function getAllConversations(userId, token) {
  const response = await fetch(
    `/api/conversations/user?user_id=${userId}`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    }
  );

  const data = await response.json();
  
  if (data.success) {
    console.log('Conversations:', data.data);
    console.log('Total sessions:', data.total_sessions);
    console.log('Total events:', data.total_events);
  } else {
    console.error('Error:', data.message);
  }
  
  return data;
}
```

### Example 2: Get Paginated Conversations
```javascript
async function getPaginatedConversations(userId, page = 1, perPage = 10, token) {
  const response = await fetch(
    `/api/conversations/user/paginated?user_id=${userId}&per_page=${perPage}`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    }
  );

  return response.json();
}
```

### Example 3: Get Session Details
```javascript
async function getSessionDetails(sessionId, token) {
  const response = await fetch(
    `/api/conversations/session?session_id=${sessionId}`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    }
  );

  const data = await response.json();
  
  if (data.success) {
    const session = data.data;
    console.log(`Session: ${session.session.id}`);
    console.log(`Messages: ${session.event_count}`);
    session.events.forEach(event => {
      console.log(`${event.event_name}: ${event.data.message}`);
    });
  }
  
  return data;
}
```

### Example 4: Get Statistics
```javascript
async function getConversationStats(userId, token) {
  const response = await fetch(
    `/api/conversations/stats?user_id=${userId}`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    }
  );

  const data = await response.json();
  
  if (data.success) {
    console.log('Total sessions:', data.data.total_sessions);
    console.log('Total events:', data.data.total_events);
    console.log('Event breakdown:', data.data.event_types);
  }
  
  return data;
}
```

---

## Response Codes Summary

### Success Codes
- **200 OK**: Request successful
- **201 Created**: Resource created (if applicable)

### Client Error Codes
- **400 Bad Request**: Invalid request format
- **401 Unauthorized**: Authentication required/failed
- **404 Not Found**: Resource not found
- **422 Unprocessable Entity**: Validation errors

### Server Error Codes
- **500 Internal Server Error**: Server error

---

## Rate Limiting

Currently no rate limiting on conversation endpoints. Implement if needed based on load.

## Caching

Responses are not cached by default. Consider implementing caching for:
- Statistics endpoints
- Frequently accessed sessions

## Future Enhancements

- [ ] Filtering by date range
- [ ] Search within events
- [ ] Webhooks for new conversations
- [ ] Real-time updates via WebSockets
- [ ] CSV export
- [ ] Analytics dashboard
