(() => {

	const { merge } = jn.require('utils/object');
	const { CounterView } = jn.require('layout/ui/counter-view');

	const TYPE_BUTTON = 'button';
	const TYPE_LAYOUT = 'layout';
	const TYPE_CANCEL = 'cancel';

	const ImageAfterTypes = {
		WEB: 'web',
	};

	const svgIcons = {
		[ImageAfterTypes.WEB]: {
			content: `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.93726 0.9375H6.00552V3.18574H4.1855C3.63321 3.18574 3.1855 3.63346 3.1855 4.18574V11.8122C3.1855 12.3645 3.63321 12.8122 4.1855 12.8122H11.812C12.3643 12.8122 12.812 12.3645 12.812 11.8122V10.5903H15.0624V12.0627C15.0624 13.7195 13.7193 15.0627 12.0624 15.0627H3.93725C2.2804 15.0627 0.937256 13.7195 0.937256 12.0627V3.9375C0.937256 2.28064 2.2804 0.9375 3.93726 0.9375Z" fill="#B8BFC9"/><path d="M8.98799 1.66387C8.799 1.47488 8.93285 1.15174 9.20012 1.15174H13.8782C14.4305 1.15174 14.8782 1.59945 14.8782 2.15174V6.82982C14.8782 7.09709 14.5551 7.23094 14.3661 7.04195L12.3898 5.06566L7.89355 9.56189C7.69829 9.75715 7.38171 9.75715 7.18644 9.56189L6.34414 8.71959C6.14888 8.52433 6.14888 8.20775 6.34414 8.01248L10.8404 3.51625L8.98799 1.66387Z" fill="#B8BFC9"/></svg>`,
		},
	};

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
			this.label = props.label || null;
			this.showActionLoader = BX.prop.getBoolean(props, 'showActionLoader', false);
			this.isSelected = (props.isSelected || false);
			this.showSelectedImage = (props.showSelectedImage || false);
			this.isDisabled = (props.isDisabled || false);
			this.sectionCode = (props.sectionCode || ContextMenuSection.getDefaultSectionName());
			this.type = (props.type || TYPE_BUTTON);
			this.data = (props.data || {});

			// @todo check this. May need to be deleted as there are currently no examples of use
			this.styles = (props.itemStyles || {});

			this.updateItemHandler = props.updateItemHandler;
			this.closeMenuHandler = props.closeHandler;

			this.onClickCallback = props.onClickCallback;
			this.onActiveCallback = (props.onActiveCallback || null);
			this.onDisableClick = props.onDisableClick;
			this.getParentWidget = (props.getParentWidget || null);

			this.showIcon = props.showIcon !== undefined ? Boolean(props.showIcon) : Boolean(this.data.svgIcon);
			this.largeIcon = props.largeIcon !== undefined ? Boolean(props.largeIcon) : true;
			this.firstInSection = props.firstInSection !== undefined ? Boolean(props.firstInSection) : false;
			this.lastInSection = props.lastInSection !== undefined ? Boolean(props.lastInSection) : false;

			this.state.isProcessing = false;

			this.isActive = props.isActive;

			this.handleSelectItem = this.handleSelectItem.bind(this);
		}

		render()
		{
			if (!this.isActive)
			{
				return null;
			}

			const { id, testId } = this.props;
			const isService = this.sectionCode === ContextMenuSection.getServiceSectionName();

			return View(
				{
					testId: `${testId || ""}_${id}`,
					style: styles.view(this.firstInSection, this.lastInSection, isService),
					onClick: this.handleSelectItem,
				},
				View(
					{
						style: {
							...styles.selectedView(this.isSelected, isService),
							...(this.styles.selectedView || {}),
						},
					},
					...this.renderByType(),
				),
			);
		}

		handleSelectItem()
		{
			if (this.state.isProcessing || this.isTypeLayout())
			{
				return;
			}

			if (this.isDisabled)
			{
				if (this.onDisableClick)
				{
					this.onDisableClick(
						this.id,
						this.parentId,
						{
							parentWidget: this.getParentWidget ? this.getParentWidget() : null,
							parent: this.parent || null,
						},
					);
				}

				return;
			}

			this.setState({ isProcessing: true }, () => {
				let promise = this.onClickCallback(
					this.id,
					this.parentId,
					{
						parentWidget: this.getParentWidget ? this.getParentWidget() : null,
						parent: this.parent || null,
					},
				);

				if (!(promise instanceof Promise))
				{
					promise = Promise.resolve();
				}

				promise
					.then(
						({ action, id, params, closeMenu = true, closeCallback } = {}) => {
							if (closeMenu)
							{
								this.closeMenuHandler(closeCallback);
							}

							this.setState({ isProcessing: false }, () => {
								if (action && this.updateItemHandler)
								{
									this.updateItemHandler(action, id, params);
								}
							});
						},
						({ errors } = {}) => {
							this.setState({ isProcessing: false }, () => {
								if (errors && errors.length)
								{
									this.showErrors(errors);
								}
							});
						},
					)
				;
			});
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
				'',
			);
		}

		renderByType()
		{
			let imageContainer = null;
			let imageAfterContainer = null;
			let labelContainer = null;
			let title = null;
			let subtitle = null;
			let selectedImage = null;

			const renderStyles = merge(
				{},
				styles,
				this.styles,
			);

			const hasLabel = Boolean(this.label);

			if (this.type === TYPE_CANCEL)
			{
				title = Text({
					style: {
						...renderStyles.title(this.isActive, hasLabel),
						...renderStyles.cancel,
					},
					text: this.title || BX.message('CONTEXT_MENU_CANCEL'),
					numberOfLines: 1,
					ellipsize: 'end',
				});
			}
			else if (this.isTypeLayout())
			{
				title = this.title;
			}
			else
			{
				title = Text({
					style: renderStyles.title(this.isActive, hasLabel),
					text: this.title,
					numberOfLines: 1,
					ellipsize: 'end',
				});
				if (this.subtitle)
				{
					subtitle = Text({
						style: renderStyles.subtitle(hasLabel),
						text: this.subtitle,
						numberOfLines: 1,
						ellipsize: 'end',
					});
				}
			}

			if (this.showActionLoader && this.isProcessing())
			{
				imageContainer = View(
					{
						style: renderStyles.imageContainerOuter(this.isDisabled),
					},
					Loader({
						style: {
							width: 25,
							height: 25,
							marginLeft: 2,
						},
						tintColor: '#000000',
						animating: true,
						size: 'small',
					}),
				);
			}
			else if (this.showIcon)
			{
				imageContainer = View(
					{
						style: renderStyles.imageContainerOuter(this.isDisabled),
					},
					View(
						{
							style: renderStyles.imageContainerInner,
						},
						Image({
							uri: this.data.imgUri || null,
							style: renderStyles.icon(this.largeIcon),
							resizeMode: this.data.imgUri ? 'contain' : 'center',
							svg: {
								content: this.data.svgIcon || null,
							},
						}),
					),
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
							style: renderStyles.imageAfterContainerOuter,
						},
						View(
							{
								style: renderStyles.imageAfterContainerInner,
							},
							Image({
								style: {
									width: 15,
									height: 15,
								},
								resizeMode: 'center',
								svg: svgIconAfter,
							}),
						),
					);
				}
			}

			if (this.props.label)
			{
				labelContainer = View(
					{
						style: styles.labelContainer,
					},
					CounterView(this.props.label),
				);
			}

			if (this.isSelected && this.props.showSelectedImage)
			{
				selectedImage = Image(
					{
						style: {
							width: 20,
							height: 15,
							marginRight: 23,
							opacity: this.isDisabled ? 0.4 : 1,
						},
						svg: {
							content: `<svg width="20" height="15" viewBox="0 0 20 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.34211 14.351L0.865234 8.03873L3.13214 5.82945L7.34211 9.9324L16.8677 0.648926L19.1346 2.85821L7.34211 14.351Z" fill="#828B95"/></svg>`,
						},
					},
				);
			}

			return [
				imageContainer,
				View(
					{
						style: renderStyles.button(!this.lastInSection, this.isTypeLayout()),
					},
					View(
						{
							style: {
								flexDirection: 'row',
								opacity: this.isDisabled ? 0.4 : 1,
							},
						},
						View(
							{
								style: {
									flexDirection: 'column',
									justifyContent: 'center',
									flexShrink: 2,
								},
							},
							title,
							subtitle,
						),
						imageAfterContainer,
						labelContainer,
					),
				),
				selectedImage,
			];
		}

		isProcessing()
		{
			return this.state.isProcessing;
		}

		/**
		 * @returns {boolean}
		 */
		isTypeLayout()
		{
			return (this.type === TYPE_LAYOUT);
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
		nonSelectedColor: (isService) => isService ? '#fbfbfc' : '#ffffff',
		view: (showTopPadding, showBottomPadding, isService) => {
			return {
				backgroundColor: styles.nonSelectedColor(isService),
				paddingHorizontal: 4,
				paddingTop: (showTopPadding && !showBottomPadding ? 4 : 0),
				paddingBottom: (showBottomPadding && !showTopPadding ? 4 : 0),
			};
		},
		selectedView: (isSelected, isService) => {
			return {
				flexDirection: 'row',
				alignItems: 'center',
				paddingLeft: 15,
				backgroundColor: (isSelected ? '#d7f4fd' : styles.nonSelectedColor(isService)),
				borderRadius: 10,
			};
		},
		button: (showBorderBottom, autoHeight = false) => {
			const styles = {
				borderBottomColor: (showBorderBottom ? '#ebebeb' : '#00ffffff'),
				borderBottomWidth: 1,
				borderTopColor: '#00ffffff',
				borderTopWidth: 1,
				paddingTop: 15,
				paddingBottom: 15,
				paddingRight: 10,
				flex: 1,
				justifyContent: 'center',
			};
			if (!autoHeight)
			{
				styles.height = 60;
			}

			return styles;
		},
		title: (isActive, hasLabel) => {
			return {
				fontSize: 18,
				color: (isActive ? '#333333' : '#d5dce2'),
				marginRight: (hasLabel ? 30 : 0),
			};
		},
		subtitle: (hasLabel) => {
			return {
				fontSize: 14,
				color: '#b8bfc9',
				marginRight: (hasLabel ? 30 : 0),
			};
		},
		cancel: {
			color: '#959ca4',
		},
		imageContainerOuter: (isDisabled) => {
			return {
				width: 30,
				height: 30,
				marginRight: 15,
				justifyContent: 'center',
				flexDirection: 'column',
				alignContent: 'center',
				opacity: (isDisabled ? 0.4 : 1),
			};
		},
		imageContainerInner: {
			flexDirection: 'row',
			justifyContent: 'center',
		},
		imageAfterContainerOuter: {
			width: 30,
			height: 30,
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
			};
		},
		labelContainer: {
			top: 1,
			right: 14,
			position: 'absolute',
		},
	};

	this.ContextMenuItem = ContextMenuItem;
	this.ContextMenuItem.ImageAfterTypes = ImageAfterTypes;
})();
