/**
 * @module im/messenger/lib/element/dialog/message/error
 */
jn.define('im/messenger/lib/element/dialog/message/error', (require, exports, module) => {
	const { Feature } = require('im/messenger/lib/feature');
	const { MessageType } = require('im/messenger/const');
	const { TextMessage } = require('im/messenger/lib/element/dialog/message/text');

	class ErrorMessage extends TextMessage
	{
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			if (Feature.isErrorMessageAvailable)
			{
				this.error = {};
			}
			else
			{
				/** @type {CopilotMessageCopilotData} */
				this.copilot = {};
			}

			this
				.setError()
				.setCanBeQuoted(false)
				.setCanBeChecked(false)
			;
		}

		/**
		 * @return {CopilotErrorDialogWidgetItem}
		 */
		toDialogWidgetItem()
		{
			const errorData = Feature.isErrorMessageAvailable ? { error: this.error } : { copilot: this.copilot };

			return {
				...super.toDialogWidgetItem(),
				...errorData,
			};
		}

		getType()
		{
			return Feature.isErrorMessageAvailable ? MessageType.error : MessageType.copilotError;
		}

		setError()
		{
			if (Feature.isErrorMessageAvailable)
			{
				this.error = {
					text: this.username,
				};

				return this;
			}

			this.copilot = {
				error: {
					text: this.username,
				},
			};

			return this;
		}
	}

	module.exports = { ErrorMessage };
});
