<?php
/**
 * Central HTML element catalog.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

final class Element_Registry {
	/**
	 * Categorized element definitions.
	 *
	 * Extensions may use the sefm_element_groups filter. Each definition must
	 * contain a label, description and selector.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function groups() {
		$groups = array(
			'global' => array(
				'label'    => __( 'General', 'saeidbakhsh-typography-manager' ),
				'elements' => array(
					'site'       => $this->item( __( 'Entire site', 'saeidbakhsh-typography-manager' ), __( 'Highest priority. Use the *, Icon and SVG switches to choose exactly where the selected values apply.', 'saeidbakhsh-typography-manager' ), ':root:not(#sefm-site):not(#sefm-site-priority), :root:not(#sefm-site):not(#sefm-site-priority) *' ),
					'body'       => $this->item( __( 'Body', 'saeidbakhsh-typography-manager' ), __( 'Visible page content.', 'saeidbakhsh-typography-manager' ), 'body' ),
					'headings'   => $this->item( __( 'All headings', 'saeidbakhsh-typography-manager' ), __( 'H1 through H6 as one group.', 'saeidbakhsh-typography-manager' ), 'h1, h2, h3, h4, h5, h6' ),
					'paragraphs' => $this->item( __( 'Paragraphs', 'saeidbakhsh-typography-manager' ), __( 'Standard paragraph text.', 'saeidbakhsh-typography-manager' ), 'p' ),
					'links'      => $this->item( __( 'Links', 'saeidbakhsh-typography-manager' ), __( 'Anchor elements in every state.', 'saeidbakhsh-typography-manager' ), 'a' ),
				),
			),
			'headings' => array(
				'label'    => __( 'Headings', 'saeidbakhsh-typography-manager' ),
				'elements' => array(
					'h1' => $this->item( 'H1', __( 'Primary page title.', 'saeidbakhsh-typography-manager' ), 'h1' ),
					'h2' => $this->item( 'H2', __( 'Second-level heading.', 'saeidbakhsh-typography-manager' ), 'h2' ),
					'h3' => $this->item( 'H3', __( 'Third-level heading.', 'saeidbakhsh-typography-manager' ), 'h3' ),
					'h4' => $this->item( 'H4', __( 'Fourth-level heading.', 'saeidbakhsh-typography-manager' ), 'h4' ),
					'h5' => $this->item( 'H5', __( 'Fifth-level heading.', 'saeidbakhsh-typography-manager' ), 'h5' ),
					'h6' => $this->item( 'H6', __( 'Sixth-level heading.', 'saeidbakhsh-typography-manager' ), 'h6' ),
				),
			),
			'content' => array(
				'label'    => __( 'Content', 'saeidbakhsh-typography-manager' ),
				'elements' => array(
					'containers'       => $this->item( __( 'Generic containers', 'saeidbakhsh-typography-manager' ), __( 'Div and span elements that directly wrap text.', 'saeidbakhsh-typography-manager' ), 'div, span' ),
					'lists'            => $this->item( __( 'Lists', 'saeidbakhsh-typography-manager' ), __( 'Ordered, unordered, menu and list-item text.', 'saeidbakhsh-typography-manager' ), 'ul, ol, menu, li' ),
					'definition_lists' => $this->item( __( 'Definition lists', 'saeidbakhsh-typography-manager' ), __( 'Definition lists, terms and descriptions.', 'saeidbakhsh-typography-manager' ), 'dl, dt, dd' ),
					'blockquote'       => $this->item( __( 'Quotes', 'saeidbakhsh-typography-manager' ), __( 'Block and inline quotations.', 'saeidbakhsh-typography-manager' ), 'blockquote, q' ),
					'emphasis'         => $this->item( __( 'Emphasis', 'saeidbakhsh-typography-manager' ), __( 'Bold, strong, italic and emphasized text.', 'saeidbakhsh-typography-manager' ), 'strong, b, em, i' ),
					'edits'            => $this->item( __( 'Edits', 'saeidbakhsh-typography-manager' ), __( 'Inserted, deleted and no-longer-accurate text.', 'saeidbakhsh-typography-manager' ), 'ins, del, s' ),
					'annotations'      => $this->item( __( 'Text annotations', 'saeidbakhsh-typography-manager' ), __( 'Underlined, subscript and superscript text.', 'saeidbakhsh-typography-manager' ), 'u, sub, sup' ),
					'small'            => $this->item( __( 'Small text', 'saeidbakhsh-typography-manager' ), __( 'Small print, captions and citations.', 'saeidbakhsh-typography-manager' ), 'small, figcaption, cite' ),
					'code'             => $this->item( __( 'Code', 'saeidbakhsh-typography-manager' ), __( 'Code, preformatted and keyboard text.', 'saeidbakhsh-typography-manager' ), 'code, pre, kbd, samp, var' ),
					'metadata'         => $this->item( __( 'Metadata', 'saeidbakhsh-typography-manager' ), __( 'Time, definitions, highlighted text and machine-readable values.', 'saeidbakhsh-typography-manager' ), 'time, abbr, dfn, mark, data' ),
					'bidi'             => $this->item( __( 'Bidirectional text', 'saeidbakhsh-typography-manager' ), __( 'Bidirectional isolation and direction overrides.', 'saeidbakhsh-typography-manager' ), 'bdi, bdo' ),
					'ruby'             => $this->item( __( 'Ruby annotations', 'saeidbakhsh-typography-manager' ), __( 'Ruby text and pronunciation annotations.', 'saeidbakhsh-typography-manager' ), 'ruby, rt, rp' ),
				),
			),
			'forms' => array(
				'label'    => __( 'Forms', 'saeidbakhsh-typography-manager' ),
				'elements' => array(
					'labels'       => $this->item( __( 'Labels', 'saeidbakhsh-typography-manager' ), __( 'Form labels and legends.', 'saeidbakhsh-typography-manager' ), 'label, legend' ),
					'inputs'       => $this->item( __( 'Text inputs', 'saeidbakhsh-typography-manager' ), __( 'Text, search, email, URL, telephone and password fields.', 'saeidbakhsh-typography-manager' ), 'input:not([type]), input[type="text"], input[type="search"], input[type="email"], input[type="url"], input[type="tel"], input[type="password"]' ),
					'value_inputs' => $this->item( __( 'Date and numeric inputs', 'saeidbakhsh-typography-manager' ), __( 'Number, date, time and file fields that render text.', 'saeidbakhsh-typography-manager' ), 'input[type="number"], input[type="date"], input[type="datetime-local"], input[type="month"], input[type="week"], input[type="time"], input[type="file"]' ),
					'textareas'    => $this->item( __( 'Text areas', 'saeidbakhsh-typography-manager' ), __( 'Multiline text controls.', 'saeidbakhsh-typography-manager' ), 'textarea' ),
					'selects'      => $this->item( __( 'Select fields', 'saeidbakhsh-typography-manager' ), __( 'Select menus and options.', 'saeidbakhsh-typography-manager' ), 'select, option, optgroup' ),
					'buttons'      => $this->item( __( 'Buttons', 'saeidbakhsh-typography-manager' ), __( 'Native form buttons.', 'saeidbakhsh-typography-manager' ), 'button, input[type="button"], input[type="submit"], input[type="reset"]' ),
					'file_button'  => $this->item( __( 'File input buttons', 'saeidbakhsh-typography-manager' ), __( 'The visible button inside file inputs.', 'saeidbakhsh-typography-manager' ), 'input[type="file"]::file-selector-button' ),
					'placeholder'  => $this->item( __( 'Placeholders', 'saeidbakhsh-typography-manager' ), __( 'Input and textarea placeholder text.', 'saeidbakhsh-typography-manager' ), 'input::placeholder, textarea::placeholder' ),
					'output'       => $this->item( __( 'Form output', 'saeidbakhsh-typography-manager' ), __( 'Calculated results displayed by forms.', 'saeidbakhsh-typography-manager' ), 'output' ),
				),
			),
			'tables' => array(
				'label'    => __( 'Tables', 'saeidbakhsh-typography-manager' ),
				'elements' => array(
					'tables'  => $this->item( __( 'Tables', 'saeidbakhsh-typography-manager' ), __( 'Base table typography.', 'saeidbakhsh-typography-manager' ), 'table' ),
					'headers' => $this->item( __( 'Table headers', 'saeidbakhsh-typography-manager' ), __( 'Header cells.', 'saeidbakhsh-typography-manager' ), 'thead, th' ),
					'cells'   => $this->item( __( 'Table cells', 'saeidbakhsh-typography-manager' ), __( 'Body and footer cells.', 'saeidbakhsh-typography-manager' ), 'tbody, tfoot, td' ),
					'caption' => $this->item( __( 'Table captions', 'saeidbakhsh-typography-manager' ), __( 'Table caption text.', 'saeidbakhsh-typography-manager' ), 'caption' ),
				),
			),
			'structure' => array(
				'label'    => __( 'Structure', 'saeidbakhsh-typography-manager' ),
				'elements' => array(
					'header'  => $this->item( __( 'Header', 'saeidbakhsh-typography-manager' ), __( 'Site and section headers.', 'saeidbakhsh-typography-manager' ), 'header' ),
					'nav'     => $this->item( __( 'Navigation', 'saeidbakhsh-typography-manager' ), __( 'Semantic navigation landmarks and their links.', 'saeidbakhsh-typography-manager' ), 'nav, nav a' ),
					'main'    => $this->item( __( 'Main content', 'saeidbakhsh-typography-manager' ), __( 'The main landmark.', 'saeidbakhsh-typography-manager' ), 'main' ),
					'section' => $this->item( __( 'Sections', 'saeidbakhsh-typography-manager' ), __( 'Sections and articles.', 'saeidbakhsh-typography-manager' ), 'section, article' ),
					'aside'   => $this->item( __( 'Sidebars', 'saeidbakhsh-typography-manager' ), __( 'Semantic aside landmarks.', 'saeidbakhsh-typography-manager' ), 'aside' ),
					'footer'  => $this->item( __( 'Footer', 'saeidbakhsh-typography-manager' ), __( 'Site and section footers.', 'saeidbakhsh-typography-manager' ), 'footer' ),
					'details' => $this->item( __( 'Details', 'saeidbakhsh-typography-manager' ), __( 'Disclosure widgets and summaries.', 'saeidbakhsh-typography-manager' ), 'details, summary' ),
					'address' => $this->item( __( 'Addresses', 'saeidbakhsh-typography-manager' ), __( 'Contact-information text blocks.', 'saeidbakhsh-typography-manager' ), 'address' ),
					'dialog'  => $this->item( __( 'Dialogs', 'saeidbakhsh-typography-manager' ), __( 'Native dialog and modal text.', 'saeidbakhsh-typography-manager' ), 'dialog' ),
				),
			),
			'wordpress' => array(
				'label'    => __( 'WordPress', 'saeidbakhsh-typography-manager' ),
				'elements' => array(
					'wp_navigation' => $this->item( __( 'Navigation', 'saeidbakhsh-typography-manager' ), __( 'Classic WordPress menus and core Navigation block text.', 'saeidbakhsh-typography-manager' ), '.menu, .menu a, .wp-block-navigation, .wp-block-navigation-item__content, .wp-block-navigation-item__label' ),
					'wp_buttons'    => $this->item( __( 'Buttons', 'saeidbakhsh-typography-manager' ), __( 'Core WordPress button classes and Button block links.', 'saeidbakhsh-typography-manager' ), '.wp-element-button, .wp-block-button__link' ),
					'site_title'   => $this->item( __( 'Site title', 'saeidbakhsh-typography-manager' ), __( 'Classic and block-theme site titles, including linked text.', 'saeidbakhsh-typography-manager' ), '.site-title, .site-title a, .wp-block-site-title, .wp-block-site-title a' ),
					'tagline'      => $this->item( __( 'Tagline', 'saeidbakhsh-typography-manager' ), __( 'Common site-description classes.', 'saeidbakhsh-typography-manager' ), '.site-description, .wp-block-site-tagline' ),
					'post_title'   => $this->item( __( 'Post and archive titles', 'saeidbakhsh-typography-manager' ), __( 'Entry, page, query and taxonomy titles.', 'saeidbakhsh-typography-manager' ), '.entry-title, .page-title, .wp-block-post-title, .wp-block-query-title, .wp-block-term-name' ),
					'post_content' => $this->item( __( 'Post content', 'saeidbakhsh-typography-manager' ), __( 'Classic and block post content wrappers.', 'saeidbakhsh-typography-manager' ), '.entry-content, .wp-block-post-content' ),
					'post_excerpt' => $this->item( __( 'Post excerpts', 'saeidbakhsh-typography-manager' ), __( 'Classic summaries and block-theme excerpts.', 'saeidbakhsh-typography-manager' ), '.entry-summary, .wp-block-post-excerpt, .wp-block-post-excerpt__excerpt' ),
					'entry_meta'   => $this->item( __( 'Entry metadata', 'saeidbakhsh-typography-manager' ), __( 'Classic-theme dates, authors, categories and tags.', 'saeidbakhsh-typography-manager' ), '.entry-meta, .entry-footer, .posted-on, .byline, .cat-links, .tags-links' ),
					'block_meta'   => $this->item( __( 'Block post metadata', 'saeidbakhsh-typography-manager' ), __( 'Core post date, author, terms and reading-time blocks.', 'saeidbakhsh-typography-manager' ), '.wp-block-post-date, .wp-block-post-author, .wp-block-post-author__name, .wp-block-post-author__bio, .wp-block-post-author-name, .wp-block-post-terms, .wp-block-post-time-to-read' ),
					'captions'     => $this->item( __( 'Media captions', 'saeidbakhsh-typography-manager' ), __( 'Classic, gallery and block captions that display text.', 'saeidbakhsh-typography-manager' ), '.wp-caption-text, .gallery-caption, .wp-element-caption, .wp-block-image figcaption, .wp-block-gallery figcaption' ),
					'comments'     => $this->item( __( 'Classic comments', 'saeidbakhsh-typography-manager' ), __( 'Classic comment content, metadata, replies and forms.', 'saeidbakhsh-typography-manager' ), '.comment-list, .comment-content, .comment-form, .comment-author, .comment-metadata, .comment-reply-link' ),
					'block_comments' => $this->item( __( 'Block comments', 'saeidbakhsh-typography-manager' ), __( 'Core comment author, content, date, reply and form blocks.', 'saeidbakhsh-typography-manager' ), '.wp-block-comments, .wp-block-comment-author-name, .wp-block-comment-content, .wp-block-comment-date, .wp-block-comment-reply-link, .wp-block-post-comments-form, .wp-block-post-comments-link' ),
					'pagination'   => $this->item( __( 'Classic pagination', 'saeidbakhsh-typography-manager' ), __( 'Classic post, archive and numbered navigation.', 'saeidbakhsh-typography-manager' ), '.pagination, .nav-links, .page-numbers, .post-navigation-link__label' ),
					'block_pagination' => $this->item( __( 'Block pagination', 'saeidbakhsh-typography-manager' ), __( 'Core query and comment pagination text.', 'saeidbakhsh-typography-manager' ), '.wp-block-query-pagination, .wp-block-query-pagination-previous, .wp-block-query-pagination-next, .wp-block-query-pagination-numbers, .wp-block-comments-pagination' ),
					'widgets'      => $this->item( __( 'Widgets', 'saeidbakhsh-typography-manager' ), __( 'Classic widgets, widget titles and block widgets.', 'saeidbakhsh-typography-manager' ), '.widget, .widget-title, .wp-block-widget, .wp-block-latest-posts, .wp-block-latest-comments, .wp-block-rss' ),
					'archive_lists' => $this->item( __( 'Archive and taxonomy lists', 'saeidbakhsh-typography-manager' ), __( 'Core archive, category, page-list and tag-cloud text.', 'saeidbakhsh-typography-manager' ), '.wp-block-archives, .wp-block-categories, .wp-block-page-list, .wp-block-tag-cloud' ),
					'read_more'    => $this->item( __( 'Read-more links', 'saeidbakhsh-typography-manager' ), __( 'Classic and block read-more links.', 'saeidbakhsh-typography-manager' ), '.more-link, .wp-block-read-more, .wp-block-post-excerpt__more-link' ),
					'block_search' => $this->item( __( 'Search block', 'saeidbakhsh-typography-manager' ), __( 'The label, field and button text in the core Search block.', 'saeidbakhsh-typography-manager' ), '.wp-block-search, .wp-block-search__label, .wp-block-search__input, .wp-block-search__button' ),
					'footnotes'    => $this->item( __( 'Footnotes', 'saeidbakhsh-typography-manager' ), __( 'Footnote text generated by the core Footnotes block.', 'saeidbakhsh-typography-manager' ), '.wp-block-footnotes' ),
				),
			),
			'woocommerce' => array(
				'label'    => __( 'WooCommerce', 'saeidbakhsh-typography-manager' ),
				'elements' => array(
					'wc_shop_title' => $this->item( __( 'Shop and archive title', 'saeidbakhsh-typography-manager' ), __( 'The official WooCommerce shop and product-taxonomy archive title.', 'saeidbakhsh-typography-manager' ), '.woocommerce-products-header__title' ),
					'wc_archive_description' => $this->item( __( 'Archive description', 'saeidbakhsh-typography-manager' ), __( 'Product category, tag and shop archive descriptions.', 'saeidbakhsh-typography-manager' ), '.woocommerce .term-description, .woocommerce-page .term-description, .woocommerce .page-description, .woocommerce-page .page-description' ),
					'wc_result_count' => $this->item( __( 'Product results count', 'saeidbakhsh-typography-manager' ), __( 'Classic and block product result-count text.', 'saeidbakhsh-typography-manager' ), '.woocommerce-result-count, .wc-block-product-results-count, .wp-block-woocommerce-product-results-count' ),
					'wc_catalog_sorting' => $this->item( __( 'Catalog sorting', 'saeidbakhsh-typography-manager' ), __( 'Classic catalog ordering and the WooCommerce Sort Select component.', 'saeidbakhsh-typography-manager' ), '.woocommerce-ordering, .woocommerce-ordering select, .woocommerce-ordering option, .wc-block-components-sort-select, .wc-block-components-sort-select select, .wc-block-components-sort-select option, .wp-block-woocommerce-catalog-sorting' ),
					'wc_product_titles' => $this->item( __( 'Catalog product titles', 'saeidbakhsh-typography-manager' ), __( 'Product titles in classic loops, legacy product grids and Product Collection blocks.', 'saeidbakhsh-typography-manager' ), '.woocommerce-loop-product__title, .wc-block-grid__product-title, .wc-block-components-product-title, .wp-block-woocommerce-product-collection .wp-block-post-title' ),
					'wc_category_titles' => $this->item( __( 'Product category titles', 'saeidbakhsh-typography-manager' ), __( 'Product-category names displayed in classic WooCommerce loops.', 'saeidbakhsh-typography-manager' ), '.woocommerce-loop-category__title' ),
					'wc_prices' => $this->item( __( 'Product prices', 'saeidbakhsh-typography-manager' ), __( 'Primary price typography across classic templates and WooCommerce block components.', 'saeidbakhsh-typography-manager' ), '.woocommerce .price, .woocommerce-page .price, .woocommerce-Price-amount, .wc-block-components-formatted-money-amount, .wc-block-components-product-price, .wc-block-components-product-price__value, .wc-block-grid__product-price' ),
					'wc_regular_prices' => $this->item( __( 'Regular prices', 'saeidbakhsh-typography-manager' ), __( 'Regular or crossed-out prices when a product is on sale.', 'saeidbakhsh-typography-manager' ), '.woocommerce .price del, .woocommerce-page .price del, .wc-block-components-product-price del, .wc-block-grid__product-price del' ),
					'wc_sale_prices' => $this->item( __( 'Sale prices', 'saeidbakhsh-typography-manager' ), __( 'Discounted or inserted prices when a product is on sale.', 'saeidbakhsh-typography-manager' ), '.woocommerce .price ins, .woocommerce-page .price ins, .wc-block-components-product-price ins, .wc-block-grid__product-price ins' ),
					'wc_currency_symbol' => $this->item( __( 'Currency symbols', 'saeidbakhsh-typography-manager' ), __( 'Currency symbols emitted by WooCommerce price formatting.', 'saeidbakhsh-typography-manager' ), '.woocommerce-Price-currencySymbol' ),
					'wc_price_details' => $this->item( __( 'Price suffix and tax label', 'saeidbakhsh-typography-manager' ), __( 'Price suffixes and tax labels added to WooCommerce prices.', 'saeidbakhsh-typography-manager' ), '.woocommerce-price-suffix, .woocommerce-Price-taxLabel, .woocommerce .tax_label, .woocommerce-page .tax_label' ),
					'wc_sale_badge' => $this->item( __( 'Sale badges', 'saeidbakhsh-typography-manager' ), __( 'Classic and block Sale badge text without targeting badge icons.', 'saeidbakhsh-typography-manager' ), '.woocommerce .onsale, .woocommerce-page .onsale, .wc-block-grid__product-onsale, .wc-block-components-product-sale-badge, .wc-block-components-product-sale-badge__text, .wp-block-woocommerce-product-sale-badge' ),
					'wc_product_buttons' => $this->item( __( 'Product action buttons', 'saeidbakhsh-typography-manager' ), __( 'Add-to-cart and product action button text in classic and block product lists.', 'saeidbakhsh-typography-manager' ), '.woocommerce a.add_to_cart_button, .woocommerce button.single_add_to_cart_button, .woocommerce .single_add_to_cart_button, .wc-block-grid__product-add-to-cart .wp-block-button__link, .wc-block-components-product-button__button, .wp-block-woocommerce-product-button .wp-block-button__link' ),
					'wc_breadcrumbs' => $this->item( __( 'Store breadcrumbs', 'saeidbakhsh-typography-manager' ), __( 'Classic WooCommerce breadcrumbs and the WooCommerce Breadcrumbs block.', 'saeidbakhsh-typography-manager' ), '.woocommerce-breadcrumb, .wp-block-woocommerce-breadcrumbs' ),
					'wc_pagination' => $this->item( __( 'Product pagination', 'saeidbakhsh-typography-manager' ), __( 'Classic product pagination and Query Pagination inside Product Collection.', 'saeidbakhsh-typography-manager' ), '.woocommerce-pagination, .woocommerce-pagination .page-numbers, .wp-block-woocommerce-product-collection .wp-block-query-pagination' ),
					'wc_filter_headings' => $this->item( __( 'Product filter headings', 'saeidbakhsh-typography-manager' ), __( 'Headings used by current WooCommerce Product Filter blocks.', 'saeidbakhsh-typography-manager' ), '.wp-block-woocommerce-accordion-header .accordion-item__toggle, .wp-block-woocommerce-product-filter-attribute .wp-block-heading, .wp-block-woocommerce-product-filter-taxonomy .wp-block-heading, .wp-block-woocommerce-product-filter-rating .wp-block-heading, .wp-block-woocommerce-product-filter-price .wp-block-heading, .wp-block-woocommerce-product-filter-status .wp-block-heading' ),
					'wc_filter_options' => $this->item( __( 'Product filter options', 'saeidbakhsh-typography-manager' ), __( 'Checkbox labels, option text, counts and show-more controls in Product Filters.', 'saeidbakhsh-typography-manager' ), '.wc-block-product-filter-checkbox-list__label, .wc-block-product-filter-checkbox-list__text, .wc-block-product-filter-checkbox-list__count, .wc-block-product-filter-checkbox-list__show-more-button, .wc-block-product-filter-chips, .wc-block-product-filter-removable-chips, .wc-block-product-filter-clear-button .wp-block-button__link' ),
					'wc_notices' => $this->item( __( 'Store notices', 'saeidbakhsh-typography-manager' ), __( 'Success, information and error notice text in classic and block WooCommerce.', 'saeidbakhsh-typography-manager' ), '.woocommerce-message, .woocommerce-info, .woocommerce-error, .wc-block-components-notice-banner__content, .wc-block-components-notice-banner__summary' ),

					'wc_single_title' => $this->item( __( 'Single product title', 'saeidbakhsh-typography-manager' ), __( 'The main product title in classic and block single-product templates.', 'saeidbakhsh-typography-manager' ), '.single-product .product_title, .single-product .wp-block-post-title' ),
					'wc_short_description' => $this->item( __( 'Short description', 'saeidbakhsh-typography-manager' ), __( 'The product short description or product excerpt.', 'saeidbakhsh-typography-manager' ), '.woocommerce-product-details__short-description, .single-product .wp-block-post-excerpt' ),
					'wc_stock_status' => $this->item( __( 'Stock status', 'saeidbakhsh-typography-manager' ), __( 'Classic stock availability and the Product Stock Indicator block.', 'saeidbakhsh-typography-manager' ), '.woocommerce .stock, .woocommerce-page .stock, .wc-block-components-product-stock-indicator, .wp-block-woocommerce-product-stock-indicator' ),
					'wc_product_meta' => $this->item( __( 'Product metadata', 'saeidbakhsh-typography-manager' ), __( 'Product SKU, category and tag metadata containers and labels.', 'saeidbakhsh-typography-manager' ), '.single-product .product_meta, .single-product .product_meta .sku_wrapper, .single-product .product_meta .posted_in, .single-product .product_meta .tagged_as, .wp-block-woocommerce-product-meta' ),
					'wc_sku' => $this->item( __( 'Product SKU', 'saeidbakhsh-typography-manager' ), __( 'The SKU value in classic metadata and the Product SKU block.', 'saeidbakhsh-typography-manager' ), '.single-product .product_meta .sku, .wp-block-woocommerce-product-sku' ),
					'wc_variation_labels' => $this->item( __( 'Variation labels', 'saeidbakhsh-typography-manager' ), __( 'Attribute labels and the clear-options link in variable product forms.', 'saeidbakhsh-typography-manager' ), '.single-product .variations th.label, .single-product .variations th.label label, .single-product .reset_variations' ),
					'wc_variation_fields' => $this->item( __( 'Variation selectors', 'saeidbakhsh-typography-manager' ), __( 'WooCommerce variable-product select fields and their options.', 'saeidbakhsh-typography-manager' ), '.single-product .variations select, .single-product .variations option' ),
					'wc_quantity' => $this->item( __( 'Quantity fields', 'saeidbakhsh-typography-manager' ), __( 'Classic quantity inputs and the reusable WooCommerce Quantity Selector component.', 'saeidbakhsh-typography-manager' ), '.woocommerce .quantity .qty, .woocommerce-page .quantity .qty, .wc-block-components-quantity-selector__input' ),
					'wc_product_tabs' => $this->item( __( 'Product tab labels', 'saeidbakhsh-typography-manager' ), __( 'Classic product-tab navigation labels.', 'saeidbakhsh-typography-manager' ), '.woocommerce-tabs .wc-tabs, .woocommerce-tabs .wc-tabs a' ),
					'wc_product_tab_content' => $this->item( __( 'Product tab content', 'saeidbakhsh-typography-manager' ), __( 'Description, additional information and other classic product-tab panels.', 'saeidbakhsh-typography-manager' ), '.woocommerce-Tabs-panel' ),
					'wc_review_headings' => $this->item( __( 'Review headings', 'saeidbakhsh-typography-manager' ), __( 'WooCommerce product review titles and the review-form heading.', 'saeidbakhsh-typography-manager' ), '.woocommerce-Reviews-title, #review_form .comment-reply-title' ),
					'wc_review_text' => $this->item( __( 'Review text and links', 'saeidbakhsh-typography-manager' ), __( 'Review content and review-count links; star glyph containers are intentionally excluded.', 'saeidbakhsh-typography-manager' ), '.woocommerce-Reviews .comment-text, .woocommerce-Reviews .description, .woocommerce-review-link' ),
					'wc_spec_labels' => $this->item( __( 'Product specification labels', 'saeidbakhsh-typography-manager' ), __( 'Label cells in the WooCommerce Product Specifications block.', 'saeidbakhsh-typography-manager' ), '.wp-block-product-specifications-item__label' ),
					'wc_spec_values' => $this->item( __( 'Product specification values', 'saeidbakhsh-typography-manager' ), __( 'Value cells in the WooCommerce Product Specifications block.', 'saeidbakhsh-typography-manager' ), '.wp-block-product-specifications-item__value' ),

					'wc_cart_product_names' => $this->item( __( 'Cart product names', 'saeidbakhsh-typography-manager' ), __( 'Product names in the classic Cart and Cart block.', 'saeidbakhsh-typography-manager' ), '.woocommerce-cart-form .product-name, .woocommerce-cart-form .product-name a, .wc-block-cart .wc-block-components-product-name' ),
					'wc_cart_item_meta' => $this->item( __( 'Cart item details', 'saeidbakhsh-typography-manager' ), __( 'Variations and item metadata shown beneath Cart products.', 'saeidbakhsh-typography-manager' ), '.woocommerce-cart-form dl.variation, .wc-block-cart .wc-block-components-product-metadata, .wc-block-cart .wc-block-components-product-details__name, .wc-block-cart .wc-block-components-product-details__value' ),
					'wc_cart_total_labels' => $this->item( __( 'Cart total labels', 'saeidbakhsh-typography-manager' ), __( 'Subtotal, shipping, tax, discount and total labels in Cart totals.', 'saeidbakhsh-typography-manager' ), '.cart_totals th, .wc-block-cart .wc-block-components-totals-item__label, .wc-block-cart .wc-block-components-totals-item__description' ),
					'wc_cart_total_values' => $this->item( __( 'Cart total values', 'saeidbakhsh-typography-manager' ), __( 'Subtotal, shipping, tax, discount and total values in Cart totals.', 'saeidbakhsh-typography-manager' ), '.cart_totals td, .wc-block-cart .wc-block-components-totals-item__value' ),
					'wc_mini_cart_items' => $this->item( __( 'Mini-Cart items', 'saeidbakhsh-typography-manager' ), __( 'Product names, quantities and metadata in classic and block Mini-Cart items.', 'saeidbakhsh-typography-manager' ), '.woocommerce-mini-cart-item, .wc-block-mini-cart .wc-block-components-product-name, .wc-block-mini-cart .wc-block-components-product-metadata, .wc-block-mini-cart .wc-block-components-product-details__name, .wc-block-mini-cart .wc-block-components-product-details__value' ),
					'wc_mini_cart_totals' => $this->item( __( 'Mini-Cart totals', 'saeidbakhsh-typography-manager' ), __( 'Classic Mini-Cart subtotal and block Mini-Cart total labels and values.', 'saeidbakhsh-typography-manager' ), '.woocommerce-mini-cart__total, .wc-block-mini-cart .wc-block-components-totals-item__label, .wc-block-mini-cart .wc-block-components-totals-item__value, .wc-block-mini-cart .wc-block-components-totals-item__description' ),

					'wc_checkout_headings' => $this->item( __( 'Checkout headings', 'saeidbakhsh-typography-manager' ), __( 'Classic checkout section headings and WooCommerce Checkout step titles.', 'saeidbakhsh-typography-manager' ), '#order_review_heading, .woocommerce-billing-fields h3, .woocommerce-shipping-fields h3, .wc-block-checkout .wc-block-components-checkout-step__title' ),
					'wc_checkout_labels' => $this->item( __( 'Checkout field labels', 'saeidbakhsh-typography-manager' ), __( 'Field, checkbox and address labels in classic and block Checkout.', 'saeidbakhsh-typography-manager' ), '.woocommerce-checkout label, .wc-block-checkout .wc-block-components-text-input label, .wc-block-checkout .wc-block-components-checkbox__label, .wc-block-checkout .wc-block-components-address-form label' ),
					'wc_checkout_fields' => $this->item( __( 'Checkout fields', 'saeidbakhsh-typography-manager' ), __( 'Text, textarea and select controls in classic and block Checkout.', 'saeidbakhsh-typography-manager' ), '.woocommerce-checkout input.input-text, .woocommerce-checkout textarea, .woocommerce-checkout select, .wc-block-checkout .wc-block-components-text-input input, .wc-block-checkout .wc-block-components-address-form input, .wc-block-checkout .wc-block-components-address-form select, .wc-block-checkout textarea' ),
					'wc_checkout_product_names' => $this->item( __( 'Checkout product names', 'saeidbakhsh-typography-manager' ), __( 'Product names in the classic order review and Checkout block order summary.', 'saeidbakhsh-typography-manager' ), '.woocommerce-checkout-review-order .product-name, .wc-block-checkout .wc-block-components-product-name' ),
					'wc_checkout_total_labels' => $this->item( __( 'Checkout total labels', 'saeidbakhsh-typography-manager' ), __( 'Subtotal, shipping, tax, discount and order-total labels in Checkout.', 'saeidbakhsh-typography-manager' ), '.woocommerce-checkout-review-order tfoot th, .wc-block-checkout .wc-block-components-totals-item__label, .wc-block-checkout .wc-block-components-totals-item__description' ),
					'wc_checkout_total_values' => $this->item( __( 'Checkout total values', 'saeidbakhsh-typography-manager' ), __( 'Subtotal, shipping, tax, discount and order-total values in Checkout.', 'saeidbakhsh-typography-manager' ), '.woocommerce-checkout-review-order tfoot td, .wc-block-checkout .wc-block-components-totals-item__value' ),
					'wc_payment_methods' => $this->item( __( 'Payment methods', 'saeidbakhsh-typography-manager' ), __( 'Payment-method titles and descriptions in classic and block Checkout.', 'saeidbakhsh-typography-manager' ), '#payment .wc_payment_method label, #payment .payment_box, .wc-block-checkout__payment-method .wc-block-components-radio-control__label, .wc-block-checkout__payment-method .wc-block-components-radio-control__description, .wc-block-components-payment-method-label' ),
					'wc_validation_messages' => $this->item( __( 'Checkout validation messages', 'saeidbakhsh-typography-manager' ), __( 'Field-level validation and invalid-field messages in WooCommerce Checkout.', 'saeidbakhsh-typography-manager' ), '.woocommerce-checkout .woocommerce-invalid label, .wc-block-checkout .wc-block-components-validation-error' ),

					'wc_account_navigation' => $this->item( __( 'My Account navigation', 'saeidbakhsh-typography-manager' ), __( 'WooCommerce My Account menu items and links.', 'saeidbakhsh-typography-manager' ), '.woocommerce-MyAccount-navigation, .woocommerce-MyAccount-navigation a' ),
					'wc_account_content' => $this->item( __( 'My Account content', 'saeidbakhsh-typography-manager' ), __( 'The main WooCommerce My Account content area.', 'saeidbakhsh-typography-manager' ), '.woocommerce-MyAccount-content' ),
					'wc_account_orders' => $this->item( __( 'My Account orders', 'saeidbakhsh-typography-manager' ), __( 'Order tables, headings and cells in WooCommerce My Account.', 'saeidbakhsh-typography-manager' ), '.woocommerce-orders-table, .woocommerce-orders-table th, .woocommerce-orders-table td' ),
					'wc_account_addresses' => $this->item( __( 'My Account addresses', 'saeidbakhsh-typography-manager' ), __( 'Billing and shipping address headings, content and edit links in My Account.', 'saeidbakhsh-typography-manager' ), '.woocommerce-Addresses, .woocommerce-Address-title, .woocommerce-Address-title a, .woocommerce-Address address' ),
				),
			),
		);

		/**
		 * Filter the complete categorized element registry.
		 *
		 * @param array<string,array<string,mixed>> $groups Element groups.
		 */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Public filter retains the former Saeidbakhsh Element Font Manager prefix for backward compatibility.
		return apply_filters( 'sefm_element_groups', $groups );
	}

	/**
	 * Flatten element definitions by ID.
	 *
	 * @return array<string,array<string,string>>
	 */
	public function all() {
		$all = array();
		foreach ( $this->groups() as $group ) {
			if ( empty( $group['elements'] ) || ! is_array( $group['elements'] ) ) {
				continue;
			}
			$all = array_merge( $all, $group['elements'] );
		}

		return $all;
	}

	/**
	 * Logical targets available to the Entire site rule.
	 *
	 * The legacy selector toggle represented all three targets at once. Keeping
	 * these as logical tokens lets administrators exclude icon or SVG rendering
	 * without changing the selector registry used by older saved settings.
	 *
	 * @return array<int,string>
	 */
	public static function site_targets() {
		return array( 'all', 'icons', 'svg' );
	}

	/**
	 * Resolve active Entire site targets with backward compatibility.
	 *
	 * A genuinely new/unsaved rule defaults to regular page elements only, so
	 * Icon and SVG are opt-in. Saved legacy rules keep their historical scope:
	 * a checked legacy selector maps to all three targets, an explicitly empty
	 * selector array stays disabled, and a pre-target assignment without selector
	 * state keeps full coverage rather than changing an existing site on update.
	 *
	 * @param array<string,mixed> $assignment Saved or submitted assignment.
	 * @return array<int,string>
	 */
	public static function active_site_targets( array $assignment ) {
		$allowed = self::site_targets();

		if ( isset( $assignment['site_targets'] ) && is_array( $assignment['site_targets'] ) ) {
			$stored = array_values(
				array_filter(
					array_map(
						static function ( $value ) {
							return is_scalar( $value ) ? sanitize_key( (string) $value ) : '';
						},
						$assignment['site_targets']
					),
					'strlen'
				)
			);

			return array_values( array_intersect( $allowed, $stored ) );
		}

		if ( array_key_exists( 'selectors', $assignment ) && is_array( $assignment['selectors'] ) ) {
			return empty( $assignment['selectors'] ) ? array() : $allowed;
		}

		if ( ! empty( $assignment ) ) {
			return $allowed;
		}

		return array( 'all' );
	}

	/**
	 * Split one registry selector into user-toggleable targets.
	 *
	 * The registry keeps Entire site as one legacy selector pair; its newer *,
	 * Icon and SVG scope switches are resolved separately by site_targets().
	 *
	 * @param string $selector CSS selector list.
	 * @param string $key Element definition ID.
	 * @return array<int,string>
	 */
	public static function selector_parts( $selector, $key = '' ) {
		$selector = trim( (string) $selector );
		if ( '' === $selector ) {
			return array();
		}

		if ( 'site' === $key ) {
			return array( $selector );
		}

		$parts       = array();
		$buffer      = '';
		$parentheses = 0;
		$brackets    = 0;
		$quote       = '';
		$escaped     = false;
		$length      = strlen( $selector );

		for ( $index = 0; $index < $length; $index++ ) {
			$character = $selector[ $index ];
			if ( $escaped ) {
				$buffer .= $character;
				$escaped = false;
				continue;
			}

			if ( '\\' === $character ) {
				$buffer .= $character;
				$escaped = true;
				continue;
			}

			if ( '' !== $quote ) {
				$buffer .= $character;
				if ( $character === $quote ) {
					$quote = '';
				}
				continue;
			}

			if ( '"' === $character || "'" === $character ) {
				$quote   = $character;
				$buffer .= $character;
				continue;
			}

			if ( '(' === $character ) {
				$parentheses++;
			} elseif ( ')' === $character && $parentheses > 0 ) {
				$parentheses--;
			} elseif ( '[' === $character ) {
				$brackets++;
			} elseif ( ']' === $character && $brackets > 0 ) {
				$brackets--;
			}

			if ( ',' === $character && 0 === $parentheses && 0 === $brackets ) {
				$part = trim( $buffer );
				if ( '' !== $part ) {
					$parts[] = $part;
				}
				$buffer = '';
				continue;
			}

			$buffer .= $character;
		}

		$part = trim( $buffer );
		if ( '' !== $part ) {
			$parts[] = $part;
		}

		return $parts;
	}

	/**
	 * Construct a definition.
	 *
	 * @param string $label Label.
	 * @param string $description Human explanation.
	 * @param string $selector CSS selector.
	 * @return array<string,string>
	 */
	private function item( $label, $description, $selector ) {
		return array(
			'label'       => $label,
			'description' => $description,
			'selector'    => $selector,
		);
	}
}
