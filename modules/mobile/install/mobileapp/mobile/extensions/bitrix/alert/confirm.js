/**
 * @module alert/confirm
 */
jn.define('alert/confirm', (require, exports, module) => {

	const ButtonType = {
		DEFAULT: 'default',
		DESTRUCTIVE: 'destructive',
		CANCEL: 'cancel',
	};

	const MIN_API_VERSION = 42;

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
			const buttonsArrayCancel = buttons.filter(({ type }) => type === ButtonType.CANCEL);
			if (buttonsArrayCancel.length > 1)
			{
				throw new Error(`Only one button with type "${ButtonType.CANCEL}" is allowed.`);
			}

			if (buttons.length === 0)
			{
				buttons.push({
					text: BX.message('ALERT_CONFIRMATION_CONFIRM'),
					type: ButtonType.CANCEL,
				});
			}

			return buttons.map((button) =>
				button.type === ButtonType.CANCEL ? {
					...button,
					text: button.text || BX.message('ALERT_CONFIRMATION_CANCEL'),
				} : button);
		}

		getButtons()
		{
			let { buttons } = this.props;
			const sortDirection = buttons.length > 2 ? -1 : 1;

			buttons = buttons.sort(({ type }) => type === ButtonType.CANCEL ? sortDirection : -1 * sortDirection);

			return (
				Application.getApiVersion() >= MIN_API_VERSION
					? buttons
					: buttons.map(({ text }) => text)
			);
		}

		open()
		{
			const { title, description } = this.props;

			navigator.notification.confirm(
				description,
				this.onPress,
				title,
				this.getButtons(),
			);
		}

		handlerOnPress(index)
		{
			const { buttons } = this.props;
			const selectedButton = buttons[index - 1];
			if (selectedButton.onPress)
			{
				selectedButton.onPress();
			}
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
		type: ButtonType.DESTRUCTIVE
	})

	module.exports = {
		ConfirmNavigator,
		ButtonType,
		makeButton,
		makeCancelButton,
		makeDestructiveButton,
	};
});