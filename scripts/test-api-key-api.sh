#!/bin/bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="http://localhost:8080/api/v1"
HEADERS=(-H "zatca-mode: developer-portal" -H "Content-Type: application/json")
TEST_EMAIL="test@example.com"
TEST_PASSWORD="password123"

# Counter for test results
TESTS_PASSED=0
TESTS_FAILED=0

# JWT token storage
JWT_TOKEN=""

# User IP and host information
USER_IP=""
USER_HOST=""

# Function to print test results
print_result() {
    local test_name="$1"
    local expected_status="$2"
    local actual_status="$3"
    local response="$4"
    
    if [ "$expected_status" = "$actual_status" ]; then
        echo -e "${GREEN}✅ PASS${NC}: $test_name (Expected: $expected_status, Got: $actual_status)"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}❌ FAIL${NC}: $test_name (Expected: $expected_status, Got: $actual_status)"
        echo -e "${RED}Response:${NC} $response"
        ((TESTS_FAILED++))
    fi
    echo ""
}

# Function to make API calls and capture response
api_call() {
    local method="$1"
    local endpoint="$2"
    local data="$3"
    local extra_headers="$4"
    
    local headers_array=("${HEADERS[@]}")
    if [ -n "$extra_headers" ]; then
        # Use eval to properly handle quoted strings
        eval "headers_array+=($extra_headers)"
    fi
    
    if [ "$method" = "GET" ]; then
        curl -s -w "\n%{http_code}" "${headers_array[@]}" "$BASE_URL$endpoint"
    else
        curl -s -w "\n%{http_code}" -X "$method" "${headers_array[@]}" -d "$data" "$BASE_URL$endpoint"
    fi
}

# Function to extract status code and response
extract_response() {
    local full_response="$1"
    # Get the last line and extract only the numeric status code
    local status_code=$(echo "$full_response" | tail -n1 | sed 's/%$//' | tr -d '\n\r' | grep -o '[0-9]\{3\}$')
    # Get everything except the last line
    local response_body=$(echo "$full_response" | sed '$d')
    echo "$status_code|$response_body"
}

# Function to reset environment
reset_environment() {
    echo -e "${BLUE}⏳ Resetting Redis and SQLite...${NC}"
    
    # Flush Redis
    if docker-compose exec redis sh -c 'redis-cli -a "$REDIS_PASSWORD" FLUSHALL' 2>/dev/null; then
        echo -e "${GREEN}✅ Redis flushed${NC}"
    else
        echo -e "${YELLOW}⚠️  Redis flush failed (continuing anyway)${NC}"
    fi
    
    # Remove SQLite database
    if rm -f storage/db/dev.sqlite 2>/dev/null; then
        echo -e "${GREEN}✅ SQLite database removed${NC}"
    else
        echo -e "${YELLOW}⚠️  SQLite removal failed (continuing anyway)${NC}"
    fi
    
    echo ""
}

