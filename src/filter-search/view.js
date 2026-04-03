import { store } from '@wordpress/interactivity';

let debounceTimer;

store( 'query-filter', {
    actions: {
        onSearchInput( event ) {
            const { state } = store( 'query-filter' );
            state.search = event.target.value;

            clearTimeout( debounceTimer );
            debounceTimer = setTimeout( () => {
                state.currentPage = 1;
                store( 'query-filter' ).actions.fetchResults();
            }, 300 );
        },
    },
} );
