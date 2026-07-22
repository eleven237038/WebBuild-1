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
		);
	}

	/**
	 * Defaults for 商品详情页 (product_detail).
	 * Shared by getDetailConfig() (frontend PDP) and the admin 商品详情页 form.
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
			'product_detail_tab_details_label'      => 'DETAILS',
			'product_detail_tab_coa_label'          => 'CERTIFICATE OF ANALYSIS',
			'product_detail_tab_shipping_label'     => 'SHIPPING & RETURNS',
			'product_detail_tab_details_body'       => 'Premium research-grade compound. Third-party HPLC tested with batch-specific Certificate of Analysis. Each order includes full documentation for complete traceability.',
			'product_detail_tab_coa_body'           => "Every batch of this compound undergoes independent third-party High-Performance Liquid Chromatography (HPLC) and Mass Spectrometry (MS) verification. A Certificate of Analysis (COA) is included with every order, documenting batch-specific purity data, molecular weight confirmation, and analytical methodology.\n\nTo request a specific batch COA, contact our support team with your order number.",
			'product_detail_tab_shipping_body'      => "Orders placed before 2 PM EST ship same business day via express courier with temperature-controlled packaging. Domestic delivery typically arrives within 2-3 business days. International orders ship within 24 hours and arrive in 5-10 business days depending on customs clearance.\n\nReturns accepted within 30 days for unopened products. If a product does not meet stated purity specifications, a full refund is issued upon verification.",
			'product_detail_trust_item_1'           => '30-day lab-verified guarantee',
			'product_detail_trust_item_2'           => 'Free shipping over $150',
			'product_detail_trust_item_3'           => 'Certificate of Analysis included',
			'product_detail_related_title'          => 'MORE COMPOUNDS',
			'product_detail_research_title'         => 'RESEARCH LIBRARY',
			'product_detail_research_link_1_label'  => 'The Complete Guide to HPLC Testing for Research Peptides',
			'product_detail_research_link_1_url'    => '#',
			'product_detail_research_link_2_label'  => 'Understanding Mass Spectrometry for Peptide Verification',
			'product_detail_research_link_2_url'    => '#',
			'product_detail_research_link_3_label'  => 'Cold-Chain Logistics: Best Practices for Peptide Storage',
			'product_detail_research_link_3_url'    => '#',
			'product_detail_research_link_4_label'  => 'Endotoxin Testing in Peptide Research: Why It Matters',
			'product_detail_research_link_4_url'    => '#',
			'product_detail_primary_color'          => '#10B981',
			'product_detail_bg_navy'                => '#0F172A',
		);
	}
}