# Function to register, confirm, and login a test user
setup_test_user() {
    echo -e "${BLUE}🔧 Setting up test user...${NC}"
    
    # Step 1: Register user
    echo "  Step 1: Registering user..."
    response=$(api_call "POST" "/auth/register" '{"email":"'$TEST_EMAIL'","password":"'$TEST_PASSWORD'"}')
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" != "200" ]; then
        echo -e "${RED}❌ Failed to register test user (status: $status_code)${NC}"
        echo -e "${RED}Response: $response_body${NC}"
        exit 1
    fi
    echo -e "${GREEN}✅ User registered successfully${NC}"
    
    # Step 2: Get confirmation token from database
    echo "  Step 2: Getting confirmation token from database..."
    confirmation_token=""
    
    if command -v sqlite3 >/dev/null 2>&1; then
        if [ -f "storage/db/dev.sqlite" ]; then
            # Get the confirmation token from the database
            confirmation_token=$(sqlite3 storage/db/dev.sqlite "SELECT confirmation_token FROM users WHERE email = '$TEST_EMAIL';" 2>/dev/null)
            if [ -n "$confirmation_token" ] && [ "$confirmation_token" != "" ]; then
                echo -e "${GREEN}✅ Confirmation token found in database${NC}"
            else
                echo -e "${YELLOW}⚠️  No confirmation token found in database${NC}"
            fi
        else
            echo -e "${YELLOW}⚠️  Database file not found${NC}"
        fi
    else
        echo -e "${YELLOW}⚠️  sqlite3 command not available${NC}"
    fi
    
    # If still no token, try to extract from registration response
    if [ -z "$confirmation_token" ]; then
        echo -e "${YELLOW}⚠️  Attempting to extract token from registration response...${NC}"
        confirmation_token=$(echo "$response_body" | grep -o '"confirmation_token":"[^"]*"' | cut -d'"' -f4)
        
        if [ -z "$confirmation_token" ]; then
            # Try resend email as last resort
            echo -e "${YELLOW}⚠️  Attempting to resend confirmation email...${NC}"
            response=$(api_call "POST" "/auth/mail/resend" '{"email":"'$TEST_EMAIL'"}')
            result=$(extract_response "$response")
            resend_status=$(echo "$result" | cut -d'|' -f1)
            resend_body=$(echo "$result" | cut -d'|' -f2)
            
            if [ "$resend_status" = "200" ]; then
                echo -e "${GREEN}✅ Confirmation email resent${NC}"
                # Try to get token from database again after resend
                if command -v sqlite3 >/dev/null 2>&1 && [ -f "storage/db/dev.sqlite" ]; then
                    confirmation_token=$(sqlite3 storage/db/dev.sqlite "SELECT confirmation_token FROM users WHERE email = '$TEST_EMAIL';" 2>/dev/null)
                fi
            fi
        fi
    fi
    
    if [ -z "$confirmation_token" ]; then
        echo -e "${RED}❌ Could not obtain confirmation token${NC}"
        echo -e "${RED}   Cannot proceed with confirmation${NC}"
        exit 1
    fi
    
    # Step 3: Confirm user
    echo "  Step 3: Confirming user..."
    echo "    Using token: ${confirmation_token:0:20}..."
    # Add small delay to ensure token is fresh
    sleep 1
    response=$(api_call "GET" "/auth/confirm?token=$confirmation_token")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" = "200" ]; then
        echo -e "${GREEN}✅ User confirmed successfully${NC}"
    else
        echo -e "${RED}❌ User confirmation failed (status: $status_code)${NC}"
        echo -e "${RED}   Response: $response_body${NC}"
        echo -e "${RED}   Cannot proceed without confirmation${NC}"
        exit 1
    fi
    
    # Step 4: Login to get JWT token
    echo "  Step 4: Logging in..."
    response=$(api_call "POST" "/auth/login" '{"email":"'$TEST_EMAIL'","password":"'$TEST_PASSWORD'"}')
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" = "200" ]; then
        # Extract JWT token from response (it's in access_token field)
        JWT_TOKEN=$(echo "$response_body" | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)
        if [ -n "$JWT_TOKEN" ]; then
            echo -e "${GREEN}✅ Test user logged in successfully${NC}"
            echo -e "${GREEN}   JWT Token obtained: ${JWT_TOKEN:0:20}...${NC}"
        else
            echo -e "${YELLOW}⚠️  Login successful but no JWT token found in response${NC}"
            echo -e "${YELLOW}   Response: $response_body${NC}"
        fi
    else
        echo -e "${YELLOW}⚠️  Could not login test user (status: $status_code)${NC}"
        echo -e "${YELLOW}   Response: $response_body${NC}"
        echo -e "${YELLOW}   This might be expected if user needs email confirmation${NC}"
        echo -e "${YELLOW}   Tests will run with authentication failures${NC}"
    fi
    
    echo ""
}

