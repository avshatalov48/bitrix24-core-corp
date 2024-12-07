/**
 * @module im/messenger/model/sidebar/links/validators/link
 */

jn.define('im/messenger/model/sidebar/links/validators/link', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @param {SidebarLink} fields
	 */
	function validate(fields)
	{
		const result = {};

		if (Type.isNumber(fields.id))
		{
			result.id = fields.id;
		}

		if (Type.isNumber(fields.messageId))
		{
			result.messageId = fields.messageId;
		}

		if (Type.isNumber(fields.chatId))
		{
			result.chatId = fields.chatId;
		}

		if (Type.isNumber(fields.authorId))
		{
			result.authorId = fields.authorId;
		}

		if (Type.isDate(fields.dateCreate) || Type.isString(fields.dateCreate))
		{
			result.dateCreate = fields.dateCreate;
		}

		if (Type.isPlainObject(fields.url))
		{
			result.url = validateUrl(fields.url);
		}

		return result;
	}

	function validateUrl(url)
	{
		const result = {};

		if (Type.isPlainObject(url))
		{
			const { source, richData } = url;

			if (Type.isString(source))
			{
				result.source = source;
			}

			if (Type.isPlainObject(richData))
			{
				result.richData = validateRichData(richData);
			}
		}

		return result;
	}

	function validateRichData(richData)
	{
		const result = {};

		if (Type.isPlainObject(richData))
		{
			const {
				id,
				description,
				link,
				name,
				previewUrl,
				type,
			} = richData;

			if (Type.isNumber(id) || Type.isNull(id))
			{
				result.id = id;
			}

			if (Type.isString(description) || Type.isNull(description))
			{
				result.description = description;
			}

			if (Type.isString(link) || Type.isNull(link))
			{
				result.link = link;
			}

			if (Type.isString(name) || Type.isNull(name))
			{
				result.name = name;
			}

			if (Type.isString(previewUrl) || Type.isNull(previewUrl))
			{
				result.previewUrl = previewUrl;
			}

			if (Type.isString(type) || Type.isNull(type))
			{
				result.type = type;
			}
		}

		return result;
	}

	module.exports = { validate };
});
