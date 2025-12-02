#!/bin/bash

#############################################
# AI Chatbot Conversation API Testing Script
#############################################
# This script tests all API endpoints
# Usage: ./test_api.sh [base_url] [token]
# Example: ./test_api.sh http://localhost:8000 "1|YOUR_TOKEN"

# Configuration
BASE_URL="${1:-http://localhost:8000}"
AUTH_TOKEN="${2:-}"
USER_ID=1
SESSION_ID="test_session_123"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
TESTS_PASSED=0
TESTS_FAILED=0

# Headers
HEADERS=(-H "Authorization: Bearer ${AUTH_TOKEN}" -H "Content-Type: application/json")

#############################################
# Helper Functions
#############################################

print_header() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

print_test() {
    echo -e "${YELLOW}→ Testing: $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ PASS${NC} - $1"
    ((TESTS_PASSED++))
}

print_failure() {
    echo -e "${RED}❌ FAIL${NC} - $1"
    ((TESTS_FAILED++))
}

test_endpoint() {
    local method=$1
    local endpoint=$2
    local description=$3
    local expected_status=$4

    print_test "$description"
    
    local url="${BASE_URL}${endpoint}"
    local response=$(curl -s -w "\n%{http_code}" -X "$method" "$url" "${HEADERS[@]}")
    local body=$(echo "$response" | head -n -1)
    local status=$(echo "$response" | tail -n 1)
    
    # Check status code
    if [ "$status" = "$expected_status" ]; then
        print_success "$description (Status: $status)"
        echo "Response:" "$body" | head -c 200
        echo "..."
    else
        print_failure "$description (Expected: $expected_status, Got: $status)"
        echo "Response:" "$body"
    fi
}

#############################################
# Main Testing
#############################################

clear

echo -e "${BLUE}"
echo " _____   _                 _            _    _____  _____"
echo "|  __ \ | |               | |          | |  |  __ \|  _  |"
echo "| |  \/ | |__   __ _  __ _| |_  ____   / /  | |  \/| | | |"
echo "| | __  | '_ \ / _\` |/ _\` | __||____| / /   | | __ | | | |"
echo "| |_\ \ | | | | (_| | (_| | |_      / /    | |_\ \| |_| |"
echo " \____/ |_| |_|\__,_|\__,_|\__|    /_/     |_____/|_____/"
echo -e "${NC}"

print_header "API Testing Configuration"
echo -e "Base URL: ${GREEN}${BASE_URL}${NC}"
echo -e "User ID: ${GREEN}${USER_ID}${NC}"
echo -e "Session ID: ${GREEN}${SESSION_ID}${NC}"

# Check if token is provided
if [ -z "$AUTH_TOKEN" ]; then
    echo ""
    echo -e "${RED}❌ ERROR: Authentication token not provided!${NC}"
    echo ""
    echo "Usage: ./test_api.sh [base_url] [token]"
    echo ""
    echo "Example:"
    echo "  ./test_api.sh http://localhost:8000 '1|YOUR_TOKEN_HERE'"
    echo ""
    echo "To get token:"
    echo "  curl -X POST http://localhost:8000/api/login \\"
    echo "    -H 'Content-Type: application/json' \\"
    echo "    -d '{\"email\":\"test@example.com\",\"password\":\"password123\"}'"
    echo ""
    exit 1
fi

print_header "Step 1: Testing Authentication"
echo -e "Token: ${AUTH_TOKEN:0:20}...${NC}"

print_header "Step 2: Testing API Endpoints"

# Test 1: Get All Conversations
test_endpoint "GET" "/api/conversations/user?user_id=${USER_ID}" \
    "Get All Conversations" "200"

echo ""

# Test 2: Get Paginated Conversations
test_endpoint "GET" "/api/conversations/user/paginated?user_id=${USER_ID}&per_page=5" \
    "Get Paginated Conversations" "200"

echo ""

# Test 3: Get Session Details
test_endpoint "GET" "/api/conversations/session?session_id=${SESSION_ID}" \
    "Get Session Details" "200"

echo ""

# Test 4: Get Statistics
test_endpoint "GET" "/api/conversations/stats?user_id=${USER_ID}" \
    "Get Conversation Statistics" "200"

echo ""

# Test 5: Export Conversations
test_endpoint "GET" "/api/conversations/export?user_id=${USER_ID}" \
    "Export Conversations" "200"

print_header "Step 3: Testing Error Cases"

echo ""

# Error Test 1: Missing parameter
print_test "Missing user_id parameter"
local response=$(curl -s -w "\n%{http_code}" -X GET "${BASE_URL}/api/conversations/user" "${HEADERS[@]}")
local status=$(echo "$response" | tail -n 1)
if [ "$status" = "422" ]; then
    print_success "Missing parameter error (Status: 422)"
else
    print_failure "Missing parameter error (Expected: 422, Got: $status)"
fi

echo ""

# Error Test 2: Invalid user ID
print_test "Invalid user_id"
local response=$(curl -s -w "\n%{http_code}" -X GET "${BASE_URL}/api/conversations/user?user_id=99999" "${HEADERS[@]}")
local status=$(echo "$response" | tail -n 1)
if [ "$status" = "404" ]; then
    print_success "Invalid user error (Status: 404)"
else
    print_failure "Invalid user error (Expected: 404, Got: $status)"
fi

echo ""

# Error Test 3: Invalid session ID
print_test "Invalid session_id"
local response=$(curl -s -w "\n%{http_code}" -X GET "${BASE_URL}/api/conversations/session?session_id=invalid_xyz" "${HEADERS[@]}")
local status=$(echo "$response" | tail -n 1)
if [ "$status" = "404" ]; then
    print_success "Invalid session error (Status: 404)"
else
    print_failure "Invalid session error (Expected: 404, Got: $status)"
fi

echo ""

# Error Test 4: Missing auth token
print_test "Missing authentication token"
local response=$(curl -s -w "\n%{http_code}" -X GET "${BASE_URL}/api/conversations/user?user_id=${USER_ID}" -H "Content-Type: application/json")
local status=$(echo "$response" | tail -n 1)
if [ "$status" = "401" ]; then
    print_success "No auth token error (Status: 401)"
else
    print_failure "No auth token error (Expected: 401, Got: $status)"
fi

echo ""

# Error Test 5: Invalid auth token
print_test "Invalid authentication token"
local response=$(curl -s -w "\n%{http_code}" -X GET "${BASE_URL}/api/conversations/user?user_id=${USER_ID}" -H "Authorization: Bearer invalid_token" -H "Content-Type: application/json")
local status=$(echo "$response" | tail -n 1)
if [ "$status" = "401" ]; then
    print_success "Invalid token error (Status: 401)"
else
    print_failure "Invalid token error (Expected: 401, Got: $status)"
fi

#############################################
# Results Summary
#############################################

print_header "Test Results Summary"

TOTAL_TESTS=$((TESTS_PASSED + TESTS_FAILED))

echo -e "Total Tests: ${BLUE}${TOTAL_TESTS}${NC}"
echo -e "Passed: ${GREEN}${TESTS_PASSED}${NC}"
echo -e "Failed: ${RED}${TESTS_FAILED}${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ ALL TESTS PASSED!${NC}"
    exit 0
else
    echo -e "${RED}❌ SOME TESTS FAILED!${NC}"
    exit 1
fi
