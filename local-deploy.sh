#!/bin/bash
set -e

# Configuration
IMAGE_NAME="localhost:5001/swarm-demo"
TAG="local-$(date +%s)"
STACK_NAME="todo_app"

echo "🚀 Starting Local CI/CD Pipeline..."

# 1. Build & Push
echo "📦 Building image: ${IMAGE_NAME}:${TAG}"
docker build -t "${IMAGE_NAME}:${TAG}" -f docker/php/Dockerfile .
docker push "${IMAGE_NAME}:${TAG}"

# 2. Deploy to Swarm
echo "🚢 Labeling nodes for Database..."
# Only label the nodes that actually exist in the swarm
for node in $(docker node ls --format '{{.Hostname}}'); do
  docker node update --label-add ssd=true "$node" || true
done

echo "🚢 Deploying to local Swarm..."

# Create a temporary stack file
# This replaces the entire image line for any service using 'swarm-demo'
sed -E "s|image: .*/swarm-demo.*|image: host.docker.internal:5001/swarm-demo:${TAG}|g" docker-stack.yml > docker-stack.local.yml

# Check if 'manager' container exists
if docker ps --format '{{.Names}}' | grep -q "^manager$"; then
  echo "📍 Detected 'manager' container. Deploying inside container..."
  docker cp docker-stack.local.yml manager:/tmp/docker-stack.local.yml
  docker exec -e IMAGE_TAG="${TAG}" manager docker stack deploy \
    --with-registry-auth \
    -c /tmp/docker-stack.local.yml \
    ${STACK_NAME}
else
  echo "📍 No 'manager' container found. Assuming Host (Mac) is the Manager..."
  IMAGE_TAG="${TAG}" docker stack deploy \
    --with-registry-auth \
    -c docker-stack.local.yml \
    ${STACK_NAME}
fi

# 3. Verify (Local Verifier)
echo "🔍 Verifying deployment..."
TIMEOUT=60
ELAPSED=0
while [ $ELAPSED -lt $TIMEOUT ]; do
  if docker ps --format '{{.Names}}' | grep -q "^manager$"; then
    NOT_RUNNING=$(docker exec manager docker stack ps ${STACK_NAME} \
      --filter "desired-state=running" \
      --format "{{.CurrentState}}" | grep -cv "Running" || true)
  else
    NOT_RUNNING=$(docker stack ps ${STACK_NAME} \
      --filter "desired-state=running" \
      --format "{{.CurrentState}}" | grep -cv "Running" || true)
  fi

  if [ "$NOT_RUNNING" -eq "0" ]; then
    echo "✅ Success! All replicas are running."
    exit 0
  fi
  
  echo "⏳ Waiting... (${ELAPSED}s)"
  sleep 5
  ELAPSED=$((ELAPSED + 5))
done

echo "❌ Timeout! Triggering Rollback..."
if docker ps --format '{{.Names}}' | grep -q "^manager$"; then
  for service in $(docker exec manager docker stack services ${STACK_NAME} --format "{{.Name}}"); do
    docker exec manager docker service rollback $service
  done
else
  for service in $(docker stack services ${STACK_NAME} --format "{{.Name}}"); do
    docker service rollback $service
  done
fi

# Cleanup
rm docker-stack.local.yml
