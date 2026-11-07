#!/usr/bin/env python3
"""Register WordPress Publisher tool directly into Dify PostgreSQL database."""
import json
import uuid
import psycopg2
from pydantic import TypeAdapter
from core.tools.utils.parser import ApiBasedToolSchemaParser
from core.tools.entities.tool_bundle import ApiToolBundle

def main():
    schema_file = "/tmp/wp_omni_bridge_tool.yaml"
    with open(schema_file, "r", encoding="utf-8") as f:
        schema_text = f.read()

    tool_bundles, schema_type = ApiBasedToolSchemaParser.auto_parse_to_tool_bundle(schema_text)
    tools = TypeAdapter(list[ApiToolBundle]).dump_python(tool_bundles, mode="json")
    tools_str = json.dumps(tools, ensure_ascii=False)

    user_id = "60f154e5-3e89-4192-a5e9-8a63a1532e85"
    tenant_id = "0cfcbe3e-fbfa-4025-8aa0-ee5ff82b77d8"
    provider_name = "WordPress Publisher"
    icon = json.dumps({"content": "📝", "background": "#2563EB"})
    credentials = json.dumps({
        "auth_type": "api_key_header",
        "api_key_header": "X-Omni-Bridge-Key",
        "api_key_header_prefix": "",
        "api_key_value": "obc_f0e92571a466e4bd96b8bbdb3ffc33db40090024"
    })
    description = "WordPress Publisher Tool via Omni Bridge Connector"

    conn = psycopg2.connect(
        host="db_postgres",
        port=5432,
        dbname="dify",
        user="postgres",
        password="difyai123456"
    )
    with conn.cursor() as cur:
        cur.execute("SELECT id FROM tool_api_providers WHERE name = %s AND tenant_id = %s;", (provider_name, tenant_id))
        row = cur.fetchone()
        if row:
            provider_id = row[0]
            cur.execute("""
                UPDATE tool_api_providers 
                SET schema = %s, schema_type_str = %s, tools_str = %s, credentials_str = %s, updated_at = NOW()
                WHERE id = %s;
            """, (schema_text, schema_type.value, tools_str, credentials, provider_id))
            print(f"Updated existing tool_api_provider {provider_id}")
        else:
            provider_id = str(uuid.uuid4())
            cur.execute("""
                INSERT INTO tool_api_providers (
                    id, name, schema, schema_type_str, user_id, tenant_id,
                    tools_str, icon, credentials_str, description, privacy_policy, custom_disclaimer
                ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);
            """, (
                provider_id, provider_name, schema_text, schema_type.value, user_id, tenant_id,
                tools_str, icon, credentials, description, "", ""
            ))
            print(f"Created new tool_api_provider {provider_id}")
    conn.commit()
    conn.close()
    print("WordPress Publisher registered successfully!")

if __name__ == "__main__":
    main()
