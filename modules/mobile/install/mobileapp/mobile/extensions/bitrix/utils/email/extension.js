/**
 * @module utils/email
 */
jn.define('utils/email', (require, exports, module) => {
	const { Type } = require('type');
	const { domains } = require('utils/email/src/domains');
	const DEFAULT = 'default';

	const emailRegExp = /([\w%+.-]+@(?:[\dA-Za-z-]+\.)+[A-Za-z]{2,})/;

	/**
	 * @param {string} email
	 * @returns {string|null}
	 */
	function getEmailDomain(email)
	{
		if (!Type.isStringFilled(email) || email.trim() === '')
		{
			return null;
		}

		const startIndex = email.lastIndexOf('@') + 1;

		return email.slice(startIndex).trim();
	}

	/**
	 * @param {string} value
	 * @returns {string}
	 */
	function getEmailServiceName(value)
	{
		const domain = getEmailDomain(value);
		const service = Object.keys(domains).find((name) => domains[name].includes(domain));

		return service || DEFAULT;
	}

	/**
	 * @function isValidEmail
	 * @param {string} email
	 * @return {Boolean}
	 */
	function isValidEmail(email)
	{
		if (typeof email !== 'string')
		{
			return false;
		}

		return emailRegExp.test(email);
	}

	/**
	 * @param {string} [service]
	 * @param {string} [email]
	 * @returns {string}
	 */
	function getDomainImageUri({ service, email } = {})
	{
		const imagePath = (imageName) => `${currentDomain}/bitrix/mobileapp/mobile/extensions/bitrix/utils/email/images/${imageName}.png`;

		if (email)
		{
			return imagePath(getEmailServiceName(email));
		}

		return imagePath(Object.keys(domains).includes(service) ? service : DEFAULT);
	}

	module.exports = {
		isValidEmail,
		emailRegExp: new RegExp(emailRegExp.source),
		getEmailDomain,
		getDomainImageUri,
		getEmailServiceName,
		defaultImageName: DEFAULT,
	};
});
