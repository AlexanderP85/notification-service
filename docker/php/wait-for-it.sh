#!/bin/sh
set -e

host="$1"
port="$2"
shift 2

echo "Waiting for $host:$port..."

for i in $(seq 1 30); do
    if nc -z "$host" "$port" 2>/dev/null; then
        echo "$host:$port is ready"
        exec "$@"
        exit 0
    fi
    echo "Attempt $i/30: $host:$port not ready..."
    sleep 1
done

echo "Warning: $host:$port not ready after 30 seconds, continuing anyway..."
exec "$@"
