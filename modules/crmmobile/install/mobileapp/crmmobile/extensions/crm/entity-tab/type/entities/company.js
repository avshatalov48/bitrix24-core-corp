/**
 * @module crm/entity-tab/type/entities/company
 */
jn.define('crm/entity-tab/type/entities/company', (require, exports, module) => {
	const { TypeId, TypeName } = require('crm/type');
	const { Base: BaseEntityType } = require('crm/entity-tab/type/entities/base');

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

		getEmptyColumnScreenConfig(data)
		{
			return {};
		}

		getIconName()
		{
			return this.getName().toLowerCase();
		}
	}

	module.exports = { Company };
});
