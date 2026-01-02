/**
 * @module im/messenger-v2/lib/widget/chat-recent/welcome-screen
 */
jn.define('im/messenger-v2/lib/widget/chat-recent/welcome-screen', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @class WelcomeScreen
	 */
	class WelcomeScreen
	{
		constructor(options = {})
		{
			this.setUpperText(options.upperText);
			this.setLowerText(options.lowerText);
			this.setIconName(options.iconName);
			this.setListener(options.listener);
			this.setStartChatButton(options.startChatButton);
		}

		static create(options = {})
		{
			return new this(options);
		}

		setUpperText(text)
		{
			this.upperText = Type.isStringFilled(text) ? text : '';

			return this;
		}

		setLowerText(text)
		{
			this.lowerText = Type.isStringFilled(text) ? text : '';

			return this;
		}

		setIconName(iconName)
		{
			this.iconName = Type.isStringFilled(iconName) ? iconName : '';

			return this;
		}

		setListener(listener)
		{
			this.listener = Type.isFunction(listener) ? listener : () => {};

			return this;
		}

		setStartChatButton(startChatButton)
		{
			this.startChatButton = Type.isObject(startChatButton) ? startChatButton : null;
		}

		toChatRecentWidgetItem()
		{
			const item = {
				upperText: this.upperText,
				lowerText: this.lowerText,
				iconName: this.iconName,
				listener: this.listener,
			};

			if (this.startChatButton)
			{
				item.startChatButton = this.startChatButton;
			}

			return item;
		}
	}

	module.exports = { WelcomeScreen };
});
