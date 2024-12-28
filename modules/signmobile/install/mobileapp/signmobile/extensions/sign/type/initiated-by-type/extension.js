/**
 * @module sign/type/initiated-by-type
 */
jn.define('sign/type/initiated-by-type', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class InitiatedByType
	 * @extends {BaseEnum<InitiatedByType>}
	 */
	class InitiatedByType extends BaseEnum
	{
		static COMPANY = new InitiatedByType('COMPANY', 'company');

		static EMPLOYEE = new InitiatedByType('EMPLOYEE', 'employee');

		/**
		 * @param {InitiatedByType.value} value
		 * @returns {Boolean}
		 * */
		static isInitiatedByEmployee(value)
		{
			return InitiatedByType.EMPLOYEE.value === value;
		}
	}

	module.exports = { InitiatedByType };
});
