/**
 * @module im/messenger/lib/integration/immobile/calls
 */
jn.define('im/messenger/lib/integration/immobile/calls', (require, exports, module) => {

	const { Logger } = jn.require('im/messenger/lib/logger');
	const { DialogHelper } = jn.require('im/messenger/lib/helper');

	/**
	 * @class Calls
	 */
	class Calls
	{
		createAudioCall(dialogId)
		{
			Logger.info('Calls.createAudioCall', dialogId);

			if (DialogHelper.isDialogId(dialogId))
			{
				const eventData = {
					dialogId,
					video: false,
					chatData: MessengerStore.getters['dialoguesModel/getById'](dialogId),
				};

				BX.postComponentEvent('onCallInvite', [ eventData ], 'calls');

				return;
			}

			const userData = MessengerStore.getters['usersModel/getUserById'](dialogId);
			const eventData = {
				userId: dialogId,
				video: false,
				userData: {
					[userData.id]: userData,
				},
			};

			BX.postComponentEvent('onCallInvite', [ eventData ], 'calls');
		}

		createVideoCall(dialogId)
		{
			Logger.info('Calls.createVideoCall', dialogId);

			if (DialogHelper.isDialogId(dialogId))
			{
				const eventData = {
					dialogId,
					video: true,
					chatData: MessengerStore.getters['dialoguesModel/getById'](dialogId),
				};

				BX.postComponentEvent('onCallInvite', [ eventData ], 'calls');

				return;
			}

			const userData = MessengerStore.getters['usersModel/getUserById'](dialogId);
			const eventData = {
				userId: dialogId,
				video: true,
				userData: {
					[dialogId]: userData,
				},
			};

			BX.postComponentEvent('onCallInvite', [ eventData ], 'calls');
		}
	}

	module.exports = {
		Calls: new Calls(),
	};
});
