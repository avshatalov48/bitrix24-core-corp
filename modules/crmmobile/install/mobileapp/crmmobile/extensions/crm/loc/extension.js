/**
 * @module crm/loc
 */
jn.define('crm/loc', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('crm/type');

	/**
	 * @param {String} messageCode
	 * @param {String|Number} entityType
	 * @returns {String}
	 */
	const getEntityMessage = (messageCode, entityType) => {
		const entityTypeName = Type.getCommonEntityTypeName(entityType);
		if (!entityTypeName)
		{
			return Loc.hasMessage(messageCode) ? Loc.getMessage(messageCode) : '';
		}

		const entityCode = `${messageCode}_${entityTypeName.toUpperCase()}`;
		if (Loc.hasMessage(entityCode))
		{
			return Loc.getMessage(entityCode);
		}

		if (Loc.hasMessage(messageCode))
		{
			return Loc.getMessage(messageCode);
		}

		return '';
	};

	/**
	 * @param {String} messageCode
	 * @param {String|Number} entityType
	 * @param {Number} value
	 * @returns {null|String}
	 */
	const getEntityMessagePlural = (messageCode, entityType, value) => {
		const entityTypeName = Type.getCommonEntityTypeName(entityType);
		if (!entityTypeName)
		{
			return null;
		}

		const entityCode = `${messageCode}_${entityTypeName}`;
		let message = Loc.getMessagePlural(entityCode, value);

		if (!message)
		{
			message = Loc.getMessagePlural(messageCode, value);
		}

		return message;
	};

	module.exports = {
		getEntityMessage,
		getEntityMessagePlural,
	};
});
