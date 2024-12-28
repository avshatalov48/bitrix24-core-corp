/**
 * @module sign/type/document-status
 */
jn.define('sign/type/document-status', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class DocumentStatus
	 * @extends {BaseEnum<DocumentStatus>}
	 */
	class DocumentStatus extends BaseEnum
	{
		static NEW = new DocumentStatus('COMPANY', 'new');

		static READY = new DocumentStatus('READY', 'ready');

		static STOPPED = new DocumentStatus('STOPPED', 'stopped');

		static SIGNING = new DocumentStatus('SIGNING', 'signing');

		static DONE = new DocumentStatus('DONE', 'done');

		static UPLOADED = new DocumentStatus('UPLOADED', 'uploaded');

		/**
		 * @param {DocumentStatus.value} value
		 * @returns {Boolean}
		 * */
		static isFinalStatus(value)
		{
			return DocumentStatus.DONE.value === value
				|| DocumentStatus.STOPPED.value === value;
		}

		/**
		 * @param {DocumentStatus.value} value
		 * @returns {Boolean}
		 * */
		static isStopped(value)
		{
			return DocumentStatus.STOPPED.value === value;
		}
	}

	module.exports = { DocumentStatus };
});
