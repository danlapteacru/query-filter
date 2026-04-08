import { __ } from "@wordpress/i18n";
import {
    useBlockProps,
    InspectorControls,
    InnerBlocks,
} from "@wordpress/block-editor";
import { PanelBody, TextControl, SelectControl } from "@wordpress/components";

export default function Edit({ attributes, setAttributes }) {
    const { queryId, filtersRelationship } = attributes;
    const blockProps = useBlockProps();

    return (
        <>
            <InspectorControls>
                <PanelBody
                    title={__("Filter Settings", "query-loop-index-filters")}
                >
                    <TextControl
                        label={__("Query Loop ID", "query-loop-index-filters")}
                        help={__(
                            "The queryId of the Query Loop block to filter.",
                            "query-loop-index-filters",
                        )}
                        type="number"
                        value={queryId || ""}
                        onChange={(val) =>
                            setAttributes({ queryId: parseInt(val, 10) || 0 })
                        }
                    />
                    <SelectControl
                        label={__(
                            "Combine filters",
                            "query-loop-index-filters",
                        )}
                        help={__(
                            "How multiple checkbox filters work together: match all (AND) or match any (OR).",
                            "query-loop-index-filters",
                        )}
                        value={filtersRelationship === "OR" ? "OR" : "AND"}
                        options={[
                            {
                                label: __(
                                    "Match all filters (AND)",
                                    "query-loop-index-filters",
                                ),
                                value: "AND",
                            },
                            {
                                label: __(
                                    "Match any filter (OR)",
                                    "query-loop-index-filters",
                                ),
                                value: "OR",
                            },
                        ]}
                        onChange={(val) =>
                            setAttributes({
                                filtersRelationship:
                                    val === "OR" ? "OR" : "AND",
                            })
                        }
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                <InnerBlocks
                    allowedBlocks={[
                        "query-filter/filter-checkboxes",
                        "query-filter/filter-search",
                        "query-filter/filter-sort",
                        "query-filter/filter-reset",
                    ]}
                    template={[]}
                />
            </div>
        </>
    );
}
