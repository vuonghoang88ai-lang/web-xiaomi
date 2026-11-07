import io
import os
import sys
import unittest
from unittest.mock import patch
from uuid import uuid4

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import scraper_tool
import serp_discovery_tool


class OnboardingReconTests(unittest.TestCase):
    def test_normalize_website_url_adds_https_and_rejects_invalid_schemes(self):
        self.assertEqual(
            serp_discovery_tool.normalize_website_url("example.com"),
            "https://example.com",
        )
        with self.assertRaises(ValueError):
            serp_discovery_tool.normalize_website_url("ftp://example.com")
        with self.assertRaises(ValueError):
            serp_discovery_tool.normalize_website_url("https://user:secret@example.com")

    def test_serp_parser_returns_real_organic_urls_and_excludes_known_non_competitors(self):
        html = """
        <div class="result">
          <a class="result__a" href="https://company.example/">Company</a>
          <a class="result__snippet">Actual result snippet</a>
        </div>
        <div class="result">
          <a class="result__a" href="https://vnexpress.net/news">News</a>
        </div>
        <div class="result">
          <a class="result__a" href="https://another.example/">Another</a>
        </div>
        """
        response = io.BytesIO(html.encode())
        with patch.object(serp_discovery_tool.urllib.request, "urlopen") as urlopen:
            urlopen.return_value.__enter__.return_value = response
            results = serp_discovery_tool._fetch_serp_results("sample query")

        self.assertEqual([item["domain"] for item in results], ["company.example", "another.example"])
        self.assertEqual(results[0]["serp_snippet"], "Actual result snippet")
        self.assertEqual(results[0]["source"], "duckduckgo_html")

    def test_discovery_marks_online_only_after_live_check(self):
        candidates = [
            {"url": "https://one.example", "domain": "one.example", "rank": 1},
            {"url": "https://two.example", "domain": "two.example", "rank": 2},
        ]
        with (
            patch.object(serp_discovery_tool, "_fetch_serp_results", return_value=candidates),
            patch.object(serp_discovery_tool, "check_url_online", side_effect=[True, False]),
        ):
            results = serp_discovery_tool.discover_top_10_competitors(
                "service", "https://seed.example"
            )

        self.assertEqual([result["status"] for result in results], ["online", "offline"])
        self.assertEqual([result["rank"] for result in results], [1, 2])
        self.assertTrue(all(result.get("verified_at") for result in results))

    def test_discovery_fails_explicitly_when_serp_has_no_results(self):
        with patch.object(serp_discovery_tool, "_fetch_serp_results", return_value=[]):
            with self.assertRaises(serp_discovery_tool.SerpDiscoveryError):
                serp_discovery_tool.discover_top_10_competitors(
                    "service", "https://seed.example"
                )

    def test_website_inspection_does_not_label_http_error_as_online(self):
        response = unittest.mock.Mock()
        response.stdout = "<html>Not found</html>\n__HTTP_STATUS__404\n__EFFECTIVE_URL__https://site.example/"
        with patch.object(serp_discovery_tool.subprocess, "run", return_value=response):
            result = serp_discovery_tool.inspect_website("https://site.example")

        self.assertEqual(result["status"], "http_error")
        self.assertEqual(result["http_status"], 404)

    def test_scraper_never_returns_fabricated_fallback_prices(self):
        empty = {"status": "empty", "products": []}
        with (
            patch.object(scraper_tool, "scrape_mihanoi_search", return_value=empty),
            patch.object(scraper_tool, "scrape_tcs_search", return_value=empty),
            patch.object(scraper_tool, "scrape_mivietnam_search", return_value=empty),
        ):
            result = scraper_tool.scrape_competitor_recon("sample product")

        self.assertEqual(result["status"], "no_results")
        self.assertEqual(result["products"], [])
        self.assertFalse(result["fallback_used"])

    def test_onboarding_database_failure_is_not_returned_as_success(self):
        with (
            patch.object(serp_discovery_tool.db_service, "get_tenant", return_value={"name": "Tenant"}),
            patch.object(serp_discovery_tool, "inspect_website", return_value={"status": "online"}),
            patch.object(
                serp_discovery_tool,
                "discover_top_10_competitors",
                return_value=[{"rank": 1, "url": "https://competitor.example", "domain": "competitor.example", "is_online": True}],
            ),
            patch.object(serp_discovery_tool, "generate_uvp_suggestions", return_value=[]),
            patch.object(serp_discovery_tool, "call_llm", return_value="report"),
            patch.object(serp_discovery_tool.db_service, "save_project", return_value="project-id"),
            patch.object(serp_discovery_tool.db_service, "save_competitors", side_effect=RuntimeError("db failed")),
            patch.object(serp_discovery_tool.rag_service, "sync_project_intelligence_to_rag") as rag_sync,
        ):
            with self.assertRaisesRegex(RuntimeError, "PostgreSQL"):
                serp_discovery_tool.run_onboarding_gap_analysis(
                    "https://brand.example",
                    "Business description",
                    "https://seed.example",
                    tenant_id=str(uuid4()),
                )

        rag_sync.assert_not_called()

    def test_onboarding_reports_partial_when_rag_is_unavailable(self):
        with (
            patch.object(serp_discovery_tool.db_service, "get_tenant", return_value={"name": "Tenant"}),
            patch.object(serp_discovery_tool, "inspect_website", return_value={"status": "online"}),
            patch.object(serp_discovery_tool, "discover_top_10_competitors", return_value=[]),
            patch.object(serp_discovery_tool, "generate_uvp_suggestions", return_value=[]),
            patch.object(serp_discovery_tool, "call_llm", return_value="report"),
            patch.object(serp_discovery_tool.db_service, "save_project", return_value="project-id"),
            patch.object(serp_discovery_tool.db_service, "save_competitors"),
            patch.object(serp_discovery_tool.db_service, "save_market_report"),
            patch.object(serp_discovery_tool.rag_service, "sync_project_intelligence_to_rag", return_value=None),
        ):
            result = serp_discovery_tool.run_onboarding_gap_analysis(
                "https://brand.example",
                "Business description",
                "https://seed.example",
                tenant_id=str(uuid4()),
            )

        self.assertEqual(result["status"], "partial")
        self.assertEqual(result["persistence"], {"postgresql": "saved", "rag": "failed", "rag_dataset_reference": "failed"})


if __name__ == "__main__":
    unittest.main()
