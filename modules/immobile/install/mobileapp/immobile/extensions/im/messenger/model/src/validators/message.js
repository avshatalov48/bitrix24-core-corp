/**
 * @module im/messenger/model/validators/message
 */
jn.define('im/messenger/model/validators/message', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { Uuid } = require('utils/uuid');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { ObjectUtils } = require('im/messenger/lib/utils');

	/**
	 * @param {object} fields
	 * @return {MessagesModelState}
	 */
	function validate(fields)
	{
		const result = {};

		if (Type.isNumber(fields.id))
		{
			result.id = fields.id;
			result.templateId = (Uuid.isV4(fields.templateId) || Uuid.isV4(fields.uuid)) ? (fields.templateId || fields.uuid) : '';
		}
		else if (Uuid.isV4(fields.templateId) || Uuid.isV4(fields.uuid))
		{
			result.id = fields.templateId;
			result.templateId = fields.templateId || fields.uuid;
		}
		else if (Uuid.isV4(fields.id))
		{
			result.id = fields.id;
			result.templateId = fields.id;
		}

		if (Type.isNumber(fields.previousId))
		{
			// from database
			result.previousId = fields.previousId;
		}
		else if (Type.isNumber(fields.prevId))
		{
			// from push & pull
			result.previousId = fields.prevId;
		}

		if (Type.isNumber(fields.nextId))
		{
			// from database
			result.nextId = fields.nextId;
		}

		if (!Type.isUndefined(fields.chat_id))
		{
			fields.chatId = fields.chat_id;
		}

		if (Type.isNumber(fields.chatId) || Type.isStringFilled(fields.chatId))
		{
			result.chatId = Number.parseInt(fields.chatId, 10);
		}

		if (Type.isStringFilled(fields.date))
		{
			result.date = DateHelper.cast(fields.date);
		}
		else if (Type.isDate(fields.date))
		{
			result.date = fields.date;
		}

		if (Type.isNumber(fields.text) || Type.isStringFilled(fields.text))
		{
			result.text = fields.text.toString();
		}

		if (Type.isNumber(fields.loadText) || Type.isString(fields.loadText))
		{
			result.loadText = fields.loadText.toString();
		}

		if (!Type.isUndefined(fields.senderId))
		{
			fields.authorId = fields.senderId;
		}
		else if (!Type.isUndefined(fields.author_id))
		{
			fields.authorId = fields.author_id;
		}

		if (Type.isNumber(fields.authorId) || Type.isStringFilled(fields.authorId))
		{
			if (
				fields.system === true
				|| fields.system === 'Y'
				|| fields.isSystem === true
			)
			{
				result.authorId = 0;
			}
			else
			{
				result.authorId = Number.parseInt(fields.authorId, 10);
			}
		}

		if (Type.isArray(fields.attach))
		{
			result.attach = fields.attach;
		}

		if (Type.isArray(fields.keyboard))
		{
			result.keyboard = fields.keyboard;
		}

		if (Type.isNumber(fields.richLinkId) || Type.isNull(fields.richLinkId))
		{
			result.richLinkId = fields.richLinkId;
		}

		if (Type.isPlainObject(fields.params))
		{
			const { params, fileIds, attach, richLinkId, keyboard } = validateParams(fields.params);
			result.params = params;
			result.files = fileIds;
			result.richLinkId = richLinkId;

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

		// passed when a file is received from the local database
		if (Type.isArrayFilled(fields.files))
		{
			result.files = fields.files;
		}

		if (Type.isPlainObject(fields.reactionCollection))
		{
			if (!result.params)
			{
				result.params = {};
			}

			if (!result.params.REACTION)
			{
				result.params.REACTION = {};
			}

			Object.entries(fields.reactionCollection).forEach(([key, value]) => {
				result.params.REACTION[key] = value;
			});
		}

		if (Type.isArray(fields.replaces))
		{
			result.replaces = fields.replaces;
		}

		if (Type.isBoolean(fields.sending))
		{
			result.sending = fields.sending;
		}

		if (Type.isBoolean(fields.unread))
		{
			result.unread = fields.unread;
		}

		if (Type.isBoolean(fields.viewed))
		{
			result.viewed = fields.viewed;
		}

		if (Type.isBoolean(fields.viewedByOthers))
		{
			result.viewedByOthers = fields.viewedByOthers;
		}

		if (Type.isBoolean(fields.error))
		{
			result.error = fields.error;
		}

		if (Type.isNumber(fields.errorReason))
		{
			result.errorReason = fields.errorReason;
		}

		if (Type.isBoolean(fields.retry))
		{
			result.retry = fields.retry;
		}

		if (Type.isBoolean(fields.audioPlaying))
		{
			result.audioPlaying = fields.audioPlaying;
		}

		if (Type.isNumber(fields.playingTime))
		{
			result.playingTime = fields.playingTime;
		}

		result.forward = {};
		if (Type.isObject(fields.forward) && fields.forward.id)
		{
			result.forward = fields.forward;
		}

		if (Type.isString(fields.uploadFileId))
		{
			result.uploadFileId = fields.uploadFileId;
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
			else if (key === 'LIKE' && Type.isArray(value))
			{
				params.REACTION = { like: value.map((element) => Number.parseInt(element, 10)) };
			}
			else if (key === 'FILE_ID' && Type.isArray(value))
			{
				fileIds = value;
			}
			else if (key === 'REPLY_ID')
			{
				params.replyId = Type.isString(value) ? parseInt(value, 10) : value;
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
