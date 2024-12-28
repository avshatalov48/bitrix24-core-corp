/**
 * @module sign/type/member-role
 */
jn.define('sign/type/member-role', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class MemberRole
	 * @extends {BaseEnum<MemberRole>}
	 */
	class MemberRole extends BaseEnum
	{
		static ASSIGNEE = new MemberRole('ASSIGNEE', 'assignee');

		static SIGNER = new MemberRole('SIGNER', 'signer');

		static EDITOR = new MemberRole('EDITOR', 'editor');

		static REVIEWER = new MemberRole('REVIEWER', 'reviewer');

		/**
		 * @param {MemberRole.value} value
		 * @returns {Boolean}
		 * */
		static getEnumByValue(value)
		{
			return Object.values(MemberRole).find((memberRole) => memberRole.value === value);
		}
	}

	module.exports = { MemberRole };
});
