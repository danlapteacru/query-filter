import { store } from '@wordpress/interactivity';

store( 'query-filter', {
    state: {
        get pagerSummary() {
            const { state } = store( 'query-filter' );
            if ( state.total === 0 ) return '';
            const perPage = state.perPage || 10;
            const start = ( state.currentPage - 1 ) * perPage + 1;
            const end = Math.min( state.currentPage * perPage, state.total );
            return `Showing ${ start }-${ end } of ${ state.total }`;
        },
        get isFirstPage() {
            return store( 'query-filter' ).state.currentPage <= 1;
        },
        get isLastPage() {
            const { state } = store( 'query-filter' );
            return state.currentPage >= state.pages;
        },
    },
    actions: {
        prevPage() {
            const { state } = store( 'query-filter' );
            if ( state.currentPage > 1 ) {
                state.currentPage--;
                store( 'query-filter' ).actions.fetchResults();
            }
        },
        nextPage() {
            const { state } = store( 'query-filter' );
            if ( state.currentPage < state.pages ) {
                state.currentPage++;
                store( 'query-filter' ).actions.fetchResults();
            }
        },
    },
} );
