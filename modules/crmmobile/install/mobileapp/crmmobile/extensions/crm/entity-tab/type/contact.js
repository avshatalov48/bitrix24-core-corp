/**
 * @module crm/entity-tab/type/contact
 */
jn.define('crm/entity-tab/type/contact', (require, exports, module) => {
	const { TypeId, TypeName } = require('crm/type');
	const { Base: BaseEntityType } = require('crm/entity-tab/type/base');

	/**
	 * @class Contact
	 */
	class Contact extends BaseEntityType
	{
		/**
		 * @returns {Number}
		 */
		getId()
		{
			return TypeId.Contact;
		}

		/**
		 * @returns {String}
		 */
		getName()
		{
			return TypeName.Contact;
		}
	}

	module.exports = { Contact };
});
