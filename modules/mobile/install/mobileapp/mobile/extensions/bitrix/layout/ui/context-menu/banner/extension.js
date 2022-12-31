/**
 * @module layout/ui/context-menu/banner
 */
jn.define('layout/ui/context-menu/banner', (require, exports, module) => {
	class ContextMenuBanner extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: styles.container,
				},
				Text(
					{
						style: styles.title,
						text: BX.message('CONTEXT_MENU_BANNER_TITLE'),
					},
				),
				View(
					{
						style: styles.listContainer(this.hasRedirectUrl()),
					},
					View(
						{
							style: styles.icon(this.imagePath),
						},
					),
					View(
						{
							style: styles.list,
						},
						...this.featureItems.map((text, index) => this.renderFeatureItem(text, index)),
					),
				),
				this.renderBannerButton(),
			);
		}

		get banner()
		{
			const { banner } = this.props;

			return typeof banner === 'object' ? banner : null;
		}

		get menu()
		{
			const { menu } = this.props;

			return typeof menu === 'object' ? menu : null;
		}

		get featureItems()
		{
			return BX.prop.getArray(this.banner, 'featureItems', []);
		}

		get imagePath()
		{
			return BX.prop.getString(this.banner, 'imagePath', '');
		}

		get qrauthParameters()
		{
			return BX.prop.getObject(this.banner, 'qrauth', {});
		}

		hasRedirectUrl()
		{
			return this.qrauthParameters && this.qrauthParameters.redirectUrl;
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
						if (typeof this.menu === 'object')
						{
							this.menu.close(() => {
								qrauth.open(this.qrauthParameters);
							});
						}
					},
				},
				Text(
					{
						style: styles.buttonText,
						text: BX.message('CONTEXT_MENU_BANNER_BUTTON'),
					},
				),
			);
		}

		renderFeatureItem(text, index)
		{
			return View(
				{
					style: styles.itemContainer(index),
				},
				View(
					{
						style: styles.itemIconContainer,
					},
					Image(
						{
							style: styles.itemIcon,
							svg: {
								content: svgImages.featureItemIcon,
							},
						},
					),
				),
				Text(
					{
						style: styles.itemText,
						numberOfLines: 2,
						ellipsize: 'end',
						text,
					},
				),
			);
		}
	}

	const svgImages = {
		featureItemIcon: `<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="9" fill="#D5F4FD"/><path fill-rule="evenodd" clip-rule="evenodd" d="M8.18543 10.1948L10.0091 12.0185L14.0893 7.93896L15.2825 9.13221L10.0094 14.4053L6.99219 11.3881L8.18543 10.1948Z" fill="#2FC6F6"/></svg>`,
	};

	const styles = {
		container: {
			borderRadius: 12,
			backgroundColor: '#F6FDFF',
			paddingTop: 10,
			paddingBottom: 28,
			flexDirection: 'column',
			marginBottom: 10,
		},
		title: {
			color: '#525C69',
			fontSize: 13,
			marginLeft: 20,
			marginRight: 20,
			marginBottom: 20,
		},
		listContainer: (hasRedirectUrl) => ({
			flexDirection: 'row',
			alignItems: 'center',
			marginBottom: hasRedirectUrl ? 30 : 0,
			paddingLeft: 24,
			paddingRight: 24,
		}),
		icon: (imageUri) => ({
			width: 116,
			height: 116,
			backgroundImage: imageUri,
			backgroundResizeMode: 'stretch',
		}),
		list: {
			marginLeft: 20,
			flex: 1,
		},
		button: {
			borderColor: '#828B95',
			borderRadius: 6,
			borderWidth: 1,
			paddingHorizontal: 32,
			paddingVertical: 11,
			alignSelf: 'center',
		},
		buttonText: {
			color: '#525C69',
			fontSize: 15,
		},
		itemContainer: (index) => ({
			flexDirection: 'row',
			marginTop: index !== 0 ? 8 : 0,
		}),
		itemIconContainer: {
			width: 22,
			height: 22,
			justifyContent: 'center',
			alignItems: 'center',
			marginRight: 4,
		},
		itemIcon: {
			width: 22,
			height: 22,
		},
		itemText: {
			fontSize: 14,
			color: '#000000',
			flexShrink: 2,
		},
	};

	module.exports = { ContextMenuBanner };
});