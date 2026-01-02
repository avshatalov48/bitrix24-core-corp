/**
 * @module im/messenger/model/dialogues/copilot/validator
 */

jn.define('im/messenger/model/dialogues/copilot/validator', (require, exports, module) => {
	const { Type } = require('type');
	const { withCurrentDomain } = require('utils/url');

	/**
	 * @param {CopilotModelState} fields
	 */
	function validate(fields)
	{
		const result = {};

		if (!Type.isUndefined(fields.chats) && !Type.isNull(fields.chats))
		{
			result.chats = fields.chats;
		}

		if (!Type.isUndefined(fields.roles) && !Type.isNull(fields.roles))
		{
			Object.entries(fields.roles).forEach(([roleId, roleData]) => {
				if (!Type.isObject(roleData) || !Type.isObject(roleData.avatar))
				{
					return;
				}

				Object.entries(roleData.avatar).forEach(([avatarSize, avatarUrl]) => {
					if (!Type.isStringFilled(avatarUrl))
					{
						return;
					}

					// eslint-disable-next-line no-param-reassign
					fields.roles[roleId].avatar[avatarSize] = withCurrentDomain(avatarUrl);
				});
			});

			result.roles = fields.roles;
		}

		if (!Type.isUndefined(fields.messages) && !Type.isNull(fields.messages) && fields.messages.length > 0)
		{
			result.messages = fields.messages;
		}

		result.dialogId = fields.dialogId || '';
		result.aiProvider = fields.aiProvider || '';

		return result;
	}

	/**
	 * @param {CopilotModelState} newElem
	 * @param {CopilotModelState} existingElem
	 */
	function prepareMergeProperty(newElem, existingElem)
	{
		const result = {
			dialogId: newElem.dialogId || existingElem.dialogId,
			aiProvider: newElem.aiProvider || existingElem.aiProvider,
		};

		if (!Type.isUndefined(newElem.chats) && !Type.isNull(newElem.chats))
		{
			result.chats = newElem.chats;
		}
		else
		{
			result.chats = existingElem.chats;
		}

		if (!Type.isUndefined(newElem.roles) && !Type.isNull(newElem.roles))
		{
			result.roles = { ...existingElem.roles, ...newElem.roles };
		}

		if (!Type.isUndefined(newElem.messages) && !Type.isNull(newElem.messages) && newElem.messages.length > 0)
		{
			result.messages = [...new Set([...existingElem.messages, ...newElem.messages])];
		}

		return result;
	}

	module.exports = {
		validate,
		prepareMergeProperty,
	};
});
