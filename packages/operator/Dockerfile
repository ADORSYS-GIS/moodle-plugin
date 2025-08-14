FROM rust:slim AS builder

WORKDIR /app
COPY . .

RUN cargo build --release

FROM debian:bookworm-slim

WORKDIR /app
COPY --from=builder /app/target/release/operator /usr/local/bin/app

EXPOSE 8888
CMD ["app"]
