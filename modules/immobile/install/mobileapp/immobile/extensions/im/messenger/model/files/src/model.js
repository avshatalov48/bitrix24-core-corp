/* eslint-disable no-param-reassign */
/**
 * @module im/messenger/model/files/model
 */
jn.define('im/messenger/model/files/model', (require, exports, module) => {
	const { Type } = require('type');

	const { ModelUtils } = require('im/messenger/lib/utils');

	const { fileDefaultElement } = require('im/messenger/model/files/default-element');
	const { validate } = require('im/messenger/model/files/validator');
	const { transcriptModel } = require('im/messenger/model/files/transcript/model');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--files');

	/** @type {FilesMessengerModel} */
	const filesModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		modules: {
			transcriptModel,
		},
		getters: {
			/**
			 * @function filesModel/hasFile
			 * @return {boolean}
			 */
			hasFile: (state) => (fileId) => {
				if (state.collection[fileId])
				{
					return true;
				}

				return Object.values(state.collection).some((file) => file.templateId === fileId);
			},

			/**
			 * @function filesModel/getById
			 * @return {FilesModelState | null}
			 */
			getById: (state, getters) => (fileId) => {
				return state.collection[fileId] ?? getters.getByTemplateId(fileId);
			},

			/**
			 * @function filesModel/getByTemplateId
			 * @returns {FilesModelState | null}
			 */
			getByTemplateId: (state) => (fileId) => {
				if (Type.isNumber(Number(fileId)))
				{
					return null;
				}

				return Object.values(state.collection).find((file) => file.templateId === fileId);
			},

			/**
			 * @function filesModel/getListByMessageId
			 * @return {Array<FilesModelState>}
			 */
			getListByMessageId: (state, getters, rootState, rootGetters) => (messageId) => {
				const message = rootGetters['messagesModel/getById'](messageId);
				if (!message.id)
				{
					return [];
				}

				const fileIdList = message.files;
				if (!Type.isArrayFilled(fileIdList))
				{
					return [];
				}
				const validFileIdList = [];
				for (const fileId of fileIdList)
				{
					if (!getters.hasFile(fileId))
					{
						logger.error(
							'filesModel: getListByMessageId error: the file is missing with fileId by messageId',
							{
								messageId,
								fileId,
							},
						);

						continue;
					}

					validFileIdList.push(fileId);
				}

				return rootGetters['filesModel/getByIdList'](validFileIdList);
			},

			/**
			 * @function filesModel/getByIdList
			 * @return {Array<FilesModelState>}
			 */
			getByIdList: (state, getters) => (fileIdList) => {
				return fileIdList.map((fileId) => getters.getById(fileId));
			},

			/**
			 * @function filesModel/isInCollection
			 * @return {boolean}
			 */
			isInCollection: (state) => ({ fileId }) => {
				return Boolean(state.collection[fileId]);
			},
		},
		actions: {
			/** @function filesModel/setState */
			setState: (store, payload) => {
				store.commit('setState', {
					actionName: 'setState',
					data: {
						collection: payload,
					},
				});
			},

			/** @function filesModel/setFromSync */
			setFromSync: (store, fileList) => {
				return store.dispatch('set', { itemList: fileList, actionName: 'setFromSync' });
			},

			/** @function filesModel/set */
			set: (store, payload) => {
				const { itemList, actionName = 'set' } = ModelUtils.normalizeItemListPayload(payload);
				const preparedItemList = itemList.map((file) => {
					const result = validate(store, { ...file });

					return {
						...fileDefaultElement,
						...result,
					};
				});

				const existingFileList = [];
				const newFileList = [];
				preparedItemList.forEach((file) => {
					if (store.getters.hasFile(file.id))
					{
						existingFileList.push(file);

						return;
					}

					newFileList.push(file);
				});

				if (existingFileList.length > 0)
				{
					store.commit('update', { // TODO this update will be recovery default fields ( if fields not has)
						actionName,
						data: {
							fileList: existingFileList,
						},
					});
				}

				if (newFileList.length > 0)
				{
					store.commit('add', {
						actionName,
						data: {
							fileList: newFileList,
						},
					});
				}
			},

			/** @function filesModel/setFromLocalDatabase */
			setFromLocalDatabase: (store, payload) => {
				let fileList = [];
				if (Type.isArray(payload))
				{
					fileList = payload.map((file) => {
						const result = validate(store, { ...file });

						return {
							...fileDefaultElement,
							...result,
						};
					});
				}
				else
				{
					const result = validate(store, { ...payload });
					fileList.push({
						...fileDefaultElement,
						...result,
					});
				}

				const existingFileList = [];
				const newFileList = [];
				fileList.forEach((file) => {
					if (store.getters.hasFile(file.id))
					{
						existingFileList.push(file);

						return;
					}

					newFileList.push(file);
				});

				if (existingFileList.length > 0)
				{
					store.commit('update', {
						actionName: 'setFromLocalDatabase',
						data: {
							fileList: existingFileList,
						},
					});
				}

				if (newFileList.length > 0)
				{
					store.commit('add', {
						actionName: 'setFromLocalDatabase',
						data: {
							fileList: newFileList,
						},
					});
				}
			},

			/** @function filesModel/setFromPush */
			setFromPush: (store, payload) => {
				if (!Type.isArrayFilled(payload))
				{
					return;
				}

				const fileList = payload.map((rawFile) => {
					const file = validate(store, { ...rawFile });
					file.templateId = file.id;

					return file;
				});

				store.commit('setFromPush', {
					actionName: 'setFromPush',
					data: {
						fileList,
					},
				});
			},

			/** @function filesModel/updateWithId */
			updateWithId: (store, payload) => {
				const { id, fields } = payload;

				if (!store.state.collection[id])
				{
					return;
				}

				store.commit('updateWithId', {
					actionName: 'updateWithId',
					data: {
						id,
						fields: validate(store, fields),
					},
				});
			},

			/** @function filesModel/delete */
			delete: (store, payload) => {
				const { id } = payload;
				if (!store.state.collection[id])
				{
					return;
				}

				store.commit('delete', {
					actionName: 'delete',
					data: {
						id,
					},
				});
			},

			/** @function filesModel/deleteByChatId */
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
			 * @param {MutationPayload<FilesSetStateData, FilesSetStateActions>} payload
			 */
			setState: (state, payload) => {
				const {
					collection,
				} = payload.data;

				state.collection = collection;
			},

			/**
			 * @param state
			 * @param {MutationPayload<FilesSetFromPushData, FilesSetFromPushActions>} payload
			 */
			setFromPush: (state, payload) => {
				logger.log('filesModel: setFromPush mutation', payload);

				const { fileList } = payload.data;

				for (const file of fileList)
				{
					state.collection[file.id] = {
						...fileDefaultElement,
						...state.collection[file.id],
						...file,
					};
				}
			},

			/**
			 * @param state
			 * @param {MutationPayload<FilesAddData, FilesAddActions>} payload
			 */
			add: (state, payload) => {
				logger.log('filesModel: add mutation', payload);

				const {
					fileList,
				} = payload.data;

				fileList.forEach((file) => {
					state.collection[file.id] = file;
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<FilesUpdateData, FilesUpdateActions>} payload
			 */
			update: (state, payload) => {
				logger.log('filesModel: update mutation', payload);

				const {
					fileList,
				} = payload.data;

				fileList.forEach((file) => {
					state.collection[file.id] = {
						...state.collection[file.id],
						...file,
					};
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<FilesUpdateWithIdData, FilesUpdateWithIdActions>} payload
			 */
			updateWithId: (state, payload) => {
				logger.log('filesModel: updateWithId mutation', payload);

				const {
					id,
					fields,
				} = payload.data;

				const currentFile = { ...state.collection[id] };

				delete state.collection[id];
				state.collection[fields.id] = {
					...currentFile,
					...fields,
				};
			},

			/**
			 * @param state
			 * @param {MutationPayload<FilesDeleteData, FilesDeleteActions>} payload
			 */
			delete: (state, payload) => {
				logger.log('filesModel: delete mutation', payload);
				const {
					id,
				} = payload.data;

				delete state.collection[id];
			},

			/**
			 * @param state
			 * @param {MutationPayload<FilesDeleteByChatIdData, FilesDeleteByChatIdActions>} payload
			 */
			deleteByChatId: (state, payload) => {
				logger.log('filesModel: deleteByChatId mutation', payload);

				const { chatId } = payload.data;

				for (const file of Object.values(state.collection))
				{
					if (file.chatId === chatId)
					{
						delete state.collection[file.id];
					}
				}
			},
		},
	};

	module.exports = { filesModel };
});
