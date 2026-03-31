# Upcoming Release — April 4, 2026

## New Features

### POS PIN Setup During Onboarding & Dashboard Prompt
New users are now guided to set their POS PIN as part of the setup wizard (Step 2, right after creating their first branch). This ensures every user is ready to use the point-of-sale terminal from day one.

Existing users who haven't set a PIN yet will see a dismissible prompt on the dashboard, making it easy to get set up without interrupting their workflow.

**Highlights:**
- **Setup Wizard Step 2** — After branch creation, users are prompted to create a 4-6 digit POS PIN before landing on the dashboard.
- **"Skip for now" option** — Users can skip PIN setup during onboarding and set it later.
- **Dashboard PIN Modal** — Any user without a PIN sees a friendly, dismissible modal on the dashboard with a quick setup form.
- **PIN uniqueness enforced** — PINs are validated to be unique within the tenant to prevent conflicts at the POS terminal.



---

### Landing Page SEO Optimization
The landing page has been fully optimized for search engines and social sharing.

**Highlights:**
- **SEO Meta Tags** — Title, description, keywords, canonical URL, and robots directives added to the landing page `<Head>`.
- **Open Graph & Twitter Cards** — Full og:* and twitter:* tags for rich link previews when shared on Facebook, Twitter/X, LinkedIn, Discord, etc.
- **JSON-LD Structured Data** — `SoftwareApplication` and `Organization` schemas injected for Google rich results.
- **Sitemap & Robots** — `sitemap.xml` created with homepage and about page; `robots.txt` updated with proper crawl directives and sitemap reference.
- **Branded Favicon** — Replaced default Laravel favicon with OmniPOS TrendingUp icon on teal background.
- **OG Image** — Branded 1200x630 social sharing image with logo, tagline, feature pills, and POS mockup.
- **Semantic HTML** — Aria labels on navigation, clickable logo, `aria-hidden` on decorative icons, `role="contentinfo"` on footer.
- **Accessibility** — `<noscript>` fallback ensures scroll-animated content is visible without JavaScript.

---

### Email Template Visual Fix
Fixed two visual issues in the email template (used for password reset, login alert, verification, etc.):

**Highlights:**
- **Real logo** — Replaced the base64-encoded SVG (generic chart icon that many email clients block) with the actual OmniPOS `icon.png` served from the app URL.
- **Unified card layout** — Header and body now share the same 570px width, forming one cohesive card instead of a full-width header over a narrower body.
- **Rounded corners** — Teal gradient header has rounded top corners; white body has rounded bottom corners.



2e033b6f360468810e4135ef8ffcebca