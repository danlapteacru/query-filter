import { store } from "@wordpress/interactivity";

const PAGER_SELECTOR = ".wp-block-query-filter-filter-pager";
const CHECKBOXES_BLOCK_SELECTOR = ".wp-block-query-filter-filter-checkboxes";
/** Checkboxes, radio, and dropdown share discrete `string[]` store values. */
const DISCRETE_FILTER_SELECTOR = `${CHECKBOXES_BLOCK_SELECTOR}, .wp-block-query-filter-filter-radio, .wp-block-query-filter-filter-dropdown`;
const SORT_BLOCK_SELECTOR = ".wp-block-query-filter-filter-sort";

const URL_SEARCH = "search";
const URL_PAGE = "pg";
const URL_FILTERS_RELATIONSHIP = "frel";

/**
 * @param {Element} block
 * @return {string}
 */
function readFilterNameFromBlock(block) {
    try {
        const n = JSON.parse(
            block.getAttribute("data-wp-context") || "{}",
        ).filterName;
        return typeof n === "string" ? n : "";
    } catch {
        return "";
    }
}

/**
 * @param {string} html
 * @return {string}
 */
function stripOuterQueryBlockHtml(html) {
    if (typeof html !== "string" || html === "") {
        return html;
    }
    const doc = new DOMParser().parseFromString(html, "text/html");
    const root = doc.body.firstElementChild;
    if (!root || !root.classList.contains("wp-block-query")) {
        return html;
    }
    return root.innerHTML;
}

/**
 * @param {HTMLElement} container
 * @return {HTMLElement|null}
 */
function findPostTemplateRoot(container) {
    if (container.classList.contains("wp-block-post-template")) {
        return container;
    }
    const byClass = container.querySelector(".wp-block-post-template");
    if (byClass) {
        return byClass;
    }
    const post = container.querySelector(".wp-block-post");
    const list = post?.closest("ul, ol");
    return list instanceof HTMLElement ? list : null;
}

/**
 * Sync checkbox, radio, and select UI from `state._filters`.
 */
function syncDiscreteFilterDomFromStore() {
    const { state } = store("query-filter");
    const filters = state._filters || {};
    document.querySelectorAll(DISCRETE_FILTER_SELECTOR).forEach((block) => {
        const filterName = readFilterNameFromBlock(block);
        if (!filterName) {
            return;
        }
        const raw = filters[filterName];
        const selected = Array.isArray(raw)
            ? raw.filter((v) => typeof v === "string")
            : [];
        block.querySelectorAll('input[type="checkbox"]').forEach((input) => {
            input.checked = selected.includes(input.value);
        });
        block.querySelectorAll('input[type="radio"]').forEach((input) => {
            input.checked = selected.length > 0 && selected[0] === input.value;
        });
        const sel = block.querySelector("select");
        if (sel instanceof HTMLSelectElement) {
            const v = selected.length > 0 ? selected[0] : "";
            sel.value = v;
        }
    });
}

function syncAllFilterDomFromStore() {
    syncDiscreteFilterDomFromStore();
}

/**
 * @return {Record<string, 'AND'|'OR'>}
 */
function collectCheckboxFilterLogicByName() {
    /** @type {Record<string, 'AND'|'OR'>} */
    const map = {};
    document.querySelectorAll(CHECKBOXES_BLOCK_SELECTOR).forEach((block) => {
        try {
            const ctx = JSON.parse(
                block.getAttribute("data-wp-context") || "{}",
            );
            const name = ctx.filterName;
            if (typeof name !== "string" || name === "") {
                return;
            }
            const L = String(ctx.logic || "OR").toUpperCase();
            map[name] = L === "AND" ? "AND" : "OR";
        } catch {
            // ignore
        }
    });
    return map;
}

/**
 * @return {Record<string, unknown>}
 */
function filtersPayloadFromStore() {
    const { state } = store("query-filter");
    const raw = state._filters || {};
    const logicByName = collectCheckboxFilterLogicByName();
    /** @type {Record<string, unknown>} */
    const out = {};
    for (const key of Object.keys(raw)) {
        const v = raw[key];
        if (Array.isArray(v)) {
            const values = v.filter((x) => typeof x === "string");
            if (values.length === 0) {
                continue;
            }
            const logic = logicByName[key] || "OR";
            out[key] = { values, logic };
        }
    }
    return out;
}

