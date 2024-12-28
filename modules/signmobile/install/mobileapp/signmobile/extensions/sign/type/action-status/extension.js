/**
 * @module sign/type/action-status
 */
jn.define('sign/type/action-status', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class ActionStatus
	 * @extends {BaseEnum<ActionStatus>}
	 */
	class ActionStatus extends BaseEnum
	{
		static DOWNLOAD = new ActionStatus('DOWNLOAD', 'download');

		static VIEW = new ActionStatus('VIEW', 'view');

		static SIGN = new ActionStatus('SIGN', 'sign');

		static APPROVE = new ActionStatus('APPROVE', 'approve');

		static EDIT = new ActionStatus('EDIT', 'edit');

		/**
		 * @param {ActionStatus.value} value
		 * @returns {Boolean}
		 * */
		static isActionStatus(value)
		{
			return value === ActionStatus.EDIT.value
				|| value === ActionStatus.APPROVE.value
				|| value === ActionStatus.SIGN.value
			;
		}

		/**
		 * @param {ActionStatus.value} value
		 * @returns {Boolean}
		 * */
		static isDownloadStatus(value)
		{
			return value === ActionStatus.VIEW.value
				|| value === ActionStatus.DOWNLOAD.value
			;
		}
	}

	module.exports = { ActionStatus };
});
