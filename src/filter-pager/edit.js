import { __ } from "@wordpress/i18n";
import { useBlockProps } from "@wordpress/block-editor";

export default function Edit() {
    return (
        <div {...useBlockProps()}>
            <p style={{ color: "#757575", fontStyle: "italic" }}>
                {__(
                    "Pagination (rendered on frontend)",
                    "query-loop-index-filters",
                )}
            </p>
        </div>
    );
}
