import { store, getContext, getElement } from "@wordpress/interactivity";

function rangePayload(state, name) {
    const cur = state._filters[name];
    if (cur && typeof cur === "object" && cur.__queryFilterKind === "range") {
        return {
            __queryFilterKind: "range",
            min: typeof cur.min === "string" ? cur.min : "",
            max: typeof cur.max === "string" ? cur.max : "",
        };
    }
    return { __queryFilterKind: "range", min: "", max: "" };
}

store("query-filter", {
    actions: {
        setRangePart() {
            const { ref } = getElement();
            const part = ref.getAttribute("data-query-filter-range");
            if (part !== "min" && part !== "max") {
                return;
            }
            const ctx = getContext();
            const { state } = store("query-filter");
            const name = ctx.filterName;
            const next = rangePayload(state, name);
            next[part] = ref.value;
            state._filters = {
                ...state._filters,
                [name]: next,
            };
            state.currentPage = 1;
            const block = ref.closest(".wp-block-query-filter-filter-range");
            if (block) {
                block
                    .querySelectorAll(`[data-query-filter-range="${part}"]`)
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
