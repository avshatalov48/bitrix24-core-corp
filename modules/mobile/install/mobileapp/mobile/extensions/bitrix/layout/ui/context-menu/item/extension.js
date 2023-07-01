(() => {
	const require = (ext) => jn.require(ext);

	const { AnalyticsLabel } = require('analytics-label');
	const { CounterView } = require('layout/ui/counter-view');
	const { chevronRight } = require('assets/common');
	const { Badge } = require('layout/ui/context-menu/item/badge');
	const { get, mergeImmutable } = require('utils/object');
	const { changeFillColor } = require('utils/svg');
	const { Type } = require('type');

	const TINT_COLOR = '#6a737f';

	const TYPE_BUTTON = 'button';
	const TYPE_LAYOUT = 'layout';
	const TYPE_CANCEL = 'cancel';

	const WARNING_TYPE = 'warning';

	const ImageAfterTypes = {
		WEB: 'web',
		LOCK: 'lock',
	};

	const svgIcons = {
		[ImageAfterTypes.WEB]: {
			content: `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.93726 0.9375H6.00552V3.18574H4.1855C3.63321 3.18574 3.1855 3.63346 3.1855 4.18574V11.8122C3.1855 12.3645 3.63321 12.8122 4.1855 12.8122H11.812C12.3643 12.8122 12.812 12.3645 12.812 11.8122V10.5903H15.0624V12.0627C15.0624 13.7195 13.7193 15.0627 12.0624 15.0627H3.93725C2.2804 15.0627 0.937256 13.7195 0.937256 12.0627V3.9375C0.937256 2.28064 2.2804 0.9375 3.93726 0.9375Z" fill="#bdc1c6"/><path d="M8.98799 1.66387C8.799 1.47488 8.93285 1.15174 9.20012 1.15174H13.8782C14.4305 1.15174 14.8782 1.59945 14.8782 2.15174V6.82982C14.8782 7.09709 14.5551 7.23094 14.3661 7.04195L12.3898 5.06566L7.89355 9.56189C7.69829 9.75715 7.38171 9.75715 7.18644 9.56189L6.34414 8.71959C6.14888 8.52433 6.14888 8.20775 6.34414 8.01248L10.8404 3.51625L8.98799 1.66387Z" fill="#bdc1c6"/></svg>`,
		},
		[ImageAfterTypes.LOCK]: {
			content: `<svg width="28" height="29" viewBox="0 0 28 29" fill="none" xmlns="http://www.w3.org/2000/svg"><g opacity="0.8"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.17 18.4443V20.3019H12.6987V18.4443C12.4339 18.2285 12.2644 17.8989 12.2644 17.5293C12.2644 16.8792 12.7882 16.3522 13.4344 16.3522C14.0804 16.3522 14.6043 16.8792 14.6043 17.5293C14.6043 17.8989 14.4348 18.2285 14.17 18.4443ZM10.2313 10.8976C10.2313 9.11768 11.6653 7.67481 13.4343 7.67481C15.2033 7.67481 16.6374 9.11768 16.6374 10.8976V13.6219H10.2313V10.8976ZM18.3301 13.6219V10.8976C18.3301 8.17704 16.1382 5.97168 13.4343 5.97168C10.7305 5.97168 8.53854 8.17704 8.53854 10.8976V13.6219H7.05225V22.9508H19.8164V13.6219H18.3301Z" fill="#2FC6F6"/></g></svg>`,
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

			this.state = {
				isProcessing: false,
			};

			this.handleSelectItem = this.handleSelectItem.bind(this);
		}

		get id()
		{
			return this.props.id;
		}

		get parentId()
		{
			return this.props.parentId;
		}

		get parent()
		{
			return this.props.parent;
		}

		get title()
		{
			return this.props.title;
		}

		get subtitle()
		{
			return this.props.subtitle;
		}

		get subtitleType()
		{
			return this.props.subtitleType;
		}

		get label()
		{
			return this.props.label;
		}

		get showArrow()
		{
			return this.props.showArrow;
		}

		get showActionLoader()
		{
			return BX.prop.getBoolean(this.props, 'showActionLoader', false);
		}

		get isCustomIconColor()
		{
			if (this.type === TYPE_CANCEL)
			{
				return true;
			}

			return BX.prop.getBoolean(this.props, 'isCustomIconColor', false);
		}

		get isSelected()
		{
			return BX.prop.getBoolean(this.props, 'isSelected', false);
		}

		get showSelectedImage()
		{
			return BX.prop.getBoolean(this.props, 'showSelectedImage', false);
		}

		get isDisabled()
		{
			return BX.prop.getBoolean(this.props, 'isDisabled', false);
		}

		get sectionCode()
		{
			return this.props.sectionCode || ContextMenuSection.getDefaultSectionName();
		}

		get type()
		{
			return this.props.type || TYPE_BUTTON;
		}

		get data()
		{
			return this.props.data || {};
		}

		get updateItemHandler()
		{
			return this.props.updateItemHandler;
		}

		get closeMenuHandler()
		{
			return this.props.closeHandler;
		}

		get onClickCallback()
		{
			return this.props.onClickCallback;
		}

		get onActiveCallback()
		{
			return this.props.onActiveCallback;
		}

		get onDisableClick()
		{
			return this.props.onDisableClick;
		}

		get getParentWidget()
		{
			return this.props.getParentWidget;
		}

		get showIcon()
		{
			return BX.prop.getBoolean(this.props, 'showIcon', Boolean(this.data.svgIcon));
		}

		get largeIcon()
		{
			return BX.prop.getBoolean(this.props, 'largeIcon', true);
		}

		get firstInSection()
		{
			return BX.prop.getBoolean(this.props, 'firstInSection', false);
		}

		get lastInSection()
		{
			return BX.prop.getBoolean(this.props, 'lastInSection', false);
		}

		get isActive()
		{
			return BX.prop.getBoolean(this.props, 'isActive', false);
		}

		get dimmed()
		{
			if (this.sectionCode === ContextMenuSection.getServiceSectionName())
			{
				return true;
			}

			return BX.prop.getBoolean(this.props, 'dimmed', false);
		}

		get isSemitransparent()
		{
			return BX.prop.getBoolean(this.props, 'isSemitransparent', false);
		}

		get analyticsLabel()
		{
			return BX.prop.getObject(this.props, 'analyticsLabel', null);
		}

		render()
		{
			if (!this.isActive)
			{
				return null;
			}

			const { id, testId } = this.props;

			const containerStyle = get(this.props, 'data.style.container', {});
			const itemStyle = get(this.props, 'data.style.item', {});

			return View(
				{
					style: mergeImmutable(styles.wrapper(this.dimmed), containerStyle),
					testId: `${testId || ""}_${id}`,
					onClick: this.handleSelectItem,
				},
				...this.renderByType(itemStyle),
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

				if (Type.isPlainObject(this.analyticsLabel))
				{
					AnalyticsLabel.send({
						event: 'context-menu-click',
						id: this.id,
						...this.analyticsLabel,
					});
				}

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

		renderByType(customStyle = {})
		{
			let imageContainer;
			let imageAfterContainer;
			let labelContainer;
			let title;
			let subtitle;
			let selectedImage;
			let arrowImage;
			let badgeContainer;

			if (this.type === TYPE_CANCEL)
			{
				title = Text({
					style: {
						...styles.title(this.isActive),
						...styles.cancel,
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
					style: styles.title(!this.isSemitransparent),
					text: this.title,
					numberOfLines: 1,
					ellipsize: 'end',
				});
				if (this.subtitle)
				{
					const subtitleStyle = get(customStyle, 'subtitle', {});

					subtitle = Text({
						style: styles.subtitle(this.subtitleType, subtitleStyle),
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
						style: styles.imageContainerOuter(this.isSelected, this.dimmed, this.isTypeLayout()),
					},
					View(
						{
							style: styles.imageContainerInner(this.isDisabled),
						},
						Loader({
							style: {
								width: 25,
								height: 25,
							},
							tintColor: '#000000',
							animating: true,
							size: 'small',
						}),
					),
				);
			}
			else if (this.showIcon)
			{
				const { imgUri } = this.data;
				let { svgIcon } = this.data;
				let tintColor;

				if (!this.isCustomIconColor)
				{
					tintColor = TINT_COLOR;

					// Android can't change color of inline svg icons, only uri
					if (svgIcon && Application.getPlatform() === 'android')
					{
						svgIcon = changeFillColor(svgIcon, TINT_COLOR);
					}
				}

				imageContainer = View(
					{
						style: styles.imageContainerOuter(this.isSelected, this.dimmed, this.isTypeLayout()),
					},
					View(
						{
							style: styles.imageContainerInner(this.isDisabled),
						},
						Image({
							uri: imgUri,
							style: styles.icon(this.largeIcon),
							resizeMode: imgUri ? 'contain' : 'center',
							tintColor,
							svg: { content: svgIcon },
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
							style: styles.imageAfterContainerOuter,
						},
						View(
							{
								style: styles.imageAfterContainerInner,
							},
							Image({
								style: {
									width: 18,
									height: 18,
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
							marginRight: 15,
							opacity: this.isDisabled ? 0.4 : 1,
						},
						svg: {
							content: `<svg width="20" height="15" viewBox="0 0 20 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.34211 14.351L0.865234 8.03873L3.13214 5.82945L7.34211 9.9324L16.8677 0.648926L19.1346 2.85821L7.34211 14.351Z" fill="#828B95"/></svg>`,
						},
					},
				);
			}

			if (this.showArrow)
			{
				arrowImage = Image(
					{
						style: {
							width: 24,
							height: 24,
							marginRight: 6,
						},
						svg: {
							content: chevronRight(),
						},
					},
				);
			}

			if (Array.isArray(this.props.badges) && this.props.badges.length)
			{
				const items = [];
				this.props.badges.forEach((params) => items.push(new Badge(params)));

				badgeContainer = View({
					style: {
						flexDirection: 'row',
					},
				}, ...items);
			}

			return [
				imageContainer,
				View(
					{
						style: styles.divider(this.hasAnyLeftIcon(), this.isSelected, this.dimmed, this.lastInSection, this.isTypeLayout()),
					},
				),
				View(
					{
						style: styles.container(this.lastInSection),
					},
					View(
						{
							style: styles.selectedView(this.isSelected, this.dimmed),
						},
						View(
							{
								style: styles.button(!this.lastInSection, this.isTypeLayout()),
							},
							View(
								{
									style: {
										flexDirection: 'row',
										alignItems: 'center',
										opacity: this.isDisabled ? 0.4 : 1,
										flex: 1,
										justifyContent: 'space-between',
										flexShrink: 2,
									},
								},
								View(
									{
										style: {
											flexDirection: 'row',
											flexShrink: 2,
											justifyContent: 'center',
											alignItems: 'center',
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
									badgeContainer,
									imageAfterContainer,
								),
								labelContainer,
							),
						),
						selectedImage,
						arrowImage,
					),
				),
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

		hasAnyLeftIcon()
		{
			return (this.showActionLoader && this.isProcessing() || this.showIcon);
		}
	}

	const styles = {
		nonSelectedColor: (isService) => isService ? '#fbfbfc' : '#ffffff',
		wrapper: (isService) => {
			return {
				flexDirection: 'row',
				alignItems: 'center',
				backgroundColor: styles.nonSelectedColor(isService),
			};
		},
		container: (isLast) => {
			return {
				justifyContent: 'center',
				alignItems: 'center',
				borderBottomColor: (!isLast ? '#edeef0' : '#00ffffff'),
				borderBottomWidth: 1,
				padding: 4,
				paddingBottom: 3,
				paddingLeft: 0,
				flex: 1,
				flexDirection: 'row',
			};
		},
		divider: (hasAnyLeftIcon, isSelected, isService, isLastInSection, autoHeight = false) => {
			const dividerStyles = {
				backgroundColor: (isSelected && !isService ? '#d3f4ff' : null),
				borderBottomLeftRadius: hasAnyLeftIcon ? 0 : 8,
				borderTopLeftRadius: hasAnyLeftIcon ? 0 : 8,
				width: hasAnyLeftIcon ? 0 : 11,
				marginLeft: hasAnyLeftIcon ? 0 : 4,
				borderBottomColor: (!isLastInSection ? '#edeef0' : '#00ffffff'),
			};

			if (!autoHeight)
			{
				dividerStyles.height = 50;
			}

			return dividerStyles;

		},
		selectedView: (isSelected, isService) => {
			return {
				flex: 1,
				flexDirection: 'row',
				justifyContent: 'center',
				alignItems: 'center',
				backgroundColor: (isSelected && !isService ? '#d3f4ff' : null),
				borderTopRightRadius: 8,
				borderBottomRightRadius: 8,
			};
		},
		button: (showBorderBottom, autoHeight = false) => {
			const styles = {
				flex: 1,
				justifyContent: 'center',
			};
			if (!autoHeight)
			{
				styles.height = 50;
			}

			return styles;
		},
		title: (isSemitransparent) => ({
			fontSize: 18,
			color: isSemitransparent ? '#333333' : '#6a737f',
		}),
		subtitle: (type, subtitleStyle = {}) => {
			let color = '#bdc1c6';

			if (type === WARNING_TYPE)
			{
				color = '#c48300';
			}

			return mergeImmutable({
				fontSize: 14,
				color,
			}, subtitleStyle);
		},
		cancel: {
			color: '#959ca4',
		},
		imageContainerOuter: (isSelected, isService, autoHeight = false) => {
			const imageStyles = {
				justifyContent: 'center',
				alignContent: 'center',

				backgroundColor: (isSelected && !isService ? '#d3f4ff' : null),

				marginTop: 4,
				marginLeft: 4,
				marginBottom: 4,
				paddingLeft: 11,
				paddingRight: 15,
				borderTopLeftRadius: 8,
				borderBottomLeftRadius: 8,
			};

			if (!autoHeight)
			{
				imageStyles.height = 50;
			}

			return imageStyles;
		},
		imageContainerInner: (isDisabled) => {
			return {
				width: 30,
				height: 30,
				opacity: (isDisabled ? 0.4 : 1),
				justifyContent: 'center',
				alignItems: 'center',
			};
		},
		imageAfterContainerOuter: {
			width: 30,
			height: 30,
			justifyContent: 'center',
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
			marginHorizontal: 6,
		},
	};

	this.ContextMenuItem = ContextMenuItem;
	this.ContextMenuItem.ImageAfterTypes = ImageAfterTypes;
})();
