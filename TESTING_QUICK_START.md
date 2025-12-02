# Testing Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Start Laravel Server (30 seconds)
```bash
cd /home/devesh/Desktop/MeraBakil.com/chamberapis
php artisan serve
```
✅ Server runs on: `http://localhost:8000`

---

### Step 2: Get Authentication Token (1 minute)

**Option A: Using cURL**
```bash
curl -X POST "http://localhost:8000/api/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

**Option B: Using Postman**
1. Open Postman
2. New Request → POST
3. URL: `http://localhost:8000/api/login`
4. Body (raw JSON):
   ```json
   {
     "email": "test@example.com",
     "password": "password123"
   }
   ```
5. Click Send

**Response**:
```json
{
  "success": true,
  "data": {
    "token": "1|XXXXXXXXXXXXXXXX"
  }
}
```

**⚠️ IMPORTANT**: Copy the token (the value after "1|")

---

### Step 3: Setup Postman Collection (2 minutes)

**Option A: Import JSON Collection (Recommended)**
1. Download `Conversation_API.postman_collection.json`
2. Open Postman → Collections → Import
3. Select the JSON file
4. Click Import

**Option B: Manual Setup**
1. New Collection → Name: "Conversation API"
2. Add Requests (see below)

---

### Step 4: Setup Environment Variables (1 minute)

In Postman:
1. Click **Environments** (bottom left)
2. Click **Create New** or **+**
3. Name: `Conversation API`
4. Add Variables:

| Variable | Value |
|----------|-------|
| base_url | http://localhost:8000 |
| auth_token | 1\|YOUR_TOKEN_FROM_STEP_2 |
| user_id | 1 |
| session_id | test_session_123 |

5. Click **Save**
6. Select environment from dropdown (top right)

---

### Step 5: Test First Endpoint (1 minute)

**Request 1: Get All Conversations**

```
GET {{base_url}}/api/conversations/user?user_id={{user_id}}

Headers:
Authorization | Bearer {{auth_token}}
Content-Type | application/json
```

**In Postman**:
1. New Request
2. Method: GET
3. URL: `http://localhost:8000/api/conversations/user?user_id=1`
4. Headers:
   - Key: `Authorization`
   - Value: `Bearer 1|YOUR_TOKEN`
5. Send

**Expected Response**:
```json
{
  "success": true,
  "data": [
    {
      "session": { ... },
      "events": [ ... ],
      "event_count": 2
    }
  ]
}
```

✅ If you see `"success": true`, the API works!

---

## 📋 Quick Reference Commands

### Test All 5 Endpoints with cURL

```bash
TOKEN="1|YOUR_TOKEN_HERE"
BASE_URL="http://localhost:8000"

# 1. Get All Conversations
curl -X GET "$BASE_URL/api/conversations/user?user_id=1" \
  -H "Authorization: Bearer $TOKEN"

# 2. Get Paginated
curl -X GET "$BASE_URL/api/conversations/user/paginated?user_id=1&per_page=5" \
  -H "Authorization: Bearer $TOKEN"

# 3. Get Session
curl -X GET "$BASE_URL/api/conversations/session?session_id=test_session_123" \
  -H "Authorization: Bearer $TOKEN"

# 4. Get Stats
curl -X GET "$BASE_URL/api/conversations/stats?user_id=1" \
  -H "Authorization: Bearer $TOKEN"

# 5. Export
curl -X GET "$BASE_URL/api/conversations/export?user_id=1" \
  -H "Authorization: Bearer $TOKEN"
```

---

## 🎯 Testing Checklist

- [ ] Laravel server running
- [ ] Got authentication token
- [ ] Postman collection imported
- [ ] Environment variables set
- [ ] Test endpoint 1: 200 OK ✅
- [ ] Test endpoint 2: 200 OK ✅
- [ ] Test endpoint 3: 200 OK ✅
- [ ] Test endpoint 4: 200 OK ✅
- [ ] Test endpoint 5: 200 OK ✅
- [ ] All error cases return correct status

---

## 🐛 Troubleshooting

### Issue: 401 Unauthorized
```
Error: Unauthenticated
```
**Solution**:
1. Get new token: `curl -X POST "http://localhost:8000/api/login"`
2. Copy token value (after "1|")
3. Update auth_token in environment
4. Retry

### Issue: 404 User not found
```
Error: "User not found"
```
**Solution**:
- Check if user ID 1 exists: `sqlite3 database/database.sqlite "SELECT * FROM users LIMIT 1;"`
- If not, create test user

### Issue: 404 Session not found
```
Error: "Session not found"
```
**Solution**:
- Check if session exists: `sqlite3 database/database.sqlite "SELECT * FROM sessions;"`
- Use correct session_id from database

### Issue: Server not running
```
Error: Connection refused
```
**Solution**:
```bash
cd /path/to/chamberapis
php artisan serve
```

