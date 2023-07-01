/**
 * @module crm/entity-detail/toolbar
 */
jn.define('crm/entity-detail/toolbar', (require, exports, module) => {
	const { Type } = require('crm/type');
	const { ToolbarContent } = require('crm/entity-detail/toolbar/content');
	const { ToolbarPanelWrapper } = require('crm/entity-detail/toolbar/panel');

	/**
	 * @class DetailToolbarFactory
	 */
	class DetailToolbarFactory
	{
		/**
		 * @param {Number|String} typeId
		 * @param {Object?} data
		 * @return {null|ToolbarPanelWrapper}
		 */
		static create({ typeId }, data = {})
		{
			if (this.has({ typeId }))
			{
				return new ToolbarPanelWrapper({
					...data,
					fade: false,
					children: ToolbarContent,
				});
			}

			return null;
		}

		/**
		 * @param {Number|String} typeId
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
