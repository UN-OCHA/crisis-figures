# API

The API will be here.

Refer to the [Getting Started Guide](https://api-platform.com/docs/distribution) for more information.

## Development Setup Notes
If you run into timeout issues with Composer, increase the default timeout value, which is 60 seconds,
by setting the `COMPOSE_HTTP_TIMEOUT` environment variable. For example:

```shell script
COMPOSE_HTTP_TIMEOUT=240 docker-compose -d 
```
