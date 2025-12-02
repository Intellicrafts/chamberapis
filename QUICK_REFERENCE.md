# API Quick Reference Guide

## File Locations

```
Models:
├── app/Models/Event.php                    (Event model with relationships)
├── app/Models/ChatSession.php              (ChatSession model)
└── app/Models/User.php                     (Updated with chat relationships)

Controller:
└── app/Http/Controllers/API/ConversationController.php

Routes:
└── routes/api.php                          (Added conversation routes)

Migrations (Not Run):
├── database/migrations/2025_12_03_000001_create_chat_sessions_table.php
└── database/migrations/2025_12_03_000002_create_chat_events_table.php

Documentation:
├── API_DOCUMENTATION.md                    (Full API reference)
├── IMPLEMENTATION_SUMMARY.md               (Detailed implementation)
└── QUICK_REFERENCE.md                      (This file)
```

## Database Tables Used

**sessions** - Existing table storing chatbot session data
- Primary Key: id (varchar)
- Foreign Key: user_id → users.id
- Fields: ip_address, user_agent, payload, last_activity

**events** - Existing table storing individual chat events/messages
- Primary Key: id (auto-increment)
- Foreign Keys: session_id → sessions.id, user_id → users.id
- Fields: event_type, event_name, event_data (JSON), url, referrer, occurred_at

## API Endpoints

### Get All Conversations
```
GET /api/conversations/user?user_id=1
Authorization: Bearer {token}
```

### Get Paginated Conversations
```
GET /api/conversations/user/paginated?user_id=1&per_page=10&sort_by=last_activity&sort_order=desc
Authorization: Bearer {token}
```

### Get Single Session
```
GET /api/conversations/session?session_id=test_session_123
Authorization: Bearer {token}
```

### Get Statistics
```
GET /api/conversations/stats?user_id=1
Authorization: Bearer {token}
```

### Export Conversations
```
GET /api/conversations/export?user_id=1
Authorization: Bearer {token}
```

## Response Structure

### Data Format (Session with Events)
```javascript
{
  session: {
    id: "string",
    user_id: number,
    ip_address: "string",
    user_agent: "string",
    last_activity: "ISO8601 datetime",
    created_at: "ISO8601 datetime"
  },
  events: [
    {
      id: number,
      event_type: "string",      // "chat", "system", etc
      event_name: "string",      // "user_message", "ai_response"
      data: {...},               // JSON object
      occurred_at: "ISO8601",
      created_at: "ISO8601"
    }
  ],
  event_count: number
}
```

## React Integration Example

```javascript
// Fetch all conversations
const fetchConversations = async (userId, token) => {
  const response = await fetch(
    `/api/conversations/user?user_id=${userId}`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    }
  );
  
  const data = await response.json();
  
  if (data.success) {
    // data.data contains array of conversations
    return data.data;
  }
};

// Render conversation
{conversations.map(conv => (
  <div key={conv.session.id}>
    <h3>{conv.session.id}</h3>
    {conv.events.map(event => (
      <div key={event.id} className={event.event_name}>
        {event.data.message}
      </div>
    ))}
  </div>
))}
```

## Controller Methods Breakdown

### ConversationController.php

| Method | Purpose | Input | Output |
|--------|---------|-------|--------|
| getUserConversations() | All user conversations | user_id | Array of sessions with events |
| getUserConversationsPaginated() | Paginated conversations | user_id, per_page, sort* | Paginated data + metadata |
| getSessionConversation() | Single session | session_id | One session with events |
| getConversationStats() | User statistics | user_id | Stats object |
| exportUserConversations() | Export as JSON | user_id | Complete export data |

### Helper Methods

| Method | Purpose |
|--------|---------|
| formatConversations() | Transform all user conversations |
| formatSessionData() | Structure single session |

## Relationships

### User Model
```
User 1---* ChatSession
User 1---* Event
```

### ChatSession Model
```
ChatSession *---1 User
ChatSession 1---* Event
```

### Event Model
```
Event *---1 User
Event *---1 ChatSession
```

## Key Features

✅ **Smart Data Grouping** - Sessions as keys, events as values
✅ **Flexible Event Data** - JSON-based event_data field
✅ **Chronological Ordering** - Events ordered by occurred_at
✅ **Pagination Support** - Per-page, sort_by, sort_order
✅ **Authentication Protected** - Requires Sanctum token
✅ **Error Handling** - Consistent error responses
✅ **Statistics Support** - Event counts, time ranges
✅ **Export Functionality** - Full data export as JSON
✅ **Well Documented** - Comments throughout code
✅ **Follows Patterns** - Matches existing codebase style

## Status

✅ All models created
✅ All controller methods implemented
✅ All routes configured
✅ All migrations documented
✅ Database schema verified
✅ Sample data confirmed
✅ Error handling implemented
✅ Documentation complete

## Important Notes

- All endpoints require authentication token
- Tables already exist in the database
- Migrations are for documentation/fresh setup
- No migrations need to be run
- Event data is flexible JSON format
- Supports any chatbot message structure
- Performance optimized with indexes
