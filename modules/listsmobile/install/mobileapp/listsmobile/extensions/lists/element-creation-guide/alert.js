/**
 * @module lists/element-creation-guide/alert
 */
jn.define('lists/element-creation-guide/alert', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const { Alert, ButtonType } = require('alert');

	class AlertWindow
	{
		constructor(buttons)
		{
			this.buttons = buttons;
		}

		show()
		{
			Haptics.impactLight();

			Alert.confirm(
				Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_CONFIRM_ALERT_TITLE'),
				Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_CONFIRM_ALERT_DESCRIPTION'),
				this.buttons,
			);
		}

		static getExitWithoutSaveButton(onPress)
		{
			return {
				text: Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_CONFIRM_ALERT_EXIT_WITHOUT_SAVE'),
				type: ButtonType.DESTRUCTIVE,
				onPress,
			};
		}

		static getContinueButton(onPress)
		{
			return {
				text: Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_CONFIRM_ALERT_CONTINUE'),
				type: ButtonType.CANCEL,
				onPress,
			};
		}

		static getSaveAndExitButton(onPress)
		{
			return {
				text: Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_CONFIRM_ALERT_SAVE_AND_EXIT'),
				type: ButtonType.DESTRUCTIVE,
				onPress,
			};
		}
	}

	module.exports = { AlertWindow };
});
