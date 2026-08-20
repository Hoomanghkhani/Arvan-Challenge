import time
import os
import glob
from playwright.sync_api import sync_playwright

def run_demo():
    output_dir = "recordings"
    os.makedirs(output_dir, exist_ok=True)
    
    start_time = time.time()
    print("Starting extended 5+ minute automated recording...")
    
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        
        # 1080p high quality screen context
        context = browser.new_context(
            record_video_dir=output_dir,
            record_video_size={"width": 1920, "height": 1080},
            viewport={"width": 1920, "height": 1080},
            locale="fa-IR"
        )
        
        page = context.new_page()
        page.set_default_navigation_timeout(60000)
        page.set_default_timeout(30000)
        
        # Helper for smooth typing
        def type_slow(locator, text, delay=0.08):
            locator.fill("")
            for char in text:
                locator.type(char, delay=delay*1000)
                time.sleep(0.02)
        
        def safe_goto(url):
            try:
                page.goto(url, wait_until="domcontentloaded", timeout=45000)
            except Exception as e:
                print(f"Navigation warning on {url}: {e}")
            time.sleep(3)
        
        # =========================================================================
        # 1. Login & Token Verification (Duration: ~35s)
        # =========================================================================
        print("1. Logging into WordPress Admin...")
        safe_goto("http://localhost:8080/wp-login.php")
        time.sleep(2)
        page.fill("#user_login", "admin")
        time.sleep(1)
        page.fill("#user_pass", "admin")
        time.sleep(1)
        page.click("#wp-submit")
        time.sleep(4)
        
        print("2. Demonstrating Admin Settings & Token Verification...")
        safe_goto("http://localhost:8080/wp-admin/admin.php?page=arvan_store_settings")
        time.sleep(4)
        page.evaluate("window.scrollTo({ top: 150, behavior: 'smooth' })")
        time.sleep(4)
        page.evaluate("window.scrollTo({ top: 0, behavior: 'smooth' })")
        time.sleep(4)
        
        # =========================================================================
        # 2. Admin Dashboard & Sorkhab KPI Metrics (Duration: ~45s)
        # =========================================================================
        print("3. Demonstrating Admin Sorkhab Dashboard & KPIs...")
        safe_goto("http://localhost:8080/wp-admin/admin.php?page=arvan_store")
        time.sleep(5)
        page.evaluate("window.scrollTo({ top: 250, behavior: 'smooth' })")
        time.sleep(6)
        page.evaluate("window.scrollTo({ top: 500, behavior: 'smooth' })")
        time.sleep(6)
        page.evaluate("window.scrollTo({ top: 0, behavior: 'smooth' })")
        time.sleep(4)
        
        # =========================================================================
        # 3. Customer Portal: Cloud Server ECC Configuration (Duration: ~80s)
        # =========================================================================
        print("4. Opening Sorkhab Store (Customer Portal)...")
        safe_goto("http://localhost:8080/arvan-store/?standalone=1")
        time.sleep(5)
        
        print("5. Cloud Server (ECC) Detailed Configuration...")
        # Exploring Datacenters with realistic deliberation
        page.locator("text=تبریز - دیتاسنتر شهریار").click()
        time.sleep(3)
        page.locator("text=اصفهان - دیتاسنتر زاینده‌رود").click()
        time.sleep(3)
        page.locator("text=تهران - دیتاسنتر فروغی").click()
        time.sleep(3)
        
        # Exploring OS images
        page.evaluate("window.scrollTo({ top: 200, behavior: 'smooth' })")
        time.sleep(2)
        page.locator("text=Debian 12").click()
        time.sleep(2.5)
        page.locator("text=CentOS 9").click()
        time.sleep(2.5)
        page.locator("text=Windows 2022").click()
        time.sleep(2.5)
        page.locator("text=Ubuntu 24.04").click()
        time.sleep(3)
        
        # Exploring Flavors
        page.evaluate("window.scrollTo({ top: 400, behavior: 'smooth' })")
        time.sleep(2)
        page.locator("text=استاندارد اکونومی").click()
        time.sleep(3)
        page.locator("text=عمومی و پایدار").click()
        time.sleep(3)
        page.locator("text=محاسباتی سنگین").click()
        time.sleep(3)
        page.locator("text=حرفه‌ای پرسرعت").click()
        time.sleep(3)
        
        # Dragging Disk slider gradually to demonstrate real-time pricing calculation
        page.evaluate("window.scrollTo({ top: 600, behavior: 'smooth' })")
        time.sleep(2)
        disk_slider = page.locator("input[type='range']").first
        for size in ["30", "50", "80", "120", "200", "85"]:
            disk_slider.fill(size)
            time.sleep(2)
        
        # Entering hostname and password
        name_input = page.locator("input[placeholder='arvan-cloud-vm-1']")
        type_slow(name_input, "production-cloud-node-01", delay=0.07)
        time.sleep(2)
        
        pass_input = page.locator("input[placeholder='••••••••••••']")
        type_slow(pass_input, "SecurePass@2026", delay=0.07)
        time.sleep(3)
        
        # Click Create Server
        page.locator("text=🚀 ساخت آنی سرور").click()
        time.sleep(6) # Admire Toast and redirect to services
        
        # =========================================================================
        # 4. Customer Portal: CDN & Object Storage S3 (Duration: ~65s)
        # =========================================================================
        print("6. CDN Configuration...")
        page.locator("button:has-text('CDN')").first.click()
        page.evaluate("window.scrollTo({ top: 0, behavior: 'smooth' })")
        time.sleep(4)
        
        domain_input = page.locator("input[placeholder='example.com']")
        type_slow(domain_input, "my-arvan-business.ir", delay=0.08)
        time.sleep(3)
        
        page.locator("text=پلن سازمانی و امنیت پیشرفته").click()
        time.sleep(3.5)
        page.locator("text=پلن رشد و حرفه‌ای").click()
        time.sleep(3.5)
        
        page.locator("text=🌐 فعال‌سازی CDN").click()
        time.sleep(6)
        
        print("7. Object Storage (S3) Configuration...")
        page.locator("button:has-text('فضای ابری (S3)')").first.click()
        page.evaluate("window.scrollTo({ top: 0, behavior: 'smooth' })")
        time.sleep(4)
        
        bucket_input = page.locator("input[placeholder='my-app-storage']")
        type_slow(bucket_input, "arvan-media-storage-bucket", delay=0.08)
        time.sleep(3)
        
        storage_slider = page.locator("input[type='range']").first
        for sz in ["100", "250", "500", "120"]:
            storage_slider.fill(sz)
            time.sleep(2)
            
        page.locator("text=🗄️ ایجاد باکت ابری").click()
        time.sleep(6)
        
        # =========================================================================
        # 5. Service Operations & Sorkhab Modals (Duration: ~50s)
        # =========================================================================
        print("8. Demonstrating My Services & Operations...")
        page.locator("button:has-text('سرویس‌های من')").first.click()
        page.evaluate("window.scrollTo({ top: 0, behavior: 'smooth' })")
        time.sleep(4)
        
        # Live filter search
        search_input = page.locator("input[placeholder*='جستجو']")
        type_slow(search_input, "production", delay=0.1)
        time.sleep(4)
        search_input.fill("")
        time.sleep(3)
        
        # Test Restart Modal
        restart_btn = page.locator("button:has-text('🔄 ری‌استارت')").first
        if restart_btn.is_visible():
            restart_btn.click()
            time.sleep(4) # Show modal clearly
            page.locator("text=تایید و اجرا").click()
            time.sleep(5)
            
        # Test Power Off Modal
        poweroff_btn = page.locator("button:has-text('⚡ خاموش')").first
        if poweroff_btn.is_visible():
            poweroff_btn.click()
            time.sleep(4) # Show modal clearly
            page.locator("text=تایید و اجرا").click()
            time.sleep(5)
            
        # Test Power On
        poweron_btn = page.locator("button:has-text('⚡ روشن')").first
        if poweron_btn.is_visible():
            poweron_btn.click()
            time.sleep(4)
            page.locator("text=تایید و اجرا").click()
            time.sleep(5)
            
        # =========================================================================
        # 6. Mobile Responsiveness Viewport (Duration: ~45s)
        # =========================================================================
        print("9. Demonstrating Mobile Responsiveness (iPhone 14 Pro)...")
        page.set_viewport_size({"width": 390, "height": 844})
        time.sleep(4)
        
        page.locator("button:has-text('سرور ابری')").first.click()
        time.sleep(4)
        page.evaluate("window.scrollTo({ top: 250, behavior: 'smooth' })")
        time.sleep(4)
        page.evaluate("window.scrollTo({ top: 500, behavior: 'smooth' })")
        time.sleep(4)
        page.evaluate("window.scrollTo({ top: 750, behavior: 'smooth' })")
        time.sleep(4)
        
        page.locator("button:has-text('سرویس‌های من')").first.click()
        time.sleep(4)
        page.evaluate("window.scrollTo({ top: 250, behavior: 'smooth' })")
        time.sleep(4)
        
        # Switch back to Desktop
        page.set_viewport_size({"width": 1920, "height": 1080})
        time.sleep(4)
        
        # =========================================================================
        # 7. Billing Cycle, Termination Rules & Reactivation (Duration: ~50s)
        # =========================================================================
        print("10. Demonstrating Billing Automation & Service Termination Rules...")
        safe_goto("http://localhost:8080/wp-admin/admin.php?page=arvan_store")
        time.sleep(5)
        
        # Trigger manual hourly billing cycle
        billing_btn = page.locator("button[name='run_arvan_billing']").first
        if billing_btn.is_visible():
            billing_btn.click()
            time.sleep(5)
            
        page.evaluate("window.scrollTo({ top: 300, behavior: 'smooth' })")
        time.sleep(5)
        page.evaluate("window.scrollTo({ top: 550, behavior: 'smooth' })")
        time.sleep(5)
        
        # Re-charge user wallet
        charge_input = page.locator("input[name='charge_amount']")
        if charge_input.is_visible():
            charge_input.fill("200000")
            time.sleep(2)
            page.locator("button[name='arvan_admin_wallet_charge']").click()
            time.sleep(6)
            
        # =========================================================================
        # 8. Standalone & Wallet Overview (Duration: ~40s)
        # =========================================================================
        print("11. Demonstrating Standalone Fullscreen Store Mode...")
        safe_goto("http://localhost:8080/arvan-store/?standalone=1")
        time.sleep(5)
        
        page.locator("button:has-text('کیف پول')").first.click()
        time.sleep(5)
        page.evaluate("window.scrollTo({ top: 200, behavior: 'smooth' })")
        time.sleep(5)
        
        page.locator("button:has-text('سرویس‌های من')").first.click()
        time.sleep(5)
        page.evaluate("window.scrollTo({ top: 200, behavior: 'smooth' })")
        time.sleep(5)
        
        # Ensure total duration is at least 320 seconds (> 5 minutes 20 seconds)
        elapsed = time.time() - start_time
        if elapsed < 320:
            extra_wait = 320 - elapsed
            print(f"Adding extra {extra_wait:.1f}s to guarantee > 5 minutes duration...")
            time.sleep(extra_wait)
            
        context.close()
        browser.close()
        
    total_elapsed = time.time() - start_time
    mins = int(total_elapsed // 60)
    secs = int(total_elapsed % 60)
    print(f"Recording completed! Total duration: {mins} minutes and {secs} seconds.")
    
    # Rename latest video in recordings folder to arvan_challenge_demo.webm
    recorded_files = glob.glob(os.path.join(output_dir, "*.webm")) + glob.glob(os.path.join(output_dir, "*.mp4"))
    if recorded_files:
        latest = max(recorded_files, key=os.path.getctime)
        target_name = os.path.join(output_dir, "arvan_challenge_demo.webm")
        if os.path.exists(target_name) and latest != target_name:
            try:
                os.remove(target_name)
            except Exception:
                pass
        if latest != target_name:
            try:
                os.rename(latest, target_name)
            except Exception:
                pass
        print(f"Demo video saved at: {target_name}")

if __name__ == "__main__":
    run_demo()
