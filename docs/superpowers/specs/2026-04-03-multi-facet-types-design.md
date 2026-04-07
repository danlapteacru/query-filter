# Multi-facet types (FacetWP-style)

**Date:** 2026-04-03  
**Status:** Implemented

## Goal

Support multiple facet UIs beyond checkboxes: **radio**, **dropdown**, **numeric range** (inputs + slider), **date range**, while reusing the index table and REST flow.

## Model

- **Discrete facets** (checkbox, radio, dropdown): same indexed rows as today (`filter_value` = term slug or meta string). Selection is still `values[]` + per-filter `logic` in REST (radio/dropdown send 0–1 values).
- **Numeric range**: one row per post per filter (`filter_value` = normalized decimal string). Query uses `CAST(filter_value AS DECIMAL(20,6))` with optional min/max bounds.
- **Date range**: one row per post per filter (`filter_value` = `Y-m-d`). Query uses string bounds (`>=` / `<=`) for ISO dates.

## REST `filters` payload

- Discrete: `{ "values": ["a","b"], "logic": "OR" }` (unchanged).
- Numeric range: `{ "min": "10", "max": "100" }` (either or both; empty omits that bound).
- Date range: `{ "after": "2024-01-01", "before": "2024-12-31" }` (either or both).

PHP normalizes each entry with a `kind`: `discrete` | `range` | `date_range`.

## Registration

Core still registers taxonomy checkbox filters. Numeric and date filters are registered via:

`do_action( 'query_filter/indexer/register_filters', $indexer );`

Example: `Query_Filter_Filter_Range` + `Query_Filter_Source_Post_Meta`, `Query_Filter_Filter_Date_Range` + `Query_Filter_Source_Post_Date`.

## Front-end

- Interactivity store `state._filters` may hold:
  - `string[]` for discrete filters.
  - `{ __queryFilterKind: 'range', min, max }` / `{ __queryFilterKind: 'dateRange', after, before }` for range facets.
- URL: discrete `name=a,b`; range `name=min..max` (omit side if open).

## Blocks

| Block | Role |
|-------|------|
| `filter-radio` | Single choice; same index as checkboxes |
| `filter-dropdown` | `<select>`; same index |
| `filter-range` | Min/max + range inputs; requires registered range filter |
| `filter-date-range` | `type="date"`; requires registered date filter |
