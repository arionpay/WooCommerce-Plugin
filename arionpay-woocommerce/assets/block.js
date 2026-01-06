(function( window, wp ) {
    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { getSetting } = window.wc.wcSettings;
    const { decodeEntities } = window.wp.htmlEntities; 
    const { createElement } = window.wp.element; // We use this instead of JSX

    const settings = getSetting( 'arionpay_data', {} );
    
    // Default Label
    const label = decodeEntities( settings.title ) || 'ArionPay Crypto';

    // The UI Component (What shows on checkout)
    const ArionPayComponent = ( props ) => {
        return createElement( 'div', { className: 'arionpay-block-description' }, 
            settings.description || 'Proceed to pay with Cryptocurrency.'
        );
    };

    // Register with WooCommerce Blocks
    registerPaymentMethod( {
        name: 'arionpay',
        label: label,
        content: createElement( ArionPayComponent, null ),
        edit: createElement( ArionPayComponent, null ),
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: settings.supports,
        },
    } );

})( window, window.wp );