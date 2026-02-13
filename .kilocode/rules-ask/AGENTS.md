# AGENTS.md

This file provides guidance to agents when working with code in this repository.

## Project Documentation Rules

- This is a single-tenant POS application - always use `Store::first()` to get the store instance
- Receipt templates support per-store customization via `receipt_template_id` field on Store model
- Bluetooth printing uses Web Bluetooth API with service UUID `000018f0-0000-1000-8000-00805f9b34fb`
- Receipt templates have three required sections: header, body, footer with configurable boolean flags
- Financial records are automatically created on checkout with profit tracking
- Product stock is decremented automatically during checkout via ProductObserver
