# Leon Popup Gate (AFF Pairs)

Leon Popup Gate (AFF Pairs) adds an affiliate-gated popup flow for chapter-based content sites. Readers are prompted to open affiliate apps to unlock chapters while administrators can control timing, messaging, and bypass roles.

## Features

- Popup gate triggers on even-numbered chapter URLs (matching `/chuong...`).
- Requires readers to click a configured affiliate call-to-action to unlock chapters for a configurable TTL period.
- Randomly selects from enabled affiliate pairs with optional hero image that also acts as the CTA.
- Customizable text copy, notice overlay, and affiliate details from a dedicated settings page.
- Chapter dismissal notice overlays the content if the popup is closed without unlocking.
- Role-based bypass to disable the popup for editors, authors, or any custom role.
- Lightweight vanilla JavaScript and CSS with no external dependencies.

## Installation

1. Download `leon-popup-gate-affpairs-v1.2.2.zip`.
2. In your WordPress admin, navigate to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP file, install, and activate the plugin.

### Where to find the packaged ZIP locally

- Repository-relative path: `leon-popup-gate-affpairs-v1.2.2.zip` (sits beside the plugin source directory).
- Absolute path inside the workspace: `/workspace/leon/leon-popup-gate-affpairs-v1.2.2.zip`.

> 💡 The shell prompt you may see in examples, e.g. `root@7115a1c9075a:/workspace/leon#`, is just the terminal prompt showing the current user, container ID, and directory—enter the commands after the `#` symbol.

## Configuration

After activation, go to **Settings → Leon Popup Gate (AFF)**.

### General

- **Enable**: Toggles the popup gate globally.
- **Delay (ms)**: Milliseconds to wait before showing the popup.
- **TTL (ms)**: Unlock duration (in milliseconds). During this time the popup will not appear once a reader has clicked a CTA.
- **Cookie Version**: Increment to invalidate existing unlock cookies/localStorage.

### Custom Texts

- **Notice HTML**: Displayed over content when readers close the popup without unlocking.
- **Note Text & Thanks Text**: Additional messages inside the popup.
- **Intro Line 1 & 2**: Main instructions. Intro line 2 supports the `%APP%` placeholder which is automatically replaced with Shopee, TikTok, or Lazada based on the selected affiliate pair.

### Affiliate Pairs

Manage multiple affiliate destinations. Each row includes:

- **Enabled**: Determines whether the pair participates in random selection.
- **Type**: Shopee, TikTok, or Lazada (controls styling and CTA text).
- **URL**: Affiliate deep link.
- **Image URL**: Optional hero image; clicking the image performs the same unlock action as the CTA.

Use the “+ Thêm dòng” button to add rows. Rows are saved even if disabled, allowing seasonal rotations.

### Bypass Roles

Select WordPress roles that should never see the popup or notice overlay. Useful for administrators, QA, or premium roles.

## How It Works

- The script watches for URLs that match `/([^/]*/)?chuong[^0-9]*([0-9]{1,6})(/|$)/i`.
- On the first even-numbered chapter encountered, the popup appears after the configured delay and sets a start flag.
- Until the reader clicks the CTA (or hero image), the popup will continue to appear on all subsequent chapter views. Closing the popup shows the notice overlay and does **not** unlock the content.
- Clicking the CTA or hero image sets an unlock key (stored in both `localStorage` and cookies) using the configured TTL. After it expires, the cycle restarts the next time an even chapter is visited.

## Troubleshooting

- **Popup never appears**: Ensure the page slug matches the chapter pattern and the plugin is enabled. Also confirm your role is not bypassed.
- **Popup appears every page**: Verify that readers click the CTA to set the unlock key and that TTL is sufficient.
- **Bypass not working**: Confirm the correct role is selected and re-save settings.
- **Need to force readers again**: Increase the cookie version; existing unlock keys will be ignored.

## Requirements

- WordPress 5.6+
- PHP 7.4+

The plugin ships without external dependencies and is GPLv2+ licensed.
