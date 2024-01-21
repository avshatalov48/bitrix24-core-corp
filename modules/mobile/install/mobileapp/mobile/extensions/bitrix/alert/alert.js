/**
 * @module alert/alert
 */
jn.define('alert/alert', (require, exports, module) => {
	/**
	 * @class AlertNavigator
	 */
	class AlertNavigator
	{
		constructor(props)
		{
			this.props = this.defaultProps(props);
			this.onPress = this.handlerOnPress.bind(this);
		}

		defaultProps(props)
		{
			return {
				onPress: BX.prop.getFunction(props, 'onPress', null),
				title: BX.prop.getString(props, 'title', ''),
				description: BX.prop.getString(props, 'description', ''),
				buttonName: BX.prop.getString(props, 'buttonName', BX.message('ALERT_CONFIRMATION_CONFIRM')),
			};
		}

		handlerOnPress()
		{
			const { onPress } = this.props;
			if (onPress)
			{
				onPress();
			}
		}

		open()
		{
			const { title, description, buttonName } = this.props;

			navigator.notification.alert(
				description,
				this.onPress,
				title,
				buttonName,
			);
		}
	}

	module.exports = { AlertNavigator };
});
