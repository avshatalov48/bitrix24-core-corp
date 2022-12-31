/**
 * @module crm/entity-actions/public-errors
 */
jn.define('crm/entity-actions/public-errors', (require, exports, module) => {

	/**
	 * @function getPublicErrors
	 * @param errors object[]
	 */
	const getPublicErrors = (errors) =>
		errors.find(({ customData, message }) => customData && customData.public && message);

	module.exports = { getPublicErrors };
});