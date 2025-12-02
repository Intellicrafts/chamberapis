# AI Chatbot Conversation API Documentation

## Overview
This API provides endpoints to retrieve and manage AI agentic chatbot conversations. The conversations are organized by sessions with events (messages) as nested data, making it easy to consume on React frontends.

## Database Tables Structure

### sessions table
- **id** (varchar, primary key) - Unique session identifier
- **user_id** (integer, FK) - User who created the session
- **ip_address** (varchar) - User's IP address
- **user_agent** (text) - User's browser/device information
- **payload** (text) - Serialized session payload
- **last_activity** (integer) - Unix timestamp of last activity

### events table
- **id** (integer, primary key) - Auto-incrementing ID
- **session_id** (varchar, FK) - Associated session
- **user_id** (integer, FK) - User who triggered the event
- **event_type** (varchar) - Type of event (e.g., "chat", "system")
- **event_name** (varchar) - Name of specific event (e.g., "user_message", "ai_response")
- **event_data** (json) - Flexible JSON payload with event details
- **ip_address** (varchar) - IP address when event occurred
- **user_agent** (text) - Browser/device info when event occurred
- **url** (varchar) - URL where event occurred
- **referrer** (varchar) - Referrer URL
- **occurred_at** (datetime) - When the event occurred
- **created_at** (datetime) - Record creation time
- **updated_at** (datetime) - Record update time

## Endpoints

All endpoints require authentication via Sanctum token (add `Authorization: Bearer {token}` header).

### Base URL
```
/api/conversations
```

### 1. Get All User Conversations
**Endpoint:** `GET /api/conversations/user`

**Query Parameters:**
- `user_id` (required, integer) - ID of the user

**Response Format:**
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

### 2. Get Paginated User Conversations
**Endpoint:** `GET /api/conversations/user/paginated`

**Query Parameters:**
- `user_id` (required, integer) - ID of the user
- `per_page` (optional, integer, default: 10) - Records per page
- `sort_by` (optional, string, default: "last_activity") - Field to sort by
- `sort_order` (optional, string, default: "desc") - Sort order (asc/desc)

**Response Format:**
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
    "per_page": 10,
    "current_page": 1,
    "last_page": 2,
    "from": 1,
    "to": 10
  },
  "message": "Paginated conversations retrieved successfully"
}
```

### 3. Get Specific Session Conversation
**Endpoint:** `GET /api/conversations/session`

**Query Parameters:**
- `session_id` (required, string) - ID of the session

**Response Format:**
```json
{
  "success": true,
  "data": {
    "session": { ... },
    "events": [ ... ],
    "event_count": 2
  },
  "message": "Session conversation retrieved successfully"
}
```

### 4. Get Conversation Statistics
**Endpoint:** `GET /api/conversations/stats`

**Query Parameters:**
- `user_id` (required, integer) - ID of the user

**Response Format:**
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

### 5. Export User Conversations
**Endpoint:** `GET /api/conversations/export`

**Query Parameters:**
- `user_id` (required, integer) - ID of the user

**Response Format:**
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

## Models Created

### Event Model (app/Models/Event.php)
- **Relationships:** BelongsTo User, BelongsTo ChatSession
- **Scopes:** byType, bySession, chatEvents, orderByOccurrence

### ChatSession Model (app/Models/ChatSession.php)
- **Relationships:** BelongsTo User, HasMany Events, HasMany ChatEvents
- **Scopes:** forUser, withConversations
- **Key Attributes:**
  - Non-incrementing string primary key
  - No timestamps (uses last_activity instead)

### Event Model Relations to User
- User has many ChatSessions
- User has many Events

## Controller: ConversationController

Located at: `app/Http/Controllers/API/ConversationController.php`

### Methods:
1. **getUserConversations()** - Fetch all user conversations
2. **getUserConversationsPaginated()** - Paginated conversations
3. **getSessionConversation()** - Single session data
4. **getConversationStats()** - Conversation statistics
5. **exportUserConversations()** - Export conversations as JSON

## Routes

All routes are under `/api/conversations` and require authentication (`middleware:auth:sanctum`):

```
GET    /api/conversations/user             → getUserConversations
GET    /api/conversations/user/paginated   → getUserConversationsPaginated
GET    /api/conversations/session          → getSessionConversation
GET    /api/conversations/stats            → getConversationStats
GET    /api/conversations/export           → exportUserConversations
```

## Migrations

Two migration files were created:
1. **2025_12_03_000001_create_chat_sessions_table.php** - Sessions table
2. **2025_12_03_000002_create_chat_events_table.php** - Events table

Note: These tables already exist in the database. Migrations are provided for documentation and setup in fresh environments.

## Usage Example (React/JavaScript)

```javascript
// Fetch all conversations for user ID 1
const response = await fetch('/api/conversations/user?user_id=1', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

const data = await response.json();

if (data.success) {
  // Loop through sessions
  data.data.forEach(conversation => {
    const session = conversation.session;
    const events = conversation.events;
    
    console.log(`Session: ${session.id}`);
    console.log(`Messages: ${events.length}`);
    
    events.forEach(event => {
      console.log(`${event.event_name}: ${event.data.message}`);
    });
  });
}
```

## Error Handling

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description"
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `404` - Resource not found
- `422` - Validation error (missing required parameters)
- `500` - Server error

## Features

✅ Sessions grouped with events as nested data
✅ Chronologically ordered events
✅ JSON-formatted event data for flexibility
✅ Pagination support
✅ Statistics and analytics
✅ Export functionality
✅ Proper error handling
✅ Comprehensive comments in code
✅ Follows existing codebase patterns
✅ Sanctum authentication protection
