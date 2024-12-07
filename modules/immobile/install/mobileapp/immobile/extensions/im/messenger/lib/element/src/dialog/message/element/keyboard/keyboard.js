/**
 * @module im/messenger/lib/element/dialog/message/element/keyboard/keyboard
 */
jn.define('im/messenger/lib/element/dialog/message/element/keyboard/keyboard', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');

	const {
		KeyboardButtonContext,
		KeyboardButtonType,
		KeyboardButtonNewLineSeparator,
		KeyboardButtonColorToken,
	} = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('element--message-keyboard');

	class Keyboard
	{
		/**
		 * @param {KeyboardButtonConfig[]} modelKeyboard
		 */
		static createByMessagesModelKeyboard(modelKeyboard)
		{
			return new this(modelKeyboard);
		}

		/**
		 * @param {KeyboardButtonConfig[]} modelKeyboard
		 */
		constructor(modelKeyboard)
		{
			/**
			 * @type {KeyboardButtonConfig[]}
			 */
			this.modelKeyboard = [];
			if (Type.isArrayFilled(modelKeyboard))
			{
				this.modelKeyboard = modelKeyboard;
			}
		}

		/**
		 * @return {KeyboardButtonConfig[]}
		 */
		toMessageFormat()
		{
			/**
			 * @type {KeyboardButtonConfig[]}
			 */
			const messageKeyboard = clone(this.modelKeyboard)
				.filter((button) => {
					const isMobileContextButton = [
						KeyboardButtonContext.all,
						KeyboardButtonContext.mobile,
					].includes(button.context);

					return isMobileContextButton || button.type === KeyboardButtonType.newLine;
				})
				.map((buttonConfig) => {
					const button = buttonConfig;
					if (button.type === KeyboardButtonType.newLine)
					{
						return KeyboardButtonNewLineSeparator;
					}

					if (!Type.isStringFilled(button.bgColorToken))
					{
						button.bgColorToken = KeyboardButtonColorToken.base;
					}

					return button;
				})
			;

			const isOnlyNewLineLeft = messageKeyboard.length === 1 && messageKeyboard[0].type === KeyboardButtonType.newLine;
			if (isOnlyNewLineLeft)
			{
				messageKeyboard.pop();
			}

			logger.log(`${this.constructor.name}.toMessageFormat: `, this.modelKeyboard, messageKeyboard);

			return messageKeyboard;
		}
	}

	module.exports = { Keyboard };
});
