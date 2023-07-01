/**
 * @module layout/ui/context-menu/banner
 */
jn.define('layout/ui/context-menu/banner', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');

	const BannerPositioning = {
		Horizontal: 'horizontal',
		Vertical: 'vertical',
	};

	/**
	 * @class ContextMenuBanner
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

		get qrauthParameters()
		{
			return BX.prop.getObject(this.props.banner, 'qrauth', {});
		}

		get bannerButtonText()
		{
			return BX.prop.getString(this.props.banner, 'buttonText', Loc.getMessage('CONTEXT_MENU_BANNER_BUTTON'));
		}

		get positioning()
		{
			return BX.prop.getString(this.props.banner, 'positioning', BannerPositioning.Horizontal);
		}

		get title()
		{
			return BX.prop.getString(this.props.banner, 'title', '');
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

		hasRedirectUrl()
		{
			return this.qrauthParameters && this.qrauthParameters.redirectUrl;
		}

		render()
		{
			const isHorizontalPositioning = this.isHorizontalPositioning();

			return View(
				{
					style: styles.container(isHorizontalPositioning),
				},
				this.renderSubtitle(),
				View(
					{
						style: styles.listContainer(isHorizontalPositioning),
					},
					View(
						{
							style: styles.icon(this.imagePath, isHorizontalPositioning),
						},
					),
					this.renderTitle(),
					View(
						{
							style: styles.featureList(isHorizontalPositioning),
						},
						...this.featureItems.map((text, index) => this.renderFeatureItem(text, index, isHorizontalPositioning)),
					),
				),
				this.renderBannerButton(),
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

		renderBannerButton()
		{
			if (!this.hasRedirectUrl())
			{
				return null;
			}

			return View(
				{
					style: styles.button,
					onClick: () => {
						if (this.props.menu)
						{
							this.props.menu.close(() => qrauth.open({
								...this.qrauthParameters,
								layout: this.parentWidget,
							}));
						}
					},
				},
				Text(
					{
						style: styles.buttonText,
						text: this.bannerButtonText,
					},
				),
			);
		}
	}

	const SvgImages = {
		featureItemIcon: `<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="9" fill="#D5F4FD"/><path fill-rule="evenodd" clip-rule="evenodd" d="M8.18543 10.1948L10.0091 12.0185L14.0893 7.93896L15.2825 9.13221L10.0094 14.4053L6.99219 11.3881L8.18543 10.1948Z" fill="#2FC6F6"/></svg>`,
	};

	const styles = {
		container: (isHorizontalPositioning) => ({
			flexDirection: 'column',
			marginBottom: 10,
			paddingTop: 10,
			paddingBottom: 28,
			backgroundColor: isHorizontalPositioning ? '#f6fdff' : '#f8fafb',
			borderRadius: 12,
		}),
		title: (isHorizontalPositioning) => ({
			color: '#000000',
			fontSize: 17,
			fontWeight: '500',
			textAlign: 'center',
			marginTop: isHorizontalPositioning ? 14 : 30,
			marginBottom: isHorizontalPositioning ? 11 : 17,
		}),
		subtitle: {
			color: '#525c69',
			fontSize: 13,
			marginLeft: 20,
			marginRight: 20,
			marginBottom: 20,
		},
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
			marginTop: index !== 0 ? (isHorizontalPositioning ? 8 : 12) : 0,
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
			color: '#000000',
			flexShrink: 2,
		}),
		button: {
			borderColor: '#828b95',
			marginTop: 30,
			paddingHorizontal: 32,
			paddingVertical: 11,
			alignSelf: 'center',
			borderRadius: 6,
			borderWidth: 1,
		},
		buttonText: {
			color: '#525c69',
			fontSize: 15,
		},
	};

	module.exports = {
		ContextMenuBanner,
		BannerPositioning,
	};
});
