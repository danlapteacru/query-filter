# Design prompt — marketing site (site representative) for the plugin

Use this brief with a **designer**, **agency**, or **AI design assistant** to shape a **public marketing site** (not the WordPress.org plugin listing itself). Copy everything below the horizontal rule into your tool or brief.

---

## Product & business model

- **Core plugin:** **Free**, **GPL-2.0-or-later**, distributed on **[WordPress.org](https://wordpress.org/plugins/)** as **Query Loop Index Filters**, slug **`query-loop-index-filters`** (re-verify slug before submission; see [`2026-04-08-branding-query-loop-index-filters.md`](2026-04-08-branding-query-loop-index-filters.md)).
- **Commercial layer:** **Paid add-ons** and/or a **premium bundle** that extends the same ecosystem. Examples of **premium-only** capabilities (positioning — exact ship order may vary):
  - **Map / geo filter** (e.g. filter posts by location, map UI).
  - **Date range** facet (published date or custom date sources).
  - **Numeric / price range** facet.
  - Other advanced facets, integrations, or support tiers as you define later.

The marketing site must make the **free vs paid** boundary **obvious** and **trustworthy** (no bait-and-switch): the free plugin is a **complete** solution for discrete faceted filtering on the Query Loop; premium adds **specialized** facet types and related features.

## What the free plugin does (factual)

- **Block-first** filtering for the **core Query Loop**: Filter Container, Checkboxes, Radio, Dropdown, Search, Sort, Reset, Pager.
- **Custom index table** for fast post-ID resolution; **REST** returns fresh Query Loop HTML + filter state; **Interactivity API** on the front end.
- **Discrete facets** in core (taxonomy-backed out of the box; extra checkbox filters via PHP hook).
- **Search:** WordPress core search or optional **SearchWP** integration (search scoped to facet results).
- **Requirements:** WordPress **6.7+**, PHP **8.1+**.

## Audience

- **Primary:** Agencies and developers building **block themes** / FSE sites who need **FacetWP-like** URL + filter UX **inside** Gutenberg.
- **Secondary:** Site owners who work with a developer; they may land on the marketing site first — copy should not require reading PHP docs to grasp value.

## Site goals (representative marketing site)

1. **Explain** the problem (slow or messy Query Loop filtering) and the approach (indexed, block-native).
2. **Drive installs** of the **free** plugin from WordPress.org (primary CTA).
3. **Seed premium interest** without hiding that map / date / numeric range etc. are **not** in the free tier — use a clear **Compare** or **Pricing** story.
4. **Build trust:** open-source core, .org reviews, changelog, documentation links, support expectations (community vs paid).

## Suggested information architecture (adjust as needed)

| Page / section | Purpose |
|----------------|---------|
| **Home** | Hero, 3 value props, “how it works” (3 steps), social proof placeholder, primary CTA → WordPress.org |
| **Features** | Free feature grid + short “Premium roadmap” or “Pro adds” strip |
| **Pricing** | Free: $0 via .org. Pro / bundles: tiers or per-addon cards (even if “coming soon” or waitlist) |
| **Documentation** | Link out to GitHub readme, wiki, or docs subdomain |
| **Changelog / releases** | Highlight parity between site and .org readme where possible |
| **About / open source** | License, repo link, contribution vibe |
| **Contact / support** | Forum (.org), GitHub issues, premium support channel for paying customers |

## Messaging pillars

1. **Performance & clarity:** Index-backed ID sets, not a giant tax/meta query on every interaction.
2. **Native WordPress:** Query Loop + blocks + Interactivity API — not a shortcode-era pattern.
3. **Free core is real:** Full discrete filtering story without paying; premium is for **advanced facet types** and power features.

## Visual & UX direction (for the designer)

- Feel **modern WordPress**: block editor aesthetic cues (clean, spacious, accessible contrast), not “startup SaaS neon” unless brand guidelines say otherwise.
- **Mobile-first:** Many evaluators check on phone.
- **Clear CTAs:** “Download on WordPress.org” vs “View Pro” / “Get add-ons” must be visually distinct.
- **Optional:** Lightweight diagram — editor (blocks) → index → REST → updated loop (no need for final copy in the prompt).

## Explicit non-goals for this site

- The site is **not** the in-plugin admin UI.
- Do not promise premium features with ship dates unless product confirms them.

## Deliverables to request from the design session

Please produce:

1. **Creative direction** — mood, color/type suggestions, 1–2 competitor references to align or avoid.
2. **Homepage wireframe or section outline** — order of blocks and primary CTA placement.
3. **Pricing page pattern** — how to show Free (single column) vs Pro/Add-ons (cards or table) without clutter.
4. **Copy hooks** — headline options (3), subhead options (3), and one short paragraph for the WordPress.org vs premium split.
5. **Accessibility note** — focus states, heading hierarchy intent, any motion preferences.

## Open decisions (flag for stakeholder)

- Final **plugin display name** and **.org slug** (see `2026-04-03-plugin-brainstorm-prompt.md`).
- **Pro** as one “Pro” plugin vs **separate add-on plugins** (affects Pricing page layout).
- **Docs** host: GitHub only vs dedicated docs site.

---

_End of paste-ready prompt._
