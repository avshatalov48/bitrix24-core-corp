/**
 * @module im/messenger/db/repository/validators/recent
 */
jn.define('im/messenger/db/repository/validators/recent', (require, exports, module) => {
	const { Type } = require('type');
	const { Uuid } = require('utils/uuid');

	/**
	 * @param fields
	 * @return {Partial<RecentModelState>}
	 */
	function validateRestItem(fields)
	{
		const result = {
			options: {},
		};

		if (Type.isNumber(fields.id) || Type.isStringFilled(fields.id))
		{
			result.id = fields.id.toString();
		}

		if (Type.isString(fields.date_last_activity))
		{
			result.lastActivityDate = fields.date_last_activity;
		}

		if (Type.isPlainObject(fields.message))
		{
			result.message = prepareMessage(fields);
		}

		if (Type.isUndefined(fields.dateMessage) && Type.isPlainObject(fields.message))
		{
			result.dateMessage = fields.message.date;
		}

		if (Type.isBoolean(fields.unread))
		{
			result.unread = fields.unread;
		}

		if (Type.isBoolean(fields.pinned))
		{
			result.pinned = fields.pinned;
		}

		if (Type.isPlainObject(fields.options))
		{
			if (!result.options)
			{
				result.options = {};
			}

			if (Type.isBoolean(fields.options.default_user_record))
			{
				// eslint-disable-next-line no-param-reassign
				fields.options.defaultUserRecord = fields.options.default_user_record;
			}

			if (Type.isBoolean(fields.options.defaultUserRecord))
			{
				result.options.defaultUserRecord = fields.options.defaultUserRecord;
			}

			if (Type.isBoolean(fields.options.birthdayPlaceholder))
			{
				result.options.birthdayPlaceholder = fields.options.birthdayPlaceholder;
			}
		}

		return result;
	}

	function prepareMessage(fields)
	{
		const message = {};
		const params = {};

		if (
			Type.isNumber(fields.message.id)
			|| Type.isStringFilled(fields.message.id)
			|| Uuid.isV4(fields.message.id)
		)
		{
			message.id = fields.message.id;
		}

		if (Type.isString(fields.message.text))
		{
			message.text = fields.message.text;
		}

		if (Type.isStringFilled(fields.message.subTitleIcon))
		{
			message.subTitleIcon = fields.message.subTitleIcon;
		}
		else
		{
			message.subTitleIcon = '';
		}

		if (
			Type.isStringFilled(fields.message.attach)
			|| Type.isBoolean(fields.message.attach)
			|| Type.isArray(fields.message.attach)
		)
		{
			params.withAttach = fields.message.attach;
		}
		else if (
			Type.isStringFilled(fields.message.params?.withAttach)
			|| Type.isBoolean(fields.message.params?.withAttach)
			|| Type.isArray(fields.message.params?.withAttach)
		)
		{
			params.withAttach = fields.message.params.withAttach;
		}

		if (Type.isBoolean(fields.message.file) || Type.isPlainObject(fields.message.file))
		{
			params.withFile = fields.message.file;
		}
		else if (Type.isBoolean(fields.message.params?.withFile) || Type.isPlainObject(fields.message.params?.withFile))
		{
			params.withFile = fields.message.params.withFile;
		}

		if (Type.isDate(fields.message.date) || Type.isString(fields.message.date))
		{
			message.date = fields.message.date;
		}

		if (Type.isNumber(fields.message.author_id))
		{
			message.senderId = fields.message.author_id;
		}
		else if (Type.isNumber(fields.message.authorId))
		{
			message.senderId = fields.message.authorId;
		}
		else if (Type.isNumber(fields.message.senderId))
		{
			message.senderId = fields.message.senderId;
		}

		if (Type.isStringFilled(fields.message.status))
		{
			message.status = fields.message.status;
		}

		if (Type.isBoolean(fields.message.sending))
		{
			message.sending = fields.message.sending;
		}

		if (Object.keys(params).length > 0)
		{
			message.params = params;
		}

		return message;
	}

	module.exports = { validateRestItem };
});
