(() => {
	const styles = {
		base: {
			wrapper: {
				flex: 1
			},
			container: {
				flexDirection: 'row',
				justifyContent: 'center',
				alignContent: 'center',
				borderWidth: 1,
				borderRadius: 24,
				height: 48
			},
			text: {
				fontWeight: '500',
				fontSize: 17,
				ellipsize: 'end',
				numberOfLines: 1
			}
		},
		primary: {
			container: {
				borderColor: '#00a2e8',
				backgroundColor: '#00a2e8'
			},
			text: {
				color: '#ffffff'
			}
		},
		secondary: {
			container: {
				borderColor: '#525c69',
				backgroundColor: '#ffffff'
			},
			text: {
				color: '#525c69'
			}
		}
	};

	const Types = {
		PRIMARY: 'primary',
		SECONDARY: 'secondary'
	};

	/**
	 * @class ActionsPanel
	 */
	class ActionsPanel extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				visible: false
			};

			this.setModel(this.props.model);
		}

		componentWillReceiveProps(newProps)
		{
			this.setModel(newProps.model);
		}

		setModel(model)
		{
			this.model = model;
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
				this.state.visible && (new UI.BottomToolbar({isWithSafeArea: false, items: this.prepareItems()}))
			);
		}

		prepareItems()
		{
			const filteredItems = this.props.actions.filter(action => action.onActiveCallback(this.props.item));
			const edgeMargin = filteredItems.length === 1 ? 48 : 25;

			return (
				filteredItems
					.map((action, index) => {
						const buttonStyles = this.getButtonStyles(action.type);

						return View(
							{
								style: buttonStyles.wrapper
							},
							View(
								{
									style: {
										...buttonStyles.container,
										marginLeft: index > 0 ? 16 : edgeMargin,
										marginRight: index === (filteredItems.length - 1) ? edgeMargin : 0
									},
									onClick: () => {
										this.props.onActionStart(action);

										action
											.onClickCallback(this.model)
											.then((data) => this.props.onActionSuccess(action, data))
											.catch((data) => this.props.onActionFailure(action, data))
										;
									}
								},
								Text({
									style: buttonStyles.text,
									text: action.title
								})
							)
						)
					})
			);
		}

		getButtonStyles(type = Types.SECONDARY)
		{
			const buttonStyles = styles[type] || styles[Types.SECONDARY];

			return {
				...styles.base,
				container: {
					...styles.base.container,
					...buttonStyles.container
				},
				text: {
					...styles.base.text,
					...buttonStyles.text
				}
			};
		}
	}

	this.ActionsPanel = ActionsPanel;
	this.ActionsPanel.ButtonType = Types;
})();
