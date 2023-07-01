/**
 * @module crm/entity-tab/type
 */

jn.define('crm/entity-tab/type', (require, exports, module) => {
	const { TypeName } = require('crm/type');
	const { Deal } = require('crm/entity-tab/type/entities/deal');
	const { Contact } = require('crm/entity-tab/type/entities/contact');
	const { Company } = require('crm/entity-tab/type/entities/company');
	const { Lead } = require('crm/entity-tab/type/entities/lead');
	const { SmartInvoice } = require('crm/entity-tab/type/entities/smart-invoice');
	const { Quote } = require('crm/entity-tab/type/entities/quote');

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

			if (entityTypeName === TypeName.Lead)
			{
				return new Lead(params);
			}

			if (entityTypeName === TypeName.SmartInvoice)
			{
				return new SmartInvoice(params);
			}

			if (entityTypeName === TypeName.Quote)
			{
				return new Quote(params);
			}

			// @todo for debug, may remove later
			console.error(`Entity type name: ${entityTypeName} not known`);

			return null;
		}
	}

	module.exports = { TypeFactory };
});
