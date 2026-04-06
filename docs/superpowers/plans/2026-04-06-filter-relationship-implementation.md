# Implementation plan: filter relationship (container + per-filter logic)

**Spec:** [2026-04-06-filter-relationship-design.md](../specs/2026-04-06-filter-relationship-design.md)

## Goals

1. **Between filters:** `filtersRelationship` on `query-filter/filter-container` (`AND` | `OR`, default `AND`).
2. **Within filter:** Honor checkbox block `logic` in REST (fix hardcoded `OR` in REST).
3. **Request:** `Query_Filter_Request` parses `filtersRelationship` and structured `filters` (with legacy `name → string[]`).
4. **Engine:** `get_post_ids( $active_filters, $between_filters_logic )` — intersect vs union.
5. **Frontend:** Interactivity state, `fetchResults` body, URL key `frel`, hydrate `frel` from URL on load.
6. **Tests:** PHPUnit (Request + QueryEngine union), Jest (`frel` in URL helpers if extended).

## Steps

| Step | Task |
|------|------|
| 1 | `class-query-engine.php` — second param `between_filters_logic`; union branch. |
| 2 | `class-request.php` — `filters_relationship`; parse `filters` object or legacy array; normalize logic. |
| 3 | `class-rest-controller.php` — pass per-filter logic; call engine with `filters_relationship`. |
| 4 | `block.json` + `edit.js` + `render.php` for filter-container. |
| 5 | `filter-container/view.js` — state, payload builder (logic from DOM), URL `frel`, hydrate. |
| 6 | `RequestTest.php` + `QueryEngineTest.php` (+ Jest if needed). |
| 7 | `npm run build` + `composer test` / `phpunit` + `npm run test:unit`. |

## Verification

- Legacy POST body with `filters: { cat: ["a"] }` still resolves; logic defaults to `OR`.
- New body with `filtersRelationship: "OR"` and two filters returns union.
- Editor: container inspector shows combine mode; checkbox “match any/all” affects REST.
