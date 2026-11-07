#!/usr/bin/env python3
"""
Novel Cover Artisan (Backwards Compatibility Wrapper - SOP 23 <= 300 lines).
Re-exports universal visual asset generator for novel covers.
"""
from universal_visual_artisan import generate_novel_cover, generate_universal_visual_asset

__all__ = ["generate_novel_cover", "generate_universal_visual_asset"]
