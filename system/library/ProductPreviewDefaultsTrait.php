<?php
/**
 * Single source of truth for 商品卡片 / 商品详情页 appearance defaults.
 *
 * OpenCart loads admin/ and catalog/ models as separate files that both
 * declare `class ModelCatalogProduct`, so an instance cannot be shared
 * across contexts. This trait lets both models expose the same pure-data
 * default arrays without duplication:
 *   - catalog model: getCardConfig()/getDetailConfig() read these as the
 *     fallback when no setting is saved (frontend rendering).
 *   - admin model: the 商品卡片管理 / 商品详情页管理 forms read these to
 *     pre-fill fields for a product type that was never customized.
 */
trait ProductPreviewDefaultsTrait {
	public function cardDefaults() {
		return array(
			'product_card_show_wishlist'   => 1,
			'product_card_show_add_button' => 1,
			'product_card_image_height'    => 200,
			'product_card_desc_length'     => 100,
			'product_card_desc_clamp'      => 2,
			'product_card_name_font_size'  => 15,
			'product_card_price_font_size' => 22,
			'product_card_add_btn_text'    => '+',
			'product_card_primary_color'   => '#10B981',
			'product_card_name_color'      => '#0F172A',
			'product_card_price_color'     => '#10B981',
			// Static badges repeater: admin-defined badges (text + bg + text color)
			// shown on every card of this type, on top of the data-driven badges.
			'product_card_badges'          => array(),
		);
	}

	/**
	 * Defaults for 商品详情页 (product_detail).
	 * Shared by getDetailConfig() (frontend PDP) and the admin 商品详情页 form.
	 *
	 * trust_items / research_links / tabs are JSON repeaters (serialized setting
	 * rows) so the admin can add/remove/reorder any number of rows - no longer
	 * capped at 3 trust items / 4 research links / 3 fixed tabs. A tab with
	 * is_details=1 renders the product description + specifications table
	 * (preserving the original DETAILS-tab behaviour).
	 */
	public function detailDefaults() {
		return array(
			'product_detail_show_breadcrumb'        => 1,
			'product_detail_show_gallery'           => 1,
			'product_detail_show_badges'            => 1,
			'product_detail_show_trust_box'         => 1,
			'product_detail_show_tabs'              => 1,
			'product_detail_show_related'           => 1,
			'product_detail_show_research'          => 1,
			'product_detail_title_font_size'        => 38,
			'product_detail_body_font_size'         => 15,
			'product_detail_coa_badge_text'         => '★ COA ON FILE',
			'product_detail_batch_verified_text'    => 'BATCH-VERIFIED',
			'product_detail_trust_items'            => array(
				'30-day lab-verified guarantee',
				'Free shipping over $150',
				'Certificate of Analysis included',
			),
			'product_detail_tabs'                   => array(
				array(
					'label'      => 'DETAILS',
					'body'       => 'Premium research-grade compound. Third-party HPLC tested with batch-specific Certificate of Analysis. Each order includes full documentation for complete traceability.',
					'is_details' => 1,
				),
				array(
					'label'      => 'CERTIFICATE OF ANALYSIS',
					'body'       => "Every batch of this compound undergoes independent third-party High-Performance Liquid Chromatography (HPLC) and Mass Spectrometry (MS) verification. A Certificate of Analysis (COA) is included with every order, documenting batch-specific purity data, molecular weight confirmation, and analytical methodology.\n\nTo request a specific batch COA, contact our support team with your order number.",
					'is_details' => 0,
				),
				array(
					'label'      => 'SHIPPING & RETURNS',
					'body'       => "Orders placed before 2 PM EST ship same business day via express courier with temperature-controlled packaging. Domestic delivery typically arrives within 2-3 business days. International orders ship within 24 hours and arrive in 5-10 business days depending on customs clearance.\n\nReturns accepted within 30 days for unopened products. If a product does not meet stated purity specifications, a full refund is issued upon verification.",
					'is_details' => 0,
				),
			),
			'product_detail_related_title'          => 'MORE COMPOUNDS',
			'product_detail_research_title'         => 'RESEARCH LIBRARY',
			'product_detail_research_links'         => array(
				array('label' => 'The Complete Guide to HPLC Testing for Research Peptides', 'url' => '#'),
				array('label' => 'Understanding Mass Spectrometry for Peptide Verification', 'url' => '#'),
				array('label' => 'Cold-Chain Logistics: Best Practices for Peptide Storage', 'url' => '#'),
				array('label' => 'Endotoxin Testing in Peptide Research: Why It Matters', 'url' => '#'),
			),
			'product_detail_primary_color'          => '#10B981',
			'product_detail_bg_navy'                => '#0F172A',
		);
	}
}
