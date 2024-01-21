/**
 * @module communication/connection
 */
jn.define('communication/connection', (require, exports, module) => {

	const { Loc } = require('loc');
	const { isEmpty } = require('utils/object');
	const { stringify } = require('utils/string');

	const PhoneType = 'phone';
	const ImType = 'im';
	const EmailType = 'email';

	const isExistContacts = (entityValue, entityType) => {

		if (isEmpty(entityValue))
		{
			return false;
		}

		return Boolean(
			Object
				.keys(entityValue)
				.filter((entityName) => Array.isArray(entityValue[entityName]))
				.map((entityName) => entityValue[entityName].map((value) => {
					return Array.isArray(value[entityType])
						? value[entityType].filter(({ value }) => entityType !== ImType || isOpenLine(value))
						: value[entityType];
				}))
				.flat(Infinity)
				.filter(Boolean)
				.length,
		);
	};

	/**
	 *
	 * @param {string} userCode
	 * @return {boolean}
	 */
	const isOpenLine = (userCode) => stringify(userCode).startsWith('imol|');

	const getOpenLineType = (userCode) => {
		userCode = stringify(userCode);

		const parts = userCode.split('|');

		if (parts.length < 2 || parts[0] !== 'imol')
		{
			return null;
		}

		return parts[1];
	};

	const getOpenLineTitle = (userCode, useDefaultPhrase = true) => {
		const type = getOpenLineType(userCode);
		if (!type && useDefaultPhrase)
		{
			return Loc.getMessage('CRM_OPEN_LINE_SEND_MESSAGE');
		}

		let phrase = Loc.hasMessage(`CRM_OPEN_LINE_${type.toUpperCase()}_MSGVER_1`)
			? Loc.getMessage(`CRM_OPEN_LINE_${type.toUpperCase()}_MSGVER_1`)
			: Loc.getMessage(`CRM_OPEN_LINE_${type.toUpperCase()}`);

		if (!phrase && useDefaultPhrase)
		{
			phrase = Loc.getMessage('CRM_OPEN_LINE_SEND_MESSAGE');
		}

		return phrase;
	};

	module.exports = {
		PhoneType,
		ImType,
		EmailType,
		isExistContacts,
		isOpenLine,
		getOpenLineType,
		getOpenLineTitle,
	};
});