/**
 * @return {Set<string>}
 */
function collectDiscreteFilterUrlKeys() {
    const names = new Set();
    document.querySelectorAll(DISCRETE_FILTER_SELECTOR).forEach((block) => {
        const n = readFilterNameFromBlock(block);
        if (n) {
            names.add(n);
        }
    });
    return names;
}

/**
 * @return {Set<string>}
 */
function collectAllFilterUrlKeys() {
    return collectDiscreteFilterUrlKeys();
}

/**
 * @param {boolean} stripEntireQuery
 */
function pushFilterStateToUrl(stripEntireQuery = false) {
    const url = new URL(window.location.href);
    if (stripEntireQuery) {
        url.search = "";
        history.pushState(null, "", url);
        return;
    }
    const { state } = store("query-filter");
    const strip = collectAllFilterUrlKeys();
    strip.add(URL_SEARCH);
    strip.add(URL_PAGE);
    strip.add(URL_FILTERS_RELATIONSHIP);
    for (const key of strip) {
        url.searchParams.delete(key);
    }
    for (const key of [...url.searchParams.keys()]) {
        if (key.startsWith("qf_")) {
            url.searchParams.delete(key);
        }
    }
    const filters = state._filters || {};
    for (const name of Object.keys(filters)) {
        if (name === URL_SEARCH || name === URL_PAGE) {
            continue;
        }
        const values = filters[name];
        if (Array.isArray(values) && values.length > 0) {
            url.searchParams.set(name, values.join(","));
        }
    }
    if (state.search) {
        url.searchParams.set(URL_SEARCH, state.search);
    }
    if (state.currentPage > 1) {
        url.searchParams.set(URL_PAGE, String(state.currentPage));
    }
    const rel = String(state.filtersRelationship || "AND").toUpperCase();
    if (rel === "OR") {
        url.searchParams.set(URL_FILTERS_RELATIONSHIP, "or");
    }
    history.pushState(null, "", url);
}

/**
 * @param {HTMLElement} container
 * @param {string}      html
 */
function applyQueryFilterResults(container, html) {
    const inner = stripOuterQueryBlockHtml(html);
    const wrap = document.createElement("div");
    wrap.innerHTML = inner;

    const newTemplate = wrap.querySelector(".wp-block-post-template");
    const oldTemplate = findPostTemplateRoot(container);
    const listTargetIsContainer =
        oldTemplate !== null && oldTemplate === container;

    if (newTemplate && oldTemplate) {
        if (listTargetIsContainer) {
            const nextNodes = Array.from(newTemplate.childNodes);
            oldTemplate.replaceChildren(...nextNodes);
        } else {
            oldTemplate.replaceWith(newTemplate);
        }
        return;
    }

    if (oldTemplate) {
        const newPosts = wrap.querySelectorAll(".wp-block-post");
        if (newPosts.length > 0) {
            oldTemplate.replaceChildren(...newPosts);
        } else {
            oldTemplate.replaceChildren();
        }
        return;
    }

    const livePager = container.querySelector(PAGER_SELECTOR);
    if (livePager) {
        livePager.remove();
    }
    container.innerHTML = inner;
    if (livePager && !container.querySelector(PAGER_SELECTOR)) {
        container.appendChild(livePager);
    }
}

