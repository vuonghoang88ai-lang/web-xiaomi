#!/usr/bin/env python3
"""
Script chạy thử nghiệm cào 1 sản phẩm thực tế từ đối thủ và xuất bản mẫu thành 1 Sản Phẩm WooCommerce hoàn chỉnh.
"""

import os
import sys
import json
import time

CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, CURRENT_DIR)

from agent_orchestrator import run_full_pipeline

def main():
    keyword = "Robot hút bụi lau nhà Xiaomi Mijia Omni 2"
    category = "Robot hút bụi Xiaomi"
    
    print("================================================================")
    print("🚀 BẮT ĐẦU CHẠY THỬ NGHIỆM XUẤT BẢN SẢN PHẨM WOOCOMMERCE")
    print(f"📦 Từ khóa sản phẩm: {keyword}")
    print(f"📂 Danh mục WooCommerce: {category}")
    print(f"🏷️ Loại bài đăng (post_type): product")
    print("================================================================\n")
    
    start_time = time.time()
    result = run_full_pipeline(
        keyword=keyword,
        category_name=category,
        publish_to_wp=True,
        post_type="product"
    )
    total_time = round(time.time() - start_time, 2)
    
    print("\n================================================================")
    print(f"🎉 HOÀN THÀNH PIPELINE TRONG {total_time} GIÂY!")
    print("================================================================")
    print(f"Post ID: {result.get('wordpress_post_id')}")
    print(f"WordPress Status: {result.get('wordpress_status')}")
    print(f"Tiêu đề: {result.get('post_title')}")
    print(f"File Campaign: {result.get('campaign_file')}")
    print("================================================================")
    
    return result

if __name__ == "__main__":
    main()
