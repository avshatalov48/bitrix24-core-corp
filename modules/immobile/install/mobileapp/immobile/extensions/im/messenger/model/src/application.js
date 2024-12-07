/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/application
 */
jn.define('im/messenger/model/application', (require, exports, module) => {
	const { AppStatus } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { Type } = require('type');
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
					backgroundSync: false,
					running: false,
				},
			},
			settings: {
				audioRate: 1,
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

				if (statusData.backgroundSync === true)
				{
					return AppStatus.backgroundSync;
				}

				return AppStatus.running;
			},

			/** @function applicationModel/getNetworkStatus */
			getNetworkStatus: (state) => () => {
				const statusData = state.common.status;

				return !statusData.networkWaiting;
			},

			/**
			 * @function applicationModel/getCurrentOpenedDialogId
			 * @return {DialogId | 0}
			 */
			getCurrentOpenedDialogId: (state) => () => {
				return state.dialog.currentId;
			},

			/** @function applicationModel/isSomeDialogOpen */
			isSomeDialogOpen: (state) => {
				return state.dialog.idList.length > 0;
			},

			/** @function applicationModel/isDialogOpen */
			isDialogOpen: (state) => (dialogId) => {
				return state.dialog.idList.includes(dialogId);
			},

			/**
			 * @function applicationModel/getOpenDialogs
			 * @return {Array<DialogId>}
			 */
			getOpenDialogs: (state) => () => {
				return state.dialog.idList;
			},

			/** @function applicationModel/getSettings */
			getSettings: (state) => () => {
				return state.settings;
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

			/** @function applicationModel/setAudioRateSetting */
			setAudioRateSetting: (store, payload) => {
				if (!Type.isNumber(payload))
				{
					return;
				}

				store.commit('setSettings', {
					actionName: 'setAudioRateSetting',
					data: {
						audioRate: payload,
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
			 * @param {MutationPayload<ApplicationSetSettingsData, ApplicationSetSettingsActions>} payload
			 */
			setSettings: (state, payload) => {
				logger.log('applicationModel: setSettings mutation', payload);

				state.settings = { ...state.settings, ...payload.data };
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
