/**
 * @module sign/type/member-status
 */
jn.define('sign/type/member-status', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class MemberStatus
	 * @extends {BaseEnum<MemberStatus>}
	 */
	class MemberStatus extends BaseEnum
	{
		static DONE = new MemberStatus('DONE', 'Y');

		static WAIT = new MemberStatus('WAIT', 'N');

		static READY = new MemberStatus('READY', 'R');

		static REFUSED = new MemberStatus('REFUSED', 'C');

		static STOPPED = new MemberStatus('STOPPED', 'S');

		static STOPPABLE_READY = new MemberStatus('STOPPABLE_READY', 'F');

		static PROCESSING = new MemberStatus('PROCESSING', 'P');

		/**
		 * @param {MemberStatus.value} value
		 * @returns {Boolean}
		 * */
		static isReadyStatusFromPresentedView(value)
		{
			return 'ready' === value || 'stoppable_ready' === value;
		}

		/**
		 * @param {MemberStatus.value} value
		 * @returns {Boolean}
		 * */
		static isCanceledStatus(value)
		{
			return MemberStatus.STOPPED.value === value || MemberStatus.REFUSED.value === value;
		}

		/**
		 * @param {MemberStatus.value} value
		 * @returns {Boolean}
		 * */
		static isDoneStatus(value)
		{
			return MemberStatus.DONE.value === value;
		}

		/**
		 * @param {MemberStatus.value} value
		 * @returns {Boolean}
		 * */
		static isNotFinishedStatus(value)
		{
			return MemberStatus.DONE.value !== value
				|| MemberStatus.STOPPED.value !== value
				|| MemberStatus.REFUSED.value !== value
			;
		}
	}

	module.exports = { MemberStatus };
});
