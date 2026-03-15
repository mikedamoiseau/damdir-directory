/**
 * APD Gutenberg Blocks
 *
 * Registers Gutenberg blocks for All Purpose Directory.
 *
 * @package APD
 * @since 1.0.0
 */

( function( wp ) {
	'use strict';

	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const {
		InspectorControls,
		useBlockProps,
	} = wp.blockEditor;
	const {
		PanelBody,
		PanelRow,
		SelectControl,
		RangeControl,
		ToggleControl,
		TextControl,
		__experimentalNumberControl: NumberControl,
	} = wp.components;
	const { ServerSideRender } = wp.serverSideRender || wp.components;
	const { __ } = wp.i18n;

	// Get localized data.
	const data = window.apdBlocks || {};

	/**
	 * Listings Block
	 */
	registerBlockType( 'apd/listings', {
		title: __( 'Listings', 'all-purpose-directory' ),
		description: __( 'Display listings in grid or list view.', 'all-purpose-directory' ),
		icon: 'grid-view',
		category: 'all-purpose-directory',
		keywords: [
			__( 'listings', 'all-purpose-directory' ),
			__( 'directory', 'all-purpose-directory' ),
			__( 'grid', 'all-purpose-directory' ),
		],
		supports: {
			html: false,
			align: [ 'wide', 'full' ],
			anchor: true,
		},
		attributes: {
			view: { type: 'string', default: 'grid' },
			columns: { type: 'number', default: 3 },
			count: { type: 'number', default: 12 },
			category: { type: 'string', default: '' },
			tag: { type: 'string', default: '' },
			orderby: { type: 'string', default: 'date' },
			order: { type: 'string', default: 'DESC' },
			ids: { type: 'string', default: '' },
			exclude: { type: 'string', default: '' },
			showImage: { type: 'boolean', default: true },
			showExcerpt: { type: 'boolean', default: true },
			excerptLength: { type: 'number', default: 15 },
			showCategory: { type: 'boolean', default: true },
			showPagination: { type: 'boolean', default: true },
		},

		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					// Layout Panel
					el(
						PanelBody,
						{ title: __( 'Layout', 'all-purpose-directory' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'View', 'all-purpose-directory' ),
							value: attributes.view,
							options: data.viewOptions || [
								{ value: 'grid', label: __( 'Grid', 'all-purpose-directory' ) },
								{ value: 'list', label: __( 'List', 'all-purpose-directory' ) },
							],
							onChange: function( value ) {
								setAttributes( { view: value } );
							},
						} ),
						attributes.view === 'grid' && el( RangeControl, {
							label: __( 'Columns', 'all-purpose-directory' ),
							value: attributes.columns,
							min: 2,
							max: 4,
							onChange: function( value ) {
								setAttributes( { columns: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'Listings per page', 'all-purpose-directory' ),
							value: attributes.count,
							min: 1,
							max: 100,
							onChange: function( value ) {
								setAttributes( { count: value } );
							},
						} )
					),
					// Filter Panel
					el(
						PanelBody,
						{ title: __( 'Filters', 'all-purpose-directory' ), initialOpen: false },
						el( SelectControl, {
							label: __( 'Category', 'all-purpose-directory' ),
							value: attributes.category,
							options: [
								{ value: '', label: __( 'All Categories', 'all-purpose-directory' ) },
								...( data.categories || [] ),
							],
							onChange: function( value ) {
								setAttributes( { category: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Tag', 'all-purpose-directory' ),
							value: attributes.tag,
							options: [
								{ value: '', label: __( 'All Tags', 'all-purpose-directory' ) },
								...( data.tags || [] ),
							],
							onChange: function( value ) {
								setAttributes( { tag: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Specific IDs', 'all-purpose-directory' ),
							help: __( 'Comma-separated list of listing IDs to display.', 'all-purpose-directory' ),
							value: attributes.ids,
							onChange: function( value ) {
								setAttributes( { ids: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Exclude IDs', 'all-purpose-directory' ),
							help: __( 'Comma-separated list of listing IDs to exclude.', 'all-purpose-directory' ),
							value: attributes.exclude,
							onChange: function( value ) {
								setAttributes( { exclude: value } );
							},
						} )
					),
					// Order Panel
					el(
						PanelBody,
						{ title: __( 'Order', 'all-purpose-directory' ), initialOpen: false },
						el( SelectControl, {
							label: __( 'Order by', 'all-purpose-directory' ),
							value: attributes.orderby,
							options: data.orderbyOptions || [
								{ value: 'date', label: __( 'Date', 'all-purpose-directory' ) },
								{ value: 'title', label: __( 'Title', 'all-purpose-directory' ) },
								{ value: 'modified', label: __( 'Modified', 'all-purpose-directory' ) },
								{ value: 'rand', label: __( 'Random', 'all-purpose-directory' ) },
								{ value: 'views', label: __( 'Views', 'all-purpose-directory' ) },
							],
							onChange: function( value ) {
								setAttributes( { orderby: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Order', 'all-purpose-directory' ),
							value: attributes.order,
							options: data.orderOptions || [
								{ value: 'DESC', label: __( 'Descending', 'all-purpose-directory' ) },
								{ value: 'ASC', label: __( 'Ascending', 'all-purpose-directory' ) },
							],
							onChange: function( value ) {
								setAttributes( { order: value } );
							},
						} )
					),
					// Display Panel
					el(
						PanelBody,
						{ title: __( 'Display Options', 'all-purpose-directory' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Show image', 'all-purpose-directory' ),
							checked: attributes.showImage,
							onChange: function( value ) {
								setAttributes( { showImage: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show excerpt', 'all-purpose-directory' ),
							checked: attributes.showExcerpt,
							onChange: function( value ) {
								setAttributes( { showExcerpt: value } );
							},
						} ),
						attributes.showExcerpt && el( RangeControl, {
							label: __( 'Excerpt length (words)', 'all-purpose-directory' ),
							value: attributes.excerptLength,
							min: 5,
							max: 55,
							onChange: function( value ) {
								setAttributes( { excerptLength: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show category', 'all-purpose-directory' ),
							checked: attributes.showCategory,
							onChange: function( value ) {
								setAttributes( { showCategory: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show pagination', 'all-purpose-directory' ),
							checked: attributes.showPagination,
							onChange: function( value ) {
								setAttributes( { showPagination: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'apd/listings',
						attributes: attributes,
					} )
				)
			);
		},

		save: function() {
			// Server-side rendering, return null.
			return null;
		},
	} );

	/**
	 * Search Form Block
	 */
	registerBlockType( 'apd/search-form', {
		title: __( 'Listing Search Form', 'all-purpose-directory' ),
		description: __( 'Display a search form for filtering listings.', 'all-purpose-directory' ),
		icon: 'search',
		category: 'all-purpose-directory',
		keywords: [
			__( 'search', 'all-purpose-directory' ),
			__( 'filter', 'all-purpose-directory' ),
			__( 'form', 'all-purpose-directory' ),
		],
		supports: {
			html: false,
			align: [ 'wide', 'full' ],
			anchor: true,
		},
		attributes: {
			filters: { type: 'string', default: '' },
			showKeyword: { type: 'boolean', default: true },
			showCategory: { type: 'boolean', default: true },
			showTag: { type: 'boolean', default: false },
			showSubmit: { type: 'boolean', default: true },
			submitText: { type: 'string', default: '' },
			action: { type: 'string', default: '' },
			layout: { type: 'string', default: 'horizontal' },
			showActive: { type: 'boolean', default: false },
		},

		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					// Layout Panel
					el(
						PanelBody,
						{ title: __( 'Layout', 'all-purpose-directory' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Layout', 'all-purpose-directory' ),
							value: attributes.layout,
							options: data.layoutOptions || [
								{ value: 'horizontal', label: __( 'Horizontal', 'all-purpose-directory' ) },
								{ value: 'vertical', label: __( 'Vertical', 'all-purpose-directory' ) },
								{ value: 'inline', label: __( 'Inline', 'all-purpose-directory' ) },
							],
							onChange: function( value ) {
								setAttributes( { layout: value } );
							},
						} )
					),
					// Filters Panel
					el(
						PanelBody,
						{ title: __( 'Filters', 'all-purpose-directory' ), initialOpen: true },
						el( ToggleControl, {
							label: __( 'Show keyword search', 'all-purpose-directory' ),
							checked: attributes.showKeyword,
							onChange: function( value ) {
								setAttributes( { showKeyword: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show category filter', 'all-purpose-directory' ),
							checked: attributes.showCategory,
							onChange: function( value ) {
								setAttributes( { showCategory: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show tag filter', 'all-purpose-directory' ),
							checked: attributes.showTag,
							onChange: function( value ) {
								setAttributes( { showTag: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Custom filters', 'all-purpose-directory' ),
							help: __( 'Comma-separated list of filter names. Overrides toggle options above.', 'all-purpose-directory' ),
							value: attributes.filters,
							onChange: function( value ) {
								setAttributes( { filters: value } );
							},
						} )
					),
					// Submit Button Panel
					el(
						PanelBody,
						{ title: __( 'Submit Button', 'all-purpose-directory' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Show submit button', 'all-purpose-directory' ),
							checked: attributes.showSubmit,
							onChange: function( value ) {
								setAttributes( { showSubmit: value } );
							},
						} ),
						attributes.showSubmit && el( TextControl, {
							label: __( 'Button text', 'all-purpose-directory' ),
							placeholder: __( 'Search', 'all-purpose-directory' ),
							value: attributes.submitText,
							onChange: function( value ) {
								setAttributes( { submitText: value } );
							},
						} )
					),
					// Advanced Panel
					el(
						PanelBody,
						{ title: __( 'Advanced', 'all-purpose-directory' ), initialOpen: false },
						el( TextControl, {
							label: __( 'Form action URL', 'all-purpose-directory' ),
							help: __( 'Leave empty to use the listings archive page.', 'all-purpose-directory' ),
							value: attributes.action,
							onChange: function( value ) {
								setAttributes( { action: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show active filters', 'all-purpose-directory' ),
							help: __( 'Display currently active filters below the form.', 'all-purpose-directory' ),
							checked: attributes.showActive,
							onChange: function( value ) {
								setAttributes( { showActive: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'apd/search-form',
						attributes: attributes,
					} )
				)
			);
		},

		save: function() {
			// Server-side rendering, return null.
			return null;
		},
	} );

	/**
	 * Categories Block
	 */
	registerBlockType( 'apd/categories', {
		title: __( 'Listing Categories', 'all-purpose-directory' ),
		description: __( 'Display listing categories in a grid or list.', 'all-purpose-directory' ),
		icon: 'category',
		category: 'all-purpose-directory',
		keywords: [
			__( 'categories', 'all-purpose-directory' ),
			__( 'taxonomy', 'all-purpose-directory' ),
			__( 'directory', 'all-purpose-directory' ),
		],
		supports: {
			html: false,
			align: [ 'wide', 'full' ],
			anchor: true,
		},
		attributes: {
			layout: { type: 'string', default: 'grid' },
			columns: { type: 'number', default: 4 },
			count: { type: 'number', default: 0 },
			parent: { type: 'string', default: '' },
			include: { type: 'string', default: '' },
			exclude: { type: 'string', default: '' },
			hideEmpty: { type: 'boolean', default: true },
			orderby: { type: 'string', default: 'name' },
			order: { type: 'string', default: 'ASC' },
			showCount: { type: 'boolean', default: true },
			showIcon: { type: 'boolean', default: true },
			showDescription: { type: 'boolean', default: false },
		},

		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();

			// Build parent options from categories.
			const parentOptions = [
				{ value: '', label: __( 'All', 'all-purpose-directory' ) },
				{ value: '0', label: __( 'Top-level only', 'all-purpose-directory' ) },
				...( data.categories || [] ).map( function( cat ) {
					return { value: cat.value, label: cat.label };
				} ),
			];

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					// Layout Panel
					el(
						PanelBody,
						{ title: __( 'Layout', 'all-purpose-directory' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Layout', 'all-purpose-directory' ),
							value: attributes.layout,
							options: [
								{ value: 'grid', label: __( 'Grid', 'all-purpose-directory' ) },
								{ value: 'list', label: __( 'List', 'all-purpose-directory' ) },
							],
							onChange: function( value ) {
								setAttributes( { layout: value } );
							},
						} ),
						attributes.layout === 'grid' && el( RangeControl, {
							label: __( 'Columns', 'all-purpose-directory' ),
							value: attributes.columns,
							min: 2,
							max: 6,
							onChange: function( value ) {
								setAttributes( { columns: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'Number of categories', 'all-purpose-directory' ),
							help: __( '0 = show all', 'all-purpose-directory' ),
							value: attributes.count,
							min: 0,
							max: 50,
							onChange: function( value ) {
								setAttributes( { count: value } );
							},
						} )
					),
					// Filter Panel
					el(
						PanelBody,
						{ title: __( 'Filters', 'all-purpose-directory' ), initialOpen: false },
						el( SelectControl, {
							label: __( 'Parent category', 'all-purpose-directory' ),
							value: attributes.parent,
							options: parentOptions,
							onChange: function( value ) {
								setAttributes( { parent: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Include IDs', 'all-purpose-directory' ),
							help: __( 'Comma-separated list of category IDs to include.', 'all-purpose-directory' ),
							value: attributes.include,
							onChange: function( value ) {
								setAttributes( { include: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Exclude IDs', 'all-purpose-directory' ),
							help: __( 'Comma-separated list of category IDs to exclude.', 'all-purpose-directory' ),
							value: attributes.exclude,
							onChange: function( value ) {
								setAttributes( { exclude: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Hide empty categories', 'all-purpose-directory' ),
							checked: attributes.hideEmpty,
							onChange: function( value ) {
								setAttributes( { hideEmpty: value } );
							},
						} )
					),
					// Order Panel
					el(
						PanelBody,
						{ title: __( 'Order', 'all-purpose-directory' ), initialOpen: false },
						el( SelectControl, {
							label: __( 'Order by', 'all-purpose-directory' ),
							value: attributes.orderby,
							options: [
								{ value: 'name', label: __( 'Name', 'all-purpose-directory' ) },
								{ value: 'count', label: __( 'Count', 'all-purpose-directory' ) },
								{ value: 'id', label: __( 'ID', 'all-purpose-directory' ) },
								{ value: 'slug', label: __( 'Slug', 'all-purpose-directory' ) },
							],
							onChange: function( value ) {
								setAttributes( { orderby: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Order', 'all-purpose-directory' ),
							value: attributes.order,
							options: [
								{ value: 'ASC', label: __( 'Ascending', 'all-purpose-directory' ) },
								{ value: 'DESC', label: __( 'Descending', 'all-purpose-directory' ) },
							],
							onChange: function( value ) {
								setAttributes( { order: value } );
							},
						} )
					),
					// Display Panel
					el(
						PanelBody,
						{ title: __( 'Display Options', 'all-purpose-directory' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Show listing count', 'all-purpose-directory' ),
							checked: attributes.showCount,
							onChange: function( value ) {
								setAttributes( { showCount: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show category icon', 'all-purpose-directory' ),
							checked: attributes.showIcon,
							onChange: function( value ) {
								setAttributes( { showIcon: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show description', 'all-purpose-directory' ),
							checked: attributes.showDescription,
							onChange: function( value ) {
								setAttributes( { showDescription: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'apd/categories',
						attributes: attributes,
					} )
				)
			);
		},

		save: function() {
			// Server-side rendering, return null.
			return null;
		},
	} );

} )( window.wp );
