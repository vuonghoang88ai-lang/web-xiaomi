#!/usr/bin/env python3
"""
Database Service Facade Shim
Refactored from monolithic 705 lines to modular Clean Architecture (SOP 23):
  - db_base.py: Connection pool, UUID validation, tenant/user CRUD, system settings
  - db_projects.py: Project CRUD, active project, conversation ID
  - db_intelligence.py: Competitors, deep audit, target analysis, market reports
  - db_gsc.py: GSC OAuth states, connections, performance sync rows
"""

from db_base import (
    get_connection, _is_valid_uuid,
    get_default_tenant_and_user, get_tenant, create_tenant, create_user,
    get_system_setting, save_system_setting
)
from db_projects import (
    save_project, update_project_uvp, update_project_info, delete_project,
    get_or_create_active_project, update_project_fields, list_projects_by_tenant,
    get_project_by_id, update_project_conversation_id, reset_project_conversation_id
)
from db_intelligence import (
    save_competitors, update_competitor_deep_audit, save_project_target_analysis,
    save_market_report, get_project_full_intelligence
)
from db_gsc import (
    create_gsc_oauth_state, consume_gsc_oauth_state, save_gsc_connection,
    get_gsc_connection, list_gsc_connections, activate_gsc_connection,
    mark_gsc_connection_error, save_gsc_performance_rows, get_gsc_performance_rows
)

__all__ = [
    "get_connection", "_is_valid_uuid",
    "get_default_tenant_and_user", "get_tenant", "create_tenant", "create_user",
    "get_system_setting", "save_system_setting",
    "save_project", "update_project_uvp", "update_project_info", "delete_project",
    "get_or_create_active_project", "update_project_fields", "list_projects_by_tenant",
    "get_project_by_id", "update_project_conversation_id", "reset_project_conversation_id",
    "save_competitors", "update_competitor_deep_audit", "save_project_target_analysis",
    "save_market_report", "get_project_full_intelligence",
    "create_gsc_oauth_state", "consume_gsc_oauth_state", "save_gsc_connection",
    "get_gsc_connection", "list_gsc_connections", "activate_gsc_connection",
    "mark_gsc_connection_error", "save_gsc_performance_rows", "get_gsc_performance_rows"
]
