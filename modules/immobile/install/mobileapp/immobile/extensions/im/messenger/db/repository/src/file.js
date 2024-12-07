/**
 * @module im/messenger/db/repository/file
 */
jn.define('im/messenger/db/repository/file', (require, exports, module) => {
	const { Type } = require('type');

	const {
		FileTable,
	} = require('im/messenger/db/table/file');
	const { DateHelper } = require('im/messenger/lib/helper');
	const {
		FileStatus,
	} = require('im/messenger/const');

	/**
	 * @class FileRepository
	 */
	class FileRepository
	{
		constructor()
		{
			this.fileTable = new FileTable();
		}

		/**
		 * @param {number} chatId
		 */
		async deleteByChatId(chatId)
		{
			return this.fileTable.delete({
				chatId,
			});
		}

		async saveFromModel(fileList)
		{
			const fileListToAdd = [];

			fileList.forEach((file) => {
				const fileToAdd = this.fileTable.validate(file);

				fileListToAdd.push(fileToAdd);
			});

			return this.fileTable.add(fileListToAdd, true);
		}

		async saveFromRest(fileList)
		{
			const fileListToAdd = [];

			fileList.forEach((file) => {
				const fileToAdd = this.validateRestFile(file);

				fileListToAdd.push(fileToAdd);
			});

			return this.fileTable.add(fileListToAdd, true);
		}

		validateRestFile(file)
		{
			const result = {};

			if (Type.isNumber(file.id) || Type.isStringFilled(file.id))
			{
				result.id = file.id;
			}

			if (Type.isNumber(file.chatId) || Type.isString(file.chatId))
			{
				result.chatId = Number(file.chatId);
			}

			if (Type.isStringFilled(file.date))
			{
				result.date = DateHelper.cast(file.date).toISOString();
			}
			else if (Type.isDate(file.date))
			{
				result.date = file.date.toISOString();
			}

			if (Type.isString(file.type))
			{
				result.type = file.type;
			}

			if (Type.isString(file.extension))
			{
				result.extension = file.extension.toString();
			}

			if (Type.isString(file.name) || Type.isNumber(file.name))
			{
				result.name = file.name.toString();
			}

			if (Type.isNumber(file.size) || Type.isString(file.size))
			{
				result.size = Number(file.size);
			}

			if (Type.isBoolean(file.image))
			{
				result.image = false;
			}
			else if (Type.isObject(file.image))
			{
				result.image = {
					width: 0,
					height: 0,
				};

				if (Type.isString(file.image.width) || Type.isNumber(file.image.width))
				{
					result.image.width = parseInt(file.image.width, 10);
				}

				if (Type.isString(file.image.height) || Type.isNumber(file.image.height))
				{
					result.image.height = parseInt(file.image.height, 10);
				}

				if (result.image.width <= 0 || result.image.height <= 0)
				{
					result.image = false;
				}

				result.image = JSON.stringify(result.image);
			}

			if (Type.isString(file.status) && !Type.isUndefined(FileStatus[file.status]))
			{
				result.status = file.status;
			}

			if (Type.isNumber(file.progress) || Type.isString(file.progress))
			{
				result.progress = Number(file.progress);
			}

			if (Type.isNumber(file.authorId) || Type.isString(file.authorId))
			{
				result.authorId = Number(file.authorId);
			}

			if (Type.isString(file.authorName) || Type.isNumber(file.authorName))
			{
				result.authorName = file.authorName.toString();
			}

			if (Type.isString(file.urlPreview))
			{
				result.urlPreview = file.urlPreview;
			}

			if (Type.isString(file.urlDownload))
			{
				result.urlDownload = file.urlDownload;
			}

			if (Type.isString(file.urlShow))
			{
				result.urlShow = file.urlShow;
			}

			return result;
		}
	}

	module.exports = {
		FileRepository,
	};
});
