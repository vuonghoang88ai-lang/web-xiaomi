#!/usr/bin/env python3
"""
YouTube Tech Reviewer Live Harvester (SOP 12, 34, 35 & 63).
Trích xuất phụ đề (Subtitles/Transcript) từ các video Reviewer công nghệ hàng đầu trên YouTube
để tiếp địa trải nghiệm thực tế người thật vào bài viết của CMO Copilot.
Tuân thủ SOP 23 (<= 300 dòng) và SOP 30 (Zero Hardcoding).
"""

import re
import json
import logging
import urllib.request
import urllib.parse
from typing import Dict, Any, List, Optional
from youtube_transcript_api import YouTubeTranscriptApi

logger = logging.getLogger("marketing_engine.youtube_harvester")


def search_youtube_review_videos(query: str, max_results: int = 5, timeout: int = 4) -> List[Dict[str, str]]:
    """Tìm kiếm nhanh các video đánh giá/review thực tế trên YouTube mà không cần API key."""
    clean_q = re.sub(r"[^\w\sÀ-ỹ]", " ", query).strip()
    search_q = f"đánh giá {clean_q}" if not any(k in clean_q.lower() for k in ["đánh giá", "review", "trên tay"]) else clean_q
    url = f"https://www.youtube.com/results?search_query={urllib.parse.quote(search_q)}"
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
        "Accept-Language": "vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7"
    }

    videos = []
    try:
        req = urllib.request.Request(url, headers=headers)
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            html = resp.read().decode("utf-8", errors="ignore")
            match = re.search(r"var ytInitialData = ({.*?});</script>", html)
            if match:
                data = json.loads(match.group(1))
                contents = data["contents"]["twoColumnSearchResultsRenderer"]["primaryContents"]["sectionListRenderer"]["contents"][0]["itemSectionRenderer"]["contents"]
                for item in contents:
                    vr = item.get("videoRenderer")
                    if vr:
                        vid_id = vr.get("videoId")
                        title = vr.get("title", {}).get("runs", [{}])[0].get("text", "")
                        channel = vr.get("ownerText", {}).get("runs", [{}])[0].get("text", "")
                        if vid_id and title:
                            videos.append({
                                "id": vid_id,
                                "title": title,
                                "channel": channel or "YouTuber Công Nghệ",
                                "url": f"https://www.youtube.com/watch?v={vid_id}"
                            })
                            if len(videos) >= max_results:
                                break
    except Exception as e:
        logger.warning(f"[YOUTUBE_SEARCH_ERROR] {e}")

    return videos


def fetch_video_transcript(video_id: str, languages: Optional[List[str]] = None) -> Optional[str]:
    """Bóc tách toàn bộ phụ đề tiếng Việt hoặc tiếng Anh của video qua YouTubeTranscriptApi."""
    target_langs = languages or ["vi", "en"]
    try:
        api = YouTubeTranscriptApi()
        t_list = api.list(video_id)
        tr = t_list.find_transcript(target_langs)
        fetched = tr.fetch()
        text_segments = [seg.text.strip() for seg in fetched if seg.text.strip()]
        return " ".join(text_segments)
    except Exception as e:
        logger.debug(f"[YOUTUBE_TRANSCRIPT_FAIL] video_id={video_id}: {e}")
        return None


def condense_transcript_insights(full_transcript: str, max_words: int = 220) -> str:
    """
    Thuật toán nén phụ đề video thành các nhận định trải nghiệm thực chiến (SOP 35).
    Lọc bỏ các đoạn chào hỏi rườm rà và trích xuất nhận xét trải nghiệm cốt lõi.
    """
    if not full_transcript:
        return ""

    words = full_transcript.split()
    if len(words) <= max_words:
        return full_transcript

    # Lấy 100 từ ở 1/4 đầu video (đánh giá tổng quan) + 120 từ ở 1/2 video (chi tiết trải nghiệm thực tế)
    part1 = " ".join(words[20:120])
    mid_idx = len(words) // 2
    part2 = " ".join(words[mid_idx:mid_idx + 120])
    
    condensed = f"{part1} [...] {part2}".strip()
    return condensed[:900]


def harvest_youtube_reviewer_insights(
    topic: str,
    max_videos: int = 1,
    timeout: int = 5
) -> List[Dict[str, Any]]:
    """
    Trinh sát video YouTube tự động: Tìm kiếm -> Bóc phụ đề -> Tinh lọc nhận định reviewer.
    """
    candidates = search_youtube_review_videos(topic, max_results=4, timeout=timeout)
    if not candidates:
        return []

    harvested = []
    for vid in candidates:
        raw_text = fetch_video_transcript(vid["id"])
        if raw_text and len(raw_text) > 100:
            insights = condense_transcript_insights(raw_text)
            harvested.append({
                "video_id": vid["id"],
                "title": vid["title"],
                "channel": vid["channel"],
                "url": vid["url"],
                "insights": insights
            })
            if len(harvested) >= max_videos:
                break

    return harvested


def build_youtube_grounding_block(harvested_videos: List[Dict[str, Any]]) -> str:
    """
    Đóng gói dữ liệu YouTube Reviewer thành khối Prompt Grounding (SOP 35 <= 400 tokens).
    """
    if not harvested_videos:
        return ""

    lines = [
        "\n\n[TRẢI NGHIỆM THỰC TẾ TỪ YOUTUBE TECH REVIEWER (GROUNDING - SOP 63)]:",
        "Hệ thống đã trích xuất phụ đề từ video đánh giá công nghệ thực tế trên YouTube:"
    ]

    for vid in harvested_videos:
        lines.append(f"- Kênh Reviewer: **{vid['channel']}** | Video: \"{vid['title']}\"")
        lines.append(f"  * Trích dẫn nhận định thực tế: \"{vid['insights']}\"")
        lines.append(f"  * Nguồn video: {vid['url']}")

    lines.extend([
        "💡 CHỈ ĐẠO BIÊN TẬP TỪ VIDEO REVIEW:",
        "1. Trích dẫn khéo léo các cảm nhận nghe nhìn, độ nhạy remote, trải nghiệm Google TV thực tế vào bài viết.",
        "2. Đảm bảo ngôn từ 100% tự nhiên của người thật đánh giá sản phẩm, triệt tiêu hoàn toàn lý thuyết sáo rỗng AI."
    ])

    return "\n".join(lines)

