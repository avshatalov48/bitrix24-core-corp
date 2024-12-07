/**
 * @module im/messenger/lib/element/dialog/message/copilot-error
 */
jn.define('im/messenger/lib/element/dialog/message/copilot-error', (require, exports, module) => {
	const { MessageType } = require('im/messenger/const');
	const { CopilotAsset } = require('im/messenger/assets/copilot');
	const { TextMessage } = require('im/messenger/lib/element/dialog/message/text');

	class CopilotErrorMessage extends TextMessage
	{
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			this.copilot = {};

			this
				.setCopilotError()
				.setCanBeQuoted(false)
			;
		}

		getType()
		{
			return MessageType.copilotError;
		}

		setCopilotError()
		{
			this.copilot = {
				error: {
					text: this.username,
					svgUrl: CopilotAsset.errorSvgUrl,
				},
			};

			return this;
		}
	}

	module.exports = { CopilotErrorMessage };
});
