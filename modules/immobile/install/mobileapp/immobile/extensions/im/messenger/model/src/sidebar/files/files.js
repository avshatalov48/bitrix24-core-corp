/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/sidebar/files/files
 */
jn.define('im/messenger/model/sidebar/files/files', (require, exports, module) => {
	const { Type } = require('type');
	const { validate } = require('im/messenger/model/sidebar/files/validators/file');
	const { SidebarFileType } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { Moment } = require('utils/date');
	const logger = LoggerManager.getInstance().getLogger('model--sidebar-files');

	function getFileState()
	{
		return {
			id: 0,
			messageId: 0,
			chatId: 0,
			authorId: 0,
			dateCreate: new Date(),
			fileId: 0,
		};
	}

	function getElementState()
	{
		return {
			items: new Map(),
			hasNextPage: true,
			isHistoryLimitExceeded: false,
		};
	}

	/**
	 *
	 * @type {SidebarFilesModel}
	 */
	const sidebarFilesModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/**
			 * @function sidebarModel/sidebarFilesModel/get
			 * @param state
			 * @return {SidebarFileSubTypeItem}
			 */
			get: (state) => (chatId, subType) => {
				if (!state.collection[chatId]?.[subType])
				{
					return {};
				}

				if (MessengerParams.isFullChatHistoryAvailable())
				{
					return state.collection[chatId][subType];
				}

				const defaultLimitDays = 30;
				const limitDays = MessengerParams.getPlanLimits()?.fullChatHistory?.limitDays || defaultLimitDays;

				const files = state.collection[chatId][subType].items;
				const filteredFiles = new Map();
				for (const [key, value] of files)
				{
					if (new Moment(value.dateCreate).daysFromNow < limitDays)
					{
						filteredFiles.set(key, value);
					}
				}

				return {
					...state.collection[chatId][subType],
					items: filteredFiles,
				};
			},
			/**
			 * @function sidebarModel/sidebarFilesModel/hasNextPage
			 * @param state
			 * @return {boolean}
			 */
			hasNextPage: (state) => (chatId, subType) => {
				return Boolean(state.collection[chatId]?.[subType]?.hasNextPage);
			},
			/**
			 * @function sidebarModel/sidebarFilesModel/isHistoryLimitExceeded
			 * @param state
			 * @param getters
			 * @return {boolean}
			 */
			isHistoryLimitExceeded: (state, getters) => (chatId, subType) => {
				if (MessengerParams.isFullChatHistoryAvailable())
				{
					return false;
				}

				if (state.collection[chatId]?.[subType]?.isHistoryLimitExceeded)
				{
					return true;
				}

				const filesCollectionSize = state.collection[chatId]?.[subType]?.items.size;
				const filesWithHistoryLimitExceeded = getters.get(chatId, subType)?.items?.size;
				if (filesCollectionSize && filesWithHistoryLimitExceeded)
				{
					return filesWithHistoryLimitExceeded !== filesCollectionSize;
				}

				return false;
			},
		},
		actions: {
			/**
			 * @function sidebarModel/sidebarFilesModel/set
			 */
			set: (store, payload) => {
				const { chatId, files } = payload;

				if (!Type.isArray(files) || !Type.isNumber(chatId))
				{
					return;
				}

				set(store, payload, 'set');
			},
			/**
			 * @function sidebarModel/sidebarFilesModel/setFromPagination
			 */
			setFromPagination: (store, payload) => {
				const { chatId, files, subType, hasNextPage, isHistoryLimitExceeded } = payload;

				if (!Type.isArray(files) || !Type.isNumber(chatId))
				{
					return;
				}

				if (Type.isBoolean(hasNextPage))
				{
					store.commit('setHasNextPage', {
						actionName: 'setHasNextPage',
						data: {
							chatId,
							subType,
							hasNextPage,
						},
					});
				}

				store.dispatch('setHistoryLimitExceeded', {
					chatId,
					subType,
					isHistoryLimitExceeded,
				});

				set(store, payload, 'setFromPagination');
			},

			/**
			 * @function sidebarModel/sidebarFilesModel/setHistoryLimitExceeded
			 */
			setHistoryLimitExceeded: (store, payload) => {
				const { chatId, subType, isHistoryLimitExceeded } = payload;

				if (!Type.isNumber(chatId) || !Type.isBoolean(isHistoryLimitExceeded))
				{
					return;
				}

				store.commit('setHistoryLimitExceeded', {
					actionName: 'setHistoryLimitExceeded',
					data: {
						chatId,
						subType,
						isHistoryLimitExceeded,
					},
				});
			},
			/** @function sidebarModel/sidebarFilesModel/delete */
			delete: (store, payload) => {
				const { chatId, id } = payload;
				const isValidParams = Type.isNumber(id) && Type.isNumber(chatId);
				const hasCollection = store.state.collection[chatId];

				if (!isValidParams || !hasCollection)
				{
					return;
				}

				store.commit('delete', {
					actionName: 'delete',
					data: {
						chatId,
						id,
					},
				});
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {
			 * MutationPayload<SidebarFilesSetHistoryLimitExceededData, SidebarFilesSetHistoryLimitExceededActions>
			 * } payload
			 */
			setHistoryLimitExceeded: (state, payload) => {
				logger.log('sidebarFilesModel: setHistoryLimitExceeded mutation', payload);
				const { chatId, subType, isHistoryLimitExceeded } = payload.data;

				if (!state.collection[chatId])
				{
					state.collection[chatId] = {};
				}

				if (!state.collection[chatId][subType])
				{
					state.collection[chatId][subType] = { ...getElementState() };
				}

				state.collection[chatId][subType].isHistoryLimitExceeded = isHistoryLimitExceeded;
			},
			/**
			 * @param state
			 * @param {MutationPayload<SidebarFilesSetData, SidebarFilesSetActions>} payload
			 */
			set: (state, payload) => {
				logger.log('sidebarFilesModel: set mutation', payload);

				const { chatId, files, subType } = payload.data;

				if (!state.collection[chatId])
				{
					state.collection[chatId] = {};
				}

				if (!state.collection[chatId][subType])
				{
					state.collection[chatId][subType] = { ...getElementState() };
				}

				files.forEach((value, key, map) => {
					state.collection[chatId][subType].items.set(key, value);
				});
			},
			/**
			 * @param state
			 * @param {MutationPayload<SidebarFilesDeleteData, SidebarFilesDeleteActions>} payload
			 */
			delete: (state, payload) => {
				logger.log('sidebarFilesModel: delete mutation', payload);

				const { chatId, id } = payload.data;

				Object.values(SidebarFileType).forEach((subType) => {
					if (state.collection[chatId][subType] && state.collection[chatId][subType].items.has(id))
					{
						state.collection[chatId][subType].items.delete(id);
					}
				});
			},
			/**
			 * @param state
			 * @param {MutationPayload<SidebarFilesSetHasNextPageData, SidebarFilesSetHasNextPageActions>} payload
			 */
			setHasNextPage: (state, payload) => {
				logger.log('sidebarFilesModel: setHasNextPage mutation', payload);

				const { chatId, subType, hasNextPage } = payload.data;

				if (!state.collection[chatId])
				{
					state.collection[chatId] = {};
				}

				if (!state.collection[chatId][subType])
				{
					state.collection[chatId][subType] = { ...getElementState() };
				}

				state.collection[chatId][subType].hasNextPage = hasNextPage;
			},
		},
	};

	function set(store, payload, actionName)
	{
		const { chatId, files, subType } = payload;

		const newFiles = new Map();
		files.forEach((file) => {
			const prepareFile = { ...getFileState(), ...validate(file) };
			newFiles.set(file.id, prepareFile);
		});

		store.commit('set', {
			actionName,
			data: {
				chatId,
				subType,
				files: newFiles,
			},
		});
	}

	module.exports = { sidebarFilesModel };
});