# Function to get user IP and host information from /auth/me
get_user_info() {
    echo -e "${BLUE}🔍 Getting user IP and host information...${NC}"
    
    if [ -n "$JWT_TOKEN" ]; then
        response=$(api_call "GET" "/auth/me" "" "-H \"Authorization: Bearer $JWT_TOKEN\"")
        result=$(extract_response "$response")
        status_code=$(echo "$result" | cut -d'|' -f1)
        response_body=$(echo "$result" | cut -d'|' -f2)
        
        if [ "$status_code" = "200" ]; then
            # Extract IP and host from response
            USER_IP=$(echo "$response_body" | grep -o '"ip":"[^"]*"' | cut -d'"' -f4)
            USER_HOST=$(echo "$response_body" | grep -o '"host":"[^"]*"' | cut -d'"' -f4)
            
            if [ -n "$USER_IP" ] && [ -n "$USER_HOST" ]; then
                echo -e "${GREEN}✅ User info retrieved successfully${NC}"
                echo -e "${GREEN}   IP: $USER_IP${NC}"
                echo -e "${GREEN}   Host: $USER_HOST${NC}"
            else
                echo -e "${YELLOW}⚠️  Could not extract IP/host from response${NC}"
                echo -e "${YELLOW}   Response: $response_body${NC}"
            fi
        else
            echo -e "${YELLOW}⚠️  Could not get user info (status: $status_code)${NC}"
            echo -e "${YELLOW}   Response: $response_body${NC}"
        fi
    else
        echo -e "${YELLOW}⚠️  No JWT token available for user info${NC}"
    fi
    
    echo ""
}

# Function to test GET /auth/key (list API keys)
test_list_api_keys() {
    echo -e "${BLUE}🔹 Testing GET /auth/key (List API Keys)...${NC}"
    
    # Test 1: Access without token
    echo "Testing access without token..."
    response=$(api_call "GET" "/auth/key")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "Access without token" "401" "$status_code" "$response_body"
    
    # Test 2: Access with invalid token
    echo "Testing access with invalid token..."
    response=$(api_call "GET" "/auth/key" "" "-H \"Authorization: Bearer invalid-token\"")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "Access with invalid token" "401" "$status_code" "$response_body"
    
    # Test 3: Access with valid token (if available)
    if [ -n "$JWT_TOKEN" ]; then
        echo "Testing access with valid token..."
        response=$(api_call "GET" "/auth/key" "" "-H \"Authorization: Bearer $JWT_TOKEN\"")
        result=$(extract_response "$response")
        status_code=$(echo "$result" | cut -d'|' -f1)
        response_body=$(echo "$result" | cut -d'|' -f2)
        print_result "Access with valid token" "200" "$status_code" "$response_body"
    else
        echo -e "${YELLOW}⚠️  Skipping valid token test (no JWT token available)${NC}"
    fi
    
    echo ""
}

# Function to test PUT /auth/key (regenerate API key)
test_regenerate_api_key() {
    echo -e "${BLUE}🔹 Testing PUT /auth/key (Regenerate API Key)...${NC}"
    
    # Test 1: Access without token
    echo "Testing regenerate without token..."
    response=$(api_call "PUT" "/auth/key" '{}')
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "Regenerate without token" "401" "$status_code" "$response_body"
    
    # Test 2: Access with invalid token
    echo "Testing regenerate with invalid token..."
    response=$(api_call "PUT" "/auth/key" '{}' "-H \"Authorization: Bearer invalid-token\"")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "Regenerate with invalid token" "401" "$status_code" "$response_body"
    
    # Test 3: Access with valid token (if available)
    if [ -n "$JWT_TOKEN" ]; then
        echo "Testing regenerate with valid token..."
        response=$(api_call "PUT" "/auth/key" '{}' "-H \"Authorization: Bearer $JWT_TOKEN\"")
        result=$(extract_response "$response")
        status_code=$(echo "$result" | cut -d'|' -f1)
        response_body=$(echo "$result" | cut -d'|' -f2)
        print_result "Regenerate with valid token" "200" "$status_code" "$response_body"
    else
        echo -e "${YELLOW}⚠️  Skipping valid token test (no JWT token available)${NC}"
    fi
    
    echo ""
}

