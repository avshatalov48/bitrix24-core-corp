/**
 * @module crm/entity-tab/type
 */

jn.define('crm/entity-tab/type', (require, exports, module) => {
	const { TypeName } = require('crm/type');
	const { Deal } = require('crm/entity-tab/type/deal');
	const { Contact } = require('crm/entity-tab/type/contact');
	const { Company } = require('crm/entity-tab/type/company');

	/**
	 * @class TypeFactory
	 */
	class TypeFactory
	{
		static getEntityByType(entityTypeName, params)
		{
			const factory = new TypeFactory(entityTypeName);
			return factory.getEntity(params);
		}

		/**
		 * @param {String} entityTypeName
		 */
		constructor(entityTypeName)
		{
			this.entityTypeName = entityTypeName;
		}

		/**
		 * @param {Object} params
		 * @returns {null|Object}
		 */
		getEntity(params)
		{
			const entityTypeName = this.entityTypeName;

			if (entityTypeName === TypeName.Deal)
			{
				return new Deal(params);
			}

			if (entityTypeName === TypeName.Contact)
			{
				return new Contact(params);
			}

			if (entityTypeName === TypeName.Company)
			{
				return new Company(params);
			}

			// @todo for debug, may remove later
			console.error(`Entity type name: ${entityTypeName} not known`);

			return null;
		}
	}

	module.exports = { TypeFactory };

});
