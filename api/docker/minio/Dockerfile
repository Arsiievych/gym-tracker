FROM golang:1.26-alpine AS build

RUN CGO_ENABLED=0 GOBIN=/out go install -trimpath \
    github.com/minio/minio@RELEASE.2025-10-15T17-29-55Z

FROM alpine:3.23

RUN apk add --no-cache ca-certificates \
    && addgroup -S minio \
    && adduser -S -G minio minio \
    && mkdir /data \
    && chown minio:minio /data

COPY --from=build /out/minio /usr/local/bin/minio

USER minio
ENTRYPOINT ["minio"]
