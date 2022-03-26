(() =>
{
	const TYPE_BUTTON = 'button';
	const TYPE_CANCEL = 'cancel';

	const ImageAfterTypes = {
		WEB: 'web',
	}

	const svgIcons = {
		[ImageAfterTypes.WEB]: {
			content: `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.93726 0.9375H6.00552V3.18574H4.1855C3.63321 3.18574 3.1855 3.63346 3.1855 4.18574V11.8122C3.1855 12.3645 3.63321 12.8122 4.1855 12.8122H11.812C12.3643 12.8122 12.812 12.3645 12.812 11.8122V10.5903H15.0624V12.0627C15.0624 13.7195 13.7193 15.0627 12.0624 15.0627H3.93725C2.2804 15.0627 0.937256 13.7195 0.937256 12.0627V3.9375C0.937256 2.28064 2.2804 0.9375 3.93726 0.9375Z" fill="#767C87"/><path d="M8.98799 1.66387C8.799 1.47488 8.93285 1.15174 9.20012 1.15174H13.8782C14.4305 1.15174 14.8782 1.59945 14.8782 2.15174V6.82982C14.8782 7.09709 14.5551 7.23094 14.3661 7.04195L12.3898 5.06566L7.89355 9.56189C7.69829 9.75715 7.38171 9.75715 7.18644 9.56189L6.34414 8.71959C6.14888 8.52433 6.14888 8.20775 6.34414 8.01248L10.8404 3.51625L8.98799 1.66387Z" fill="#767C87"/></svg>`
		}
	}

	/**
	 * @class ContextMenuItem
	 */
	class ContextMenuItem extends LayoutComponent
	{
		static create(props)
		{
			return new this(props);
		}

		constructor(props)
		{
			super(props);

			this.id = props.id;
			this.parentId = props.parentId;
			this.parent = props.parent;
			this.title = props.title;
			this.subtitle = props.subtitle;
			this.isSelected = (props.isSelected || false);
			this.isDisabled = (props.isDisabled || false);
			this.sectionCode = (props.sectionCode || ContextMenuSection.getDefaultSectionName());
			this.type = (props.type || TYPE_BUTTON);
			this.data = (props.data || {});

			this.updateItemHandler = props.updateItemHandler;
			this.closeMenuHandler = props.closeHandler;
			this.changeAvailabilityHandler = (props.changeAvailabilityHandler || null);

			this.onClickCallback = props.onClickCallback;
			this.onActiveCallback = (props.onActiveCallback || null);
			this.getParentWidget = (props.getParentWidget || null);

			this.showIcon = props.showIcon !== undefined ? Boolean(props.showIcon) : Boolean(this.data.svgIcon);
			this.largeIcon = props.largeIcon !== undefined ? Boolean(props.largeIcon) : true;
			this.firstInSection = props.firstInSection !== undefined ? Boolean(props.firstInSection) : false;
			this.lastInSection = props.lastInSection !== undefined ? Boolean(props.lastInSection) : false;

			this.state.isProcessing = false;

			this.isActive = (
				!this.onActiveCallback
				|| (
					this.onActiveCallback
					&& this.onActiveCallback(this.id, this.parentId, this.parent)
				)
			);
		}

		isActiveItem()
		{
			return this.isActive;
		}

		render()
		{
			if (!this.isActive)
			{
				return null;
			}

			return View(
				{
					style: styles.view(this.isDisabled, this.firstInSection, this.lastInSection),
					onClick: () => {
						if (!this.props.enabled  || this.isDisabled)
						{
							return;
						}

						if (this.changeAvailabilityHandler)
						{
							this.changeAvailabilityHandler(false);
						}

						this.setState(
							{
								isProcessing: true,
							},
							() => {
								this.onClickCallback(
									this.id,
									this.parentId,
									{
										parentWidget: this.getParentWidget ? this.getParentWidget() : null
									}
								).then(
									result => {

										if (this.changeAvailabilityHandler)
										{
											this.changeAvailabilityHandler(true);
										}

										this.setState({
											isProcessing: false,
										}, () => {
											this.closeMenuHandler();
										});

										if (this.updateItemHandler)
										{
											this.updateItemHandler(result.action, result.id, result.params);
										}
									},
									({errors, showErrors, callback}) => {
										if (this.changeAvailabilityHandler)
										{
											this.changeAvailabilityHandler(true);
										}
										this.setState({
											isProcessing: false,
										}, () => {
											if (showErrors)
											{
												this.showErrors(errors, () => {
													this.closeMenuHandler();
												});
											}
											else
											{
												this.closeMenuHandler(callback);
											}
										});
									}
								);
							}
						);
					}
				},
				View(
					{
						style: styles.selectedView(this.isSelected),
					},
					...this.renderByType()
				)
			);
		}

		showError(errorText)
		{
			if (errorText.length)
			{
				navigator.notification.alert(errorText, null, '');
			}
		}

		showErrors(errors, callback = null)
		{
			navigator.notification.alert(
				errors.map(error => error.message).join('\n'),
				callback,
				''
			);
		}

		renderByType()
		{
			let imageContainer = null;
			let imageAfterContainer = null;
			let title = null;
			let subtitle = null;

			if (this.type === TYPE_BUTTON)
			{
				title = Text({
					style: styles.title(this.isActive),
					text: this.title,
					numberOfLines: 1,
					ellipsize: 'end',
				});
				if (this.subtitle)
				{
					subtitle = Text({
						style: styles.subtitle,
						text: this.subtitle,
						numberOfLines: 1,
						ellipsize: 'end',
					});
				}
			}
			else if (this.type === TYPE_CANCEL)
			{
				title = Text({
					style: {
						...styles.title(this.isActive),
						...styles.cancel
					},
					text: this.title || BX.message('CONTEXT_MENU_CANCEL'),
					numberOfLines: 1,
					ellipsize: 'end',
				});
			}

			if (this.showIcon && !this.isProcessing())
			{
				imageContainer = View(
					{
						style: styles.imageContainerOuter
					},
					View(
						{
							style: styles.imageContainerInner
						},
						Image({
							style: styles.icon(this.largeIcon),
							resizeMode: 'center',
							svg: {
								content: this.data.svgIcon || null
							}
						})
					)
				);
			}
			else if (this.isProcessing())
			{
				imageContainer = View(
					{
						style: styles.imageContainerOuter
					},
					Loader({
						style: {
							width: 25,
							height: 25,
							marginLeft: 2
						},
						tintColor: '#000000',
						animating: true,
						size: 'small'
					})
				);
			}

			if (this.props.data && this.props.data.svgIconAfter)
			{
				const svgIconAfterProp = this.props.data.svgIconAfter;
				const svgIconAfter = svgIconAfterProp.type && svgIcons.hasOwnProperty(svgIconAfterProp.type)
					? svgIcons[svgIconAfterProp.type]
					: svgIconAfterProp;

				if (svgIconAfter)
				{
					imageAfterContainer = View(
						{
							style: styles.imageAfterContainerOuter
						},
						View(
							{
								style: styles.imageAfterContainerInner
							},
							Image({
								style: {
									width: 15,
									height: 15,
								},
								resizeMode: 'center',
								svg: svgIconAfter
							})
						)
					);
				}
			}

			return [
				imageContainer,
				View(
					{
						style: styles.button(!this.lastInSection)
					},
					View(
						{
							style: {
								flexDirection: 'row',
							}
						},
						View(
							{
								style: {
									flexDirection: 'column',
									justifyContent: 'center'
								}
							},
							title,
							subtitle
						),
						imageAfterContainer
					)
				),
			];
		}

		isProcessing()
		{
			return this.state.isProcessing;
		}

		static getTypeButtonName()
		{
			return TYPE_BUTTON;
		}

		static getTypeCancelName()
		{
			return TYPE_CANCEL;
		}
	}

	const styles = {
		view: (isDisabled, showTopPadding, showBottomPadding) => {
			return {
				backgroundColor: '#ffffff',
				paddingHorizontal: 4,
				paddingTop: (showTopPadding && !showBottomPadding ? 4 : 0),
				paddingBottom: (showBottomPadding && !showTopPadding ? 4 : 0),
				opacity: (isDisabled ? 0.4 : 1),
			};
		},
		selectedView: (isSelected) => {
			return {
				flexDirection: 'row',
				alignItems: 'center',
				paddingLeft: 15,
				backgroundColor: (isSelected ? '#d7f4fd' : '#ffffff'),
				borderRadius: 10,
			};
		},
		button: (showBorderBottom) => {
			return {
				borderBottomColor: (showBorderBottom ? '#ebebeb': '#00ffffff'),
				borderBottomWidth: 1,
				borderTopColor: '#00ffffff',
				borderTopWidth: 1,
				paddingTop: 15,
				paddingBottom: 15,
				paddingRight: 10,
				flex: 1,
				height: 60,
				justifyContent: 'center'
			}
		},
		title: (isActive) => {
			return {
				fontSize: 18,
				color: (isActive ? '#000000' : '#d5dce2')
			};
		},
		subtitle: {
			fontSize: 14,
			color: '#b8bfc9'
		},
		cancel: {
			color: '#6c6d6d'
		},
		imageContainerOuter: {
			width: 30,
			height: 30,
			marginRight: 15,
			justifyContent: 'center',
			flexDirection: 'column',
			alignContent: 'center',
		},
		imageContainerInner: {
			flexDirection: 'row',
			justifyContent: 'center',
		},
		imageAfterContainerOuter: {
			width: 30,
			height: 30,
			marginRight: 15,
			justifyContent: 'center',
			flexDirection: 'column',
			alignContent: 'center',
		},
		imageAfterContainerInner: {
			flexDirection: 'row',
			justifyContent: 'center',
		},
		icon: (largeIcon) => {
			return {
				width: largeIcon ? 30 : 16,
				height: largeIcon ? 30 : 16,
			}
		}
	};

	this.ContextMenuItem = ContextMenuItem;
	this.ContextMenuItem.ImageAfterTypes = ImageAfterTypes;
})();
