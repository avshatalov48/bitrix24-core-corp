/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/collab/dialog
 */
jn.define('im/messenger/provider/pull/collab/dialog', (require, exports, module) => {
	const { ChatDialogPullHandler } = require('im/messenger/provider/pull/chat');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { Counters } = require('im/messenger/lib/counters');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--collab-dialog');

	/**
	 * @class CollabDialogPullHandler
	 */
	class CollabDialogPullHandler extends ChatDialogPullHandler
	{
		constructor()
		{
			super({ logger });
		}

		/**
		 * @param {String} dialogId
		 * @void
		 */
		deleteCounters(dialogId)
		{
			delete Counters.collabCounter.detail[dialogId];
		}
	}

	module.exports = {
		CollabDialogPullHandler,
	};
});
