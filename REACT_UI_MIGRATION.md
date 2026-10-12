# React → Laravel Visual Migration

## Goal
The React project is the single visual source of truth. Laravel business logic, routes, controllers, models, permissions, workflows, and database behavior were not intentionally changed.

## Applied globally
- Replaced Laravel's visual CSS entrypoint with a React-first stylesheet.
- Matched React shell tokens: Cairo typography, compact type scale, navy/gold palette, 60px top bar, 288px expanded sidebar, 68px collapsed sidebar, 1680px shell width, React-like radii, shadows, transitions, dark/light modes, and custom scrollbars.
- Restyled Bootstrap-compatible cards, forms, tables, buttons, badges, alerts, modals, dropdowns, tabs, pagination, Select2, notifications, loader and responsive behavior so existing Laravel markup follows the React design language.
- Rebuilt the shared navbar visually to follow the React Navbar while preserving Laravel routes, permissions, notifications, user menu, logout, and sidebar behavior.
- Removed embedded `<style>` blocks from every Blade view that extends `layouts.app` so page-level legacy CSS can no longer compete with the React layer.
- Removed custom Laravel CSS links from those app views. Functional vendor styles remain available through the shared layout.
- Unified Login / Forgot Password / Reset Password / Force Change Password with the React login identity.
- Added React visual CSS to standalone non-print HTML views that do not inherit `layouts.app`.
- Removed two unused duplicate layout backup files (`app.blade copy.php`, `app.blade copy 2.php`).

## Static verification
- 307 views inherit `layouts.app`.
- 0 embedded `<style>` blocks remain in those 307 app views.
- React theme CSS brace validation passed.
- No controller/model/database/workflow files were intentionally edited for this UI migration.

## Important fidelity note
The React source contains fewer distinct screens than the Laravel project. Screens with no direct React counterpart now inherit the exact same design system and component language, but a literal pixel-by-pixel match for every route requires running the Laravel app and performing screenshot comparison/visual QA route-by-route. This archive contains the global migration foundation and removes the old-style mixing that prevented such parity.
