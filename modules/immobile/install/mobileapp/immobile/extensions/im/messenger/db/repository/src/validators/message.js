/**
 * @module im/messenger/db/repository/validators/message
 */
jn.define('im/messenger/db/repository/validators/message', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { ObjectUtils } = require('im/messenger/lib/utils');
	const { clone } = require('utils/object');

	function validate(message)
	{
		const result = {};

		if (Type.isNumber(message.id))
		{
			result.id = message.id;
		}

		/**
		 * field from context creator
		 * @see MessageContextCreator
		 */
		if (Type.isNumber(message.previousId))
		{
			result.previousId = message.previousId;
		}

		/**
		 * field from context creator
		 * @see MessageContextCreator
		 */
		if (Type.isNumber(message.nextId))
		{
			result.nextId = message.nextId;
		}

		if (!Type.isUndefined(message.chat_id))
		{
			// eslint-disable-next-line no-param-reassign
			message.chatId = message.chat_id;
		}

		if (Type.isNumber(message.chatId) || Type.isStringFilled(message.chatId))
		{
			result.chatId = Number.parseInt(message.chatId, 10);
		}

		if (Type.isStringFilled(message.date))
		{
			result.date = DateHelper.cast(message.date);
		}
		else if (Type.isDate(message.date))
		{
			result.date = message.date;
		}

		if (Type.isNumber(message.text) || Type.isStringFilled(message.text))
		{
			result.text = message.text.toString();
		}

		if (!Type.isUndefined(message.senderId))
		{
			// eslint-disable-next-line no-param-reassign
			message.authorId = message.senderId;
		}
		else if (!Type.isUndefined(message.author_id))
		{
			// eslint-disable-next-line no-param-reassign
			message.authorId = message.author_id;
		}

		if (Type.isNumber(message.authorId) || Type.isStringFilled(message.authorId))
		{
			if (
				message.system === true
				|| message.system === 'Y'
				|| message.isSystem === true
			)
			{
				result.authorId = 0;
			}
			else
			{
				result.authorId = Number.parseInt(message.authorId, 10);
			}
		}

		if (Type.isBoolean(message.sending))
		{
			result.sending = message.sending;
		}

		if (Type.isBoolean(message.unread))
		{
			result.unread = message.unread;
		}

		if (Type.isBoolean(message.viewed))
		{
			result.viewed = message.viewed;
		}

		if (Type.isBoolean(message.viewedByOthers))
		{
			result.viewedByOthers = message.viewedByOthers;
		}

		if (Type.isBoolean(message.error))
		{
			result.error = message.error;
		}

		if (Type.isBoolean(message.retry))
		{
			result.retry = message.retry;
		}

		if (Type.isArray(message.attach))
		{
			result.attach = message.attach;
		}

		if (Type.isArray(message.keyboard))
		{
			result.keyboard = message.keyboard;
		}

		if (Type.isNumber(message.richLinkId) || Type.isNull(message.richLinkId))
		{
			result.richLinkId = message.richLinkId;
		}

		if (Type.isPlainObject(message.params))
		{
			const { params, fileIds, attach, richLinkId, keyboard } = validateParams(message.params);
			result.params = params;
			result.files = fileIds;

			if (Type.isUndefined(result.attach))
			{
				result.attach = attach;
			}

			if (Type.isUndefined(result.richLinkId))
			{
				result.richLinkId = richLinkId;
			}

			if (Type.isUndefined(result.keyboard))
			{
				result.keyboard = keyboard;
			}
		}

		result.forward = {};
		if (Type.isPlainObject(message.forward))
		{
			result.forward = message.forward;
		}

		return result;
	}

	function validateParams(rawParams)
	{
		const params = {};
		let fileIds = [];
		let attach = [];
		let keyboard = [];
		let richLinkId = null;

		Object.entries(rawParams).forEach(([key, value]) => {
			if (key === 'COMPONENT_ID' && Type.isStringFilled(value))
			{
				params.componentId = value;
			}
			else if (key === 'FILE_ID' && Type.isArray(value))
			{
				fileIds = value;
			}
			else if (key === 'ATTACH')
			{
				attach = ObjectUtils.convertKeysToCamelCase(clone(value), true);
				params.ATTACH = value;
			}
			else if (key === 'KEYBOARD')
			{
				if (Type.isArray(value))
				{
					keyboard = ObjectUtils.convertKeysToCamelCase(clone(value), true);
					keyboard = keyboard.map((rawButton) => {
						return {
							...rawButton,
							block: rawButton.block === 'Y',
							disabled: rawButton.disabled === 'Y',
							vote: rawButton.vote === 'Y',
							wait: rawButton.wait === 'Y',
						};
					});

					params.KEYBOARD = value;
				}
				else
				{
					params.KEYBOARD = [];
				}
			}
			else if (key === 'URL_ID')
			{
				richLinkId = value[0] ? Number(value[0]) : null;
				params.URL_ID = value;
			}
			else
			{
				params[key] = value;
			}
		});

		return { params, fileIds, attach, richLinkId, keyboard };
	}

	module.exports = { validate };
});
