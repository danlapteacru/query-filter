import { store, getContext, getElement } from "@wordpress/interactivity";

function datePayload(state, name) {
    const cur = state._filters[name];
    if (
        cur &&
        typeof cur === "object" &&
        cur.__queryFilterKind === "dateRange"
    ) {
        return {
            __queryFilterKind: "dateRange",
            after: typeof cur.after === "string" ? cur.after : "",
            before: typeof cur.before === "string" ? cur.before : "",
        };
    }
    return { __queryFilterKind: "dateRange", after: "", before: "" };
}

store("query-filter", {
    actions: {
        setDatePart() {
            const { ref } = getElement();
            const part = ref.getAttribute("data-query-filter-date");
            if (part !== "after" && part !== "before") {
                return;
            }
            const ctx = getContext();
            const { state } = store("query-filter");
            const name = ctx.filterName;
            const next = datePayload(state, name);
            next[part] = ref.value;
            state._filters = {
                ...state._filters,
                [name]: next,
            };
            state.currentPage = 1;
            const block = ref.closest(
                ".wp-block-query-filter-filter-date-range",
            );
            if (block) {
                block
                    .querySelectorAll(`[data-query-filter-date="${part}"]`)
                    .forEach((el) => {
                        if (el !== ref) {
                            el.value = ref.value;
                        }
                    });
            }
            store("query-filter").actions.fetchResults();
        },
    },
});
