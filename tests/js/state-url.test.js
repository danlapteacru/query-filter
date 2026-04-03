// Extract and test URL helpers independently.
function serializeFiltersToUrl( baseUrl, filters, search, page ) {
    const url = new URL( baseUrl );
    Object.entries( filters ).forEach( ( [ name, values ] ) => {
        if ( values.length > 0 ) {
            url.searchParams.set( `qf_${ name }`, values.join( ',' ) );
        } else {
            url.searchParams.delete( `qf_${ name }` );
        }
    } );
    if ( search ) {
        url.searchParams.set( 'qf_search', search );
    } else {
        url.searchParams.delete( 'qf_search' );
    }
    if ( page > 1 ) {
        url.searchParams.set( 'qf_page', String( page ) );
    } else {
        url.searchParams.delete( 'qf_page' );
    }
    return url.toString();
}

function deserializeFiltersFromUrl( url ) {
    const parsed = new URL( url );
    const filters = {};
    let search = '';
    let page = 1;

    parsed.searchParams.forEach( ( value, key ) => {
        if ( key.startsWith( 'qf_' ) && key !== 'qf_search' && key !== 'qf_page' ) {
            const filterName = key.slice( 3 );
            filters[ filterName ] = value.split( ',' ).filter( Boolean );
        }
    } );

    search = parsed.searchParams.get( 'qf_search' ) || '';
    page = parseInt( parsed.searchParams.get( 'qf_page' ) || '1', 10 );

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
        expect( result ).toContain( 'qf_category=shoes%2Cboots' );
        expect( result ).toContain( 'qf_color=red' );
        expect( result ).not.toContain( 'qf_search' );
        expect( result ).not.toContain( 'qf_page' );
    } );

    it( 'serializes search and page', () => {
        const result = serializeFiltersToUrl(
            'https://example.com/shop',
            {},
            'running',
            3
        );
        expect( result ).toContain( 'qf_search=running' );
        expect( result ).toContain( 'qf_page=3' );
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
        expect( result ).not.toContain( 'qf_page' );
    } );
} );
