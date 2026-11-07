#!/usr/bin/env python3
"""Validate and bootstrap the GSC ownership-transfer prerequisites.

This command is safe to run repeatedly. It applies the idempotent schema,
validates OAuth configuration without printing secrets, and reports the next
WordPress/OAuth action. It intentionally does not install a WordPress plugin
or perform Google consent on behalf of a user.
"""

import argparse
import os
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
ENGINE_DIR = ROOT / "agent-engine"
sys.path.insert(0, str(ENGINE_DIR))


def validate_environment():
    required = ("GSC_CLIENT_ID", "GSC_CLIENT_SECRET", "GSC_REDIRECT_URI", "GSC_TOKEN_ENCRYPTION_KEY")
    missing = [name for name in required if not os.getenv(name, "").strip()]
    if missing:
        raise RuntimeError("Thiếu biến môi trường: " + ", ".join(missing))

    from cryptography.fernet import Fernet

    try:
        Fernet(os.environ["GSC_TOKEN_ENCRYPTION_KEY"].encode("ascii"))
    except Exception as exc:
        raise RuntimeError("GSC_TOKEN_ENCRYPTION_KEY không phải Fernet key hợp lệ.") from exc


def apply_schema():
    import db_service

    schema_path = ENGINE_DIR / "schema.sql"
    schema_sql = schema_path.read_text(encoding="utf-8")
    with db_service.get_connection() as connection:
        with connection.cursor() as cursor:
            cursor.execute(schema_sql)
        connection.commit()


def main():
    parser = argparse.ArgumentParser(description="Bootstrap Google Search Console connector")
    parser.add_argument("--skip-schema", action="store_true", help="Chỉ kiểm tra biến môi trường, không chạy DDL")
    args = parser.parse_args()

    validate_environment()
    if not args.skip_schema:
        apply_schema()
        print("OK: PostgreSQL schema đã được áp dụng.")
    print("OK: OAuth configuration hợp lệ; không in secret ra màn hình.")
    print("Bước tiếp theo: cài Omni Bridge, cấu hình CMS Bridge, rồi mở /gsc/connect để cấp quyền Engine một lần.")


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        raise SystemExit(1)
