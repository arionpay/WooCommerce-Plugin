# WooCommerce-Plugin
# ArionPay Crypto Payment Gateway for WooCommerce

**Accept Bitcoin, Ethereum, USDT, Tron, Solana, and more directly on your WooCommerce store.**

The ArionPay Crypto Gateway allows merchants to accept cryptocurrency payments seamlessly. It supports two modes: **Standard Redirect** (Hosted Checkout) and **White Label** (On-Site Payment).

## 🚀 Features

* **Universal Support:** Works with both **Classic WooCommerce Checkout** and the new **WooCommerce Blocks**.
* **Multi-Currency:** Support for BTC, ETH, LTC, TRX, SOL, USDT (TRC20/ERC20), USDC, and more.
* **Two Payment Modes:**
    1.  **Redirect Mode:** Redirects customers to a secure, hosted ArionPay checkout page.
    2.  **White Label Mode:** Keeps customers on your website with a native dropdown and QR code UI.
* **Real-Time Status:** Automatic order status updates via secure Webhooks (Pending → Processing/Completed).
* **Secure:** HMAC SHA-256 signature verification for all callbacks.
* **No Third-Party Branding:** (In White Label Mode) Your customers never leave your domain.

---

## 📋 Requirements

* **WordPress:** 5.8 or higher
* **WooCommerce:** 7.0 or higher
* **PHP:** 7.4 or higher
* **SSL Certificate:** Required (HTTPS) for secure communication.
* **ArionPay Merchant Account:** You need an active store at [merchant.arionpay.com](https://merchant.arionpay.com).

---

## 📦 Installation

### Option 1: Upload via WordPress Admin
1.  Download the repository as a `.zip` file.
2.  Log in to your WordPress Admin Dashboard.
3.  Go to **Plugins > Add New > Upload Plugin**.
4.  Select the `arionpay-woocommerce.zip` file and click **Install Now**.
5.  Click **Activate Plugin**.

### Option 2: Manual FTP/SFTP
1.  Unzip the plugin archive.
2.  Upload the `arionpay-woocommerce` folder to your server's `/wp-content/plugins/` directory.
3.  Go to **WordPress Admin > Plugins** and activate **ArionPay Crypto (Universal)**.

---

## ⚙️ Configuration

1.  Log in to your **ArionPay Merchant Dashboard**.
2.  Go to **Stores** and select your store.
3.  Copy your **Store ID**, **Public API Key**, and **Secret Key**.
4.  In WordPress, go to **WooCommerce > Settings > Payments**.
5.  Click **Manage** next to **ArionPay Crypto**.
6.  Enter your credentials:
    * **Enabled:** Yes
    * **Title/Description:** Customize what the customer sees.
    * **API Key:** Paste your Public Key.
    * **Secret Key:** Paste your Secret Key.
    * **Store ID:** Paste your Store ID.
7.  Click **Save Changes**.

---

## 🎨 Setting Up White Label Mode (On-Site)

White Label mode allows the payment UI (Coin Selector & QR Code) to appear directly on your "Order Received" page without redirecting the user.

**Step 1: In WordPress Plugin Settings**
1.  Go to **WooCommerce > Settings > Payments > ArionPay Crypto**.
2.  Check the box: **"Enable White Label (On-Site Payment)"**.
3.  Save Changes.

**Step 2: In ArionPay Merchant Dashboard (CRITICAL)**
1.  Log in to [merchant.arionpay.com](https://merchant.arionpay.com).
2.  Go to **Stores > Settings**.
3.  Find the **Branding / White Label** section.
4.  Enable **White Label Mode**.
5.  Save.

> ⚠️ **Note:** If you enable it in WordPress but disable it in the Merchant Dashboard (or vice versa), the payment flow may fail or fallback to redirect mode.

---

## 🛠 Troubleshooting

### 1. "Payment Error: Unauthorized"
* **Cause:** Your API Keys are wrong or have been regenerated.
* **Fix:** Go to your Merchant Dashboard, copy the keys again, and update them in WooCommerce settings.

### 2. "There are no payment methods available"
* **Cause:** The `assets/block.js` file might be missing or cached.
* **Fix:** Ensure the plugin folder structure is correct and clear your WordPress cache.

### 3. Payment is successful, but Order Status stays "Pending Payment"
* **Cause:** Webhooks are failing or blocked.
* **Fix:**
    * Check if your site is publicly accessible (Webhooks cannot reach `localhost`).
    * Ensure your **Secret Key** matches exactly in both the Dashboard and WordPress.
    * Check **WooCommerce > Status > Logs** for `arionpay-webhook` errors.

### 4. I am redirected to the Merchant Dashboard instead of the Checkout Page
* **Cause:** You are testing while logged in as the Merchant Admin in the same browser.
* **Fix:** Open an **Incognito/Private Window** to test the checkout flow as a guest.

---

## 📂 Folder Structure

Ensure your plugin files look like this for version 19+:

```text
/wp-content/plugins/arionpay-woocommerce/
│
├── arionpay-woocommerce.php   (Main Plugin File)
│
└── assets/
    ├── block.js               (WooCommerce Blocks Integration)
    ├── arionpay-checkout.css  (Checkout UI Styles)
    ├── arionpay-checkout.js   (Checkout UI Logic)
    └── logos/                 (Crypto Icons)
          ├── btc.png
          ├── eth.png
          ├── trx.png
          └── ...
```
📜 License
This project is licensed under the MIT License.

