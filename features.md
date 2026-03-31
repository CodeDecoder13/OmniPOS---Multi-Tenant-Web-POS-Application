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

### Google Sign-In (SSO)
Users can now sign in or register using their Google account for a faster, passwordless experience.

Existing users who registered with email and password can also use Google to log in — if their Google email matches their account, it's automatically linked. After linking, both login methods (Google and email/password) work seamlessly.

**Highlights:**
- **Login page** — "Continue with Google" button below the email/password form lets users sign in with one click.
- **Register page** — Google button on Step 1 lets new users skip manual name/email entry. After authenticating with Google, the wizard jumps straight to Step 2 (Your Business) with account details pre-filled.
- **3-step registration for Google users** — The password step is removed entirely: Account ✓ → Business → Plan.
- **Automatic account linking** — Existing email/password users who click Google are matched by email, their Google ID is linked, and they're logged in instantly. No duplicate accounts.
- **2FA compatible** — If an existing user has two-factor authentication enabled, Google login still triggers the 2FA challenge before granting access.
- **Set a password later** — Google-only users can add a password anytime from Security settings, enabling both login methods.
- **Auto email verification** — Google users skip the email verification step since Google already verified the address.

---

### Email Template Visual Fix
Fixed two visual issues in the email template (used for password reset, login alert, verification, etc.):

**Highlights:**
- **Real logo** — Replaced the base64-encoded SVG (generic chart icon that many email clients block) with the actual OmniPOS `icon.png` served from the app URL.
- **Unified card layout** — Header and body now share the same 570px width, forming one cohesive card instead of a full-width header over a narrower body.
- **Rounded corners** — Teal gradient header has rounded top corners; white body has rounded bottom corners.


