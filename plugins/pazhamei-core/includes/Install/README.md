# Install

Setup that runs on activation, upgrade or from WP-CLI: roles and capabilities, the pz_settings option, the page and menu installer, and (from Phase 3) the wp_pz_* tables.

MovedPagesMigration renames or retires storefront pages that moved (`/about-contact` became `/about-us`; `/enquire` was retired) and points saved menus, template parts and edited pages at the new addresses. It runs as part of `wp pz install-pages` and does nothing once the pages have moved.
