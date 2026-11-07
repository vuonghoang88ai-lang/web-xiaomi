import os
import sys
import unittest
from datetime import date
from unittest.mock import patch

from cryptography.fernet import Fernet

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
import gsc_service


class FakeResponse:
    def __init__(self, payload, ok=True, status_code=200):
        self._payload = payload
        self.ok = ok
        self.status_code = status_code
        self.text = str(payload)

    def json(self):
        return self._payload


class GSCServiceTests(unittest.TestCase):
    def setUp(self):
        os.environ["GSC_CLIENT_ID"] = "client.apps.googleusercontent.com"
        os.environ["GSC_CLIENT_SECRET"] = "secret"
        os.environ["GSC_REDIRECT_URI"] = "https://engine.example/api/v1/gsc/oauth/callback"
        os.environ["GSC_TOKEN_ENCRYPTION_KEY"] = Fernet.generate_key().decode("ascii")

    def test_authorization_url_requests_offline_readonly_access(self):
        url = gsc_service.build_authorization_url("state-value")
        self.assertIn("access_type=offline", url)
        self.assertIn("webmasters.readonly", url)
        self.assertIn("state-value", url)

    def test_refresh_token_is_encrypted_and_round_trips(self):
        encrypted = gsc_service.encrypt_refresh_token("do-not-log-this")
        self.assertNotIn("do-not-log-this", encrypted)
        self.assertEqual(gsc_service.decrypt_refresh_token(encrypted), "do-not-log-this")

    @patch("gsc_service.requests.post")
    def test_search_analytics_uses_official_endpoint_and_payload(self, post):
        post.return_value = FakeResponse({"rows": [{"keys": ["2026-10-01"], "clicks": 4}]})
        result = gsc_service.query_search_analytics(
            "access-token", "https://example.com/", date(2026, 10, 1), date(2026, 10, 7), ["date"]
        )
        self.assertEqual(result["rows"][0]["clicks"], 4)
        request = post.call_args
        self.assertIn("webmasters/v3/sites/", request.args[0])
        self.assertEqual(request.kwargs["json"]["dimensions"], ["date"])


if __name__ == "__main__":
    unittest.main()
