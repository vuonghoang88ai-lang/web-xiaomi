"""Portal routes package."""
from .projects import router as projects_router
from .analysis import router as analysis_router
from .keywords import router as keywords_router
from .chat import router as chat_router
from .chat_sessions import router as chat_sessions_router
from .settings import router as settings_router
from .settings_test import router as settings_test_router
from .rescan import router as rescan_router
from .license import router as license_router
from .admin import router as admin_router
from .bridge import router as bridge_router
from .intelligence import router as intelligence_router
from .agentic import router as agentic_router
from .brief_and_drift import router as brief_drift_router
from .seed import router as seed_router
from .prompt_expander import router as prompt_expander_router
from .commercial_guard import router as commercial_guard_router
from .covers import router as covers_router
from .plugin_interop import router as plugin_interop_router
from .chat_upload import router as chat_upload_router
from .onpage_ai import router as onpage_ai_router

all_routers = [
    projects_router,
    analysis_router,
    rescan_router,
    keywords_router,
    chat_router,
    chat_upload_router,
    chat_sessions_router,
    settings_router,
    settings_test_router,
    license_router,
    admin_router,
    bridge_router,
    intelligence_router,
    agentic_router,
    brief_drift_router,
    seed_router,
    prompt_expander_router,
    commercial_guard_router,
    covers_router,
    plugin_interop_router,
    onpage_ai_router,
]