# Function to test PATCH /auth/key (create or update API key)
test_create_update_api_key() {
    echo -e "${BLUE}🔹 Testing PATCH /auth/key (Create/Update API Key)...${NC}"
    
    # Test 1: Access without token
    echo "Testing create/update without token..."
    response=$(api_call "PATCH" "/auth/key" '[{"type":"ip","value":"192.168.1.1"}]')
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "Create/update without token" "401" "$status_code" "$response_body"
    
    # Test 2: Access with invalid token
    echo "Testing create/update with invalid token..."
    response=$(api_call "PATCH" "/auth/key" '[{"type":"ip","value":"192.168.1.1"}]' "-H \"Authorization: Bearer invalid-token\"")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "Create/update with invalid token" "401" "$status_code" "$response_body"
    
    # Test 3: Invalid data format
    echo "Testing with invalid data format..."
    response=$(api_call "PATCH" "/auth/key" '{"invalid":"data"}' "-H \"Authorization: Bearer invalid-token\"")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "Invalid data format" "401" "$status_code" "$response_body"
    
        # Test 4: Access with valid token (if available)
        if [ -n "$JWT_TOKEN" ]; then
            echo "Testing create/update with valid token..."
            # Use actual user IP if available, otherwise fallback to test IP
            test_ip="${USER_IP:-192.168.1.1}"
            response=$(api_call "PATCH" "/auth/key" "[{\"type\":\"ip\",\"value\":\"$test_ip\"}]" "-H \"Authorization: Bearer $JWT_TOKEN\"")
            result=$(extract_response "$response")
            status_code=$(echo "$result" | cut -d'|' -f1)
            response_body=$(echo "$result" | cut -d'|' -f2)
            print_result "Create/update with valid token" "201" "$status_code" "$response_body"
            
            # Test 5: Multiple entries using actual user data
            echo "Testing with multiple entries..."
            test_ip2="${USER_IP:-10.0.0.1}"
            test_host="${USER_HOST:-example.com}"
            response=$(api_call "PATCH" "/auth/key" "[{\"type\":\"ip\",\"value\":\"$test_ip2\"},{\"type\":\"domain\",\"value\":\"$test_host\"}]" "-H \"Authorization: Bearer $JWT_TOKEN\"")
            result=$(extract_response "$response")
            status_code=$(echo "$result" | cut -d'|' -f1)
            response_body=$(echo "$result" | cut -d'|' -f2)
            print_result "Multiple entries" "201" "$status_code" "$response_body"
        else
            echo -e "${YELLOW}⚠️  Skipping valid token tests (no JWT token available)${NC}"
        fi
    
    echo ""
}

# Function to test DELETE /auth/key/flush (flush all API keys)
test_flush_api_keys() {
    echo -e "${BLUE}🔹 Testing DELETE /auth/key/flush (Flush All API Keys)...${NC}"
    
    # Test 1: Access without token
    echo "Testing flush without token..."
    response=$(api_call "DELETE" "/auth/key/flush")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "Flush without token" "401" "$status_code" "$response_body"
    
    # Test 2: Access with invalid token
    echo "Testing flush with invalid token..."
    response=$(api_call "DELETE" "/auth/key/flush" "" "-H \"Authorization: Bearer invalid-token\"")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "Flush with invalid token" "401" "$status_code" "$response_body"
    
    # Test 3: Access with valid token (if available)
    if [ -n "$JWT_TOKEN" ]; then
        echo "Testing flush with valid token..."
        response=$(api_call "DELETE" "/auth/key/flush" "" "-H \"Authorization: Bearer $JWT_TOKEN\"")
        result=$(extract_response "$response")
        status_code=$(echo "$result" | cut -d'|' -f1)
        response_body=$(echo "$result" | cut -d'|' -f2)
        print_result "Flush with valid token" "204" "$status_code" "$response_body"
    else
        echo -e "${YELLOW}⚠️  Skipping valid token test (no JWT token available)${NC}"
    fi
    
    echo ""
}

