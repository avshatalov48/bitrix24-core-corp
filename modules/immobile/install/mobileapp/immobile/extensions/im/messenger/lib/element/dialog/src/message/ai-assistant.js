/**
 * @module im/messenger/lib/element/dialog/message/ai-assistant
 */
jn.define('im/messenger/lib/element/dialog/message/ai-assistant', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');

	const { Theme } = require('im/lib/theme');
	const { AiAssistantButtonType } = require('im/messenger/const');
	const { TextMessage } = require('im/messenger/lib/element/dialog/message/text');

	class AiAssistantMessage extends TextMessage
	{
		constructor(modelMessage = {}, options = {})
		{
			super(modelMessage, options);

			this
				.setButtons()
				.setFootNote()
				.setCanBeQuoted(true)
				.setCanBeChecked(true)
			;
		}

		/**
		 * @returns {AiAssistantDialogWidgetItem}
		 */
		toDialogWidgetItem()
		{
			return {
				...super.toDialogWidgetItem(),
				buttons: this.buttons,
				footnote: this.footnote,
			};
		}

		setButtons()
		{
			this.buttons = [
				{
					id: AiAssistantButtonType.copy,
					text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_AI_ASSISTANT_BUTTON_COPY'),
					editable: false,
					leftIcon: `${currentDomain}/bitrix/mobileapp/immobile/extensions/im/messenger/assets/common/svg/copy.svg`,
				},
			];

			return this;
		}

		setFootNote()
		{
			this.footnote = {
				text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_AI_ASSISTANT_FOOT_NOTE_TEXT'),
				textColor: Theme.colors.chatOtherBase1_2,
				backgroundColor: Theme.colors.accentSoftBlue3,
			};

			return this;
		}
	}

	module.exports = { AiAssistantMessage };
});
