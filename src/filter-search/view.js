import { store } from '@wordpress/interactivity';

let searchDebounceTimer;

store( 'query-filter', {
    actions: {
        onSearchInput( event ) {
            const { state } = store( 'query-filter' );
            state.search = event.target.value;

            clearTimeout( searchDebounceTimer );
            searchDebounceTimer = setTimeout( () => {
                state.currentPage = 1;
                store( 'query-filter' ).actions.fetchResults();
            }, 300 );
        },
        clearSearchDebounce() {
            clearTimeout( searchDebounceTimer );
        },
    },
} );
