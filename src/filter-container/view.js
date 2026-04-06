import { store } from '@wordpress/interactivity';

const PAGER_SELECTOR = '.wp-block-query-filter-filter-pager';
/** Wrapper from block name `query-filter/filter-checkboxes` → …-filter-checkboxes */
const CHECKBOXES_BLOCK_SELECTOR = '.wp-block-query-filter-filter-checkboxes';
/** Wrapper from `query-filter/filter-sort` */
const SORT_BLOCK_SELECTOR = '.wp-block-query-filter-filter-sort';

/** Query-string keys for shareable filter state (no qf_ prefix). */
const URL_SEARCH = 'search';
/** Not `page` / `paged` — those are WordPress query vars. */
const URL_PAGE = 'pg';
/** Between-filter mode: `and` (default) or `or`. */
const URL_FILTERS_RELATIONSHIP = 'frel';

/**
 * REST returns a full core/query render; the live DOM already has the outer
 * .wp-block-query. Keep inner markup only to avoid nested query wrappers.
 *
 * @param {string} html
 * @return {string}
 */
function stripOuterQueryBlockHtml( html ) {
	if ( typeof html !== 'string' || html === '' ) {
		return html;
	}
	const doc = new DOMParser().parseFromString( html, 'text/html' );
	const root = doc.body.firstElementChild;
	if ( ! root || ! root.classList.contains( 'wp-block-query' ) ) {
		return html;
	}
	return root.innerHTML;
}

/**
 * querySelector does not match the root element itself. When data-query-filter-query
 * is on the post template ul (first tag in some themes), the old code saw no template
 * and wiped the query block via innerHTML.
 *
 * @param {HTMLElement} container Node that has data-query-filter-query (query root or list).
 * @return {HTMLElement|null}
 */
function findPostTemplateRoot( container ) {
	if ( container.classList.contains( 'wp-block-post-template' ) ) {
		return container;
	}
	const byClass = container.querySelector( '.wp-block-post-template' );
	if ( byClass ) {
		return byClass;
	}
	const post = container.querySelector( '.wp-block-post' );
	const list = post?.closest( 'ul, ol' );
	return list instanceof HTMLElement ? list : null;
}

/**
 * Keep checkbox DOM aligned with store (reset, fetch, no bind--checked).
 */
function syncCheckboxDomFromStore() {
	const { state } = store( 'query-filter' );
	const filters = state._filters || {};
	document
		.querySelectorAll( CHECKBOXES_BLOCK_SELECTOR )
		.forEach( ( block ) => {
			let filterName = '';
			try {
				filterName =
					JSON.parse( block.getAttribute( 'data-wp-context' ) || '{}' )
						.filterName || '';
			} catch {
				return;
			}
			if ( ! filterName ) {
				return;
			}
			const selected = filters[ filterName ] || [];
			block.querySelectorAll( 'input[type="checkbox"]' ).forEach( ( input ) => {
				input.checked = selected.includes( input.value );
			} );
		} );
}

/**
 * Per-filter within-filter logic from checkbox block `data-wp-context` (authoritative on front).
 *
 * @return {Record<string, 'AND'|'OR'>}
 */
function collectCheckboxFilterLogicByName() {
	/** @type {Record<string, 'AND'|'OR'>} */
	const map = {};
	document.querySelectorAll( CHECKBOXES_BLOCK_SELECTOR ).forEach( ( block ) => {
		try {
			const ctx = JSON.parse(
				block.getAttribute( 'data-wp-context' ) || '{}'
			);
			const name = ctx.filterName;
			if ( typeof name !== 'string' || name === '' ) {
				return;
			}
			const L = String( ctx.logic || 'OR' ).toUpperCase();
			map[ name ] = L === 'AND' ? 'AND' : 'OR';
		} catch {
			// ignore
		}
	} );
	return map;
}

/**
 * REST `filters` object: values + per-filter logic.
 *
 * @return {Record<string, { values: string[], logic: string }>}
 */
