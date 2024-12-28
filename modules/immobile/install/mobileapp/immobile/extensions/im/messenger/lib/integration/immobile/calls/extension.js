/**
 * @module im/messenger/lib/integration/immobile/calls
 */
jn.define('im/messenger/lib/integration/immobile/calls', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Logger } = require('im/messenger/lib/logger');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { EventType, Analytics, DialogType } = require('im/messenger/const');
	const { AnalyticsEvent } = require('analytics');

	/**
	 * @class Calls
	 */
	class Calls
	{
		static createAudioCall(dialogId)
		{
			Logger.info('Calls.createAudioCall', dialogId);

			if (DialogHelper.isDialogId(dialogId))
			{
				const eventData = {
					dialogId,
					video: false,
					chatData: serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](dialogId),
				};

				BX.postComponentEvent('onCallInvite', [eventData], 'calls');

				return;
			}

			const userData = serviceLocator.get('core').getStore().getters['usersModel/getById'](dialogId);
			const eventData = {
				userId: dialogId,
				video: false,
				userData: {
					[userData.id]: userData,
				},
			};

			BX.postComponentEvent('onCallInvite', [eventData], 'calls');
		}

		static createVideoCall(dialogId)
		{
			Logger.info('Calls.createVideoCall', dialogId);

			if (DialogHelper.isDialogId(dialogId))
			{
				const eventData = {
					dialogId,
					video: true,
					chatData: serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](dialogId),
				};

				BX.postComponentEvent('onCallInvite', [eventData], 'calls');

				return;
			}

			const userData = serviceLocator.get('core').getStore().getters['usersModel/getById'](dialogId);
			const eventData = {
				userId: dialogId,
				video: true,
				userData: {
					[dialogId]: userData,
				},
			};

			BX.postComponentEvent('onCallInvite', [eventData], 'calls');
		}

		static joinCall(callId)
		{
			Logger.info('Calls.joinCall', callId);

			BX.postComponentEvent(EventType.call.join, [callId], 'calls');
		}

		static leaveCall(dialogId)
		{
			Logger.info('Calls.leaveCall', dialogId);
			const chatData = serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](dialogId);

			const eventData = {
				dialogId,
				chatData,
				userId: serviceLocator.get('core').getUserId(),
			};

			BX.postComponentEvent(EventType.call.leave, [eventData], 'calls');
		}

		static sendAnalyticsEvent(dialogId, callElement, analyticSection)
		{
			const dialogData = serviceLocator.get('core').getStore().getters['dialoguesModel/getById'](dialogId);

			if (!dialogData)
			{
				return;
			}

			const callType = dialogData.type === DialogType.videoconf
				? Analytics.Type.videoconf
				: DialogHelper.isDialogId(dialogId)
					? Analytics.Type.groupCall
					: Analytics.Type.privateCall;

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

			if (DialogHelper.createByModel(dialogData).isCollab)
			{
				const collabId = serviceLocator.get('core').getStore()
					.getters['dialoguesModel/collabModel/getCollabIdByDialogId'](dialogId)
				;

				analytics.setP4(`collabId_${collabId}`);
			}

			analytics.send();
		}
	}

	module.exports = {
		Calls,
	};
});
