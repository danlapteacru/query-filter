# Brainstorming prompt — Query Loop Index Filters (plugin)

Copy everything below the line into another chat, a doc, or a workshop agenda. Edit the bracketed bits if needed.

---

## Context for collaborators

You are helping brainstorm **positioning, naming, roadmap, and messaging** for a WordPress plugin that is **in active development**. **Public name and WordPress.org slug are decided:** **Query Loop Index Filters** / **`query-loop-index-filters`** — see [`2026-04-08-branding-query-loop-index-filters.md`](2026-04-08-branding-query-loop-index-filters.md). Internal block and REST identifiers may still use the `query-filter` prefix; that is **not** the legacy WordPress.org plugin at slug `query-filter`.

## One-sentence pitch

**Index-backed, block-first faceted filtering for the core Query Loop** — filters update results without full page reloads; the server maintains a custom DB index and returns fresh Query Loop HTML plus filter counts via REST.

## Who it is for

- Sites using **block themes** and the **Query Loop** block who want **FacetWP-style** behavior (combine filters, shareable URLs) **without** leaving the block editor for the main UX.
- Developers who are OK registering **extra discrete filters** in PHP (e.g. post meta as checkboxes) via a documented hook.

## Who it is not for (today)

- **WooCommerce archive filtering** as a first-class, supported story (unless we add it explicitly later).
- **Visual page builders** (Elementor, Bricks) as primary targets — the product is **Gutenberg / Interactivity API** centered.
- **Numeric / date range** facet blocks — those were **removed** from scope; **discrete** facets only (checkboxes, radio, dropdown).

## How it works (technical, short)

1. **Custom table** `{prefix}query_filter_index` stores rows: post + filter name + normalized value (and display metadata where needed).
2. **Indexer** runs on publish/update/delete and term changes; **Tools → Query Filter** can **rebuild** the index (with batching for slow hosts).
3. **Public taxonomies** get a **checkbox filter** registered automatically; more filters via `query_filter/indexer/register_filters`.
4. **Front end:** **Interactivity API** store `query-filter`; **Filter container** ties child blocks to a **Query Loop ID**; **POST** ` /wp-json/query-filter/v1/results` returns `results_html`, `filters` (counts/options), `total`, `pages`.
5. **Search block** can use **core WordPress** search **or** **SearchWP** (`\SWP_Query` with `post__in` limited to facet-matched IDs) when SearchWP is active — similar in spirit to **FacetWP + SearchWP**.

## Blocks (inventory)

- **Filter container** — `queryId`, AND/OR across filters (`filtersRelationship`).
- **Filter: Checkboxes, Radio, Dropdown** — discrete; filter name must align with indexer keys (taxonomy slug, etc.).
- **Filter: Search**, **Sort**, **Reset**; **Filter: Pager** (inside Query Loop).

## Differentiation (honest)

| Area | This plugin | Typical alternatives |
|------|-------------|----------------------|
| Core query | Targets **core Query Loop** + server HTML swap | Many plugins target shortcodes, widgets, or builder-specific loops |
| Speed at scale | **Precomputed index** for ID resolution | Some use heavy `WP_Query` + tax/meta on every request |
| Editor | **Blocks + Interactivity API** | Varies widely |
| Maturity | Early (version **0.1.x**), opinionated scope | FacetWP, Search & Filter Pro, etc. are mature ecosystems |

## Constraints

- **WordPress 6.7+**, **PHP 8.1+**
- Requires **`npm run build`** for block assets if installing from source (`build/` may be gitignored).
- Naming: avoid implying we are the legacy **“Query Filter”** on WordPress.org.

## Brainstorm questions (use any subset)

1. **Positioning:** Is the headline “indexed faceted filters for Query Loop” enough, or do we need a category label (e.g. “performance”, “FSE”, “headless-friendly”)?
2. **Naming:** Short brand vs descriptive SEO name? Any words to **avoid** (Facet, Loop, Query are crowded)?
3. **Audience:** Prioritize **agencies**, **in-house devs**, or **merchants** who do not code?
4. **Monetization:** Stay **100% community**, **Pro** add-on later, or **hosted** never?
5. **Roadmap:** What is the **next** most valuable gap: WooCommerce, more sources, accessibility, i18n, or documentation?
6. **Risks:** What could make a user **bounce** after install (index rebuild, block setup, naming confusion)?

## Ask of the brainstorm session

Please:

- Propose **3–5 plugin display names** and matching **slug ideas**, noting likely WordPress.org collisions.
- Suggest **tagline + 2–3 bullet value props** for a readme or landing page.
- List **objections** a skeptical developer might raise and how to answer them.
- Optionally outline a **minimal marketing story** (who / pain / outcome) in five sentences.

---

_End of paste-ready prompt._
