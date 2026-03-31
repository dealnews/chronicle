# Chronicle Docker Deployment

This directory contains Docker deployment files for Chronicle.

## Architecture

- **Base Image**: Phusion Baseimage (Ubuntu 22.04 LTS)
- **Services**: Nginx + PHP 8.4-FPM (managed by runit)
- **Process Manager**: Runit (built into Phusion baseimage)
- **Document Root**: `/app/public`

## Quick Start

### Using Docker Compose (Recommended for Development)

```bash
# Start the stack
docker compose up -d

# Initialize the database
docker compose exec mysql mysql -uroot -pchronicle_root_pass chronicle < schema/mysql.sql

# Access the application
open http://localhost:8000

# Create your first user
open http://localhost:8000/first-user

# View logs
docker-compose logs -f chronicle

# Stop the stack
docker-compose down
```

### Using Docker CLI (Production)

```bash
# Build the image
docker build -t chronicle:latest .

# Run with environment variables
docker run -d \
  --name chronicle \
  -p 8000:80 \
  -e DB_CHRONICLE_TYPE=mysql \
  -e DB_CHRONICLE_HOST=db.example.com \
  -e DB_CHRONICLE_PORT=3306 \
  -e DB_CHRONICLE_DB=chronicle \
  -e DB_CHRONICLE_USER=chronicle_user \
  -e DB_CHRONICLE_PASS=secret \
  chronicle:latest

# Run with volume-mounted config
docker run -d \
  --name chronicle \
  -p 8000:80 \
  -v /path/to/config.ini:/app/etc/config.ini:ro \
  chronicle:latest
```

## Environment Variables

### Database Configuration (DB_CHRONICLE_ Prefix)

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `DB_CHRONICLE_TYPE` | Yes | - | Database type (`mysql`, `pgsql`, or `sqlite`) |
| `DB_CHRONICLE_HOST` | Yes* | - | Database server hostname (*not required for SQLite) |
| `DB_CHRONICLE_PORT` | No | 3306/5432 | Database server port (auto-detected based on type) |
| `DB_CHRONICLE_DB` | Yes | - | Database name (or path for SQLite) |
| `DB_CHRONICLE_USER` | No | - | Database username |
| `DB_CHRONICLE_PASS` | No | - | Database password |

### Application Configuration

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `SESSION_TIMEOUT` | No | `1800` | Session timeout in seconds (30 minutes) |
| `CHRONICLE_BASE_URL_PATH` | No | - | Base URL path for subdirectory installs (e.g., `/chronicle`) |

## Volume Mounts

### Configuration (Recommended)

Mount a custom `config.ini` file to `/app/etc/config.ini`:

```bash
docker run -d \
  -v /path/to/config.ini:/app/etc/config.ini:ro \
  chronicle:latest
```

**Note**: Volume-mounted config takes precedence over environment variables.

### Logs (Optional)

```bash
docker run -d \
  -v chronicle-nginx-logs:/var/log/nginx \
  -v chronicle-php-logs:/var/log/php \
  chronicle:latest
```

## File Structure

```
docker/
├── nginx/
│   └── chronicle.conf              # Nginx site configuration
├── php-fpm/
│   └── pool.conf               # PHP-FPM pool configuration
├── runit/
│   ├── nginx/
│   │   └── run                 # Nginx runit service
│   └── php-fpm/
│       └── run                 # PHP-FPM runit service
└── scripts/
    └── 01_setup_config.sh      # Startup configuration script
```

## Database Initialization

### MySQL

```bash
# Using Docker Compose
docker-compose exec mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD} ${MYSQL_DATABASE} < schema/mysql.sql

# Using standalone container
docker exec -i chronicle-mysql mysql -uroot -psecret chronicle < schema/mysql.sql
```

### PostgreSQL

```bash
# Using standalone container
docker exec -i chronicle-postgres psql -U chronicle_user -d chronicle < schema/pgsql.sql
```

### SQLite

```bash
# Copy schema to container and initialize
docker cp schema/sqlite.sql chronicle:/tmp/
docker exec chronicle sqlite3 /app/data/chronicle.db < /tmp/sqlite.sql
```

## Healthcheck

The container includes basic HTTP healthchecking:

```bash
# Check container health
docker inspect --format='{{.State.Health.Status}}' chronicle
```

## Troubleshooting

### View Logs

```bash
# Using Docker Compose
docker-compose logs -f chronicle

# Using Docker CLI
docker logs -f chronicle
```

### Access Container Shell

```bash
# Using Docker Compose
docker-compose exec chronicle bash

# Using Docker CLI
docker exec -it chronicle bash
```

### Verify Configuration

```bash
# Check generated config.ini
docker exec chronicle cat /app/etc/config.ini

# Verify database connection
docker exec chronicle php -r "require '/app/vendor/autoload.php'; \DealNews\DB\DB::init('chronicle'); echo 'Connected successfully';"
```

### Common Issues

**Database connection failed**
- Verify `DB_CHRONICLE_HOST` is reachable from container
- Check database credentials
- Ensure database exists and schema is initialized

**Permission denied**
- Ensure volume-mounted files are readable by `www-data` (UID 33)
- Check file permissions: `chmod 644 config.ini`

**Nginx/PHP-FPM not starting**
- Check logs: `docker logs chronicle`
- Verify runit services: `docker exec chronicle sv status /etc/service/*`

## Production Deployment

### Kubernetes

Example deployment with MySQL:

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: chronicle
spec:
  replicas: 2
  selector:
    matchLabels:
      app: chronicle
  template:
    metadata:
      labels:
        app: chronicle
    spec:
      containers:
      - name: chronicle
        image: chronicle:latest
        ports:
        - containerPort: 80
        env:
        - name: DB_CHRONICLE_TYPE
          value: "mysql"
        - name: DB_CHRONICLE_HOST
          value: "mysql-service"
        - name: DB_CHRONICLE_DB
          value: "chronicle"
        - name: DB_CHRONICLE_USER
          valueFrom:
            secretKeyRef:
              name: chronicle-db-secret
              key: username
        - name: DB_CHRONICLE_PASS
          valueFrom:
            secretKeyRef:
              name: chronicle-db-secret
              key: password
        volumeMounts:
        - name: config
          mountPath: /app/etc/config.ini
          subPath: config.ini
          readOnly: true
      volumes:
      - name: config
        configMap:
          name: chronicle-config
```

### Docker Swarm

```bash
docker service create \
  --name chronicle \
  --replicas 3 \
  --publish 8000:80 \
  --env DB_CHRONICLE_TYPE=mysql \
  --env DB_CHRONICLE_HOST=mysql \
  --env DB_CHRONICLE_DB=chronicle \
  --secret source=db_user,target=DB_CHRONICLE_USER \
  --secret source=db_pass,target=DB_CHRONICLE_PASS \
  chronicle:latest
```

## Security Considerations

1. **Don't commit secrets**: Use environment variables or mounted secrets
2. **Use read-only volumes**: Mount config.ini as read-only (`:ro`)
3. **Network isolation**: Use Docker networks to isolate services
4. **HTTPS**: Use reverse proxy (Traefik, Nginx) for TLS termination
5. **Database access**: Restrict database network access to Chronicle containers only
6. **Regular updates**: Keep base image and PHP packages updated

## License

BSD 3-Clause License - Copyright (c) 2025, DealNews
