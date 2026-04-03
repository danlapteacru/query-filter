import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, InnerBlocks } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
    const { queryId } = attributes;
    const blockProps = useBlockProps();

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Filter Settings', 'query-filter' ) }>
                    <TextControl
                        label={ __( 'Query Loop ID', 'query-filter' ) }
                        help={ __( 'The queryId of the Query Loop block to filter.', 'query-filter' ) }
                        type="number"
                        value={ queryId || '' }
                        onChange={ ( val ) => setAttributes( { queryId: parseInt( val, 10 ) || 0 } ) }
                    />
                </PanelBody>
            </InspectorControls>
            <div { ...blockProps }>
                <InnerBlocks
                    allowedBlocks={ [
                        'query-filter/filter-checkboxes',
                        'query-filter/filter-search',
                        'query-filter/filter-sort',
                        'query-filter/filter-reset',
                    ] }
                    template={ [] }
                />
            </div>
        </>
    );
}
