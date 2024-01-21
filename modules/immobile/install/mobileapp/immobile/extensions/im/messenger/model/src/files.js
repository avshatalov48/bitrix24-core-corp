/* eslint-disable no-param-reassign */
/**
 * @module im/messenger/model/files
 */
jn.define('im/messenger/model/files', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('model--files');

	const {
		FileStatus,
		FileType,
		FileImageType,
	} = require('im/messenger/const');

	const elementState = {
		id: 0,
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
		urlShow: '',
		urlDownload: '',
		localUrl: '',
		viewerAttrs: null,
		uploadData: {
			byteSent: 0,
			byteTotal: 0,
		},
	};

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
				return Boolean(state.collection[fileId]);
			},

			/**
			 * @function filesModel/getById
			 * @return {FilesModelState}
			 */
			getById: (state) => (fileId) => {
				return state.collection[fileId];
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
						result.templateId = result.id;

						return {
							...elementState,
							...result,
						};
					});
				}
				else
				{
					const result = validate(store, { ...payload });
					result.templateId = result.id;
					fileList.push({
						...elementState,
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
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload} payload
			 */
			setState: (state, payload) => {
				const {
					collection,
				} = payload.data;

				state.collection = collection;
			},

			/**
			 * @param state
			 * @param {MutationPayload} payload
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
			 * @param {MutationPayload} payload
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
			 * @param {MutationPayload} payload
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
			 * @param {MutationPayload} payload
			 */
			delete: (state, payload) => {
				logger.log('filesModel: delete mutation', payload);
				const {
					id,
				} = payload.data;

				delete state.collection[id];
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
		else if (Type.isString(fields.templateId))
		{
			if (fields.templateId.startsWith('temporary'))
			{
				result.templateId = fields.templateId;
			}
			else
			{
				result.templateId = Number(fields.templateId);
			}
		}

		if (Type.isNumber(fields.chatId) || Type.isString(fields.chatId))
		{
			result.chatId = Number(fields.chatId);
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

			if (result.type === FileType.image
				&& Application.getPlatform() === 'ios' // ios cant show webp and bmp and others type
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

		return result;
	}

	module.exports = { filesModel };
});
