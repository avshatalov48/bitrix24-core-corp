/**
 * @module im/messenger/lib/element/dialog/message/copilot-promt
 */

jn.define('im/messenger/lib/element/dialog/message/copilot-promt', (require, exports, module) => {
	const { TextMessage } = require('im/messenger/lib/element/dialog/message/text');
	const { Loc } = require('loc');
	const { CopilotButtonType } = require('im/messenger/const');

	class CopilotPromtMessage extends TextMessage
	{
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			/**
			 *
			 * @type {{promt: {title: string, text: string}, buttons: Array<CopilotButton>}}
			 */
			this.copilot = {};
			this
				.setCopilotButtons()
				.setCopilotPromt()
				.setShowAvatarForce(false)
				.setCanBeQuoted(false)
			;
		}

		getType()
		{
			return 'copilot-promt';
		}

		/**
		 * @desc blocking the value setting
		 * @param modelMessage
		 * @param shouldShowAvatar
		 * @return {CopilotPromtMessage}
		 */
		setShowAvatar(modelMessage, shouldShowAvatar)
		{
			this.showAvatar = false;

			return this;
		}

		setCopilotButtons()
		{
			this.copilot.buttons = [
				{
					id: CopilotButtonType.promtSend,
					text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_PROMT_ACTION_1'),
					editable: false,
					leftIcon: null,
					code: 'ability',
				},
				{
					id: CopilotButtonType.promtSend,
					text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_PROMT_ACTION_2'),
					editable: false,
					leftIcon: null,
					code: 'greeting',
				},
				{
					id: CopilotButtonType.promtSend,
					text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_PROMT_ACTION_3'),
					editable: false,
					leftIcon: null,
					code: 'congratulation',
				},
				{
					id: CopilotButtonType.promtSend,
					text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_PROMT_ACTION_4'),
					editable: false,
					leftIcon: null,
					code: 'poem',
				},
				{
					id: CopilotButtonType.promtSend,
					text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_PROMT_ACTION_5'),
					editable: false,
					leftIcon: null,
					code: 'autocad',
				},
			];

			return this;
		}

		setCopilotPromt()
		{
			this.copilot.promt = {
				title: this.username,
				text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_PROMT_TEXT'),
			};

			return this;
		}
	}

	module.exports = { CopilotPromtMessage };
});
