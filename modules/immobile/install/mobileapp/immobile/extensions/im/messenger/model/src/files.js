/* eslint-disable no-param-reassign */
/**
 * @module im/messenger/model/files
 */
jn.define('im/messenger/model/files', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { Feature } = require('im/messenger/lib/feature');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--files');

	const {
		FileStatus,
		FileType,
		FileImageType,
	} = require('im/messenger/const');

	const fileDefaultElement = Object.freeze({
		id: 0,
		templateId: '',
		chatId: 0,
		dialogId: '0',
		name: 'File is deleted',
		date: new Date(),
		type: 'file',
		extension: '',
		size: 0,
		image: false,
		status: FileStatus.done,
		progress: 100,
		authorId: 0,
		authorName: '',
		urlPreview: '',
		urlLocalPreview: '',
		urlShow: '',
		urlDownload: '',
		localUrl: '',
		viewerAttrs: null,
		uploadData: {
			byteSent: 0,
			byteTotal: 0,
		},
	});

	/** @type {FilesMessengerModel} */
	const filesModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
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
			 * @return {FilesModelState[] || []}
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
			 * @return {FilesModelState[]}
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

			/** @function filesModel/set */
			set: (store, payload) => {
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
					store.commit('update', { // TODO this update will be recovery default fields ( if fields not has)
						actionName: 'set',
						data: {
							fileList: existingFileList,
						},
					});
				}

				if (newFileList.length > 0)
				{
					store.commit('add', {
						actionName: 'set',
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

	function validate(store, fields)
	{
		const result = {};

		if (Type.isNumber(fields.id) || Type.isStringFilled(fields.id))
		{
			result.id = fields.id;
		}

		if (Type.isNumber(fields.templateId))
		{
			result.templateId = fields.templateId;
		}

		if (Type.isString(fields.templateId))
		{
			result.templateId = fields.templateId;
		}

		if (Type.isNumber(fields.chatId) || Type.isString(fields.chatId))
		{
			result.chatId = Number(fields.chatId);
		}

		if (Type.isString(fields.dialogId))
		{
			result.dialogId = fields.dialogId;
		}

		if (!Type.isUndefined(fields.date))
		{
			result.date = DateHelper.cast(fields.date);
		}

		if (Type.isString(fields.type))
		{
			result.type = fields.type;
		}

		if (Type.isString(fields.extension))
		{
			result.extension = fields.extension.toString().toLowerCase();
			if (!Feature.isSupportBMPImageType && result.type === FileType.image
					&& Application.getPlatform() === 'ios' // ios cant support webp and bmp type before 53 api
					&& (result.extension !== FileImageType.jpeg
						&& result.extension !== FileImageType.jpg
						&& result.extension !== FileImageType.png
						&& result.extension !== FileImageType.gif
						&& result.extension !== FileImageType.heif
						&& result.extension !== FileImageType.heic)
			)
			{
				result.type = FileType.file;
			}

			if (result.type === FileType.image
				&& Application.getPlatform() !== 'ios'
				&& (result.extension === FileImageType.heic || result.extension === FileImageType.heif)
			)
			{
				result.type = FileType.file;
			}
		}

		if (Type.isString(fields.name) || Type.isNumber(fields.name))
		{
			result.name = fields.name.toString();
		}

		if (Type.isNumber(fields.size) || Type.isString(fields.size))
		{
			result.size = Number(fields.size);
		}

		if (Type.isBoolean(fields.image))
		{
			result.image = false;
		}
		else if (Type.isObject(fields.image))
		{
			result.image = {
				width: 0,
				height: 0,
			};

			if (Type.isString(fields.image.width) || Type.isNumber(fields.image.width))
			{
				result.image.width = parseInt(fields.image.width, 10);
			}

			if (Type.isString(fields.image.height) || Type.isNumber(fields.image.height))
			{
				result.image.height = parseInt(fields.image.height, 10);
			}

			if (result.image.width <= 0 || result.image.height <= 0)
			{
				result.image = false;
			}
		}

		if (Type.isString(fields.status) && !Type.isUndefined(FileStatus[fields.status]))
		{
			result.status = fields.status;
		}

		if (Type.isNumber(fields.progress) || Type.isString(fields.progress))
		{
			result.progress = Number(fields.progress);
		}

		if (Type.isNumber(fields.authorId) || Type.isString(fields.authorId))
		{
			result.authorId = Number(fields.authorId);
		}

		if (Type.isString(fields.authorName) || Type.isNumber(fields.authorName))
		{
			result.authorName = fields.authorName.toString();
		}

		if (Type.isString(fields.urlPreview))
		{
			if (
				!fields.urlPreview
				|| fields.urlPreview.startsWith('http')
				|| fields.urlPreview.startsWith('bx')
				|| fields.urlPreview.startsWith('file')
				|| fields.urlPreview.startsWith('blob')
			)
			{
				result.urlPreview = fields.urlPreview;
			}
			else
			{
				result.urlPreview = store.rootState.applicationModel.common.host + fields.urlPreview;
			}
		}

		if (Type.isString(fields.urlDownload))
		{
			if (
				!fields.urlDownload
				|| fields.urlDownload.startsWith('http')
				|| fields.urlDownload.startsWith('bx')
				|| fields.urlPreview.startsWith('file')
			)
			{
				result.urlDownload = fields.urlDownload;
			}
			else
			{
				result.urlDownload = store.rootState.applicationModel.common.host + fields.urlDownload;
			}
		}

		if (Type.isString(fields.urlShow))
		{
			if (
				!fields.urlShow
				|| fields.urlShow.startsWith('http')
				|| fields.urlShow.startsWith('bx')
				|| fields.urlShow.startsWith('file')
			)
			{
				result.urlShow = fields.urlShow;
			}
			else
			{
				result.urlShow = store.rootState.applicationModel.common.host + fields.urlShow;
			}
		}

		if (Type.isString(fields.localUrl))
		{
			result.localUrl = fields.localUrl;
		}
		else
		{
			const localUrl = store.state.collection[fields.id]?.localUrl;
			if (localUrl)
			{
				result.localUrl = localUrl;
			}
		}

		// it is necessary for the native dialog on iOS to display heic as an image message
		if (Application.getPlatform() === 'ios' && result.extension === 'heic' && !result.urlPreview)
		{
			result.type = FileType.image;
			result.urlPreview = result.urlShow;
		}

		if (Type.isObject(fields.uploadData))
		{
			result.uploadData = fields.uploadData;
		}

		if (isLocalFileByUrl(fields.urlShow))
		{
			if (fields.type === FileType.image)
			{
				result.urlLocalPreview = fields.urlShow;
			}

			if (fields.type === FileType.video)
			{
				result.urlLocalPreview = fields.urlPreview;
			}
		}

		return result;
	}

	/**
	 * @param {string} url
	 * @return {boolean}
	 */
	function isLocalFileByUrl(url)
	{
		return Type.isString(url) && url.startsWith('file');
	}

	module.exports = { filesModel, fileDefaultElement };
});
