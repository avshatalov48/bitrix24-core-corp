/**
 * @module alert/confirm
 */
jn.define('alert/confirm', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');

	const ButtonType = {
		DEFAULT: 'default',
		DESTRUCTIVE: 'destructive',
		CANCEL: 'cancel',
	};

	const MIN_API_VERSION = 42;
	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class AlertNavigator
	 */
	class ConfirmNavigator
	{
		constructor(props)
		{
			this.onPress = this.handlerOnPress.bind(this);
			this.props = this.prepareProps(props);
		}

		handlerOnPress(index)
		{
			const { buttons } = this.props;
			const selectedButton = buttons[index - 1];

			if (selectedButton && selectedButton.onPress)
			{
				selectedButton.onPress();
			}
		}

		prepareProps(props)
		{
			const buttons = BX.prop.getArray(props, 'buttons', []);

			return {
				title: BX.prop.getString(props, 'title', ''),
				description: BX.prop.getString(props, 'description', ''),
				buttons: this.prepareButtons(buttons),
			};
		}

		prepareButtons(buttons)
		{
			if (!Type.isArrayFilled(buttons))
			{
				return buttons;
			}

			let preparedButtons = [...buttons];
			const buttonsArrayCancel = preparedButtons.filter(({ type }) => type === ButtonType.CANCEL);
			if (buttonsArrayCancel.length > 1)
			{
				throw new Error(`Only one button with type "${ButtonType.CANCEL}" is allowed.`);
			}

			if (preparedButtons.length === 0)
			{
				preparedButtons.push({
					text: Loc.getMessage('ALERT_CONFIRMATION_CONFIRM'),
					type: ButtonType.DEFAULT,
				});
			}
			else
			{
				preparedButtons = this.setButtonsTextByType(preparedButtons);
			}

			preparedButtons = this.moveCancelButtonLast(preparedButtons);

			// fix weird android behavior when last button becomes first
			if (isAndroid && preparedButtons.length === 3)
			{
				const [first, ...others] = preparedButtons;

				preparedButtons = [...others, first];
			}

			return (
				Application.getApiVersion() >= MIN_API_VERSION
					? preparedButtons
					: preparedButtons.map(({ text }) => text)
			);
		}

		setButtonsTextByType(buttons)
		{
			if (Type.isArrayFilled(buttons))
			{
				return buttons.map((button) => {
					if (button.text)
					{
						return button;
					}

					switch (button.type)
					{
						case ButtonType.DEFAULT:
							return {
								...button,
								text: Loc.getMessage('ALERT_CONFIRMATION_CONFIRM'),
							};
						case ButtonType.CANCEL:
							return {
								...button,
								text: Loc.getMessage('ALERT_CONFIRMATION_CANCEL'),
							};
						case ButtonType.DESTRUCTIVE:
							return {
								...button,
								text: Loc.getMessage('ALERT_CONFIRM_DELETING'),
							};
						default:
							return button;
					}
				});
			}

			return buttons;
		}

		moveCancelButtonLast(buttons)
		{
			const cancelButton = buttons.find(({ type }) => type === ButtonType.CANCEL);
			if (!cancelButton)
			{
				return buttons;
			}

			const buttonsWithoutCancel = buttons.filter(({ type }) => type !== ButtonType.CANCEL);

			return [...buttonsWithoutCancel, cancelButton];
		}

		open()
		{
			const { title, description, buttons } = this.props;

			navigator.notification.confirm(
				description,
				this.onPress,
				title,
				buttons,
			);
		}
	}

	const makeButton = (text, onPress, type = ButtonType.DEFAULT) => ({
		text,
		onPress,
		type,
	});

	const makeCancelButton = (onPress = null, text = null) => ({
		text,
		onPress,
		type: ButtonType.CANCEL,
	});

	const makeDestructiveButton = (text, onPress) => ({
		text,
		onPress,
		type: ButtonType.DESTRUCTIVE,
	});

	module.exports = {
		ConfirmNavigator,
		ButtonType,
		makeButton,
		makeCancelButton,
		makeDestructiveButton,
	};
});
