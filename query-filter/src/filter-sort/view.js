import { store } from '@wordpress/interactivity';

store( 'query-filter', {
    actions: {
        onSortChange( event ) {
            const [ orderby, order ] = event.target.value.split( ':' );
            const { state } = store( 'query-filter' );
            state.orderby = orderby;
            state.order = order;
            state.currentPage = 1;
            store( 'query-filter' ).actions.fetchResults();
        },
    },
} );
