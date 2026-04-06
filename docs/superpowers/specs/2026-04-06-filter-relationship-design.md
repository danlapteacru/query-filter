# Filter relationship: container (between filters) + checkboxes (within filter)

**Date:** 2026-04-06  
**Status:** Approved (user confirmed 2026-04-06)

---

## Problem

Editors need two independent logic controls:

1. **Between filters** — How multiple **checkbox filter blocks** (different dimensions, e.g. category vs. color) combine.
2. **Within a filter** — How multiple **checked values** for a **single** checkbox filter combine (e.g. “Shoes OR Boots” vs “Shoes AND Red” on one taxonomy).

`Query_Filter_Query_Engine` already implements **within-filter** `OR` / `AND` per filter config. It **always intersects** result sets across different filter names (**AND between filters**). The REST layer currently passes **`'logic' => 'OR'`** for every checkbox and accepts **`filters` as `name → string[]`**, so **per-block checkbox `logic` is not applied** on the live request path.

---

## Decisions

| Concern | Decision |
|--------|----------|
| Between-filter control | New attribute on **`query-filter/filter-container`**: `filtersRelationship`, enum **`AND`** \| **`OR`**, default **`AND`** (preserves current behavior). |
| Within-filter control | Existing attribute on **`query-filter/filter-checkboxes`**: `logic`, enum **`OR`** \| **`AND`** — must be **honored end-to-end** (PHP render context, client payload, REST, engine). |
| REST payload | Request includes **`filtersRelationship`** (string, normalized to `AND` \| `OR`). **`filters`** carries **per-filter** `values` and **`logic`** (not only a flat string array). |
| Legacy payloads | Clients that still send **`filters` as `Record<string, string[]>`** are supported: treat each entry as **`values`** with **`logic` default `OR`**. |
| Engine | **`get_post_ids()`** (or equivalent) accepts **between-filters** mode: **`AND`** → intersect non-empty per-filter ID sets (current); **`OR`** → union (unique IDs) of those sets. |
| URL share state | Persist container mode with a **short** query key, e.g. **`frel`**, values **`and`** \| **`or`**. Omit or `and` when default **`AND`**. Hydrate on load with the same rules as other filter URL state. |
| Search / sort / pager | Unchanged ordering: resolve checkbox filter post IDs with the two-level logic, then apply **search**, **sort**, and **pagination** on that resolved set as today. |
| Non-checkbox filters | **Search**, **sort**, **pager**, **reset** do not participate in “between filters” OR/AND; that mode applies only to **indexed checkbox dimensions** present in the request. |

---

## Semantics

### Within one checkbox filter (unchanged engine behavior)

- **`OR`**: post matches if it has **any** of the selected values for that filter name (subject to source semantics).
- **`AND`**: post matches only if it has **all** selected values for that filter name (engine uses `HAVING COUNT(DISTINCT filter_value) = N`).

### Between checkbox filters

- **`AND` (default)**: post must match **every** active checkbox filter’s constraint (intersection of per-filter ID sets).
- **`OR`**: post matches if it satisfies **at least one** active checkbox filter’s constraint (union of per-filter ID sets).

### Edge cases

- **Zero** checkbox filters with non-empty values: engine behavior stays **all indexed post IDs** (or current empty-handling), unchanged.
- **One** active checkbox filter: **`filtersRelationship`** has no observable effect (single set).
- **Empty values** for a filter name: that filter is skipped when building sets (same as today); do not treat as “match nothing” unless that is already the product rule for the last remaining filter.

---

## REST request shape (normative for new clients)

Top-level fields (existing unless noted):

- `queryId`, `pageId`, `page`, `orderby`, `order`, `search` — unchanged.
- **`filtersRelationship`**: optional string; if missing, **`AND`**.

**`filters`** — preferred shape:

```json
{
  "category": { "values": ["shoes", "boots"], "logic": "OR" },
  "color": { "values": ["red"], "logic": "OR" }
}
```

**Legacy shape** (must remain valid):

```json
{
  "category": ["shoes", "boots"]
}
```

Parsed as: `values` = sanitized strings, `logic` = **`OR`**.

Invalid `logic` values normalize to **`OR`**. Unknown keys in filter objects are ignored.

---

## Block editor UX

- **Filter container**: Inspector control (e.g. **`SelectControl`**) labeled clearly, e.g. “Combine filters” with help text: **“Match all filters (AND)”** vs **“Match any filter (OR)”**.
- **Filter checkboxes**: Keep existing **“Match: Any / All”** (or equivalent) for **values within this filter**.

Copy should avoid ambiguous “AND/OR” without saying **all vs any** or **within this filter vs across filters**.

---

## Implementation touchpoints (reference)

| Area | Change |
|------|--------|
| `src/filter-container/block.json` | Add `filtersRelationship` attribute. |
| `src/filter-container/edit.js` | Inspector for `filtersRelationship`. |
| `src/filter-container/render.php` | Pass initial state / `data-wp-context` for Interactivity. |
| `src/filter-container/view.js` | Store field, include in `fetchResults` body; URL `frel`; hydrate from URL. |
| `includes/class-request.php` | Parse `filtersRelationship`; parse `filters` as object-per-key or legacy array. |
| `includes/class-rest-controller.php` | Build `active_filters` with **per-filter `logic`** from request; pass **between-filters** mode into engine. |
| `includes/class-query-engine.php` | Parameter for between-filters **AND** vs **OR** (intersect vs union). |
| Tests | PHPUnit: engine union vs intersect; `RequestTest` for new and legacy payloads; Jest for URL round-trip of `frel` if applicable. |

---

## Out of scope

- Nested filter groups or arbitrary boolean expressions (e.g. `(A AND B) OR C`).
- Applying **OR** across non-checkbox filter types in one expression.
- Changing index schema or checkbox indexing rules.

---

## Spec self-review

- **Placeholders:** None; enums and keys are fixed.
- **Consistency:** Container = between filters; checkboxes = within filter; REST and URL align with attributes.
- **Scope:** Single feature slice suitable for one implementation plan.
- **Ambiguity:** Search/sort interaction explicitly chained after ID resolution; legacy `filters` shape defined.
