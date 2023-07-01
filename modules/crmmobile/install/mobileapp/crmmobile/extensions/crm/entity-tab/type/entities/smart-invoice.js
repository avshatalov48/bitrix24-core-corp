/**
 * @module crm/entity-tab/type/entities/smart-invoice
 */
jn.define('crm/entity-tab/type/entities/smart-invoice', (require, exports, module) => {
	const { Base: BaseEntityType } = require('crm/entity-tab/type/entities/base');
	const { TypeId, TypeName } = require('crm/type');

	/**
	 * @class SmartInvoice
	 */
	class SmartInvoice extends BaseEntityType
	{
		/**
		 * @returns {Number}
		 */
		getId()
		{
			return TypeId.SmartInvoice;
		}

		/**
		 * @returns {String}
		 */
		getName()
		{
			return TypeName.SmartInvoice;
		}

		getMenuActions()
		{
			return [
				{
					type: UI.Menu.Types.HELPDESK,
					data: {
						articleCode: '17418408',
					},
				},
			];
		}
	}

	module.exports = { SmartInvoice };
});
