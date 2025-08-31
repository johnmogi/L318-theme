jQuery(function ($) {
	/**
	 * Remove the “/ 12 months”, “for 12 months”, etc.
	 * that YITH (or Woo Subscriptions) appends after the price.
	 */
	function cleanPrice($context) {
		$context.find('p.price, .woocommerce-variation-price, .ywsbs-price')   // cover all spots
			.each(function () {
				const $p = $(this);

				// 1. Kill known extra spans
				$p.find('.price_time_opt, .price_time, .ywsbs_subscription_details').remove();

				// 2. Remove any leftover plain-text tail (e.g. " for 12 months")
				$p.contents().filter(function () {
					return this.nodeType === 3 && $.trim(this.nodeValue).match(/^\s*(for|\/)/i);
				}).remove();
			});
	}

	// Run once on initial load
	cleanPrice($(document));

	// Re-run whenever a variation is selected (variable subs)
	$(document).on('found_variation wc_variation_form', function (e, form) {
		cleanPrice($(form));
	});
});