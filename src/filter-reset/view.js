import { store } from "@wordpress/interactivity";

store("query-filter", {
    state: {
        get hasNoActiveFilters() {
            const { state } = store("query-filter");
            const hasFilters = Object.values(state._filters || {}).some((v) => {
                if (Array.isArray(v)) {
                    return v.length > 0;
                }
                if (v && typeof v === "object") {
                    if (v.__queryFilterKind === "range") {
                        return (
                            (typeof v.min === "string" && v.min !== "") ||
                            (typeof v.max === "string" && v.max !== "")
                        );
                    }
                    if (v.__queryFilterKind === "dateRange") {
                        return (
                            (typeof v.after === "string" && v.after !== "") ||
                            (typeof v.before === "string" && v.before !== "")
                        );
                    }
                }
                return false;
            });
            const sortDirty =
                typeof state.initialSortControlValue === "string" &&
                state.sortControlValue !== state.initialSortControlValue;
            return !hasFilters && !state.search && !sortDirty;
        },
    },
});
