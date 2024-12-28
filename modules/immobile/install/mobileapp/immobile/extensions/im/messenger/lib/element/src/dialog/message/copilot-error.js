/**
 * @module im/messenger/lib/element/dialog/message/copilot-error
 */
jn.define('im/messenger/lib/element/dialog/message/copilot-error', (require, exports, module) => {
	const { Type } = require('type');

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
				.setCanBeChecked(false)
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

		/**
		 * @param {UsersModelState|null} user
		 * @void
		 */
		setAvatarDetail(user)
		{
			super.setAvatarDetail(user);

			// TODO: switch to ChatAvatar for CoPilot
			if (Type.isObject(this.avatar) && this.avatar.uri)
			{
				this.avatar.uri = this.avatarUrl;
			}
		}
	}

	module.exports = { CopilotErrorMessage };
});
