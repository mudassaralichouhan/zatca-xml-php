#!/bin/bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="http://localhost:8080/api/v1"
HEADERS=(-H "zatca-mode: developer-portal" -H "Content-Type: application/json")

# Variables
EMAIL=""
PASSWORD=""
JWT_TOKEN=""
CONFIRMATION_TOKEN=""
FLUSH_REDIS=false

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS] EMAIL PASSWORD"
    echo ""
    echo "Options:"
    echo "  -h, --help     Show this help message"
    echo "  -f, --flush    Flush Redis before testing (optional)"
    echo ""
    echo "Arguments:"
    echo "  EMAIL          Email address for testing (required)"
    echo "  PASSWORD       Password for testing (required)"
    echo ""
    echo "Examples:"
    echo "  $0 user@example.com mypassword      # Test with custom email/password"
    echo "  $0 -f user@example.com mypassword   # Test with Redis flush"
    echo ""
    echo "Note: EMAIL and PASSWORD are required arguments"
}

# Function to make API calls
api_call() {
    local method="$1"
    local endpoint="$2"
    local data="$3"
    local extra_headers="$4"
    
    local headers_array=("${HEADERS[@]}")
    if [ -n "$extra_headers" ]; then
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
    local status_code=$(echo "$full_response" | tail -n1 | sed 's/%$//' | tr -d '\n\r' | grep -o '[0-9]\{3\}$')
    local response_body=$(echo "$full_response" | sed '$d')
    echo "$status_code|$response_body"
}

# Function to print step result
print_step() {
    local step="$1"
    local status="$2"
    local message="$3"
    
    if [ "$status" = "success" ]; then
        echo -e "${GREEN}✅ $step${NC}: $message"
    elif [ "$status" = "error" ]; then
        echo -e "${RED}❌ $step${NC}: $message"
    else
        echo -e "${YELLOW}⚠️  $step${NC}: $message"
    fi
}

# Function to reset environment
reset_environment() {
    echo -e "${BLUE}🔄 Resetting environment...${NC}"
    
    # Flush Redis only if requested
    if [ "$FLUSH_REDIS" = true ]; then
        if docker-compose exec redis sh -c 'redis-cli -a "$REDIS_PASSWORD" FLUSHALL' 2>/dev/null; then
            echo -e "${GREEN}✅ Redis flushed${NC}"
        else
            echo -e "${YELLOW}⚠️  Redis flush failed (continuing anyway)${NC}"
        fi
    else
        echo -e "${YELLOW}⚠️  Redis flush skipped (use -f to flush)${NC}"
    fi
    
    # Remove SQLite database
    if rm -f storage/db/dev.sqlite 2>/dev/null; then
        echo -e "${GREEN}✅ SQLite database removed${NC}"
    else
        echo -e "${YELLOW}⚠️  SQLite removal failed (continuing anyway)${NC}"
    fi
    
    echo ""
}

# Function to test registration
test_register() {
    echo -e "${BLUE}📝 Step 1: Registering user...${NC}"
    echo -e "${CYAN}   Email: $EMAIL${NC}"
    echo -e "${CYAN}   Password: $PASSWORD${NC}"
    
    response=$(api_call "POST" "/auth/register" '{"email":"'$EMAIL'","password":"'$PASSWORD'"}')
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" = "200" ]; then
        print_step "Registration" "success" "User registered successfully"
        echo -e "${GREEN}   Response: $response_body${NC}"
    else
        print_step "Registration" "error" "Failed with status $status_code"
        echo -e "${RED}   Response: $response_body${NC}"
        return 1
    fi
    
    echo ""
}

# Function to test resend mail
test_resend() {
    echo -e "${BLUE}📧 Step 2: Resending confirmation email...${NC}"
    
    response=$(api_call "POST" "/auth/mail/resend" '{"email":"'$EMAIL'"}')
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" = "200" ]; then
        print_step "Resend Mail" "success" "Confirmation email resent"
        echo -e "${GREEN}   Response: $response_body${NC}"
    else
        print_step "Resend Mail" "error" "Failed with status $status_code"
        echo -e "${RED}   Response: $response_body${NC}"
        return 1
    fi
    
    echo ""
}

# Function to test confirmation
test_confirm() {
    echo -e "${BLUE}✅ Step 3: Confirming user...${NC}"
    
    # Get confirmation token from database
    if command -v sqlite3 >/dev/null 2>&1 && [ -f "storage/db/dev.sqlite" ]; then
        CONFIRMATION_TOKEN=$(sqlite3 storage/db/dev.sqlite "SELECT confirmation_token FROM users WHERE email = '$EMAIL';" 2>/dev/null)
        
        if [ -n "$CONFIRMATION_TOKEN" ] && [ "$CONFIRMATION_TOKEN" != "" ]; then
            echo -e "${GREEN}   Confirmation token found: ${CONFIRMATION_TOKEN:0:20}...${NC}"
        else
            print_step "Confirmation" "error" "No confirmation token found in database"
            return 1
        fi
    else
        print_step "Confirmation" "error" "Cannot access database or sqlite3 not available"
        return 1
    fi
    
    # Confirm user
    response=$(api_call "GET" "/auth/confirm?token=$CONFIRMATION_TOKEN")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" = "200" ]; then
        print_step "Confirmation" "success" "User confirmed successfully"
        echo -e "${GREEN}   Response: $response_body${NC}"
    else
        print_step "Confirmation" "error" "Failed with status $status_code"
        echo -e "${RED}   Response: $response_body${NC}"
        return 1
    fi
    
    echo ""
}

