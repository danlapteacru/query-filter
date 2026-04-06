const URL_SEARCH = 'search';
const URL_PAGE = 'pg';

// Extract and test URL helpers independently.
function serializeFiltersToUrl( baseUrl, filters, search, page ) {
    const url = new URL( baseUrl );
    Object.entries( filters ).forEach( ( [ name, values ] ) => {
        if ( name === URL_SEARCH || name === URL_PAGE ) {
            return;
        }
        if ( values.length > 0 ) {
            url.searchParams.set( name, values.join( ',' ) );
        } else {
            url.searchParams.delete( name );
        }
    } );
    if ( search ) {
        url.searchParams.set( URL_SEARCH, search );
    } else {
        url.searchParams.delete( URL_SEARCH );
    }
    if ( page > 1 ) {
        url.searchParams.set( URL_PAGE, String( page ) );
    } else {
        url.searchParams.delete( URL_PAGE );
    }
    return url.toString();
}

function deserializeFiltersFromUrl( url ) {
    const parsed = new URL( url );
    const filters = {};
    let search = '';
    let page = 1;

    parsed.searchParams.forEach( ( value, key ) => {
        if ( key === URL_SEARCH ) {
            search = value;
            return;
        }
        if ( key === URL_PAGE ) {
            page = parseInt( value || '1', 10 );
            return;
        }
        filters[ key ] = value.split( ',' ).filter( Boolean );
    } );

    if ( Number.isNaN( page ) || page < 1 ) {
        page = 1;
    }

    return { filters, search, page };
}

describe( 'URL state serialization', () => {
    it( 'serializes filters to URL params', () => {
        const result = serializeFiltersToUrl(
            'https://example.com/shop',
            { category: [ 'shoes', 'boots' ], color: [ 'red' ] },
            '',
            1
        );
        expect( result ).toContain( 'category=shoes%2Cboots' );
        expect( result ).toContain( 'color=red' );
        expect( result ).not.toContain( 'search=' );
        expect( result ).not.toContain( 'pg=' );
    } );

    it( 'serializes search and page', () => {
        const result = serializeFiltersToUrl(
            'https://example.com/shop',
            {},
            'running',
            3
        );
        expect( result ).toContain( 'search=running' );
        expect( result ).toContain( 'pg=3' );
    } );

    it( 'round-trips filter state', () => {
        const original = { category: [ 'shoes' ], color: [ 'red', 'blue' ] };
        const url = serializeFiltersToUrl( 'https://example.com/', original, 'test', 2 );
        const result = deserializeFiltersFromUrl( url );
        expect( result.filters ).toEqual( original );
        expect( result.search ).toBe( 'test' );
        expect( result.page ).toBe( 2 );
    } );

    it( 'omits page 1 from URL', () => {
        const result = serializeFiltersToUrl( 'https://example.com/', {}, '', 1 );
        expect( result ).not.toContain( 'pg=' );
    } );
} );
