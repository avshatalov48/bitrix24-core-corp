/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/base/application
 */
jn.define('im/messenger/provider/pull/base/application', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base/pull-handler');
	const { Type } = require('type');
	const {
		EventType,
		ComponentCode,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');

	/**
	 * @class BaseApplicationPullHandler
	 */
	class BaseApplicationPullHandler extends BasePullHandler
	{
		handleApplicationOpenChat(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleApplicationOpenChat`, params);
			const dialogModelState = this.store.getters['dialoguesModel/getById'](params.dialogId);
			if (Type.isUndefined(dialogModelState))
			{
				return;
			}

			MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId: params.dialogId }, ComponentCode.imMessenger);
		}

		/**
		 * @desc get class name for logger
		 * @return {string}
		 */
		getClassName()
		{
			return this.constructor.name;
		}
	}

	module.exports = {
		BaseApplicationPullHandler,
	};
});
