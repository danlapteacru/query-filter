import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, TextControl, ToggleControl } from "@wordpress/components";

export default function Edit({ attributes, setAttributes }) {
    const { filterName, label, showLabel } = attributes;
    const blockProps = useBlockProps();

    return (
        <>
            <InspectorControls>
                <PanelBody title={__("Date range filter", "query-filter")}>
                    <TextControl
                        label={__("Filter name", "query-filter")}
                        help={__(
                            "Default post_date is registered (published date). Change only if you register another Date_Range filter in PHP. Rebuild index after changing filters.",
                            "query-filter",
                        )}
                        value={filterName}
                        onChange={(v) => setAttributes({ filterName: v })}
                    />
                    <TextControl
                        label={__("Label", "query-filter")}
                        value={label}
                        onChange={(v) => setAttributes({ label: v })}
                    />
                    <ToggleControl
                        label={__("Show label", "query-filter")}
                        checked={showLabel}
                        onChange={(v) => setAttributes({ showLabel: v })}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                <p style={{ color: "#757575", fontStyle: "italic" }}>
                    {__("Date range", "query-filter")}
                    {filterName ? `: ${filterName}` : ""}
                </p>
            </div>
        </>
    );
}
