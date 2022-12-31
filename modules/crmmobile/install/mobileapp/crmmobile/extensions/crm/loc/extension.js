/**
 * @module crm/loc
 */
jn.define('crm/loc', (require, exports, module) => {

	const { Loc } = require('loc');
	const { Type } = require('crm/type');

	/**
	 * @param {String} messageCode
	 * @param {String|Number} entityType
	 * @returns {null|String}
	 */
	const getEntityMessage = (messageCode, entityType) => {
		const entityTypeName = getEntityTypeName(entityType);
		if (!entityTypeName)
		{
			return null;
		}

		const entityCode = `${messageCode}_${entityTypeName.toUpperCase()}`;
		let message = Loc.getMessage(entityCode);

		if (!message)
		{
			message = Loc.getMessage(messageCode);
		}

		return message;
	};

	/**
	 * @param {String} messageCode
	 * @param {String|Number} entityType
	 * @param {Number} value
	 * @returns {null|String}
	 */
	const getEntityMessagePlural = (messageCode, entityType, value) => {
		const entityTypeName = getEntityTypeName(entityType);
		if (!entityTypeName)
		{
			return null;
		}

		const entityCode = `${messageCode}_${entityTypeName.toUpperCase()}`;
		let message = Loc.getMessagePlural(entityCode, value);

		if (!message)
		{
			message = Loc.getMessagePlural(messageCode, value);
		}

		return message;
	};

	/**
	 * @private
	 */
	const getEntityTypeName = (entityType) => {
		if (Type.existsByName(entityType))
		{
			return entityType;
		}

		if (Type.existsById(entityType))
		{
			return Type.resolveNameById(entityType);
		}

		return null;
	};

	module.exports = {
		getEntityMessage,
		getEntityMessagePlural,
	};
});
