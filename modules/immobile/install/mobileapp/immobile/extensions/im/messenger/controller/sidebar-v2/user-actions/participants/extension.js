/**
 * @module im/messenger/controller/sidebar-v2/user-actions/participants
 */
jn.define('im/messenger/controller/sidebar-v2/user-actions/participants', (require, exports, module) => {
	const { unique } = require('utils/array');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType, ComponentCode } = require('im/messenger/const');
	const { addChat, addParticipants, deleteParticipant } = require(
		'im/messenger/controller/sidebar-v2/user-actions/participants/src/rest-service',
	);
	const { MemberSelector } = require('im/messenger/controller/selector/member');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');
	const { DialogType } = require('im/messenger/const');
	const { Type } = require('type');

	/**
	 * @param {DialogId} dialogId
	 * @param {Object} store
	 * @param {String} [widgetTitle]
	 * @returns {Promise<void>}
	 */
	async function onAddParticipants({ store, dialogId, widgetTitle } = {})
	{
		const dialog = store.getters['dialoguesModel/getById'](dialogId);

		if (!dialog)
		{
			return Promise.reject(new Error('openParticipantsAddWidget: unknown dialog'));
		}

		AnalyticsService.getInstance().sendUserAddButtonClicked({ dialogId });

		if (dialog.type === DialogType.collab)
		{
			const collabId = store.getters['dialoguesModel/collabModel/getCollabIdByDialogId'](dialogId);
			if (Type.isNumber(collabId))
			{
				const { openCollabInvite, CollabInviteAnalytics } = await requireLazy('collab/invite');
				openCollabInvite({
					collabId,
					analytics: new CollabInviteAnalytics()
						.setSection(CollabInviteAnalytics.Section.CHAT_SIDEBAR)
						.setChatId(dialog.chatId),
				});
			}

			return Promise.resolve();
		}

		return new Promise((resolve, reject) => {
			const memberSelector = new MemberSelector({
				title: widgetTitle,
				onSelectMembers: (selectedUsersIds) => {
					try
					{
						const { participants } = store.getters['dialoguesModel/getById'](dialogId);
						const currentParticipantIds = unique(participants.filter(Boolean));
						const uniqueIds = selectedUsersIds.filter((id) => !currentParticipantIds.includes(id));

						if (uniqueIds.length === 0)
						{
							reject();

							return;
						}

						const isCreateChat = !DialogHelper.isDialogId(dialogId);
						if (isCreateChat)
						{
							addChat([...uniqueIds, ...currentParticipantIds])
								.then(resolve)
								.catch(reject);

							return;
						}

						addParticipants(dialogId, uniqueIds)
							.then(resolve)
							.catch(reject);
					}
					catch (error)
					{
						reject(error);
					}
				},
			});

			memberSelector.open();
		});
	}

	/**
	 * @desc Handler remove participant
	 * @param {DialogId} dialogId
	 * @param {number} userId
	 * @return {Promise<boolean>}
	 */
	function onRemoveParticipant(dialogId, userId)
	{
		return deleteParticipant(dialogId, userId);
	}

	/**
	 * @desc Handler on click send user from participants menu
	 * @param {DialogId} dialogId
	 */
	function onSendMessage(dialogId)
	{
		MessengerEmitter.emit(
			EventType.messenger.openDialog,
			{ dialogId },
			ComponentCode.imMessenger,
		);
	}

	module.exports = {
		onSendMessage,
		onAddParticipants,
		onRemoveParticipant,
	};
});