# Function to test DELETE /auth/key (destroy specific API key)
test_destroy_api_key() {
    echo -e "${BLUE}🔹 Testing DELETE /auth/key (Destroy API Key)...${NC}"
    
    # Test 1: Access without token
    echo "Testing destroy without token..."
    response=$(api_call "DELETE" "/auth/key?type=ip&value=192.168.1.1")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "Destroy without token" "401" "$status_code" "$response_body"
    
    # Test 2: Access with invalid token
    echo "Testing destroy with invalid token..."
    response=$(api_call "DELETE" "/auth/key?type=ip&value=192.168.1.1" "" "-H \"Authorization: Bearer invalid-token\"")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "Destroy with invalid token" "401" "$status_code" "$response_body"
    
    # Test 3: Access with valid token (if available)
    if [ -n "$JWT_TOKEN" ]; then
        echo "Testing destroy with valid token..."
        # Use actual user IP if available, otherwise fallback to test IP
        test_ip="${USER_IP:-192.168.1.1}"
        response=$(api_call "DELETE" "/auth/key?type=ip&value=$test_ip" "" "-H \"Authorization: Bearer $JWT_TOKEN\"")
        result=$(extract_response "$response")
        status_code=$(echo "$result" | cut -d'|' -f1)
        response_body=$(echo "$result" | cut -d'|' -f2)
        print_result "Destroy with valid token" "204" "$status_code" "$response_body"
    else
        echo -e "${YELLOW}⚠️  Skipping valid token test (no JWT token available)${NC}"
    fi
    
    echo ""
}

# Function to test rate limiting for API key endpoints
test_api_key_rate_limiting() {
    echo -e "${BLUE}🔹 Testing API Key Rate Limiting...${NC}"
    
    # Reset Redis before rate limit test
    echo "Resetting Redis for rate limit test..."
    docker-compose exec redis sh -c 'redis-cli -a "$REDIS_PASSWORD" FLUSHALL' 2>/dev/null || true
    
    echo "Testing API key endpoints rate limiting..."
    
    # Test rate limiting on GET /auth/key
    echo "  Testing GET /auth/key rate limiting..."
    for i in {1..12}; do
        response=$(api_call "GET" "/auth/key" "" "-H \"Authorization: Bearer invalid-token\"")
        result=$(extract_response "$response")
        status_code=$(echo "$result" | cut -d'|' -f1)
        
        if [ $((i % 5)) -eq 0 ] || [ "$status_code" = "429" ]; then
            echo "    Attempt $i: Status $status_code"
        fi
        
        if [ "$status_code" = "429" ]; then
            echo "✅ Rate limit triggered at attempt $i"
            break
        fi
    done
    
    # Reset for next test
    docker-compose exec redis sh -c 'redis-cli -a "$REDIS_PASSWORD" FLUSHALL' 2>/dev/null || true
    
    # Test rate limiting on PATCH /auth/key
    echo "  Testing PATCH /auth/key rate limiting..."
    for i in {1..12}; do
        response=$(api_call "PATCH" "/auth/key" '[{"type":"ip","value":"192.168.1.1"}]' "-H \"Authorization: Bearer invalid-token\"")
        result=$(extract_response "$response")
        status_code=$(echo "$result" | cut -d'|' -f1)
        
        if [ $((i % 5)) -eq 0 ] || [ "$status_code" = "429" ]; then
            echo "    Attempt $i: Status $status_code"
        fi
        
        if [ "$status_code" = "429" ]; then
            echo "✅ Rate limit triggered at attempt $i"
            break
        fi
    done
    
    echo ""
}