### Issue: Database connection error
```
Error: SQLSTATE [HY000]
```
**Solution**:
1. Verify database exists: `ls database/database.sqlite`
2. Check database permissions: `chmod 777 database/`
3. Run migrations if needed: `php artisan migrate`

---

## 📊 Sample Database Query

Check sample data in database:

```bash
# Check users
sqlite3 database/database.sqlite "SELECT * FROM users;"

# Check sessions
sqlite3 database/database.sqlite "SELECT * FROM sessions;"

# Check events
sqlite3 database/database.sqlite "SELECT * FROM events WHERE user_id = 1;"

# Check total data
sqlite3 database/database.sqlite "SELECT 
  (SELECT COUNT(*) FROM users) as users,
  (SELECT COUNT(*) FROM sessions) as sessions,
  (SELECT COUNT(*) FROM events) as events;"
```

---

## 📝 Test Results Log

Use this to record your test results:

```
Test Date: __________________
Tester: ______________________

Login Endpoint
Status: ✅ PASS  ❌ FAIL
Token Received: YES / NO

Endpoint 1: Get All Conversations
Status: ✅ PASS  ❌ FAIL
Response Time: ____ ms
Data Received: YES / NO

Endpoint 2: Get Paginated
Status: ✅ PASS  ❌ FAIL
Response Time: ____ ms
Pagination Works: YES / NO

Endpoint 3: Get Session
Status: ✅ PASS  ❌ FAIL
Response Time: ____ ms
Session Data: YES / NO

Endpoint 4: Get Stats
Status: ✅ PASS  ❌ FAIL
Response Time: ____ ms
Statistics: YES / NO

Endpoint 5: Export
Status: ✅ PASS  ❌ FAIL
Response Time: ____ ms
Export Data: YES / NO

Error Cases
Missing Parameter: ✅ PASS  ❌ FAIL
Invalid User: ✅ PASS  ❌ FAIL
Invalid Session: ✅ PASS  ❌ FAIL
No Auth Token: ✅ PASS  ❌ FAIL
Invalid Token: ✅ PASS  ❌ FAIL

Overall Status: ✅ PASS  ❌ FAIL
Notes: _________________________
```

---

## 💡 Pro Tips

### Tip 1: Save Response as Environment Variable
In Postman → Scripts → Tests tab:
```javascript
pm.environment.set("auth_token", pm.response.json().data.token);
```

### Tip 2: Test All Endpoints in Sequence
Use Postman Collection Runner:
1. Click collection name
2. Click "▶️ Run"
3. Select requests in order
4. Click "Run Conversation API"

### Tip 3: Add Response Validation
In Tests tab:
```javascript
pm.test("Status is 200", function() {
    pm.response.to.have.status(200);
});

pm.test("Response has success field", function() {
    var jsonData = pm.response.json();
    pm.expect(jsonData.success).to.equal(true);
});
```

### Tip 4: Pretty Print JSON Response
In Postman, response automatically formats as JSON. Click "Pretty" tab to view formatted.

---

## 🔄 Full Test Workflow

1. **Prepare**
   ```bash
   php artisan serve
   ```

2. **Authenticate**
   - POST `/api/login` with credentials
   - Get and save token

3. **Setup Postman**
   - Import collection
   - Set environment variables
   - Select environment

4. **Test Endpoints**
   - Run each endpoint individually
   - Verify 200 status
   - Check response data

5. **Test Error Cases**
   - Test missing parameters
   - Test invalid IDs
   - Test without auth

6. **Document Results**
   - Record response times
   - Note any issues
   - Sign off testing

---

## 📚 Next Steps After Testing

1. ✅ All endpoints working
2. ✅ All error cases handled
3. ✅ Response format correct
4. → **Integrate with React**
   - Install axios or fetch
   - Create API service
   - Build conversation components
   - Display data on UI

---

## 📞 Documentation Files

For more details, see:
- **API_DOCUMENTATION.md** - Full API reference
- **API_ENDPOINTS.md** - Endpoint details with examples
- **API_TESTING_GUIDE.md** - Comprehensive testing guide
- **IMPLEMENTATION_SUMMARY.md** - Technical details
- **CONVERSATION_API_README.md** - Master reference

---

## ✨ Summary

**What You Have**:
- ✅ 5 working API endpoints
- ✅ Postman collection ready
- ✅ cURL commands for testing
- ✅ Complete documentation
- ✅ Sample test data

**Time to Production**:
1. ⏱️ 5 min - Initial testing
2. ⏱️ 15 min - Integration with React
3. ⏱️ 30 min - UI display components
4. ⏱️ Ready! 🚀

---

**Start Testing**: Run `php artisan serve` and test first endpoint!

Last Updated: December 3, 2025
