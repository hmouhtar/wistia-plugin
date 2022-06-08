wp.domReady(function () {
    const {
        __,
        _x,
        _n,
        _nx
    } = wp.i18n;
    const wistiaIcon = wp.element.createElement('svg', {
            width: 20,
            height: 20,
            viewBox: "0.17 0.49 668.58 508.1",
        },
        wp.element.createElement('path', {
            d: "M633.56 106.09C647.33 23.45 600.67.49 600.67.49s2.3 67.34-121.63 81.88C368.88 95.38.93 85.43.93 85.43L119.5 221.64c32.13 36.73 48.96 41.32 84.92 43.61 35.95 2.3 115.51 1.54 169.82-2.29 58.9-4.59 143.05-23.72 199.66-68.1 28.3-22.96 53.54-54.33 59.66-88.77zm7.65 85.71s-14.53 29.84-88.73 76.52c-31.37 19.89-97.15 41.32-181.3 48.97-45.9 4.59-129.28.76-165.23.76-35.96 0-52.79 7.66-84.92 44.39L.17 497.11h72.67c31.36 0 227.2 11.48 313.64-12.24C668.75 406.82 641.21 191.8 641.21 191.8z"
        })
    );
    wp.blocks.registerBlockVariation('core/embed', {
        name: 'wistia',
        title: "Wistia",
        description: __('Embed Wistia content.'),
        patterns: ['/https?:\/\/(.+)?(wistia.com|wi.st)\/(medias|embed)\/.*/'],
        attributes: {
            providerNameSlug: 'wistia',
            responsive: true,
            className: "wistia_embed"
        },
        icon: wistiaIcon,
        isActive: (blockAttributes, variationAttributes) =>
            blockAttributes.providerNameSlug ===
            variationAttributes.providerNameSlug
    });

});