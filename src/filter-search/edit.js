import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, TextControl, ToggleControl } from "@wordpress/components";

export default function Edit({ attributes, setAttributes }) {
    const { label, showLabel, placeholder } = attributes;
    return (
        <>
            <InspectorControls>
                <PanelBody title={__("Search Settings", "query-filter")}>
                    <TextControl
                        label={__("Label", "query-filter")}
                        value={label}
                        onChange={(v) => setAttributes({ label: v })}
                    />
                    <ToggleControl
                        label={__("Show Label", "query-filter")}
                        checked={showLabel}
                        onChange={(v) => setAttributes({ showLabel: v })}
                    />
                    <TextControl
                        label={__("Placeholder", "query-filter")}
                        value={placeholder}
                        onChange={(v) => setAttributes({ placeholder: v })}
                    />
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
