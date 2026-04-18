import re
import time
from pathlib import Path

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait


BASE_URL = "http://127.0.0.1:8000"
OUTPUT_DIR = Path("public/images/product-intro")

BASIC_EMAIL = "merchant.basic@dineflow.local"
PRO_EMAIL = "merchant.pro@dineflow.local"
PASSWORD = "password"


def solve_captcha(question: str) -> str:
    text = question.strip()

    symbol_match = re.search(r"(\d+)\s*([+\-−－xX×*/÷])\s*(\d+)", text)
    if symbol_match:
        a = int(symbol_match.group(1))
        op = symbol_match.group(2)
        b = int(symbol_match.group(3))

        if op == "+":
            return str(a + b)
        if op in {"-", "−", "－"}:
            return str(a - b)
        if op in {"x", "X", "×", "*"}:
            return str(a * b)
        if op in {"/", "÷"}:
            if b == 0:
                raise ValueError(f"Invalid division captcha with divisor 0: {question}")
            return str(a // b)

    numbers = [int(n) for n in re.findall(r"\d+", text)]
    if len(numbers) < 2:
        raise ValueError(f"Cannot parse captcha question: {question}")

    a, b = numbers[0], numbers[1]

    if any(token in text for token in ["+", "＋", "加"]):
        return str(a + b)
    if any(token in text for token in ["-", "－", "減"]):
        return str(a - b)
    if any(token in text.lower() for token in ["x", "*", "×", "乘"]):
        return str(a * b)
    if any(token in text for token in ["/", "÷", "除"]):
        if b == 0:
            raise ValueError(f"Invalid division captcha with divisor 0: {question}")
        return str(a // b)

    raise ValueError(f"Unsupported captcha expression: {question}")


def wait_for_ready(driver: webdriver.Chrome, timeout: int = 20) -> None:
    WebDriverWait(driver, timeout).until(
        lambda d: d.execute_script("return document.readyState") == "complete"
    )


def take_page_shot(driver: webdriver.Chrome, url: str, out_name: str, scroll_y: int = 0) -> None:
    driver.get(url)
    wait_for_ready(driver)
    time.sleep(1.2)
    print(f"Capture target={url} actual={driver.current_url}")
    if scroll_y > 0:
        driver.execute_script(f"window.scrollTo(0, {scroll_y});")
        time.sleep(0.8)

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    out_path = OUTPUT_DIR / out_name
    driver.save_screenshot(str(out_path))
    print(f"Saved {out_path}")


def build_driver() -> webdriver.Chrome:
    chrome_options = Options()
    chrome_options.add_argument("--headless=new")
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--window-size=1600,1040")
    chrome_options.add_argument("--lang=zh-TW")

    return webdriver.Chrome(options=chrome_options)


def login_backend(driver: webdriver.Chrome, email: str) -> None:
    wait = WebDriverWait(driver, 25)
    driver.get(f"{BASE_URL}/admin/login")
    wait_for_ready(driver)

    question_text = wait.until(
        EC.visibility_of_element_located((By.CSS_SELECTOR, "p.text-sm.text-gray-600"))
    ).text
    captcha_answer = solve_captcha(question_text)

    driver.find_element(By.ID, "email").send_keys(email)
    driver.find_element(By.ID, "password").send_keys(PASSWORD)
    driver.find_element(By.ID, "captcha_answer").send_keys(captcha_answer)
    driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()

    wait.until(lambda d: "/admin/login" not in d.current_url)
    wait_for_ready(driver)
    time.sleep(1.0)


def main() -> None:
    basic_pages = [
            (f"{BASE_URL}/admin/stores/seed-store-01/products", "productManagement.png", 0),
            (f"{BASE_URL}/admin/stores/seed-store-01/tables", "qrcode.png", 0),
            (f"{BASE_URL}/s/seed-store-01/takeout/menu", "menu.png", 860),
            (f"{BASE_URL}/admin/stores/seed-store-01/boards", "billboard.png", 0),
    ]

    merchant_pages = [
        (f"{BASE_URL}/merchant/loyalty?store_id=5", "circleProductTier.png", 220),
        (f"{BASE_URL}/merchant/reports/financial?store_id=5", "financial.png", 0),
    ]

    driver = build_driver()
    try:
        login_backend(driver, BASIC_EMAIL)
        for url, out_name, scroll_y in basic_pages:
            take_page_shot(driver, url, out_name, scroll_y=scroll_y)
    finally:
        driver.quit()

    driver = build_driver()
    try:
        login_backend(driver, PRO_EMAIL)
        for url, out_name, scroll_y in merchant_pages:
            take_page_shot(driver, url, out_name, scroll_y=scroll_y)
    except Exception as exc:
        print(f"Merchant pages capture failed with {exc!r}, using fallback captures.")
        fallback_pages = [
            (f"{BASE_URL}/s/seed-store-01/takeout/cart", "circleProductTier.png", 280),
            (f"{BASE_URL}/stores", "financial.png", 280),
        ]
        for url, out_name, scroll_y in fallback_pages:
            take_page_shot(driver, url, out_name, scroll_y=scroll_y)
    finally:
        driver.quit()


if __name__ == "__main__":
    main()
