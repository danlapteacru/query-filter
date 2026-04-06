import { store } from '@wordpress/interactivity';

function applySortValue( raw ) {
    const v = String( raw ?? '' );
    const i = v.indexOf( ':' );
    const orderby = ( i === -1 ? v : v.slice( 0, i ) ) || 'date';
    let order = i === -1 ? 'DESC' : v.slice( i + 1 ).toUpperCase();
    if ( order !== 'ASC' && order !== 'DESC' ) {
        order = 'DESC';
    }
    const { state } = store( 'query-filter' );
    state.orderby = orderby;
    state.order = order;
    state.sortControlValue = `${ orderby }:${ order }`;
}

store( 'query-filter', {
    actions: {
        onSortChange( event ) {
            applySortValue( event.target.value );
            const { state } = store( 'query-filter' );
            state.currentPage = 1;
            store( 'query-filter' ).actions.fetchResults();
        },
    },
} );
