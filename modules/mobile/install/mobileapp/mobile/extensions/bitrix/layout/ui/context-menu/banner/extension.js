/**
 * @module layout/ui/context-menu/banner
 */
jn.define('layout/ui/context-menu/banner', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { qrauth } = require('qrauth/utils');
	const AppTheme = require('apptheme');

	const BannerPositioning = {
		Horizontal: 'horizontal',
		Vertical: 'vertical',
	};

	const ButtonType = {
		Transparent: 'transparent',
		ActiveGreen: 'activeGreen',
		Reject: 'reject',
	};

	/**
	 * @typedef ContextMenuBannerProps
	 * @property {?Object} banner
	 * @property {string[]} banner.featureItems
	 * @property {string} banner.imagePath
	 * @property {string} banner.imageSvg
	 * @property {?string} banner.positioning
	 * @property {?string} banner.title
	 * @property {?boolean} banner.showSubtitle
	 * @property {?string} banner.buttonText
	 * @property {?string} banner.rejectButtonText
	 * @property {?string} banner.subtextAlign
	 * @property {?string} banner.buttonType
	 * @property {?object} banner.onButtonClick
	 * @property {?object} banner.onRejectButtonClick
	 * @property {?object} banner.onCloseBanner
	 * @property {?object} banner.qrAuth
	 * @property {string} banner.qrAuth.redirectUrl
	 * @property {boolean} banner.showRejectButton
	 * @property {boolean} banner.centerVertically
	 *
	 * @class ContextMenuBanner
	 * @param {...ContextMenuBannerProps} props
	 */
	class ContextMenuBanner extends LayoutComponent
	{
		get parentWidget()
		{
			const { parentWidget } = this.props;

			return parentWidget || PageManager;
		}

		get featureItems()
		{
			return BX.prop.getArray(this.props.banner, 'featureItems', []);
		}

		get imagePath()
		{
			return BX.prop.getString(this.props.banner, 'imagePath', '');
		}

		get imageSvg()
		{
			return BX.prop.getString(this.props.banner, 'imageSvg', '');
		}

		get qrauthParameters()
		{
			return BX.prop.getObject(this.props.banner, 'qrauth', {});
		}

		get buttonText()
		{
			return BX.prop.getString(this.props.banner, 'buttonText', Loc.getMessage('CONTEXT_MENU_BANNER_BUTTON'));
		}

		get rejectButtonText()
		{
			return BX.prop.getString(
				this.props.banner,
				'rejectButtonText',
				Loc.getMessage('CONTEXT_MENU_REJECT_BANNER_BUTTON'),
			);
		}

		get positioning()
		{
			return BX.prop.getString(this.props.banner, 'positioning', BannerPositioning.Horizontal);
		}

		get title()
		{
			return BX.prop.getString(this.props.banner, 'title', '');
		}

		get subtext()
		{
			return BX.prop.getString(this.props.banner, 'subtext', '');
		}

		get subtextAlign()
		{
			return BX.prop.getString(this.props.banner, 'subtextAlign', 'left');
		}

		get onButtonClick()
		{
			return BX.prop.getFunction(this.props.banner, 'onButtonClick', null);
		}

		get onRejectButtonClick()
		{
			return BX.prop.getFunction(this.props.banner, 'onRejectButtonClick', null);
		}

		get showRejectButton()
		{
			return BX.prop.getBoolean(this.props.banner, 'showRejectButton', false);
		}

		get onCloseBanner()
		{
			return BX.prop.getFunction(this.props.banner, 'onCloseBanner', null);
		}

		isHorizontalPositioning()
		{
			return this.positioning === BannerPositioning.Horizontal;
		}

		hasTitle()
		{
			return Type.isStringFilled(this.title);
		}

		shouldShowSubtitle()
		{
			return BX.prop.getBoolean(this.props.banner, 'showSubtitle', true);
		}

		shouldCenterVertically()
		{
			return BX.prop.getBoolean(this.props.banner, 'centerVertically', false);
		}

		hasRedirectUrl()
		{
			return this.qrauthParameters && this.qrauthParameters.redirectUrl;
		}

		hasButtonAction()
		{
			return this.onButtonClick;
		}

		hasRejectButtonAction()
		{
			return this.onRejectButtonClick;
		}

		hasActionToCloseBanner()
		{
			return this.onCloseBanner;
		}

		renderImage()
		{
			const imageSvg = this.imageSvg;
			const isHorizontalPositioning = this.isHorizontalPositioning();

			if (imageSvg)
			{
				return Image({
					svg: {
						content: imageSvg,
					},
					style: {
						marginTop: isHorizontalPositioning ? 0 : 20,
						width: isHorizontalPositioning ? 116 : 120,
						height: isHorizontalPositioning ? 116 : 120,
					},
				});
			}

			return View(
				{
					style: styles.icon(this.imagePath, isHorizontalPositioning),
				},
			);
		}

		render()
		{
			const isHorizontalPositioning = this.isHorizontalPositioning();

			return View(
				{
					style: {
						...styles.container(isHorizontalPositioning),
						/*temporarily*/
						marginTop: this.shouldCenterVertically() ? 120 : 0,
					},
				},
				this.renderSubtitle(),
				View(
					{
						style: styles.listContainer(isHorizontalPositioning),
					},
					this.renderImage(),
					this.renderTitle(),
					View(
						{
							style: styles.featureList(isHorizontalPositioning),
						},
						...this.featureItems.map((text, index) => this.renderFeatureItem(
							text,
							index,
							isHorizontalPositioning,
						)),
					),
					this.renderSubtext(),
				),
				this.renderBannerButton(),
				this.renderBannerButton(ButtonType.Reject),
			);
		}

		renderTitle()
		{
			if (this.hasTitle())
			{
				return Text(
					{
						style: styles.title(this.isHorizontalPositioning()),
						text: this.title,
					},
				);
			}

			return null;
		}

		renderSubtitle()
		{
			if (this.shouldShowSubtitle())
			{
				return Text(
					{
						style: styles.subtitle,
						text: Loc.getMessage('CONTEXT_MENU_BANNER_TITLE'),
					},
				);
			}

			return null;
		}

		renderFeatureItem(text, index, isHorizontalPositioning)
		{
			return View(
				{
					style: styles.itemContainer(index),
				},
				View(
					{
						style: styles.itemIconContainer(isHorizontalPositioning),
					},
					Image(
						{
							style: styles.itemIcon(isHorizontalPositioning),
							svg: {
								content: SvgImages.featureItemIcon,
							},
						},
					),
				),
				Text(
					{
						style: styles.itemText(isHorizontalPositioning),
						numberOfLines: 2,
						ellipsize: 'end',
						text,
					},
				),
			);
		}

		renderSubtext()
		{
			if (!this.subtext)
			{
				return null;
			}

			return Text(
				{
					style: styles.subtext(this.subtextAlign),
					text: this.subtext,
				},
			);
		}

		getButtonType(forcedType = null)
		{
			if (forcedType !== null)
			{
				return forcedType;
			}

			if (this.hasButtonAction() || this.hasActionToCloseBanner())
			{
				return ButtonType.ActiveGreen;
			}

			return ButtonType.Transparent;
		}

		renderBannerButton(forcedType = null)
		{
			let action;
			const buttonType = this.getButtonType(forcedType);

			if (buttonType === ButtonType.Reject && !this.showRejectButton)
			{
				return null;
			}

			if (this.hasRedirectUrl())
			{
				action = () => {
					if (this.props.menu)
					{
						this.props.menu.close(() => qrauth.open({
							...this.qrauthParameters,
							layout: this.parentWidget,
						}));
					}
				};
			}
			else if (this.hasButtonAction() && buttonType !== ButtonType.Reject)
			{
				action = () => {
					this.onButtonClick(this.parentWidget);
				};
			}
			else if (this.hasRejectButtonAction() && buttonType === ButtonType.Reject)
			{
				if (this.hasActionToCloseBanner())
				{
					action = () => {
						if (this.props.menu)
						{
							this.props.menu.close(() => this.onRejectButtonClick(this.parentWidget));
						}
					};
				}
				else
				{
					action = () => {
						this.onRejectButtonClick(this.parentWidget);
					};
				}
			}
			else if (this.hasActionToCloseBanner())
			{
				action = () => {
					if (this.props.menu)
					{
						this.props.menu.close(() => this.onCloseBanner(this.parentWidget));
					}
				};
			}
			else
			{
				return null;
			}

			return View(
				{
					testId: 'context-menu-action-banner-button',
					style: styles.button[buttonType],
					onClick: action,
				},
				Text(
					{
						style: styles.buttonText[buttonType],
						text: forcedType === ButtonType.Reject ? this.rejectButtonText : this.buttonText,
					},
				),
			);
		}
	}

	const SvgImages = {
		featureItemIcon: `<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="9" fill="${AppTheme.colors.accentSoftBlue1}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M8.18543 10.1948L10.0091 12.0185L14.0893 7.93896L15.2825 9.13221L10.0094 14.4053L6.99219 11.3881L8.18543 10.1948Z" fill="${AppTheme.colors.accentMainPrimary}"/></svg>`,
	};

	const styles = {
		container: () => ({
			flexDirection: 'column',
			marginBottom: 10,
			paddingTop: 10,
			paddingBottom: 28,
			backgroundColor: AppTheme.colors.bgContentPrimary,
			borderRadius: 12,
		}),
		title: (isHorizontalPositioning) => ({
			color: AppTheme.colors.base1,
			fontSize: 17,
			fontWeight: '500',
			textAlign: 'center',
			marginTop: isHorizontalPositioning ? 14 : 30,
			marginBottom: isHorizontalPositioning ? 11 : 17,
		}),
		subtitle: {
			color: AppTheme.colors.base3,
			fontSize: 13,
			marginLeft: 20,
			marginRight: 20,
			marginBottom: 20,
		},
		subtext: (subtextAlign) => ({
			textAlign: subtextAlign || 'left',
			textAlign: 'center',
			color: AppTheme.colors.base3,
			fontSize: 13,
			margin: 20,
			marginBottom: 0,
		}),
		listContainer: (isHorizontalPositioning) => ({
			flexDirection: isHorizontalPositioning ? 'row' : 'column',
			flexGrow: 1,
			alignItems: 'center',
			paddingLeft: 24,
			paddingRight: 24,
		}),
		icon: (imageUri, isHorizontalPositioning) => ({
			width: isHorizontalPositioning ? 116 : 147,
			height: isHorizontalPositioning ? 116 : 120,
			marginTop: isHorizontalPositioning ? 0 : 20,
			backgroundImage: imageUri,
			backgroundResizeMode: 'stretch',
		}),
		featureList: (isHorizontalPositioning) => ({
			marginLeft: isHorizontalPositioning ? 20 : undefined,
			marginHorizontal: isHorizontalPositioning ? undefined : 10,
			width: isHorizontalPositioning ? undefined : '100%',
			flex: isHorizontalPositioning ? 1 : undefined,
		}),
		itemContainer: (index, isHorizontalPositioning) => ({
			flexDirection: 'row',
			marginTop: index === 0 ? 0 : (isHorizontalPositioning ? 8 : 12),
		}),
		itemIconContainer: (isHorizontalPositioning) => ({
			width: isHorizontalPositioning ? 22 : 24,
			height: isHorizontalPositioning ? 22 : 24,
			justifyContent: 'center',
			alignItems: 'center',
			marginRight: isHorizontalPositioning ? 4 : 10,
		}),
		itemIcon: (isHorizontalPositioning) => ({
			width: isHorizontalPositioning ? 22 : 24,
			height: isHorizontalPositioning ? 22 : 24,
		}),
		itemText: (isHorizontalPositioning) => ({
			fontSize: isHorizontalPositioning ? 14 : 15,
			color: AppTheme.colors.base1,
			flexShrink: 2,
		}),
		button: {
			activeGreen:
				{
					backgroundColor: AppTheme.colors.accentMainSuccess,
					marginTop: 30,
					paddingHorizontal: 32,
					paddingVertical: 11,
					alignSelf: 'center',
					borderRadius: 6,
					borderWidth: 1,
				},
			transparent:
				{
					borderColor: AppTheme.colors.bgSeparatorPrimary,
					marginTop: 30,
					paddingHorizontal: 32,
					paddingVertical: 11,
					alignSelf: 'center',
					borderRadius: 6,
					borderWidth: 1,
				},
			reject:
				{
					borderColor: AppTheme.colors.bgContentPrimary,
					marginTop: 8,
					paddingHorizontal: 32,
					paddingVertical: 11,
					alignSelf: 'center',
					borderRadius: 6,
					borderWidth: 1,
				},
		},
		buttonText: {
			activeGreen:
				{
					color: AppTheme.colors.baseWhiteFixed,
					fontSize: 15,
				},
			transparent:
				{
					color: AppTheme.colors.base2,
					fontSize: 15,
				},
			reject:
				{
					color: AppTheme.colors.baseWhiteFixed,
					fontSize: 15,
				},
		},
	};

	module.exports = {
		ContextMenuBanner,
		BannerPositioning,
	};
});
