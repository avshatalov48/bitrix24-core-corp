/**
 * @module im/messenger-v2/lib/widget/header-button/popup-create-button
 */
jn.define('im/messenger-v2/lib/widget/header-button/popup-create-button', (require, exports, module) => {
	const { Type } = require('type');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const logger = getLoggerWithContext('widget--popup', 'PopupCreateButton');

	/**
	 * @class PopupCreateButton
	 */
	class PopupCreateButton
	{
		/**
		 * @param {HeaderPopupCreateButtonConfig} options
		 * @return {PopupCreateButton}
		 */
		static create(options)
		{
			return new this(options);
		}

		/**
		 * @param {HeaderPopupCreateButtonConfig} options
		 */
		constructor(options)
		{
			this.id = options.id;
			this.type = options.type;
			this.title = options.title;
			this.buttons = options.buttons;

			this.sections = Type.isArrayFilled(options.sections) ? options.sections : [{ id: 'general' }];
			this.testId = Type.isStringFilled(options.testId) ? options.testId : `${options.id}_button`;
			this.badgeCode = Type.isStringFilled(options.badgeCode) ? options.badgeCode : null;
		}

		async shouldShow()
		{
			const shouldShowPromiseList = this.buttons.map(async (button) => button.shouldShow());
			const shouldShowResult = await Promise.all(shouldShowPromiseList);

			return shouldShowResult.includes(true);
		}

		async callback()
		{
			const menuPopup = window.dialogs.createPopupMenu();
			const shouldShowButtonPromiseList = this.buttons.map(async (button) => {
				const isVisible = await button.shouldShow();

				return isVisible ? button : null;
			});
			const shouldShowResult = await Promise.all(shouldShowButtonPromiseList);
			const menuButtons = shouldShowResult
				.filter((button) => button !== null)
				.map((button) => button.toPopupMenuButton())
			;

			const menuButtonHandler = async (event, item) => {
				const popupButton = this.buttons.find((button) => button.id === item.id);
				if (event === 'onItemSelected' && popupButton)
				{
					try
					{
						await popupButton.callback();
					}
					catch (error)
					{
						logger.error(`Error executing callback for button ${popupButton.id}:`, error);
					}
				}
			};

			menuPopup.setData(menuButtons, this.sections, menuButtonHandler);
			menuPopup.show([]);
		}

		toWidgetHeaderButton()
		{
			return {
				id: this.id,
				testId: this.testId,
				type: this.type,
				callback: this.callback.bind(this),
				badgeCode: this.badgeCode,
			};
		}
	}

	module.exports = { PopupCreateButton };
});
