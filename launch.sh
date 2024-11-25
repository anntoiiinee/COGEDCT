#!/bin/bash
git pull
docker rm -f $(docker ps --filter "ancestor=eolienne:latest" --format "{{.ID}}")
docker build -t eolienne:latest .
docker run -it -d -p 80:80 -p 502:502 eolienne:latest