# Paylands Gateway - PrestaShop Module

Payment gateway module for PrestaShop developed by [paynopain.com](https://paynopain.com). Supports card payments, Bizum, and a configurable multi-method checkout form across production and sandbox environments.

---

## Compatibility

- PrestaShop 1.6 and 1.7 / 8.x
- PHP 7.1 or higher
- EU-compatible module

---

## Payment Methods

### Card Payment

The default payment option. Redirects the customer to a hosted Paylands payment form identified by a Service UUID. Supports optional 3-D Secure enforcement and card tokenization for returning customers.

### Bizum

An optional standalone payment option that appears alongside the card option at checkout. Uses a separate Service UUID dedicated to Bizum. 3-D Secure is always enforced for Bizum transactions. Can be enabled or disabled independently from the card option.

### Checkout Mode

An alternative to the Service UUID flow that uses a dedicated Checkout UUID. In this mode the payment form is configured entirely from the module settings and supports any combination of the following methods:

- Payment Card
- Bizum
- Sofort
- iDEAL
- Crypto
- Pix
- Viacash
- Klarna
- Giropay
- Google Pay
- Apple Pay
- Click to Pay

In Checkout Mode you can set a default payment method, a theme (Light or Dark), a custom font, and a description displayed at the top of the form.

---

## Features

### Dual Environment

Production and sandbox credentials are stored separately. Switching between environments from the admin panel instantly applies the correct API key, signature, Service UUID, Bizum UUID, and Checkout UUID without clearing the other environment's data. All runtime controllers always read the base configuration keys, which are updated on every save to reflect the active environment.

### 3-D Secure

When enabled, every card payment requires 3DS authentication. Guest checkouts always go through 3DS regardless of this setting. Bizum payments always use 3DS regardless of the card setting.

### Card Tokenization and Saved Cards

Registered (non-guest) customers can have their card details saved after a successful payment. On subsequent checkouts a card selection screen is shown that allows the customer to pay directly using a saved card without re-entering card details. If 3DS is off and a saved card is selected, a direct server-to-server payment is attempted. If 3DS is on, the card token is passed to a tokenized 3DS flow. Customers can also delete saved cards. Guests are never offered saved cards.

### Asynchronous Payment Callback

Paylands notifies the module of the final payment outcome via a server-to-server callback. The callback updates the internal order record and transitions the PrestaShop order to the configured success or error state independently of what the customer does in the browser.

### Order State Mapping

Two order states are configurable from the admin panel:

- Awaiting Payment state: applied as soon as the order is created and the customer is redirected to the payment form. A custom "Awaiting for Paylands Payment" state is created automatically on install.
- Payment Accepted state: applied by the callback when Paylands reports a successful payment. Defaults to PrestaShop's built-in Payment Accepted state.

### Appearance

- Custom logo URL for the hosted payment form header.
- Accent color with a color picker synced to a hex input field.
- Payment form language: follows the active store language or can be fixed to English, Spanish, German, Portuguese, or French.
- Custom checkout button labels for both the card option and the Bizum option.

### Multi-Currency

The module registers with PrestaShop's currency system in checkbox mode, allowing individual currencies to be enabled or disabled for the module from the Currencies configuration.

---

## Database Tables

The module creates two tables on install and drops them on uninstall.

**pnp_paylands_orders** - one record per payment attempt, linked to the PrestaShop cart and order. Stores the Paylands order UUID, token, client UUID, amount, currency, status, 3DS flag, service identifier, antifraud result, IP address, and the full raw API response.

**pnp_paylands_cards** - one record per saved card per customer. Stores the card UUID, token, last four digits, brand, card type (credit/debit), BIN, expiry, holder name, issuing bank, and the full raw card object returned by the API.

---

## Admin Configuration

The configuration page is organized into six sections.

**API Credentials** - API key and signature for Production and Sandbox. Both sets of credentials are stored at all times; only the active environment's values are used at runtime.

**Card Payment** - Service UUID and checkout button label, stored per environment.

**Bizum** - Toggle to enable the Bizum payment option. When enabled, exposes the Bizum Service UUID and button label, stored per environment.

**Order Flow** - 3-D Secure toggle, Save Cards toggle, Awaiting Payment state selector, and Payment Accepted state selector.

**Appearance** - Payment form logo URL, accent color, and form language.

**Checkout Mode** - Switch between Service UUID mode (classic) and Checkout mode. In Checkout mode, exposes the Checkout UUID (per environment), the payment method selection, the default method, the theme, the font, and the description.

---

## Installation

Upload the module directory to `[prestashop]/modules/paylands/` and install from the PrestaShop back office module manager. After installation, configure at minimum the API key, signature, and a Service UUID for the active environment before processing live orders.

## Uninstallation

Uninstalling the module removes all configuration keys and drops both database tables. Existing order and card records are permanently deleted.

---

## License

Academic Free License (AFL 3.0). See LICENSE.md for full terms.
