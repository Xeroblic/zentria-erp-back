#!/bin/bash

echo 'Probando con datos válidos (nombres de permisos)'
curl -X POST 'http://127.0.0.1:8000/api/admin/users/2/permissions' \
  -H 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwiaWF0IjoxNzU0OTQzNzIwLCJleHAiOjE3NTQ5NDczMjAsIm5iZiI6MTc1NDk0MzcyMCwianRpIjoiNHlBWGlSZ1ZkN05HajYwZCIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.C04OLYHnUCbK9JuL3Al9RE2XdgtqtSUPoAlWdx-EdNo' \
  -H 'Content-Type: application/json' \
  -d '{"permissions":["view-dashboard","view-user","create-user"]}'

echo '

Probando con datos inválidos (IDs en lugar de nombres)'
curl -X POST 'http://127.0.0.1:8000/api/admin/users/2/permissions' \
  -H 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwiaWF0IjoxNzU0OTQzNzIwLCJleHAiOjE3NTQ5NDczMjAsIm5iZiI6MTc1NDk0MzcyMCwianRpIjoiNHlBWGlSZ1ZkN05HajYwZCIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.C04OLYHnUCbK9JuL3Al9RE2XdgtqtSUPoAlWdx-EdNo' \
  -H 'Content-Type: application/json' \
  -d '{"permissions":[1,2]}'
