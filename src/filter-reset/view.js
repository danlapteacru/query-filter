import { store } from "@wordpress/interactivity";

store("query-filter", {
    state: {
        get hasNoActiveFilters() {
            const { state } = store("query-filter");
            const hasFilters = Object.values(state._filters || {}).some(
                (v) => v.length > 0,
            );
            const sortDirty =
                typeof state.initialSortControlValue === "string" &&
                state.sortControlValue !== state.initialSortControlValue;
            return !hasFilters && !state.search && !sortDirty;
        },
    },
});