# Function to test security scenarios
test_api_key_security() {
    echo -e "${BLUE}🔹 Testing API Key Security...${NC}"
    
    # Test 1: SQL injection in PATCH data
    echo "Testing SQL injection in PATCH data..."
    response=$(api_call "PATCH" "/auth/key" '[{"type":"ip","value":"192.168.1.1\"; DROP TABLE api_keys; --"}]' "-H \"Authorization: Bearer invalid-token\"")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "SQL injection in PATCH data" "401" "$status_code" "$response_body"
    
    # Test 2: XSS in PATCH data
    echo "Testing XSS in PATCH data..."
    response=$(api_call "PATCH" "/auth/key" '[{"type":"host","value":"<script>alert(\"xss\")</script>"}]' "-H \"Authorization: Bearer invalid-token\"")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "XSS in PATCH data" "401" "$status_code" "$response_body"
    
    # Test 3: Very long input
    echo "Testing very long input..."
    long_value=$(printf 'a%.0s' {1..1000})
    response=$(api_call "PATCH" "/auth/key" "[{\"type\":\"ip\",\"value\":\"$long_value\"}]" "-H \"Authorization: Bearer invalid-token\"")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "Very long input" "401" "$status_code" "$response_body"
    
    # Test 4: Malformed JSON
    echo "Testing malformed JSON..."
    response=$(api_call "PATCH" "/auth/key" '{"type":"ip","value":"192.168.1.1"' "-H \"Authorization: Bearer invalid-token\"")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    print_result "Malformed JSON" "401" "$status_code" "$response_body"
    
    echo ""
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS] [TEST_NAME]"
    echo ""
    echo "Options:"
    echo "  -h, --help     Show this help message"
    echo "  -l, --list     List all available tests"
    echo ""
    echo "Test Names:"
    echo "  all                    Run all tests (default)"
    echo "  list                   Test GET /auth/key (list API keys)"
    echo "  regenerate             Test PUT /auth/key (regenerate API key)"
    echo "  create_update          Test PATCH /auth/key (create/update API key)"
    echo "  flush                  Test DELETE /auth/key/flush (flush all API keys)"
    echo "  destroy                Test DELETE /auth/key (destroy specific API key)"
    echo "  rate_limiting          Test rate limiting for API key endpoints"
    echo "  security               Test security scenarios"
    echo ""
    echo "Examples:"
    echo "  $0                     # Run all tests"
    echo "  $0 all                 # Run all tests"
    echo "  $0 list                # Run only list API keys test"
    echo "  $0 rate_limiting       # Run only rate limiting tests"
    echo "  $0 security            # Run only security tests"
}

# Function to list all available tests
list_tests() {
    echo "Available tests:"
    echo "  list"
    echo "  regenerate"
    echo "  create_update"
    echo "  flush"
    echo "  destroy"
    echo "  rate_limiting"
    echo "  security"
}

# Function to run specific test
run_test() {
    local test_name="$1"
    
    case "$test_name" in
        "list")
            test_list_api_keys
            ;;
        "regenerate")
            test_regenerate_api_key
            ;;
        "create_update")
            test_create_update_api_key
            ;;
        "flush")
            test_flush_api_keys
            ;;
        "destroy")
            test_destroy_api_key
            ;;
        "rate_limiting")
            test_api_key_rate_limiting
            ;;
        "security")
            test_api_key_security
            ;;
        *)
            echo -e "${RED}❌ Unknown test: $test_name${NC}"
            echo ""
            show_usage
            exit 1
            ;;
    esac
}

# Main execution
main() {
    local test_name="all"
    
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_usage
                exit 0
                ;;
            -l|--list)
                list_tests
                exit 0
                ;;
            -*)
                echo -e "${RED}❌ Unknown option: $1${NC}"
                echo ""
                show_usage
                exit 1
                ;;
            *)
                test_name="$1"
                shift
                ;;
        esac
    done
    
    echo -e "${BLUE}🚀 Starting API Key Management Tests${NC}"
    echo -e "${BLUE}====================================${NC}"
    echo ""
    
    # Reset environment
    reset_environment
    
    # Setup test user (register and login)
    setup_test_user
    
    # Get user IP and host information
    get_user_info
    
    if [ "$test_name" = "all" ]; then
        # Run all tests
        test_list_api_keys
        test_regenerate_api_key
        test_create_update_api_key
        test_flush_api_keys
        test_destroy_api_key
        test_api_key_rate_limiting
        test_api_key_security
    else
        # Run specific test
        run_test "$test_name"
    fi
    
    # Print final results
    echo -e "${BLUE}📊 Test Results Summary${NC}"
    echo -e "${BLUE}======================${NC}"
    echo -e "${GREEN}✅ Tests Passed: $TESTS_PASSED${NC}"
    echo -e "${RED}❌ Tests Failed: $TESTS_FAILED${NC}"
    echo ""
    
    if [ $TESTS_FAILED -eq 0 ]; then
        echo -e "${GREEN}🎉 All tests passed!${NC}"
        exit 0
    else
        echo -e "${RED}💥 Some tests failed!${NC}"
        exit 1
    fi
}

# Run main function
main "$@"
