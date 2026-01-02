/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/sidebar/src/files/model
 */
jn.define('im/messenger/model/sidebar/src/files/model', (require, exports, module) => {
	const { Type } = require('type');
	const { Moment } = require('utils/date');

	const { validate } = require('im/messenger/model/sidebar/src/files/validator');
	const { fileItem, fileDefaultElement } = require('im/messenger/model/sidebar/src/files/default-element');
	const { MessengerParams } = require('im/messenger/lib/params');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--sidebar-files');

	/** @type {SidebarFilesModel} */
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

				if (!state.collection[chatId]?.[subType]?.isHistoryLimitExceeded)
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
			 * @return {function(*, *): boolean}
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

			/**
			 * @function sidebarModel/sidebarFilesModel/getLastLoadFileId
			 * @param state
			 * @return {function(number, string): number}
			 */
			getLastLoadFileId: (state) => (chatId, subType) => {
				logger.log('sidebarFilesModel: getLastLoadFileId getter', chatId, subType);

				return state.collection[chatId]?.[subType]?.lastLoadFileId ?? 0;
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
				const { chatId, files, subType, hasNextPage, isHistoryLimitExceeded, lastLoadFileId } = payload;

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

				if (lastLoadFileId)
				{
					store.commit('setLastLoadFileId', {
						actionName: 'setLastLoadFileId',
						data: {
							chatId,
							subType,
							lastLoadFileId,
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
				const { chatId, id, fileId } = payload;
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
						fileId,
					},
				});
			},

			/**
			 * @function sidebarModel/sidebarFilesModel/deleteFilesGroupedByChatId
			 * @param {Object} store
			 * @param {Object} payload
			 * @param {Record<number, Array<string|number>>} payload.filesGroupedByChatId
			 */
			deleteFilesGroupedByChatId: (store, payload) => {
				const { filesGroupedByChatId } = payload;

				Object.keys(filesGroupedByChatId).forEach((chatId) => {
					const fileIds = filesGroupedByChatId[chatId];

					fileIds.forEach((id) => {
						store.commit('delete', {
							actionName: 'deleteFilesGroupedByChatId',
							data: {
								chatId,
								id,
							},
						});
					});
				});
			},

			/**
			 * @function sidebarModel/sidebarFilesModel/deleteByChatId
			 * @param {Object} store
			 * @param {Object} payload
			 * @param {number} payload.chatId
			 */
			deleteByChatId: (store, payload) => {
				const { chatId } = payload;

				store.commit('deleteByChatId', {
					actionName: 'deleteByChatId',
					data: {
						chatId,
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
					state.collection[chatId][subType] = fileDefaultElement();
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
					state.collection[chatId][subType] = fileDefaultElement();
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

				const subtypesInCollection = Object.keys(state.collection[chatId]);

				subtypesInCollection.forEach((subType) => {
					if (state.collection[chatId][subType] && state.collection[chatId][subType].items.has(id))
					{
						state.collection[chatId][subType].items.delete(id);
					}
				});
			},
			/**
			 * @param state
			 * @param {MutationPayload<SidebarFilesDeleteByChatIdActions, SidebarFilesDeleteByChatIdData>} payload
			 */
			deleteByChatId: (state, payload) => {
				logger.log('sidebarFilesModel: deleteByChatId mutation', payload);

				const { chatId } = payload.data;
				if (state.collection[chatId])
				{
					delete state.collection[chatId];
				}
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
					state.collection[chatId][subType] = fileDefaultElement();
				}

				state.collection[chatId][subType].hasNextPage = hasNextPage;
			},

			/**
			 * @param state
			 * @param {MutationPayload<SidebarFilesSetHasNextPageData, SidebarFilesSetHasNextPageActions>} payload
			 */
			setLastLoadFileId: (state, payload) => {
				logger.log('sidebarFilesModel: setLastLoadFileId mutation', payload);

				const { chatId, subType, lastLoadFileId } = payload.data;

				if (!state.collection[chatId])
				{
					state.collection[chatId] = {};
				}

				if (!state.collection[chatId][subType])
				{
					state.collection[chatId][subType] = fileDefaultElement();
				}

				state.collection[chatId][subType].lastLoadFileId = lastLoadFileId;
			},
		},
	};

	function set(store, payload, actionName)
	{
		const { chatId, files, subType } = payload;

		const newFiles = new Map();
		files.forEach((file) => {
			const prepareFile = { ...fileItem, ...validate(file) };
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
