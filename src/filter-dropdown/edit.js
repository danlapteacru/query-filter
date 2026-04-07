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
    const {
        filterName,
        sourceType,
        sourceKey,
        label,
        showLabel,
        showCounts,
        placeholder,
    } = attributes;
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
                    <TextControl
                        label={__("Placeholder", "query-filter")}
                        value={placeholder}
                        onChange={(val) => setAttributes({ placeholder: val })}
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
                {showLabel && label ? (
                    <span className="wp-block-query-filter__label">
                        {label}{" "}
                    </span>
                ) : null}
                <select
                    disabled
                    aria-label={label || __("Dropdown filter", "query-filter")}
                >
                    <option>
                        {placeholder || __("(Dropdown filter)", "query-filter")}
                    </option>
                </select>
            </div>
        </>
    );
}
