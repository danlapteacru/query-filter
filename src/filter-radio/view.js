import { store, getContext, getElement } from "@wordpress/interactivity";

store("query-filter", {
    actions: {
        selectRadio() {
            const { ref } = getElement();
            if (!ref.checked) {
                return;
            }
            const ctx = getContext();
            const { state } = store("query-filter");
            const name = ctx.filterName;
            const value = ref.value;
            state._filters = {
                ...state._filters,
                [name]: [value],
            };
            state.currentPage = 1;
            store("query-filter").actions.fetchResults();
        },
    },
});
