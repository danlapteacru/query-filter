# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]

### Removed

- **Number range** and **date range** filter blocks (`query-filter/filter-range`, `query-filter/filter-date-range`), plus server support for numeric and date range facets. The plugin now supports discrete filters only (checkboxes, radio, dropdown), with `query_filter/indexer/register_filters` used for extra `Query_Filter_Filter_Checkboxes` sources (for example post meta). See commit `40b458b` on `main` for the full change set.