function filtersPayloadFromStore() {
	const { state } = store( 'query-filter' );
	const raw = state._filters || {};
	const logicByName = collectCheckboxFilterLogicByName();
	/** @type {Record<string, { values: string[], logic: string }>} */
	const out = {};
	for ( const key of Object.keys( raw ) ) {
		const arr = raw[ key ];
		if ( ! Array.isArray( arr ) ) {
			continue;
		}
		const values = arr.filter( ( v ) => typeof v === 'string' );
		if ( values.length === 0 ) {
			continue;
		}
		const logic = logicByName[ key ] || 'OR';
		out[ key ] = { values, logic };
	}
	return out;
}

/**
 * Checkbox filter names used in the URL (same keys as REST `filters`).
 *
 * @return {Set<string>}
 */
function collectCheckboxFilterUrlKeys() {
	const names = new Set();
	document
		.querySelectorAll( CHECKBOXES_BLOCK_SELECTOR )
		.forEach( ( block ) => {
			try {
				const n = JSON.parse(
					block.getAttribute( 'data-wp-context' ) || '{}'
				).filterName;
				if ( typeof n === 'string' && n !== '' ) {
					names.add( n );
				}
			} catch {
				// ignore
			}
		} );
	return names;
}

/**
 * Sync the address bar to store: strip stale filter params, then apply current state.
 *
 * @param {boolean} stripEntireQuery When true (reset), remove all ?… params; otherwise only plugin keys.
 */
function pushFilterStateToUrl( stripEntireQuery = false ) {
	const { state } = store( 'query-filter' );
	const url = new URL( window.location.href );
	if ( stripEntireQuery ) {
		url.search = '';
		history.pushState( null, '', url );
		return;
	}
	const strip = collectCheckboxFilterUrlKeys();
	strip.add( URL_SEARCH );
	strip.add( URL_PAGE );
	strip.add( URL_FILTERS_RELATIONSHIP );
	for ( const key of strip ) {
		url.searchParams.delete( key );
	}
	for ( const key of [ ...url.searchParams.keys() ] ) {
		if ( key.startsWith( 'qf_' ) ) {
			url.searchParams.delete( key );
		}
	}
	const filters = state._filters || {};
	for ( const name of Object.keys( filters ) ) {
		if ( name === URL_SEARCH || name === URL_PAGE ) {
			continue;
		}
		const values = filters[ name ];
		if ( Array.isArray( values ) && values.length > 0 ) {
			url.searchParams.set( name, values.join( ',' ) );
		}
	}
	if ( state.search ) {
		url.searchParams.set( URL_SEARCH, state.search );
	}
	if ( state.currentPage > 1 ) {
		url.searchParams.set( URL_PAGE, String( state.currentPage ) );
	}
	const rel = String( state.filtersRelationship || 'AND' ).toUpperCase();
	if ( rel === 'OR' ) {
		url.searchParams.set( URL_FILTERS_RELATIONSHIP, 'or' );
	}
	history.pushState( null, '', url );
}

/**
 * Update posts. Never swap the pager node for server HTML: new markup is not
 * Interactivity-hydrated, so Next/Prev would stop working. Only replace the
 * post template; on full innerHTML, detach the live pager and re-append it.
 *
 * @param {HTMLElement} container Query block root (data-query-filter-query).
 * @param {string}      html      Full render_block( core/query ) HTML.
 */
function applyQueryFilterResults( container, html ) {
	const inner = stripOuterQueryBlockHtml( html );
	const wrap = document.createElement( 'div' );
	wrap.innerHTML = inner;

	const newTemplate = wrap.querySelector( '.wp-block-post-template' );
	const oldTemplate = findPostTemplateRoot( container );
	const listTargetIsContainer =
		oldTemplate !== null && oldTemplate === container;

	if ( newTemplate && oldTemplate ) {
		// Never replace the node that carries data-query-filter-query (breaks next fetch).
		if ( listTargetIsContainer ) {
			const nextNodes = Array.from( newTemplate.childNodes );
			oldTemplate.replaceChildren( ...nextNodes );
		} else {
			oldTemplate.replaceWith( newTemplate );
		}
		return;
	}

	// Server markup without a template wrapper (e.g. legacy bare <li>) or empty results:
	// refresh list items inside the live <ul> so we never replace container.innerHTML
	// (that would delete sibling filter blocks and drop the list wrapper).
	if ( oldTemplate ) {
		const newPosts = wrap.querySelectorAll( '.wp-block-post' );
		if ( newPosts.length > 0 ) {
			oldTemplate.replaceChildren( ...newPosts );
		} else {
			oldTemplate.replaceChildren();
		}
		return;
	}

	// Fallback (e.g. render_simple): preserve the same pager element (not a clone).
	const livePager = container.querySelector( PAGER_SELECTOR );
	if ( livePager ) {
		livePager.remove();
	}
	container.innerHTML = inner;
	if ( livePager && ! container.querySelector( PAGER_SELECTOR ) ) {
		container.appendChild( livePager );
	}
}

