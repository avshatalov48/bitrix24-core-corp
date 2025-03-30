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

		static isReviewerRole(value)
		{
			return MemberRole.REVIEWER.value === value;
		}

		/**
		 * @param {MemberRole.value} value
		 * @returns {Boolean}
		 * */

		static isSignerRole(value)
		{
			return MemberRole.SIGNER.value === value;
		}
	}

	module.exports = { MemberRole };
});
