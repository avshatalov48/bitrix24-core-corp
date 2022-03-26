(() => {
	const styles = {
		saveButton: {
			wrapper: {
				flex: 1,
				marginLeft: 25,
				marginRight: 0
			},
			container: {
				flexDirection: 'row',
				justifyContent: 'center',
				alignContent: 'center',
				borderWidth: 1,
				borderRadius: 24,
				height: 48,
				borderColor: '#00a2e8',
				backgroundColor: '#00A2E8'
			},
			text: {
				fontWeight: '500',
				fontSize: 17,
				ellipsize: 'end',
				numberOfLines: 1,
				color: '#FFFFFF'
			},
		},
		cancelButton: {
			wrapper: {
				flex: 1,
				marginLeft: 16,
				marginRight: 25
			},
			container: {
				flexDirection: 'row',
				justifyContent: 'center',
				alignContent: 'center',
				borderWidth: 1,
				borderRadius: 24,
				height: 48,
				borderColor: '#525c69',
				backgroundColor: '#FFFFFF'
			},
			text: {
				fontWeight: '500',
				fontSize: 17,
				ellipsize: 'end',
				numberOfLines: 1,
				color: '#525c69'
			}
		}
	};

	/**
	 * @class ToolPanel
	 */
	class ToolPanel extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				visible: false
			};
		}

		show()
		{
			this.setState({visible: true});
		}

		hide()
		{
			this.setState({visible: false});
		}

		render()
		{
			return View(
				{
					style: (
						this.state.visible
							? UI.BottomToolbar.styles.innerContainer(false)
							: {display: 'none'}
					)
				},
				this.state.visible && (new UI.BottomToolbar({isWithSafeArea: false, items: this.getItems()}))
			);
		}

		getItems()
		{
			return [
				View(
					{
						style: styles.saveButton.wrapper
					},
					View(
						{
							style: styles.saveButton.container,
							onClick: this.props.onSave
						},
						Text({
							style: styles.saveButton.text,
							text: BX.message('DETAIL_CARD_BUTTONS_TOOLBAR_SAVE')
						})
					)
				),
				View(
					{
						style: styles.cancelButton.wrapper
					},
					View(
						{
							style: styles.cancelButton.container,
							onClick: this.props.onCancel
						},
						Text({
							style: styles.cancelButton.text,
							text: BX.message('DETAIL_CARD_BUTTONS_TOOLBAR_CANCEL')
						})
					)
				)
			];
		}
	}

	this.ToolPanel = ToolPanel;
})();
