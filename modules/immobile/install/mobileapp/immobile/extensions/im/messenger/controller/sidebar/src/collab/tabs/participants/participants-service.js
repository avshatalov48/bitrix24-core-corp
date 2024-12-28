/**
 * @module im/messenger/controller/sidebar/collab/tabs/participants/participants-service
 */
jn.define('im/messenger/controller/sidebar/collab/tabs/participants/participants-service', (require, exports, module) => {
	const { ParticipantsService } = require('im/messenger/controller/sidebar/chat/tabs/participants/participants-service');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType } = require('im/messenger/const');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--participants-service');

	/**
	 * @class CollabParticipantsService
	 */
	class CollabParticipantsService extends ParticipantsService
	{
		/**
		 * @desc Handler on click leave chat from participants menu
		 */
		onClickLeaveChat()
		{
			this.sidebarRestService.leaveChat()
				.then(
					(result) => {
						if (result)
						{
							try
							{
								PageManager.getNavigator().popTo('im.tabs')
									// eslint-disable-next-line promise/no-nesting
									.catch((err) => {
										logger.error(`${this.constructor.name}.onClickLeaveChat.popTo.catch error`, err);
										BX.onCustomEvent('onDestroySidebar');
										MessengerEmitter.emit(EventType.messenger.destroyDialog);
									});
							}
							catch (e)
							{
								logger.error(`${this.constructor.name}.onClickLeaveChat.getNavigator()`, e);
								BX.onCustomEvent('onDestroySidebar');
								MessengerEmitter.emit(EventType.messenger.destroyDialog);
							}
						}
					},
				)
				.catch((err) => logger.error(`${this.constructor.name}.onClickLeaveChat.sidebarRestService.leaveChat`, err));
		}
	}

	module.exports = {
		CollabParticipantsService,
	};
});
