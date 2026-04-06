import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, TextControl } from "@wordpress/components";

export default function Edit({ attributes, setAttributes }) {
    const { label } = attributes;
    return (
        <>
            <InspectorControls>
                <PanelBody title={__("Reset Settings", "query-filter")}>
                    <TextControl
                        label={__("Label", "query-filter")}
                        value={label}
                        onChange={(v) => setAttributes({ label: v })}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...useBlockProps()}>
                <button
                    className="wp-block-query-filter-reset__button"
                    disabled
                >
                    {label}
                </button>
            </div>
        </>
    );
}