const { state } = store("query-filter", {
    state: {
        get activeFilters() {
            return state._filters || {};
        },
        _filters: {},
        currentPage: 1,
        orderby: "date",
        order: "DESC",
        sortControlValue: "date:DESC",
        initialSortControlValue: "",
        search: "",
        loading: false,
        error: "",
        filterStates: {},
        filtersRelationship: "AND",
        initialFiltersRelationship: "AND",
    },
    actions: {
        setFilter(filterName, values) {
            state._filters = {
                ...state._filters,
                [filterName]: values,
            };
            state.currentPage = 1;
            store("query-filter").actions.fetchResults();
        },
        setSearch(value) {
            state.search = value;
            state.currentPage = 1;
        },
        setSort(orderby, order) {
            const ord = String(order ?? "DESC").toUpperCase();
            state.orderby = orderby;
            state.order = ord === "ASC" || ord === "DESC" ? ord : "DESC";
            state.sortControlValue = `${state.orderby}:${state.order}`;
            state.currentPage = 1;
            store("query-filter").actions.fetchResults();
        },
        setPage(page) {
            state.currentPage = page;
            store("query-filter").actions.fetchResults();
        },
        resetAll() {
            store("query-filter").actions.clearSearchDebounce?.();
            state._filters = {};
            state.search = "";
            state.currentPage = 1;
            const sortRoot = document.querySelector(
                `${SORT_BLOCK_SELECTOR}[data-default-sort]`,
            );
            const def =
                (typeof state.initialSortControlValue === "string" &&
                state.initialSortControlValue !== ""
                    ? state.initialSortControlValue
                    : sortRoot?.getAttribute("data-default-sort")) ||
                "date:DESC";
            const colon = def.indexOf(":");
            state.sortControlValue = def;
            state.orderby =
                colon === -1 ? def || "date" : def.slice(0, colon) || "date";
            const ord =
                colon === -1 ? "DESC" : def.slice(colon + 1).toUpperCase();
            state.order = ord === "ASC" || ord === "DESC" ? ord : "DESC";
            const initRel = String(
                state.initialFiltersRelationship || "AND",
            ).toUpperCase();
            state.filtersRelationship = initRel === "OR" ? "OR" : "AND";
            syncAllFilterDomFromStore();
            pushFilterStateToUrl(true);
            store("query-filter").actions.fetchResults();
        },
        *fetchResults() {
            state.loading = true;
            state.error = "";

            try {
                const response = yield fetch(state.restUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-WP-Nonce": state.restNonce,
                    },
                    body: JSON.stringify({
                        queryId: state.queryId,
                        pageId: state.pageId,
                        filtersRelationship:
                            String(
                                state.filtersRelationship || "AND",
                            ).toUpperCase() === "OR"
                                ? "OR"
                                : "AND",
                        filters: filtersPayloadFromStore(),
                        page: state.currentPage,
                        orderby: state.orderby,
                        order: state.order,
                        search: state.search,
                    }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = yield response.json();

                const container = document.querySelector(
                    `[data-query-filter-query="${state.queryId}"]`,
                );
                if (container && data.results_html) {
                    applyQueryFilterResults(container, data.results_html);
                }

                state.filterStates = data.filters || {};
                state.total = data.total;
                state.pages = data.pages;

                syncAllFilterDomFromStore();

                pushFilterStateToUrl();
            } catch (e) {
                state.error = "Failed to load results. Please try again.";
            }

            state.loading = false;
        },
    },
});

function hydrateFiltersRelationshipFromUrl() {
    const { state: filterState } = store("query-filter");
    const raw = new URL(window.location.href).searchParams.get(
        URL_FILTERS_RELATIONSHIP,
    );
    if (raw === "or") {
        filterState.filtersRelationship = "OR";
        return;
    }
    if (raw === "and") {
        filterState.filtersRelationship = "AND";
    }
}

function hydrateFiltersFromUrl() {
    const url = new URL(window.location.href);
    const { state: s } = store("query-filter");
    const next = { ...s._filters };

    collectDiscreteFilterUrlKeys().forEach((name) => {
        const raw = url.searchParams.get(name);
        if (raw === null || raw === "") {
            return;
        }
        next[name] = raw.split(",").filter(Boolean);
    });

    s._filters = next;
}

function urlHasFacetParams() {
    const url = new URL(window.location.href);
    for (const k of collectAllFilterUrlKeys()) {
        const v = url.searchParams.get(k);
        if (v !== null && v !== "") {
            return true;
        }
    }
    const s = url.searchParams.get(URL_SEARCH);
    return typeof s === "string" && s !== "";
}

hydrateFiltersRelationshipFromUrl();
hydrateFiltersFromUrl();
if (urlHasFacetParams()) {
    queueMicrotask(() => {
        store("query-filter").actions.fetchResults();
    });
}
