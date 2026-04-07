import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, TextControl, ToggleControl } from "@wordpress/components";

export default function Edit({ attributes, setAttributes }) {
    const { filterName, label, showLabel, step, inputMin, inputMax } =
        attributes;
    const blockProps = useBlockProps();

    return (
        <>
            <InspectorControls>
                <PanelBody title={__("Range filter", "query-filter")}>
                    <TextControl
                        label={__("Filter name", "query-filter")}
                        help={__(
                            "Must match a registered Query_Filter_Filter_Range (see README).",
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
                    <TextControl
                        label={__("Step", "query-filter")}
                        type="number"
                        value={step}
                        onChange={(v) =>
                            setAttributes({ step: parseFloat(v) || 1 })
                        }
                    />
                    <TextControl
                        label={__("Slider min (optional)", "query-filter")}
                        value={inputMin}
                        onChange={(v) => setAttributes({ inputMin: v })}
                    />
                    <TextControl
                        label={__("Slider max (optional)", "query-filter")}
                        value={inputMax}
                        onChange={(v) => setAttributes({ inputMax: v })}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                <p style={{ color: "#757575", fontStyle: "italic" }}>
                    {__("Number range / slider", "query-filter")}
                    {filterName ? `: ${filterName}` : ""}
                </p>
            </div>
        </>
    );
}
