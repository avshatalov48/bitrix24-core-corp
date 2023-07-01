/**
 * @module crm/entity-detail/component/set-available-tabs
 */
jn.define('crm/entity-detail/component/set-available-tabs', (require, exports, module) => {
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');

	/**
	 * @param {[]} tabs
	 * @param {DetailCardComponent} detailCard
	 * @returns {[]}
	 */
	const setAvailableTabs = (tabs, detailCard) => {
		const timelineTab = tabs.find((tab) => tab.id === TabType.TIMELINE);
		if (timelineTab)
		{
			timelineTab.available = !detailCard.isNewEntity();
		}

		return tabs;
	};

	module.exports = { setAvailableTabs };
});
