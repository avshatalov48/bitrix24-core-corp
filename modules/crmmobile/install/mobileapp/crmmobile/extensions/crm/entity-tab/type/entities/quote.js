/**
 * @module crm/entity-tab/type/entities/quote
 */
jn.define('crm/entity-tab/type/entities/quote', (require, exports, module) => {
	const { Base: BaseEntityType } = require('crm/entity-tab/type/entities/base');
	const { TypeId, TypeName } = require('crm/type');

	/**
	 * @class Quote
	 */
	class Quote extends BaseEntityType
	{
		/**
		 * @returns {Number}
		 */
		getId()
		{
			return TypeId.Quote;
		}

		/**
		 * @returns {String}
		 */
		getName()
		{
			return TypeName.Quote;
		}
	}

	module.exports = { Quote };
});
