import { store, getContext } from '@wordpress/interactivity';

const { state } = store( 'query-filter', {
    state: {
        get activeFilters() {
            return state._filters || {};
        },
        _filters: {},
        currentPage: 1,
        orderby: 'date',
        order: 'DESC',
        search: '',
        loading: false,
        error: '',
        total: 0,
        pages: 0,
        filterStates: {},
    },
    actions: {
        setFilter( filterName, values ) {
            state._filters = {
                ...state._filters,
                [ filterName ]: values,
            };
            state.currentPage = 1;
            store( 'query-filter' ).actions.fetchResults();
        },
        setSearch( value ) {
            state.search = value;
            state.currentPage = 1;
        },
        setSort( orderby, order ) {
            state.orderby = orderby;
            state.order = order;
            state.currentPage = 1;
            store( 'query-filter' ).actions.fetchResults();
        },
        setPage( page ) {
            state.currentPage = page;
            store( 'query-filter' ).actions.fetchResults();
        },
        resetAll() {
            state._filters = {};
            state.search = '';
            state.orderby = 'date';
            state.order = 'DESC';
            state.currentPage = 1;
            store( 'query-filter' ).actions.fetchResults();
        },
        *fetchResults() {
            state.loading = true;
            state.error = '';

            try {
                const response = yield fetch( state.restUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': state.restNonce,
                    },
                    body: JSON.stringify( {
                        queryId: state.queryId,
                        pageId: state.pageId,
                        filters: state.activeFilters,
                        page: state.currentPage,
                        orderby: state.orderby,
                        order: state.order,
                        search: state.search,
                    } ),
                } );

                if ( ! response.ok ) {
                    throw new Error( `HTTP ${ response.status }` );
                }

                const data = yield response.json();

                // Replace Query Loop content.
                const container = document.querySelector(
                    `[data-query-filter-query="${ state.queryId }"]`
                );
                if ( container ) {
                    container.innerHTML = data.results_html;
                }

                state.filterStates = data.filters || {};
                state.total = data.total;
                state.pages = data.pages;

                // Update URL.
                const url = new URL( window.location );
                Object.entries( state.activeFilters ).forEach(
                    ( [ name, values ] ) => {
                        if ( values.length > 0 ) {
                            url.searchParams.set(
                                `qf_${ name }`,
                                values.join( ',' )
                            );
                        } else {
                            url.searchParams.delete( `qf_${ name }` );
                        }
                    }
                );
                if ( state.search ) {
                    url.searchParams.set( 'qf_search', state.search );
                } else {
                    url.searchParams.delete( 'qf_search' );
                }
                if ( state.currentPage > 1 ) {
                    url.searchParams.set( 'qf_page', state.currentPage );
                } else {
                    url.searchParams.delete( 'qf_page' );
                }
                history.pushState( null, '', url );
            } catch ( e ) {
                state.error = 'Failed to load results. Please try again.';
            }

            state.loading = false;
        },
    },
} );
