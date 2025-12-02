# AI Chatbot Conversation API - Master Reference

## 📋 Project Summary

A complete REST API implementation for retrieving AI chatbot conversations from your MeraBakil.com application. The API intelligently organizes conversation data by sessions with events (messages) as nested arrays, optimized for React frontend consumption.

**Database**: SQLite
**Tables Used**: sessions, events (existing tables in your database)
**Authentication**: Sanctum Bearer Token
**Framework**: Laravel 11
**Language**: PHP 8.2+

## 🎯 What Was Built

### 1. **Two Eloquent Models**
- **Event.php** - Represents individual chat messages/events
- **ChatSession.php** - Represents conversation sessions with grouped events

### 2. **Conversation Controller**
- **ConversationController.php** - 5 methods handling all conversation operations
  - Get all user conversations
  - Get paginated conversations
  - Get single session details
  - Get conversation statistics
  - Export conversations as JSON

### 3. **API Routes**
- 5 new endpoints under `/api/conversations` prefix
- All require authentication

### 4. **Migrations** (For documentation, not run)
- Sessions table migration
- Events table migration

### 5. **Documentation**
- API_DOCUMENTATION.md - Full API reference
- API_ENDPOINTS.md - Endpoint details with examples
- IMPLEMENTATION_SUMMARY.md - Technical details
- QUICK_REFERENCE.md - Quick lookup guide
- This README

## 📁 File Structure

```
chamberapis/
├── app/
│   ├── Models/
│   │   ├── Event.php                    ← NEW
│   │   ├── ChatSession.php              ← NEW
│   │   └── User.php                     ← MODIFIED (added relationships)
│   └── Http/Controllers/API/
│       └── ConversationController.php   ← NEW
├── database/migrations/
│   ├── 2025_12_03_000001_create_chat_sessions_table.php    ← NEW
│   └── 2025_12_03_000002_create_chat_events_table.php      ← NEW
├── routes/
│   └── api.php                          ← MODIFIED (added routes)
├── API_DOCUMENTATION.md                 ← NEW
├── API_ENDPOINTS.md                     ← NEW
├── IMPLEMENTATION_SUMMARY.md            ← NEW
├── QUICK_REFERENCE.md                   ← NEW
└── CONVERSATION_API_README.md           ← NEW (this file)
```

## 🚀 Quick Start

### 1. Database Status
✅ **Sessions** table exists with 6 records
✅ **Events** table exists with 3 records
✅ **Relationships** properly linked with foreign keys
✅ **Indexes** optimized for query performance

**No migrations need to be run** - tables already exist in your database

### 2. Authentication
Get a token first:
```bash
curl -X POST "http://your-app/api/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password"
  }'
```

### 3. Test the API
```bash
curl "http://your-app/api/conversations/user?user_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 📊 Data Format Example

**Input**: GET `/api/conversations/user?user_id=1`

**Output**: 
```json
{
  "success": true,
  "total_sessions": 1,
  "total_events": 2,
  "data": [
    {
      "session": {
        "id": "test_session_123",
        "user_id": 1,
        "last_activity": "2025-08-13T18:09:33Z"
      },
      "events": [
        {
          "id": 1,
          "event_type": "chat",
          "event_name": "user_message",
          "data": {
            "message": "Hello, I need legal advice"
          },
          "occurred_at": "2025-08-13T18:09:33Z"
        },
        {
          "id": 2,
          "event_type": "chat",
          "event_name": "ai_response",
          "data": {
            "message": "Hello! How can I help you?"
          },
          "occurred_at": "2025-08-13T18:09:33Z"
        }
      ],
      "event_count": 2
    }
  ]
}
```

## 🔌 API Endpoints

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| GET | `/api/conversations/user` | All user conversations | ✓ |
| GET | `/api/conversations/user/paginated` | Paginated conversations | ✓ |
| GET | `/api/conversations/session` | Single session details | ✓ |
| GET | `/api/conversations/stats` | Conversation statistics | ✓ |
| GET | `/api/conversations/export` | Export as JSON | ✓ |

**Full documentation**: See `API_ENDPOINTS.md`

## 🎨 React Component Example

```javascript
import { useEffect, useState } from 'react';

