/**
 * @module layout/ui/detail-card/tabs/factory/type
 */
jn.define('layout/ui/detail-card/tabs/factory/type', (require, exports, module) => {

	/** @var TabType */
	const TabType = {
		EDITOR: 'editor',
		PRODUCT: 'product',
		CRM_PRODUCT: 'crm-product',
		TIMELINE: 'timeline',
	};

	module.exports = { TabType };

});
