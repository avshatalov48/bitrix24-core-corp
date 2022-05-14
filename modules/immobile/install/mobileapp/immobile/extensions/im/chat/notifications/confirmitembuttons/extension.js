(() =>
{
	var styles = {
		button: {
			height: 40,
			maxWidth: 120,
			marginRight: 12,
			marginBottom: 1,
			paddingLeft: 10,
			paddingRight: 10,
			borderRadius: 3,
			borderWidth: 1,
			fontWeight: 'bold',
			fontSize: 14,
			whiteSpace: 'nowrap',
		},
	}

	this.ConfirmItemButtons = class ConfirmItemButtons extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				isRequestSent: false,
			};
		}

		componentWillReceiveProps(props) {
			this.setState({
				isRequestSent: false
			});
		}

		renderButtons()
		{
			if (!this.props.buttons || this.props.buttons.length === 0)
			{
				return [];
			}
			if (this.state.isRequestSent)
			{
				return [
					Loader({
						style: {
							width: 26,
							height: 26,
							alignSelf: 'center'
						},
						tintColor: '#666666',
						animating: true,
						size: 'small'
					})
				];
			}

			return this.props.buttons.map(button => Button({
				style: Object.assign({
					color: this.getButtonColor(button).textColor,
					backgroundColor: this.getButtonColor(button).backgroundColor,
					borderColor: this.getButtonColor(button).borderColor
				}, styles.button),
				text: this.getButtonText(button),
				onClick: () => {
					const queryParams = this.getNotifyCommandParams(button);
					this.setState({
						isRequestSent: true
					});
					BX.rest.callMethod('im.notify.confirm', queryParams)
						.then(res => {
							this.props.confirmButtonsHandler();
							//console.log('im.notify.confirm res:', res);
						})
						.catch(error => {
							this.setState({
								isRequestSent: false
							});
							Utils.showError(
								BX.message['MOBILE_EXT_CONFIRM_ITEM_BUTTONS_ERROR_TITLE'],
								BX.message['MOBILE_EXT_CONFIRM_ITEM_BUTTONS_ERROR_TEXT'],
								'#affb0000'
							);
							console.log(error);
						});
				}})
			);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						marginTop: 10
					},
				},
				...this.renderButtons()
			);
		}

		getButtonColor(button)
		{
			if (
				(button.hasOwnProperty('COMMAND_PARAMS') && button.COMMAND_PARAMS.endsWith("N")) ||
				(button.hasOwnProperty('TYPE') && button.TYPE === "cancel")
			)
			{
				return {
					textColor: "#535c69",
					backgroundColor: "#fff",
					borderColor: "#c6cdd3"
				};
			}
			else
			{
				return {
					textColor: "#fff",
					backgroundColor: "#3bc8f5",
					borderColor: "#3bc8f5"
				};
			}
		}

		getButtonText(button)
		{
			if (button.hasOwnProperty('TEXT'))
			{
				return button.TEXT.toUpperCase();
			}
			else if (button.hasOwnProperty('TITLE'))
			{
				return button.TITLE.toUpperCase();
			}
		}

		getNotifyCommandParams(button)
		{
			if (button.hasOwnProperty('COMMAND_PARAMS'))
			{
				const options = button.COMMAND_PARAMS.split('|');

				return {
					'NOTIFY_ID': options[0],
					'NOTIFY_VALUE': options[1],
				};
			}
			else if (button.hasOwnProperty('VALUE'))
			{
				return {
					'NOTIFY_ID': this.props.messageId,
					'NOTIFY_VALUE': button.VALUE,
				};
			}
		}
	}

})();