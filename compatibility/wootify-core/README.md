# Wootify Core Compatibility

This folder contains theme-side compatibility code for the `wootify-core` plugin.

Boundary:
- `wootify-core` owns product matrix data, variant lookup, cart metadata, pricing, thumbnails, and order metadata.
- The theme owns cart drawer rendering, upsell interactions, and the lightweight selector sheet used before add-to-cart.

Current integrations:
- `CartDrawerIntegration.php` bridges Wootify matrix products into the cart drawer "You may also like" selector flow.