function ConversationHistory({ userId, token }) {
  const [conversations, setConversations] = useState([]);

  useEffect(() => {
    fetch(`/api/conversations/user?user_id=${userId}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) setConversations(data.data);
    });
  }, [userId, token]);

  return (
    <div>
      {conversations.map(conv => (
        <div key={conv.session.id}>
          <h3>Session: {conv.session.id}</h3>
          {conv.events.map(event => (
            <p key={event.id}>
              <strong>{event.event_name}</strong>: {event.data.message}
            </p>
          ))}
        </div>
      ))}
    </div>
  );
}
```

## 🗄️ Database Schema

### sessions Table
```
id (varchar) - Primary Key
user_id (int) - Foreign Key
ip_address (varchar)
user_agent (text)
payload (text)
last_activity (int)
```

### events Table
```
id (int) - Primary Key, Auto-increment
session_id (varchar) - Foreign Key
user_id (int) - Foreign Key
event_type (varchar) - "chat", "system", etc
event_name (varchar) - "user_message", "ai_response"
event_data (json) - Flexible payload
occurred_at (datetime)
created_at, updated_at (datetime)
```

## 🔐 Security Features

✅ **Sanctum Authentication** - All endpoints require valid token
✅ **Parameter Validation** - Required fields checked
✅ **User Verification** - User must exist
✅ **Foreign Key Constraints** - Database integrity
✅ **SQL Injection Prevention** - Eloquent ORM used

## ⚡ Performance Optimizations

✅ **Database Indexes** - On frequently queried columns
✅ **Eager Loading** - Relationships loaded efficiently
✅ **Query Scopes** - Optimize common queries
✅ **Pagination** - Large datasets handled efficiently
✅ **JSON Casts** - Automatic serialization

## 📚 Documentation Files

1. **API_DOCUMENTATION.md** - Complete API reference with request/response examples
2. **API_ENDPOINTS.md** - Detailed endpoint documentation with curl examples
3. **IMPLEMENTATION_SUMMARY.md** - Technical implementation details
4. **QUICK_REFERENCE.md** - Quick lookup guide
5. **CONVERSATION_API_README.md** - This master reference

## 🧪 Testing

All files verified:
- ✅ PHP syntax validated
- ✅ Models and relationships tested
- ✅ Database connections verified
- ✅ Sample data confirmed
- ✅ Routes registered

**To verify routes:**
```bash
php artisan route:list | grep conversations
```

## 🛠️ Controller Methods

### getUserConversations()
Gets all conversations for a user, grouped by session

### getUserConversationsPaginated()
Same as above but with pagination support

### getSessionConversation()
Gets a specific session with all its events

### getConversationStats()
Returns statistics: total sessions, events, breakdown by type

### exportUserConversations()
Exports complete conversation history as JSON

## 📝 Model Relationships

```
User 1---* ChatSession
User 1---* Event
ChatSession 1---* Event
```

## 🎓 Usage Tips

### Tip 1: Get data for current user
```javascript
const userId = currentUser.id;
const response = await fetch(`/api/conversations/user?user_id=${userId}`);
```

### Tip 2: Use pagination for large datasets
```javascript
const response = await fetch(
  `/api/conversations/user/paginated?user_id=${userId}&per_page=5`
);
```

### Tip 3: Export user data
```javascript
const response = await fetch(`/api/conversations/export?user_id=${userId}`);
const blob = new Blob([JSON.stringify(response.json())]);
downloadFile(blob, `conversations-${userId}.json`);
```

### Tip 4: Display statistics
```javascript
const response = await fetch(`/api/conversations/stats?user_id=${userId}`);
const stats = response.json();
console.log(`Total: ${stats.data.total_events} messages in ${stats.data.total_sessions} sessions`);
```

## 🔄 Data Flow

```
React Frontend
    ↓
HTTP Request (with token)
    ↓
ConversationController
    ↓
Eloquent Models (Event, ChatSession, User)
    ↓
SQLite Database (sessions, events tables)
    ↓
Format Response
    ↓
JSON Response
    ↓
React Frontend Renders
```

## ✅ Implementation Checklist

- [x] Event model created with relationships
- [x] ChatSession model created with relationships
- [x] User model updated with chat relationships
- [x] ConversationController created with 5 methods
- [x] Routes added to api.php
- [x] Migrations created (not run)
- [x] Error handling implemented
- [x] Pagination support added
- [x] Statistics & export features included
- [x] Code comments added throughout
- [x] Database schema verified
- [x] Sample data confirmed

## 🐛 Troubleshooting

**Issue**: 401 Unauthorized
**Solution**: Ensure you're including valid authentication token in headers

**Issue**: 404 User not found
**Solution**: Verify user_id exists in database

**Issue**: 422 Missing parameters
**Solution**: Check required query parameters (user_id, session_id)

**Issue**: No data returned
**Solution**: Verify sessions and events exist for the user in database

## 📞 Support

For detailed information, see:
- `API_DOCUMENTATION.md` - Full API reference
- `API_ENDPOINTS.md` - Endpoint examples
- `IMPLEMENTATION_SUMMARY.md` - Technical details

## 🚀 Next Steps

1. Get authentication token from login endpoint
2. Include token in Authorization header
3. Call conversation endpoints
4. Parse and display data in React
5. Implement pagination for large datasets
6. Add search/filter functionality as needed

## 📦 Installation Notes

**No installation required**
- Models already created
- Controller already created  
- Routes already configured
- Database tables already exist
- Just start using the API!

---

**Last Updated**: December 3, 2025
**Status**: ✅ Complete and Ready for Use
**Created By**: AI Assistant
