#!/bin/bash

docker buildx build \
    --platform linux/amd64,linux/arm64 \
    -t dealnews/chronicle:latest \
    -t dealnews/chronicle:$1 \
    --push \
    .