const { state } = store( 'query-filter', {
	state: {
		get activeFilters() {
			return state._filters || {};
		},
		_filters: {},
		currentPage: 1,
		orderby: 'date',
		order: 'DESC',
		sortControlValue: 'date:DESC',
		initialSortControlValue: '',
		search: '',
		loading: false,
		error: '',
		filterStates: {},
		filtersRelationship: 'AND',
		initialFiltersRelationship: 'AND',
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
			const ord = String( order ?? 'DESC' ).toUpperCase();
			state.orderby = orderby;
			state.order =
				ord === 'ASC' || ord === 'DESC' ? ord : 'DESC';
			state.sortControlValue = `${ state.orderby }:${ state.order }`;
			state.currentPage = 1;
			store( 'query-filter' ).actions.fetchResults();
		},
		setPage( page ) {
			state.currentPage = page;
			store( 'query-filter' ).actions.fetchResults();
		},
		resetAll() {
			store( 'query-filter' ).actions.clearSearchDebounce?.();
			state._filters = {};
			state.search = '';
			state.currentPage = 1;
			const sortRoot = document.querySelector(
				`${ SORT_BLOCK_SELECTOR }[data-default-sort]`
			);
			const def =
				( typeof state.initialSortControlValue === 'string' &&
				state.initialSortControlValue !== ''
					? state.initialSortControlValue
					: sortRoot?.getAttribute( 'data-default-sort' ) ) ||
				'date:DESC';
			const colon = def.indexOf( ':' );
			state.sortControlValue = def;
			state.orderby =
				colon === -1 ? def || 'date' : def.slice( 0, colon ) || 'date';
			let ord =
				colon === -1 ? 'DESC' : def.slice( colon + 1 ).toUpperCase();
			state.order =
				ord === 'ASC' || ord === 'DESC' ? ord : 'DESC';
			const initRel = String(
				state.initialFiltersRelationship || 'AND'
			).toUpperCase();
			state.filtersRelationship = initRel === 'OR' ? 'OR' : 'AND';
			syncCheckboxDomFromStore();
			pushFilterStateToUrl( true );
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
						filtersRelationship: String(
							state.filtersRelationship || 'AND'
						).toUpperCase() === 'OR'
							? 'OR'
							: 'AND',
						filters: filtersPayloadFromStore(),
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

				const container = document.querySelector(
					`[data-query-filter-query="${ state.queryId }"]`
				);
				if ( container && data.results_html ) {
					applyQueryFilterResults( container, data.results_html );
				}

				state.filterStates = data.filters || {};
				state.total = data.total;
				state.pages = data.pages;

				syncCheckboxDomFromStore();

				pushFilterStateToUrl();
			} catch ( e ) {
				state.error = 'Failed to load results. Please try again.';
			}

			state.loading = false;
		},
	},
} );

/**
 * Optional URL override for between-filter mode (`frel=or` / `frel=and`).
 */
function hydrateFiltersRelationshipFromUrl() {
	const { state } = store( 'query-filter' );
	const raw = new URL( window.location.href ).searchParams.get(
		URL_FILTERS_RELATIONSHIP
	);
	if ( raw === 'or' ) {
		state.filtersRelationship = 'OR';
		return;
	}
	if ( raw === 'and' ) {
		state.filtersRelationship = 'AND';
	}
}

hydrateFiltersRelationshipFromUrl();
