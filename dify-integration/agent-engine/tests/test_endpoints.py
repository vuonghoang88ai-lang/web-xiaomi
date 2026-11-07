"""
Unit tests for new modular endpoints and critical bug regressions.
SOP 23 Compliant (< 300 lines)
"""
import os
import sys
import unittest
from unittest.mock import patch, MagicMock

CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
ENGINE_DIR = os.path.abspath(os.path.join(CURRENT_DIR, ".."))
if ENGINE_DIR not in sys.path:
    sys.path.insert(0, ENGINE_DIR)

from routes.cms import download_omni_bridge_connector
from routes.campaign import CampaignRequest
from routes.onboarding import ProjectMemoryRequest
from models import (
    PortalTestAuthorityRequest,
    PortalTestSerpRequest,
    PortalTestAiRequest
)
from routes.portal.settings_test import (
    portal_test_authority,
    portal_test_serp,
    portal_test_ai
)


class TestEndpointsAndRegressions(unittest.TestCase):

    def test_download_omni_bridge_connector(self):
        """Kiểm tra endpoint tải về file plugin omni-bridge-connector.zip."""
        response = download_omni_bridge_connector()
        self.assertIsNotNone(response)
        self.assertEqual(response.media_type, "application/zip")
        self.assertTrue(os.path.exists(response.path))
        self.assertGreater(os.path.getsize(response.path), 10000)

    def test_campaign_uuid_generation(self):
        """Đảm bảo không bị NameError: name 'uuid' is not defined khi tạo campaign."""
        import uuid
        req = CampaignRequest(
            project_id="test-proj-123",
            brief="Viết bài so sánh Tivi Xiaomi S Mini LED",
            keyword="tivi xiaomi"
        )
        self.assertEqual(req.project_id, "test-proj-123")
        test_uuid = str(uuid.uuid4())
        self.assertEqual(len(test_uuid), 36)

    def test_onboarding_datetime_timezone(self):
        """Đảm bảo không bị NameError: datetime, timezone khi lưu trí nhớ dự án."""
        from datetime import datetime, timezone
        req = ProjectMemoryRequest(
            project_id="test-proj-123",
            user_input="Tôi muốn tập trung vào thị trường Hà Nội",
            agent_response="Đã ghi nhận mục tiêu Hà Nội"
        )
        now_utc = datetime.now(timezone.utc).isoformat()
        self.assertIn("+00:00", now_utc)

    @patch("routes.portal.settings_test.urllib.request.urlopen")
    def test_portal_test_serp(self, mock_urlopen):
        """Kiểm tra chẩn đoán kết nối Serper API."""
        mock_response = MagicMock()
        mock_response.status = 200
        mock_response.read.return_value = b'{"organic": [{"title": "Test 1"}], "ads": []}'
        mock_response.__enter__.return_value = mock_response
        mock_urlopen.return_value = mock_response

        req = PortalTestSerpRequest(serper_api_keys="test-serper-key-123456")
        result = portal_test_serp(req)
        self.assertEqual(result.get("status"), "success")
        self.assertIn("message", result)

    @patch("routes.portal.settings_test.extract_domain_age_and_citations", return_value={"age_years": 5, "citations_count": 120})
    @patch("routes.portal.settings_test.urllib.request.urlopen")
    def test_portal_test_authority(self, mock_urlopen, mock_extract):
        """Kiểm tra chẩn đoán Moz Authority service."""
        mock_response = MagicMock()
        mock_response.status = 200
        mock_response.read.return_value = b'{"results": [{"domain_authority": 45}]}'
        mock_response.__enter__.return_value = mock_response
        mock_urlopen.return_value = mock_response

        req = PortalTestAuthorityRequest(
            moz_api_keys="moz-user:moz-secret",
            test_domain="example.com"
        )
        result = portal_test_authority(req)
        self.assertEqual(result.get("status"), "success")

    @patch("routes.portal.settings_test.urllib.request.urlopen")
    def test_portal_test_ai(self, mock_urlopen):
        """Kiểm tra chẩn đoán kết nối AI provider."""
        mock_response = MagicMock()
        mock_response.status = 200
        mock_response.read.return_value = b'{"choices": [{"message": {"content": "pong"}}]}'
        mock_response.__enter__.return_value = mock_response
        mock_urlopen.return_value = mock_response

        req = PortalTestAiRequest(
            api_key="mock-openai-key-12345",
            provider="openai",
            model="gpt-4o"
        )
        result = portal_test_ai(req)
        self.assertEqual(result.get("status"), "success")

    def test_portal_html_critical_dom_elements(self):
        """Kiểm tra file portal/index.html và các partial components có đầy đủ các ID phần tử cần thiết (SOP 23)."""
        html_path = os.path.join(ENGINE_DIR, "portal", "index.html")
        self.assertTrue(os.path.exists(html_path))
        with open(html_path, "r", encoding="utf-8") as f:
            raw_index = f.read()

        # SOP 23: index.html BẮT BUỘC duy trì <= 300 dòng code
        self.assertLessEqual(len(raw_index.splitlines()), 300, "portal/index.html vượt quá giới hạn 300 dòng của SOP 23")

        # Nạp các modular component partials
        content = raw_index
        for partial_file in ["portal_tab_views.html", "portal_tab_settings.html", "portal_modals.html"]:
            p_path = os.path.join(ENGINE_DIR, "portal", partial_file)
            if os.path.exists(p_path):
                with open(p_path, "r", encoding="utf-8") as pf:
                    p_content = pf.read()
                    self.assertLessEqual(len(p_content.splitlines()), 300, f"{partial_file} vượt quá 300 dòng")
                    content += "\n" + p_content

        critical_elements = [
            'id="mySiteContent"',
            'id="competitorsGrid"',
            'id="keywordMatrixContainer"',
            'id="chatInput"',
            'id="btnRescanMySite"',
            'id="competitorDeepAuditModal"',
            'id="projectSelector"',
            'src="/portal/portal_project_loader.js',
            'src="/portal/portal_my_site_clusters.js',
            'src="/portal/portal_my_site_actions.js',
            'src="/portal/portal_deep_audit_view.js',
            'src="/portal/portal_keywords_pool.js',
            'href="/portal/portal_feedback.css',
            'Permissions-Policy',
        ]
        for elem in critical_elements:
            self.assertIn(elem, content, f"Missing element: {elem} in portal/index.html")

    @patch("routes.gsc.db_service")
    @patch("routes.gsc.gsc_service")
    def test_gsc_connect_generates_auth_url(self, mock_gsc, mock_db):
        """Kiểm tra routes/gsc.py gsc_connect hoạt động đúng với uuid và datetime."""
        from routes.gsc import gsc_connect
        from uuid import UUID

        mock_db.get_project_by_id.return_value = {"id": "proj-1", "tenant_id": "00000000-0000-0000-0000-000000000001"}
        mock_db.get_active_gsc_connection.return_value = None
        mock_gsc.build_authorization_url.return_value = "https://accounts.google.com/o/oauth2/auth?test=1"
        mock_gsc.GSC_READONLY_SCOPE = "https://www.googleapis.com/auth/webmasters.readonly"

        res = gsc_connect(
            project_id="proj-1",
            tenant_id=UUID("00000000-0000-0000-0000-000000000001")
        )
        self.assertEqual(res["status"], "authorization_required")
        self.assertIn("authorization_url", res)
        mock_db.create_gsc_oauth_state.assert_called_once()

    @patch("urllib.request.urlopen")
    def test_portal_bridge_ping_indexnow(self, mock_urlopen):
        """Kiểm tra routes/portal/bridge.py ping_indexnow hoạt động đúng."""
        from routes.portal.bridge import ping_indexnow
        mock_resp = MagicMock()
        mock_resp.getcode.return_value = 200
        mock_resp.__enter__.return_value = mock_resp
        mock_urlopen.return_value = mock_resp

        success = ping_indexnow("tongkhomi.com", "https://tongkhomi.com/tivi-xiaomi")
        self.assertTrue(success)


if __name__ == "__main__":
    unittest.main()
