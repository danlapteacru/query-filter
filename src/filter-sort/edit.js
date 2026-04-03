import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes } ) {
    const { label, options } = attributes;
    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Sort Settings', 'query-filter' ) }>
                    <p>{ __( 'Sort options are configured via block attributes.', 'query-filter' ) }</p>
                </PanelBody>
            </InspectorControls>
            <div { ...useBlockProps() }>
                <label className="wp-block-query-filter__label">{ label }</label>
                <select disabled>
                    { options.map( ( opt, i ) => (
                        <option key={ i }>{ opt.label }</option>
                    ) ) }
                </select>
            </div>
        </>
    );
}
