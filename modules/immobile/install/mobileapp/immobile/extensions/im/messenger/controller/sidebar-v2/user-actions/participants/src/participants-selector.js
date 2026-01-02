/**
 * @module im/messenger/controller/sidebar-v2/user-actions/participants/src/participants-selector
 */
jn.define(
	'im/messenger/controller/sidebar-v2/user-actions/participants/src/participants-selector',
	(require, exports, module) => {
		const { Loc } = require('im/messenger/loc');
		const { Type } = require('type');
		const { getLogger } = require('im/messenger/lib/logger');
		const { AnalyticsService } = require('im/messenger/provider/services/analytics');
		const { DialogType } = require('im/messenger/const');
		const { SocialNetworkUserSelector } = require('selector/widget/entity/socialnetwork/user');

		const logger = getLogger('sidebar--participants-view');

		/**
		 * @param {string} widgetTitle
		 * @param {string }dialogId
		 * @param {object} store
		 * @param {function} onClose
		 * @returns {Promise<*>}
		 */
		async function openParticipantsSelector({ widgetTitle, dialogId, store, onClose })
		{
			logger.log('participantsWidget.onClickBtnAdd');

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

			return new Promise((resolve) => {
				SocialNetworkUserSelector.make({
					initSelectedIds: [],
					createOptions: {
						enableCreation: false,
					},
					allowMultipleSelection: true,
					closeOnSelect: true,
					provider: {
						context: 'IMMOBILE_SIDEBAR_ADD_PARTICIPANTS',
						options: {
							recentItemsLimit: 20,
							maxUsersInRecentTab: 20,
						},
					},
					events: {
						onClose: resolve,
					},
					widgetParams: {
						title: widgetTitle,
						backdrop: {
							mediumPositionPercent: 70,
							horizontalSwipeAllowed: false,
						},
						sendButtonName: Loc.getMessage('IMMOBILE_SIDEBAR_V2_PARTICIPANTS_BUTTON_SELECTOR_ADD'),
					},
				}).show({}).catch(logger.error);
			});
		}

		module.exports = {
			openParticipantsSelector,
		};
	},
);
