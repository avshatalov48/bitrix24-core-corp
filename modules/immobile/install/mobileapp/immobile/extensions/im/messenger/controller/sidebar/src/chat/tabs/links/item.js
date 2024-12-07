/**
 * @module im/messenger/controller/sidebar/chat/tabs/links/item
 */
jn.define('im/messenger/controller/sidebar/chat/tabs/links/item', (require, exports, module) => {
	const { inAppUrl } = require('in-app-url');
	const { URL } = require('utils/url');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	const { Theme } = require('im/lib/theme');
	const { LinkContextMenu } = require('im/messenger/controller/sidebar/chat/tabs/links/context-menu');

	/**
	 * @class SidebarLinksItem
	 * @typedef {LayoutComponent<SidebarLink, {showImageAvatar: boolean}>} SidebarLinksItem
	 */
	class SidebarLinksItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				showImageAvatar: Boolean(this.props.url.richData.previewUrl),
			};
		}

		render()
		{
			return View(
				{
					ref: (ref) => {
						if (ref)
						{
							this.itemRef = ref;
						}
					},
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'space-between',
						height: 51,
					},
					onClick: () => {
						this.openLink();
					},
					onLongClick: () => {
						this.onOpenContextMenu(this.itemRef);
					},
				},
				this.renderAvatar(),
				this.renderDescription(),
				this.renderEllipsisButton(),
			);
		}

		renderAvatar()
		{
			const { previewUrl } = this.props.url.richData;
			const { showImageAvatar } = this.state;

			return View(
				{
					style: {
						display: 'flex',
						flexDirection: 'column',
						alignItems: 'center',
						justifyContent: 'center',
						backgroundColor: Theme.colors.accentSoftBlue3,
						borderColor: Theme.colors.bgSeparatorSecondary,
						borderWidth: showImageAvatar ? 0 : 1,
						borderRadius: 8,
						width: 48,
						height: 48,
					},
				},
				showImageAvatar
					? this.renderImage(previewUrl)
					: this.renderPlaceholder(),

			);
		}

		renderImage(uri)
		{
			return Image({
				style: {
					width: '100%',
					height: '100%',
				},
				uri: encodeURI(uri),
				onFailure: () => {
					this.setState({ showImageAvatar: false });
				},
			});
		}

		renderPlaceholder()
		{
			const { type } = this.props.url.richData;

			const icons = {
				tasks: Icon.TASK,
				landing: Icon.KNOWLEDGE_BASE,
				post: Icon.MESSAGES,
				calendar: Icon.CALENDAR_WITH_SLOTS,
			};

			const icon = icons[type?.toLowerCase()] ?? Icon.LINK;

			return IconView({
				icon,
				iconColor: Theme.color.accentMainPrimary,
			});
		}

		renderDescription()
		{
			const { source, richData } = this.props.url;

			const title = richData.description || new URL(source)?.hostname || source;

			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						alignItems: 'flex-start',
						justifyContent: 'center',
						height: '100%',
						marginHorizontal: 12,
					},
				},
				Text({
					style: {
						color: Theme.colors.base2,
						fontSize: 15,
						fontWeight: 500,
					},
					text: title,
					ellipsize: 'end',
					numberOfLines: 1,
					marginBottom: 4,
				}),
				Text({
					style: {
						color: Theme.colors.base4,
						fontSize: 12,
						fontWeight: 400,
					},
					text: source,
					ellipsize: 'end',
					numberOfLines: 2,
				}),
			);
		}

		renderEllipsisButton()
		{
			return View(
				{
					ref: (ref) => {
						if (ref)
						{
							this.viewRef = ref;
						}
					},
					style: {
						alignSelf: 'center',
					},
				},
				ImageButton({
					style: {
						width: 24,
						height: 24,
					},
					onClick: () => {
						this.onOpenContextMenu(this.viewRef);
					},
					iconName: Icon.MORE.getIconName(),
					testId: 'ITEM_ELLIPSIS_BUTTON',
				}),
			);
		}

		onOpenContextMenu(ref)
		{
			LinkContextMenu.open({ ...this.props, ref });
		}

		openLink()
		{
			inAppUrl.open(this.props.url.source);
		}
	}

	module.exports = { SidebarLinksItem };
});
