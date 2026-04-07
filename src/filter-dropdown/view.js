import { store, getContext, getElement } from "@wordpress/interactivity";

store("query-filter", {
    actions: {
        changeDropdown() {
            const ctx = getContext();
            const { ref } = getElement();
            const { state } = store("query-filter");
            const name = ctx.filterName;
            const value = ref.value;
            state._filters = {
                ...state._filters,
                [name]: value === "" ? [] : [value],
            };
            state.currentPage = 1;
            store("query-filter").actions.fetchResults();
        },
    },
});
