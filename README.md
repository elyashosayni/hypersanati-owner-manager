# HyperSanati Owner Manager

Independent WordPress/WooCommerce plugin for the HyperSanati business-owner management panel.

## Architecture

The plugin is intentionally independent from the HyperSanati theme.

Dependencies:

- WooCommerce
- HSB Auth

## Phase 1

- Dedicated business-owner role
- Username/password authentication gate
- Existing HSB Auth mobile OTP as second factor
- Frontend owner panel
- Product search and listing
- Product main image management
- Product gallery management
- Explicit confirmation before saving image changes

## Future modules

- Product pricing
- Product descriptions and content
- Stock management
- Inventory workflows
- Additional granular business permissions
- Audit logging

## Security model

The Owner role receives only explicit plugin capabilities.

Initial capabilities:

- `hom_access_owner_panel`
- `hom_view_products`
- `hom_manage_product_images`

Future capabilities exist separately and are not granted to the Owner role until their modules are enabled.
