/**
 * @module crm/entity-detail/toolbar
 */
jn.define('crm/entity-detail/toolbar', (require, exports, module) => {
	const { Type } = require('crm/type');
	const { DealDetailToolbar } = require('crm/entity-detail/toolbar/deal');
	const { ActivityToolbar } = require('crm/entity-detail/toolbar/activity');
	const { ToolbarPanelWrapper } = require('crm/entity-detail/toolbar/panel');

	/**
	 * @class DetailToolbarFactory
	 */
	class DetailToolbarFactory
	{
		/**
		 * @param {Number|String} typeId
		 * @param {String} activeTab
		 * @param {Object?} data
		 * @return {null|DealDetailToolbar}
		 */
		static create({ typeId, activeTab }, data = {})
		{
			if (DetailToolbarFactory.has({ typeId, activeTab }))
			{
				const props = {
					...data,
					fade: false,
					children: ActivityToolbar,
				};

				return new ToolbarPanelWrapper(props);
			}

			// 	return new ToolbarPanelWrapper(props);

			return null;
		}

		/**
		 * @param {Number|String} typeId
		 * @param activeTab
		 * @return {Boolean}
		 */
		static has({ typeId })
		{
			if (!Type.existsById(typeId))
			{
				typeId = Type.resolveIdByName(typeId);
			}

			return Number.isInteger(typeId);
		}

	}

	module.exports = { DetailToolbarFactory };
});
