/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/application
 */
jn.define('im/messenger/model/application', (require, exports, module) => {
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Logger } = require('im/messenger/lib/logger');

	const applicationModel = {
		namespaced: true,
		state: () => ({
			dialog: {
				currentId: 0,
				idList: [],
			},
			common: {
				host: `${currentDomain}/`,
			},
		}),
		getters: {
			/** @function applicationModel/getDialogId */
			getDialogId: (state) => {
				const chatSettings = Application.storage.getObject('settings.chat', {
					chatBetaEnable: false,
				});

				if (chatSettings.chatBetaEnable)
				{
					return state.dialog.currentId;
				}

				const page = PageManager.getNavigator().getVisible();
				if (page.type === 'Web' && page.pageId === `im-${state.dialog.currentId}`)
				{
					return state.dialog.currentId;
				}

				return 0;
			},

			/** @function applicationModel/isDialogOpen */
			isDialogOpen: (state, getters) => {
				return state.dialog.idList.length > 0;
			},
		},
		actions: {
			/** @function applicationModel/openDialogId */
			openDialogId: (store, payload) => {
				let dialogId;
				if (DialogHelper.isDialogId(payload))
				{
					dialogId = payload;
				}
				else if (DialogHelper.isChatId(payload))
				{
					dialogId = Number(payload);
				}

				store.commit('openDialogId', {
					actionName: 'openDialogId',
					data: {
						dialogId,
					},
				});
			},

			/** @function applicationModel/closeDialogId */
			closeDialogId: (store, payload) => {
				let dialogId;
				if (DialogHelper.isDialogId(payload))
				{
					dialogId = payload;
				}
				else if (DialogHelper.isChatId(payload))
				{
					dialogId = Number(payload);
				}

				store.commit('closeDialogId', {
					actionName: 'closeDialogId',
					data: {
						dialogId,
					},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			openDialogId: (state, payload) => {
				Logger.warn('applicationModel: openDialogId mutation', payload);

				const { dialogId } = payload.data;
				state.dialog.currentId = dialogId;

				state.dialog.idList.push(dialogId);
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			closeDialogId: (state, payload) => {
				Logger.warn('applicationModel: closeDialogId mutation', payload);

				const { dialogId } = payload.data;
				const index = state.dialog.idList.lastIndexOf(dialogId);
				if (index !== -1)
				{
					state.dialog.idList.splice(index, 1);
				}

				if (state.dialog.idList.length === 0)
				{
					state.dialog.currentId = 0;

					return;
				}

				state.dialog.currentId = state.dialog.idList[state.dialog.idList.length - 1];
			},
		},
	};

	module.exports = { applicationModel };
});
