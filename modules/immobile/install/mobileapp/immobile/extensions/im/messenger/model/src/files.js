/* eslint-disable flowtype/require-return-type */

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
		templateId: 0,
		date: new Date(),
		type: 'file',
		extension: '',
		icon: 'empty',
		size: 0,
		image: false,
		status: FileStatus.done,
		progress: 100,
		authorId: 0,
		authorName: '',
		urlPreview: '',
		urlShow: '',
		urlDownload: '',
		init: false,
		viewerAttrs: {},
	};

	const filesModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/** @function filesModel/hasFile */
			hasFile: (state) => (fileId) => {
				return !!state.collection[fileId];
			},

			/** @function filesModel/getById */
			getById: (state) => (fileId) => {
				return state.collection[fileId];
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
					fileList = payload.map(file => {
						const result = validate(store, { ...file });
						result.templateId = result.id;

						return {
							...elementState,
							...result,
							...{ init: true },
						}
					});
				}
				else
				{
					const result = validate(store, { ...payload });
					result.templateId = result.id;
					fileList.push({
						...elementState,
						...result,
						...{ init: true },
					});
				}

				const existingFileList = [];
				const newFileList = [];
				fileList.forEach(file => {
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

			/** @function filesModel/delete */
			delete: (store, payload) => {

			},
		},
		mutations: {
			setState: (state, payload) => {
				state.collection = payload.collection;
			},
			add: (state, payload) => {
				Logger.warn('filesModel: add mutation', payload);

				payload.forEach(file => {
					state.collection[file.id] = file;
				});

				FilesCache.save();
			},
			update: (state, payload) => {
				Logger.warn('filesModel: update mutation', payload);

				payload.forEach(file => {
					state.collection[file.id] = {
						...state.collection[file.id],
						...file.fields
					};
				});

				FilesCache.save();
			},
			delete: (state, payload) => {
				Logger.warn('filesModel: update mutation', payload);
				//TODO: delete mutation
				FilesCache.save();
			},
		}
	};

	function validate(store, fields)
	{
		const result = {};

		if (Type.isNumber(fields.id))
		{
			result.id = fields.id;
		}
		else if (Type.isString(fields.id))
		{
			if (fields.id.startsWith('temporary'))
			{
				result.id = fields.id;
			}
			else
			{
				result.id = Number(fields.id);
			}
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

			if (result.type === 'image')
			{
				result.icon = 'img';
			}
			else if (result.type === 'video')
			{
				result.icon = 'mov';
			}
			else
			{
				result.icon = getIconType(result.extension);
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
				result.image.width = parseInt(fields.image.width);
			}
			if (Type.isString(fields.image.height) || Type.isNumber(fields.image.height))
			{
				result.image.height = parseInt(fields.image.height);
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

		return result;
	}

	function getType(name)
	{
		const extension = name.toString().toLowerCase().split('.').splice(-1)[0];

		switch(extension)
		{
			case 'png':
			case 'jpe':
			case 'jpg':
			case 'jpeg':
			case 'gif':
			case 'heic':
			case 'bmp':
			case 'webp':
				return FileType.image;

			case 'mp4':
			case 'mkv':
			case 'webm':
			case 'mpeg':
			case 'hevc':
			case 'avi':
			case '3gp':
			case 'flv':
			case 'm4v':
			case 'ogg':
			case 'wmv':
			case 'mov':
				return FileType.video;

			case 'mp3':
				return FileType.audio;
		}

		return FileType.file;
	}

	function getIconType(extension)
	{
		switch(extension.toString())
		{
			case 'png':
			case 'jpe':
			case 'jpg':
			case 'jpeg':
			case 'gif':
			case 'heic':
			case 'bmp':
			case 'webp':
				return 'img';

			case 'mp4':
			case 'mkv':
			case 'webm':
			case 'mpeg':
			case 'hevc':
			case 'avi':
			case '3gp':
			case 'flv':
			case 'm4v':
			case 'ogg':
			case 'wmv':
			case 'mov':
				return 'mov';

			case 'txt':
				return 'txt';

			case 'doc':
			case 'docx':
				return 'doc';

			case 'xls':
			case 'xlsx':
				return 'xls';

			case 'php':
				return 'php';

			case 'pdf':
				return 'pdf';

			case 'ppt':
			case 'pptx':
				return 'ppt';

			case 'rar':
				return 'rar';

			case 'zip':
			case '7z':
			case 'tar':
			case 'gz':
			case 'gzip':
				return 'zip';

			case 'set':
				return 'set';

			case 'conf':
			case 'ini':
			case 'plist':
				return 'set';
		}

		return 'empty';
	}

	module.exports = { filesModel };
});
