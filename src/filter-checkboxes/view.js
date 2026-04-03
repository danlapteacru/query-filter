import { store, getContext, getElement } from '@wordpress/interactivity';

store( 'query-filter', {
    actions: {
        toggleCheckbox() {
            const ctx = getContext();
            const { ref } = getElement();
            const value = ref.value;
            const selected = [ ...( ctx.selected || [] ) ];

            const idx = selected.indexOf( value );
            if ( idx > -1 ) {
                selected.splice( idx, 1 );
            } else {
                selected.push( value );
            }

            ctx.selected = selected;

            const { state } = store( 'query-filter' );
            state._filters = {
                ...state._filters,
                [ ctx.filterName ]: selected,
            };
            state.currentPage = 1;
            store( 'query-filter' ).actions.fetchResults();
        },
    },
} );
