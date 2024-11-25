#!/bin/bash
docker build -t eolienne:latest .
docker run -it -d -p 80:80 -p 502:502 eolienne:latest