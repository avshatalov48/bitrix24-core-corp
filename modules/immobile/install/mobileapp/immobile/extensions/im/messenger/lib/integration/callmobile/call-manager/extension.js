/**
 * @module im/messenger/lib/integration/callmobile/call-manager
 */
jn.define('im/messenger/lib/integration/callmobile/call-manager', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');

	const {
		EventType,
		Analytics,
		DialogType,
	} = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Logger } = require('im/messenger/lib/logger');
	const { DialogHelper } = require('im/messenger/lib/helper');

	/**
	 * @class CallManager
	 */
	class CallManager
	{
		/**
		 * @return {CallManager}
		 */
		static getInstance()
		{
			if (!this.instance)
			{
				this.instance = new this();
			}

			return this.instance;
		}

		constructor()
		{
			this.core = serviceLocator.get('core');
			this.store = this.core.getStore();
			this.messengerInitService = serviceLocator.get('messenger-init-service');

			this.bindMethods();
		}

		bindMethods()
		{
			this.messengerInitHandler = this.messengerInitHandler.bind(this);
		}

		subscribeMessengerInitEvent()
		{
			this.messengerInitService.onInit(this.messengerInitHandler);
		}

		/**
		 * @param {immobileTabChatLoadResult} messengerInitData
		 */
		messengerInitHandler(messengerInitData)
		{
			Logger.info('CallManager.messengerInitHandler', messengerInitData);

			const {
				activeCalls,
			} = messengerInitData;

			const currentUserId = this.core.getUserId();
			const currentUser = {
				[currentUserId]: this.store.getters['usersModel/getById'](currentUserId),
			};
			BX.postComponentEvent(EventType.callManager.setCurrentUser, [currentUser], 'calls');

			if (activeCalls)
			{
				BX.postComponentEvent(EventType.callManager.activeCallsReceived, [activeCalls], 'calls');
			}
		}

		createAudioCall(dialogId)
		{
			Logger.info('CallManager.createAudioCall', dialogId);
			const currentUser = this.core.getUserId();

			if (DialogHelper.isDialogId(dialogId))
			{
				const eventData = {
					dialogId,
					video: false,
					chatData: this.store.getters['dialoguesModel/getById'](dialogId),
					userData: {
						[currentUser]: this.store.getters['usersModel/getById'](currentUser),
					},
				};

				BX.postComponentEvent(EventType.callManager.createCall, [eventData], 'calls');

				return;
			}

			const eventData = {
				userId: dialogId,
				video: false,
				chatData: this.store.getters['dialoguesModel/getById'](dialogId),
				userData: {
					[dialogId]: this.store.getters['usersModel/getById'](dialogId),
					[currentUser]: this.store.getters['usersModel/getById'](currentUser),
				},
			};

			BX.postComponentEvent(EventType.callManager.createCall, [eventData], 'calls');
		}

		createVideoCall(dialogId)
		{
			Logger.info('CallManager.createVideoCall', dialogId);
			const currentUser = this.core.getUserId();

			if (DialogHelper.isDialogId(dialogId))
			{
				const eventData = {
					dialogId,
					video: true,
					chatData: this.store.getters['dialoguesModel/getById'](dialogId),
					userData: {
						[currentUser]: this.store.getters['usersModel/getById'](currentUser),
					},
				};

				BX.postComponentEvent(EventType.callManager.createCall, [eventData], 'calls');

				return;
			}

			const userData = this.store.getters['usersModel/getById'](dialogId);
			const eventData = {
				userId: dialogId,
				video: true,
				chatData: this.store.getters['dialoguesModel/getById'](dialogId),
				userData: {
					[dialogId]: userData,
					[currentUser]: this.store.getters['usersModel/getById'](currentUser),
				},
			};

			BX.postComponentEvent(EventType.callManager.createCall, [eventData], 'calls');
		}

		joinCall(callId, callUuid, associatedEntity)
		{
			Logger.info('CallManager.joinCall', callId);

			BX.postComponentEvent(EventType.call.join, [{callId, callUuid, associatedEntity}], 'calls');
		}

		leaveCall(dialogId)
		{
			Logger.info('CallManager.leaveCall', dialogId);
			const chatData = this.store.getters['dialoguesModel/getById'](dialogId);

			const eventData = {
				dialogId,
				chatData,
				userId: this.core.getUserId(),
			};

			BX.postComponentEvent(EventType.call.leave, [eventData], 'calls');
		}

		/**
		 * @param {number} chatId
		 * @param {string} token
		 */
		updateCallToken(chatId, token)
		{
			const eventData = {
				chatId,
				token,
			};

			BX.postComponentEvent(EventType.callManager.updateCallToken, [eventData], 'calls');
		}

		sendAnalyticsEvent(dialogId, callElement, analyticSection)
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](dialogId);
			const callType = dialogData.type === DialogType.videoconf
				? Analytics.Type.videoconf
				: (
					DialogHelper.isDialogId(dialogId)
						? Analytics.Type.groupCall
						: Analytics.Type.privateCall
				)
			;

			const analytics = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(Analytics.Category.messenger)
				.setEvent(Analytics.Event.clickCallButton)
				.setType(callType)
				.setSection(analyticSection)
				.setSubSection(Analytics.SubSection.window)
				.setElement(callElement)
				.setP5(`chatId_${dialogData.chatId}`)
			;

			analytics.send();
		}
	}

	module.exports = {
		CallManager,
	};
});
