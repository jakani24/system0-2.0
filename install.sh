#!/bin/bash
chmod 777 sys0-code/log
mkdir -p sys0-code/user_files
mkdir -p sys0-code/user_files/public
chmod 777 sys0-code/user_files
chmod 777 sys0-code/user_files/public
touch sys0-code/log/sys0.log
touch sys0-code/log/log.txt
chmod 777 sys0-code/log/sys0.log
chmod 777 sys0-code/log/log.txt
docker volume create sys0-db
