/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/application
 */
jn.define('im/messenger/model/application', (require, exports, module) => {
	const { AppStatus } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--application');

	const applicationModel = {
		namespaced: true,
		state: () => ({
			dialog: {
				currentId: 0,
				idList: [],
			},
			common: {
				host: `${currentDomain}/`,
				status: {
					networkWaiting: false,
					connection: false,
					sync: false,
					running: false,
				},
			},
		}),
		getters: {
			/** @function applicationModel/getStatus */
			getStatus: (state) => () => {
				const statusData = state.common.status;

				if (statusData.networkWaiting === true)
				{
					return AppStatus.networkWaiting;
				}

				if (statusData.connection === true)
				{
					return AppStatus.connection;
				}

				if (statusData.sync === true)
				{
					return AppStatus.sync;
				}

				return AppStatus.running;
			},

			/** @function applicationModel/getNetworkStatus */
			getNetworkStatus: (state) => () => {
				const statusData = state.common.status;

				return !statusData.networkWaiting;
			},

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
			isDialogOpen: (state) => {
				return state.dialog.idList.length > 0;
			},

			/** @function applicationModel/getOpenDialogs */
			getOpenDialogs: (state) => () => {
				return state.dialog.idList;
			},
		},
		actions: {
			/** @function applicationModel/setStatus */
			setStatus: (store, status) => {
				store.commit('setStatus', {
					actionName: 'setStatus',
					data: {
						status,
					},
				});
			},

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
			 * @param {MutationPayload<ApplicationSetStatusData, ApplicationSetStatusActions>} payload
			 */
			setStatus: (state, payload) => {
				logger.log('applicationModel: setStatus mutation', payload);
				const {
					name,
					value,
				} = payload.data.status;

				state.common.status[name] = value;
			},
			/**
			 * @param state
			 * @param {MutationPayload<ApplicationOpenDialogIdData, ApplicationOpenDialogIdActions>} payload
			 */
			openDialogId: (state, payload) => {
				logger.warn('applicationModel: openDialogId mutation', payload);

				const { dialogId } = payload.data;
				state.dialog.currentId = dialogId;

				state.dialog.idList.push(dialogId);
			},

			/**
			 * @param state
			 * @param {MutationPayload<ApplicationCloseDialogIdData, ApplicationCloseDialogIdActions>} payload
			 */
			closeDialogId: (state, payload) => {
				logger.warn('applicationModel: closeDialogId mutation', payload);

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
