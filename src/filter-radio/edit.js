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
                <PanelBody title={__("Filter Settings", "query-loop-index-filters")}>
                    <TextControl
                        label={__("Filter Name", "query-loop-index-filters")}
                        help={__(
                            "Indexed key: for taxonomy, use the taxonomy slug (match Source key). Custom labels break options unless Source key is set.",
                            "query-loop-index-filters",
                        )}
                        value={filterName}
                        onChange={(val) => setAttributes({ filterName: val })}
                    />
                    <SelectControl
                        label={__("Data Source", "query-loop-index-filters")}
                        value={sourceType}
                        options={[
                            { label: "Taxonomy", value: "taxonomy" },
                            { label: "Post Meta", value: "postmeta" },
                        ]}
                        onChange={(val) => setAttributes({ sourceType: val })}
                    />
                    {sourceType === "taxonomy" && (
                        <SelectControl
                            label={__("Taxonomy", "query-loop-index-filters")}
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
                            label={__("Meta Key", "query-loop-index-filters")}
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
                        label={__("Label", "query-loop-index-filters")}
                        value={label}
                        onChange={(val) => setAttributes({ label: val })}
                    />
                    <ToggleControl
                        label={__("Show Label", "query-loop-index-filters")}
                        checked={showLabel}
                        onChange={(val) => setAttributes({ showLabel: val })}
                    />
                    <ToggleControl
                        label={__("Show Result Counts", "query-loop-index-filters")}
                        checked={showCounts}
                        onChange={(val) => setAttributes({ showCounts: val })}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                <fieldset>
                    {showLabel && label && <legend>{label}</legend>}
                    <p style={{ color: "#757575", fontStyle: "italic" }}>
                        {__("Radio filter", "query-loop-index-filters")}:{" "}
                        {sourceKey || __("(not configured)", "query-loop-index-filters")}
                    </p>
                </fieldset>
            </div>
        </>
    );
}
