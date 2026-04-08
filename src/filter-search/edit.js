import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import {
    PanelBody,
    TextControl,
    ToggleControl,
    SelectControl,
} from "@wordpress/components";

export default function Edit({ attributes, setAttributes }) {
    const { label, showLabel, placeholder, searchSource, searchwpEngine } =
        attributes;
    return (
        <>
            <InspectorControls>
                <PanelBody title={__("Search Settings", "query-loop-index-filters")}>
                    <TextControl
                        label={__("Label", "query-loop-index-filters")}
                        value={label}
                        onChange={(v) => setAttributes({ label: v })}
                    />
                    <ToggleControl
                        label={__("Show Label", "query-loop-index-filters")}
                        checked={showLabel}
                        onChange={(v) => setAttributes({ showLabel: v })}
                    />
                    <TextControl
                        label={__("Placeholder", "query-loop-index-filters")}
                        value={placeholder}
                        onChange={(v) => setAttributes({ placeholder: v })}
                    />
                    <SelectControl
                        label={__("Search source", "query-loop-index-filters")}
                        help={__(
                            "Use SearchWP when the plugin is active; matches FacetWP-style SearchWP integration (search within filtered results).",
                            "query-loop-index-filters",
                        )}
                        value={
                            searchSource === "searchwp"
                                ? "searchwp"
                                : "wordpress"
                        }
                        options={[
                            {
                                label: __(
                                    "WordPress (default)",
                                    "query-loop-index-filters",
                                ),
                                value: "wordpress",
                            },
                            {
                                label: __("SearchWP", "query-loop-index-filters"),
                                value: "searchwp",
                            },
                        ]}
                        onChange={(v) =>
                            setAttributes({
                                searchSource:
                                    v === "searchwp" ? "searchwp" : "wordpress",
                            })
                        }
                    />
                    {searchSource === "searchwp" && (
                        <TextControl
                            label={__("SearchWP engine", "query-loop-index-filters")}
                            help={__(
                                "Engine name from SearchWP settings (usually default).",
                                "query-loop-index-filters",
                            )}
                            value={searchwpEngine || "default"}
                            onChange={(v) =>
                                setAttributes({
                                    searchwpEngine: v || "default",
                                })
                            }
                        />
                    )}
                </PanelBody>
            </InspectorControls>
            <div {...useBlockProps()}>
                {showLabel && (
                    <label
                        className="wp-block-query-filter__label"
                        htmlFor="query-filter-search-preview"
                    >
                        {label}
                    </label>
                )}
                <input
                    id="query-filter-search-preview"
                    type="search"
                    placeholder={placeholder}
                    disabled
                />
            </div>
        </>
    );
}
