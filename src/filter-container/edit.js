import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, InnerBlocks } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
    const { queryId, filtersRelationship } = attributes;
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
                    <SelectControl
                        label={ __( 'Combine filters', 'query-filter' ) }
                        help={ __(
                            'How multiple checkbox filters work together: match all (AND) or match any (OR).',
                            'query-filter'
                        ) }
                        value={ filtersRelationship === 'OR' ? 'OR' : 'AND' }
                        options={ [
                            {
                                label: __( 'Match all filters (AND)', 'query-filter' ),
                                value: 'AND',
                            },
                            {
                                label: __( 'Match any filter (OR)', 'query-filter' ),
                                value: 'OR',
                            },
                        ] }
                        onChange={ ( val ) =>
                            setAttributes( { filtersRelationship: val === 'OR' ? 'OR' : 'AND' } )
                        }
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
