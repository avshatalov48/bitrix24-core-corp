/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/files/validator
 */
jn.define('im/messenger/model/files/validator', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { FileStatus, FileType, FileImageType } = require('im/messenger/const');

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

		if (fields.mediaUrl && Type.isObject(fields.mediaUrl))
		{
			result.mediaUrl = fields.mediaUrl;
		}

		if (Type.isBoolean(fields.isTranscribable))
		{
			result.isTranscribable = fields.isTranscribable;
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

	module.exports = { validate };
});
