#!/usr/bin/env python3
"""Automate repeatable GSC/WordPress bridge onboarding.

The script can provision the local WordPress plugin through an explicitly
provided WP-CLI command, but never prints the Omni Bridge key or Google secret.
It stops before Google consent because that step must be approved by a user.
"""

import argparse
import json
import shlex
import subprocess
import sys
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen


ROOT = Path(__file__).resolve().parents[1]
PLUGIN_ZIP = ROOT / "plugins" / "omni-bridge-connector.zip"
sys.path.insert(0, str(ROOT / "tools"))
from bootstrap_gsc import apply_schema, validate_environment  # noqa: E402


def run_command(command, label, secret=False, cwd=None):
    result = subprocess.run(command, capture_output=True, text=True, check=False, cwd=cwd)
    if result.returncode != 0:
        detail = result.stderr.strip() or result.stdout.strip()
        raise RuntimeError(f"{label} thất bại: {detail[:500]}")
    return result.stdout.strip()


def wp_command(prefix, args, workdir=None):
    return run_command(prefix + list(args), "WP-CLI", secret=True, cwd=workdir)


def configure_wordpress(args, wp_prefix):
    if args.install_plugin:
        if not PLUGIN_ZIP.exists():
            raise RuntimeError(f"Không tìm thấy plugin: {PLUGIN_ZIP}")
        installed = subprocess.run(
            wp_prefix + ["plugin", "is-installed", "omni-bridge-connector"],
            capture_output=True,
            text=True,
            check=False,
            cwd=args.wp_cli_workdir,
        ).returncode == 0
        if not installed:
            wp_command(
                wp_prefix,
                ["plugin", "install", str(PLUGIN_ZIP), "--activate"],
                args.wp_cli_workdir,
            )
        else:
            wp_command(wp_prefix, ["plugin", "activate", "omni-bridge-connector"], args.wp_cli_workdir)
        print("OK: Omni Bridge đã được cài và kích hoạt.")

    options = {
        "omni_bridge_gsc_engine_url": args.engine_url.rstrip("/"),
        "omni_bridge_gsc_tenant_id": args.tenant_id,
        "omni_bridge_gsc_project_id": args.project_id,
    }
    for name, value in options.items():
        wp_command(wp_prefix, ["option", "update", name, value], args.wp_cli_workdir)

    api_key = wp_command(wp_prefix, ["option", "get", "omni_bridge_api_key"], args.wp_cli_workdir)
    if not api_key.startswith("obc_"):
        raise RuntimeError("Omni Bridge API key không hợp lệ hoặc plugin chưa được kích hoạt.")
    return api_key


def configure_engine(args, api_key):
    payload = {
        "tenant_id": args.tenant_id,
        "cms_type": "wordpress",
        "cms_api_url": args.wordpress_url.rstrip("/"),
        "api_key": api_key,
    }
    request = Request(
        f"{args.engine_url.rstrip('/')}/api/v1/projects/{args.project_id}/cms-bridge",
        data=json.dumps(payload).encode("utf-8"),
        headers={"Content-Type": "application/json", "X-Engine-API-Key": args.engine_api_key},
        method="POST",
    )
    try:
        with urlopen(request, timeout=30) as response:
            result = json.loads(response.read().decode("utf-8"))
    except (HTTPError, URLError) as exc:
        raise RuntimeError(f"CMS Bridge provisioning thất bại: {exc}") from exc
    return result


def get_connect_url(args):
    request = Request(
        f"{args.engine_url.rstrip('/')}/api/v1/projects/{args.project_id}/gsc/connect?tenant_id={args.tenant_id}",
            headers={"Accept": "application/json", "X-Engine-API-Key": args.engine_api_key},
    )
    try:
        with urlopen(request, timeout=30) as response:
            return json.loads(response.read().decode("utf-8"))
    except (HTTPError, URLError) as exc:
        raise RuntimeError(f"Không tạo được URL GSC OAuth: {exc}") from exc


def main():
    parser = argparse.ArgumentParser(description="Automate WordPress bridge + GSC onboarding")
    parser.add_argument("--engine-url", required=True)
    parser.add_argument("--engine-api-key", required=True, help="ENGINE_API_KEY của Marketing Engine")
    parser.add_argument("--wordpress-url", required=True)
    parser.add_argument("--tenant-id", required=True)
    parser.add_argument("--project-id", required=True)
    parser.add_argument(
        "--wp-cli-prefix",
        help='Lệnh WP-CLI, ví dụ: "docker compose exec -T wordpress wp --path=/var/www/html"',
    )
    parser.add_argument("--wp-cli-workdir", help="Thư mục chạy WP-CLI/Docker Compose")
    parser.add_argument("--install-plugin", action="store_true", help="Cài/kích hoạt plugin ZIP trước khi cấu hình")
    parser.add_argument("--skip-schema", action="store_true")
    args = parser.parse_args()

    validate_environment()
    if not args.skip_schema:
        apply_schema()
        print("OK: PostgreSQL schema đã được áp dụng.")

    if not args.wp_cli_prefix:
        raise RuntimeError("Cần --wp-cli-prefix để tự động cấu hình WordPress.")
    wp_prefix = shlex.split(args.wp_cli_prefix)
    api_key = configure_wordpress(args, wp_prefix)
    bridge_result = configure_engine(args, api_key)
    print(f"OK: CMS Bridge đã cấu hình cho project {args.project_id}.")
    print(f"GSC source detected: {(bridge_result.get('gsc_connector') or {}).get('source', 'none')}")

    connect_result = get_connect_url(args)
    print("Bước cuối cần người dùng xác nhận Google OAuth.")
    print(f"Mở URL OAuth: {connect_result.get('authorization_url', 'Kết nối đã sở hữu hoặc cần kiểm tra trạng thái.')}")


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        raise SystemExit(1)
