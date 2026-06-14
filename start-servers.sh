#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo -e "${BLUE}Starting all development servers...${NC}\n"

# Kill any existing processes on these ports
for port in 8001 8002 8003 8004; do
    lsof -i :$port -t 2>/dev/null | xargs kill -9 2>/dev/null || true
done

sleep 1

# Start mm-frontend on port 8001
echo -e "${GREEN}► Starting mm-frontend on http://localhost:8001${NC}"
cd "$BASE_DIR/mm-frontend" && php -S localhost:8001 -t public public/index.php > /tmp/mm-frontend.log 2>&1 &
echo $! > /tmp/mm-frontend.pid

# Start mm-admin on port 8002
echo -e "${GREEN}► Starting mm-admin on http://localhost:8002${NC}"
cd "$BASE_DIR/mm-admin" && php -S localhost:8002 -t public public/index.php > /tmp/mm-admin.log 2>&1 &
echo $! > /tmp/mm-admin.pid

# Start cd-frontend on port 8003
echo -e "${GREEN}► Starting cd-frontend on http://localhost:8003${NC}"
cd "$BASE_DIR/cd-frontend" && php -S localhost:8003 -t public public/index.php > /tmp/cd-frontend.log 2>&1 &
echo $! > /tmp/cd-frontend.pid

# Start cd-admin on port 8004
echo -e "${GREEN}► Starting cd-admin on http://localhost:8004${NC}"
cd "$BASE_DIR/cd-admin" && php -S localhost:8004 -t public public/index.php > /tmp/cd-admin.log 2>&1 &
echo $! > /tmp/cd-admin.pid

sleep 2

echo -e "\n${GREEN}✓ All servers started!${NC}\n"
echo "Dashboard URLs:"
echo "  mm-frontend:    http://localhost:8001"
echo "  mm-admin:       http://localhost:8002/login"
echo "  cd-frontend:    http://localhost:8003"
echo "  cd-admin:       http://localhost:8004/login"
echo ""
echo "Stop all servers: Press Ctrl+C or run 'stop-servers.sh'"
echo ""

# Wait for all background processes
wait
