/**
 * @module im/messenger-v2/controller/recent/service/external/chat
 */
jn.define('im/messenger-v2/controller/recent/service/external/chat', (require, exports, module) => {
	const { Type } = require('type');
	const { EventType } = require('im/messenger/const');
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {IExternalService}
	 * @class ChatExternalService
	 */
	class ChatExternalService extends BaseRecentService
	{
		onInit()
		{
			this.logger.log('on init');

			this.#subscribeEvents();
		}

		#subscribeEvents()
		{
			BX.addCustomEvent(EventType.call.active, this.callActiveHandler);
			BX.addCustomEvent(EventType.call.inactive, this.callInactiveHandler);
		}

		callActiveHandler = (call, callStatus) => {
			this.logger.warn('callActiveHandler', call, callStatus);

			let status = callStatus;
			if (
				call.associatedEntity.advanced.entityType === 'VIDEOCONF'
				&& call.associatedEntity.advanced.entityData1 === 'BROADCAST'
			)
			{
				status = 'remote';
			}

			const recentCallItem = {
				type: 'call',
				id: this.#createCallId(call),
				call,
				callStatus: status,
			};

			const renderService = this.recentLocator.get('render');

			renderService.upsertItems([recentCallItem], {
				findItemMethod: 'findInNative',
			});

			renderService.renderInstant();
		};

		/**
		 * @param {RecentCallData} call
		 */
		callInactiveHandler = (call) => {
			this.logger.warn('callInactiveHandler', call);

			if (Type.isNil(call.associatedEntity.id))
			{
				return;
			}

			const callId = this.#createCallId(call);
			const renderService = this.recentLocator.get('render');

			renderService.deleteItems([{ id: callId }]);
			renderService.renderInstant();
		};

		/**
		 * @param {RecentCallData} call
		 * @return {string}
		 */
		#createCallId(call)
		{
			return `call${call.associatedEntity.id}`;
		}
	}

	module.exports = ChatExternalService;
});