# Function to test login
test_login() {
    echo -e "${BLUE}🔐 Step 4: Logging in...${NC}"
    
    response=$(api_call "POST" "/auth/login" '{"email":"'$EMAIL'","password":"'$PASSWORD'"}')
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" = "200" ]; then
        # Extract JWT token
        JWT_TOKEN=$(echo "$response_body" | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)
        
        if [ -n "$JWT_TOKEN" ]; then
            print_step "Login" "success" "User logged in successfully"
            echo -e "${GREEN}   JWT Token: ${JWT_TOKEN:0:50}...${NC}"
            echo -e "${GREEN}   Response: $response_body${NC}"
        else
            print_step "Login" "error" "Login successful but no JWT token found"
            echo -e "${RED}   Response: $response_body${NC}"
            return 1
        fi
    else
        print_step "Login" "error" "Failed with status $status_code"
        echo -e "${RED}   Response: $response_body${NC}"
        return 1
    fi
    
    echo ""
}

# Function to test /auth/me
test_me() {
    echo -e "${BLUE}👤 Step 5: Testing /auth/me endpoint...${NC}"
    
    if [ -z "$JWT_TOKEN" ]; then
        print_step "/auth/me" "error" "No JWT token available"
        return 1
    fi
    
    response=$(api_call "GET" "/auth/me" "" "-H \"Authorization: Bearer $JWT_TOKEN\"")
    result=$(extract_response "$response")
    status_code=$(echo "$result" | cut -d'|' -f1)
    response_body=$(echo "$result" | cut -d'|' -f2)
    
    if [ "$status_code" = "200" ]; then
        # Extract IP and host from response
        USER_IP=$(echo "$response_body" | grep -o '"ip":"[^"]*"' | cut -d'"' -f4)
        USER_HOST=$(echo "$response_body" | grep -o '"host":"[^"]*"' | cut -d'"' -f4)
        
        print_step "/auth/me" "success" "User info retrieved successfully"
        echo -e "${GREEN}   IP: $USER_IP${NC}"
        echo -e "${GREEN}   Host: $USER_HOST${NC}"
        echo -e "${GREEN}   Response: $response_body${NC}"
    else
        print_step "/auth/me" "error" "Failed with status $status_code"
        echo -e "${RED}   Response: $response_body${NC}"
        return 1
    fi
    
    echo ""
}

# Function to show summary
show_summary() {
    echo -e "${BLUE}📊 Test Summary${NC}"
    echo -e "${BLUE}===============${NC}"
    echo -e "${CYAN}Email: $EMAIL${NC}"
    echo -e "${CYAN}Password: $PASSWORD${NC}"
    
    if [ -n "$JWT_TOKEN" ]; then
        echo -e "${CYAN}JWT Token: $JWT_TOKEN${NC}"
    else
        echo -e "${RED}JWT Token: Not obtained${NC}"
    fi
    
    if [ -n "$CONFIRMATION_TOKEN" ]; then
        echo -e "${CYAN}Confirmation Token: ${CONFIRMATION_TOKEN:0:20}...${NC}"
    else
        echo -e "${RED}Confirmation Token: Not obtained${NC}"
    fi
    
    echo ""
}

# Main execution
main() {
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_usage
                exit 0
                ;;
            -f|--flush)
                FLUSH_REDIS=true
                shift
                ;;
            -*)
                echo -e "${RED}❌ Unknown option: $1${NC}"
                echo ""
                show_usage
                exit 1
                ;;
            *)
                if [ -z "$EMAIL" ]; then
                    EMAIL="$1"
                elif [ -z "$PASSWORD" ]; then
                    PASSWORD="$1"
                else
                    echo -e "${RED}❌ Too many arguments${NC}"
                    echo ""
                    show_usage
                    exit 1
                fi
                shift
                ;;
        esac
    done
    
    # Check if required arguments are provided
    if [ -z "$EMAIL" ] || [ -z "$PASSWORD" ]; then
        echo -e "${RED}❌ Missing required arguments${NC}"
        echo ""
        show_usage
        exit 1
    fi
    
    echo -e "${BLUE}🚀 Quick Authentication Test${NC}"
    echo -e "${BLUE}============================${NC}"
    echo ""
    
    # Reset environment
    reset_environment
    
    # Run all tests
    if test_register && test_resend && test_confirm && test_login && test_me; then
        echo -e "${GREEN}🎉 All tests passed successfully!${NC}"
        echo ""
        show_summary
        exit 0
    else
        echo -e "${RED}💥 Some tests failed!${NC}"
        echo ""
        show_summary
        exit 1
    fi
}

# Run main function
main "$@"
