/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/files
 */
jn.define('im/messenger/model/files', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { FilesCache } = require('im/messenger/cache');
	const { Logger } = require('im/messenger/lib/logger');

	const {
		FileStatus,
		FileType,
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
				store.commit('setState', payload);
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
					store.commit('update', existingFileList);
				}

				if (newFileList.length > 0)
				{
					store.commit('add', newFileList);
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
					id,
					fields: validate(store, fields),
				});
			},

			/** @function filesModel/delete */
			delete: (store, payload) => {
				const { id } = payload;
				if (!store.state.collection[id])
				{
					return;
				}

				store.commit('delete', { id });
			},
		},
		mutations: {
			setState: (state, payload) => {
				state.collection = payload.collection;
			},
			add: (state, payload) => {
				Logger.warn('filesModel: add mutation', payload);

				payload.forEach((file) => {
					state.collection[file.id] = file;
				});

				FilesCache.save();
			},
			update: (state, payload) => {
				Logger.warn('filesModel: update mutation', payload);

				payload.forEach((file) => {
					state.collection[file.id] = {
						...state.collection[file.id],
						...file,
					};
				});

				FilesCache.save();
			},
			updateWithId: (state, payload) => {
				Logger.warn('filesModel: updateWithId mutation', payload);

				const { id, fields } = payload;
				const currentFile = { ...state.collection[id] };

				delete state.collection[id];
				state.collection[fields.id] = {
					...currentFile,
					...fields,
				};

				FilesCache.save();
			},
			delete: (state, payload) => {
				Logger.warn('filesModel: delete mutation', payload);
				const { id } = payload;

				delete state.collection[id];
				FilesCache.save();
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
			result.extension = fields.extension.toString();
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

		return result;
	}

	module.exports = { filesModel };
});
