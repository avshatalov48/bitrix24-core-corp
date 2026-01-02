/**
 * @module im/messenger/model/dialogues/ai-assistant/validator
 */

jn.define('im/messenger/model/dialogues/ai-assistant/validator', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @param {AiAssistantModelState['notifyPanel']} fields
	 */
	function validateNotifyPanel(fields)
	{
		const result = {};

		if (Type.isBoolean(fields.isClosedNotifyPanel))
		{
			result.isClosedNotifyPanel = fields.isClosedNotifyPanel;
		}

		return result;
	}

	module.exports = { validateNotifyPanel };
});
