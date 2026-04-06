import { store, getContext, getElement } from '@wordpress/interactivity';

store( 'query-filter', {
    actions: {
        toggleCheckbox() {
            const ctx = getContext();
            const { ref } = getElement();
            const value = ref.value;
            const checked = ref.checked;
            const { state } = store( 'query-filter' );
            const name = ctx.filterName;
            const prev = [ ...( state._filters[ name ] || [] ) ];
            let next;
            if ( checked ) {
                next = prev.includes( value ) ? prev : [ ...prev, value ];
            } else {
                next = prev.filter( ( v ) => v !== value );
            }
            state._filters = {
                ...state._filters,
                [ name ]: next,
            };
            state.currentPage = 1;
            store( 'query-filter' ).actions.fetchResults();
        },
    },
} );
