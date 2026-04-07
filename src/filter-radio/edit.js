import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import {
    PanelBody,
    SelectControl,
    TextControl,
    ToggleControl,
} from "@wordpress/components";
import { useSelect } from "@wordpress/data";

export default function Edit({ attributes, setAttributes }) {
    const { filterName, sourceType, sourceKey, label, showLabel, showCounts } =
        attributes;
    const blockProps = useBlockProps();

    const taxonomies = useSelect((select) => {
        return (select("core").getTaxonomies({ per_page: 100 }) || []).filter(
            (t) => t.visibility.publicly_queryable,
        );
    }, []);

    if (!filterName && sourceKey) {
        setAttributes({ filterName: sourceKey });
    }

    return (
        <>
            <InspectorControls>
                <PanelBody title={__("Filter Settings", "query-filter")}>
                    <TextControl
                        label={__("Filter Name", "query-filter")}
                        help={__(
                            "Must match the indexed filter (e.g. taxonomy slug).",
                            "query-filter",
                        )}
                        value={filterName}
                        onChange={(val) => setAttributes({ filterName: val })}
                    />
                    <SelectControl
                        label={__("Data Source", "query-filter")}
                        value={sourceType}
                        options={[
                            { label: "Taxonomy", value: "taxonomy" },
                            { label: "Post Meta", value: "postmeta" },
                        ]}
                        onChange={(val) => setAttributes({ sourceType: val })}
                    />
                    {sourceType === "taxonomy" && (
                        <SelectControl
                            label={__("Taxonomy", "query-filter")}
                            value={sourceKey}
                            options={(taxonomies || []).map((t) => ({
                                label: t.name,
                                value: t.slug,
                            }))}
                            onChange={(val) =>
                                setAttributes({
                                    sourceKey: val,
                                    filterName: val,
                                })
                            }
                        />
                    )}
                    {sourceType === "postmeta" && (
                        <TextControl
                            label={__("Meta Key", "query-filter")}
                            value={sourceKey}
                            onChange={(val) =>
                                setAttributes({
                                    sourceKey: val,
                                    filterName: val,
                                })
                            }
                        />
                    )}
                    <TextControl
                        label={__("Label", "query-filter")}
                        value={label}
                        onChange={(val) => setAttributes({ label: val })}
                    />
                    <ToggleControl
                        label={__("Show Label", "query-filter")}
                        checked={showLabel}
                        onChange={(val) => setAttributes({ showLabel: val })}
                    />
                    <ToggleControl
                        label={__("Show Result Counts", "query-filter")}
                        checked={showCounts}
                        onChange={(val) => setAttributes({ showCounts: val })}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                <fieldset>
                    {showLabel && label && <legend>{label}</legend>}
                    <p style={{ color: "#757575", fontStyle: "italic" }}>
                        {__("Radio filter", "query-filter")}:{" "}
                        {sourceKey || __("(not configured)", "query-filter")}
                    </p>
                </fieldset>
            </div>
        </>
    );
}
