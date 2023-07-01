/**
 * @module crm/entity-tab/type/company
 */
jn.define('crm/entity-tab/type/company', (require, exports, module) => {
	const { TypeId, TypeName } = require('crm/type');
	const { Base: BaseEntityType } = require('crm/entity-tab/type/base');

	/**
	 * @class Company
	 */
	class Company extends BaseEntityType
	{
		/**
		 * @returns {Number}
		 */
		getId()
		{
			return TypeId.Company;
		}

		/**
		 * @returns {String}
		 */
		getName()
		{
			return TypeName.Company;
		}
	}

	module.exports = { Company };
});
