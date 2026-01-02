/**
 * @module im/messenger/controller/sidebar-v2/tabs/links/src/content
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/links/src/content', (require, exports, module) => {
	const {
		SidebarBaseTabListContent,
		SidebarTabListItemModel,
	} = require('im/messenger/controller/sidebar-v2/tabs/base');
	const { ListItem } = require('im/messenger/controller/sidebar-v2/ui/layout/list-item');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SafeImage } = require('layout/ui/safe-image');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Color, Corner } = require('tokens');
	const { URL } = require('utils/url');
	const { inAppUrl } = require('in-app-url');
	const { DialogTextHelper } = require('im/messenger/controller/dialog/lib/helper/text');
	const { resolveSidebarType, SidebarType } = require('im/messenger/controller/sidebar-v2/factory');
	const { SIDEBAR_DEFAULT_TOAST_OFFSET } = require('im/messenger/controller/sidebar-v2/const');

	class SidebarLinksTabContent extends SidebarBaseTabListContent
	{
		constructor(props)
		{
			super(props);

			/** @type {SidebarLinksService} */
			this.dataProvider = props.dataProvider;
		}

		getItemsFromStore()
		{
			const { links, hasNextPage } = this.store.getters['sidebarModel/sidebarLinksModel/get'](this.props.chatId);

			const items = links ? this.convertToSortedList(links) : null;

			return { items, hasNextPage };
		}

		bindMethods()
		{
			this.onStoreSetSidebarLinks = this.onStoreSetSidebarLinks.bind(this);
			this.onStoreDeleteSidebarLinks = this.onStoreDeleteSidebarLinks.bind(this);
			this.onStoreDeleteAllDataSidebarLinks = this.onStoreDeleteAllDataSidebarLinks.bind(this);
		}

		subscribeStoreEvents()
		{
			this.storeManager.on('sidebarModel/sidebarLinksModel/set', this.onStoreSetSidebarLinks);
			this.storeManager.on('sidebarModel/sidebarLinksModel/delete', this.onStoreDeleteSidebarLinks);
			this.storeManager.on('sidebarModel/sidebarLinksModel/deleteByChatId', this.onStoreDeleteAllDataSidebarLinks);
		}

		unsubscribeStoreEvents()
		{
			this.storeManager.off('sidebarModel/sidebarLinksModel/set', this.onStoreSetSidebarLinks);
			this.storeManager.off('sidebarModel/sidebarLinksModel/delete', this.onStoreDeleteSidebarLinks);
			this.storeManager.off('sidebarModel/sidebarLinksModel/deleteByChatId', this.onStoreDeleteAllDataSidebarLinks);
		}

		onStoreSetSidebarLinks(mutation)
		{
			const { chatId: eventChatId } = mutation.payload.data;

			if (eventChatId !== this.props.chatId)
			{
				return;
			}

			this.onStoreItemsUpdated();
		}

		onStoreDeleteSidebarLinks(mutation)
		{
			const { id, chatId: eventChatId } = mutation.payload.data;

			if (eventChatId !== this.props.chatId)
			{
				return;
			}

			void this.deleteItemById(id);
		}

		onStoreDeleteAllDataSidebarLinks(mutation)
		{
			const { chatId } = mutation.payload.data;

			if (chatId !== this.props.chatId)
			{
				return;
			}

			void this.deleteAllData();
		}

		convertToSortedList(map)
		{
			return [...map]
				.map(([_, value]) => (value))
				.sort((a, b) => new Date(b.dateCreate).getTime() - new Date(a.dateCreate).getTime())
				.map((data) => new SidebarTabListItemModel(data));
		}

		openUrl(source)
		{
			inAppUrl.open(source);
		}

		prepareItemContextMenuActions(item)
		{
			const isChannel = resolveSidebarType(this.dialogId) === SidebarType.channel;
			const openInDialogTitle = isChannel
				? Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_OPEN_IN_CHANNEL')
				: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_OPEN_IN_CHAT');

			const actions = [
				{
					id: 'open-in-dialog',
					testId: 'open-in-dialog',
					title: openInDialogTitle,
					iconName: Icon.ARROW_TO_THE_RIGHT,
					onItemSelected: () => {
						const messageId = Number(item.data.messageId);
						void this.widgetNavigator.backToChatMessage(messageId);
					},
				},
				{
					id: 'copy-link',
					testId: 'copy-link',
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_COPY_LINK'),
					iconName: Icon.LINK,
					onItemSelected: () => {
						DialogTextHelper.copyToClipboard(
							item.data.url.source,
							{
								notificationText: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_COPY_LINK_SUCCESS'),
								notificationIcon: Icon.LINK,
								toastOffset: SIDEBAR_DEFAULT_TOAST_OFFSET,
							},
							true,
						);
					},
				},
			];

			const linkAuthorId = item.data.authorId;
			const linkId = item.data.id;
			if (linkAuthorId === this.currentUserId && linkId)
			{
				actions.push({
					id: 'delete-link',
					testId: 'delete-link',
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TAB_LINKS_MENU_DELETE_LINK'),
					iconName: Icon.MINUS,
					onItemSelected: () => {
						void this.dataProvider.deleteLink(linkId);
					},
				});
			}

			return actions;
		}

		getEmptyScreenProps()
		{
			return {
				testId: 'links',
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TAB_LINKS_EMPTY_TITLE'),
				image: 'links.svg',
			};
		}

		renderItem(item)
		{
			const { source, richData } = item.data.url;
			const { description } = richData;
			const title = description || new URL(source)?.hostname || source;

			return new ListItem({
				testId: 'sidebar-tab-links-item',
				title: {
					text: title,
				},
				subtitle: {
					text: source,
				},
				onClick: () => this.openUrl(source),
				onShowMenu: ({ ref }) => {
					this.showItemContextMenu(ref, item.id, () => this.prepareItemContextMenuActions(item));
				},
				leftIcon: () => this.renderImage(richData),
			});
		}

		renderImage(richData)
		{
			const { previewUrl } = richData;
			const placeholder = !previewUrl;

			return View(
				{
					style: {
						width: 40,
						height: 40,
						backgroundColor: Color.accentSoftBlue3.toHex(),
						borderRadius: Corner.S.toNumber(),
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				previewUrl && this.renderPreviewUrl(richData),
				placeholder && this.renderPlaceholder(richData),
			);
		}

		renderPreviewUrl(richData)
		{
			const { previewUrl } = richData;

			return SafeImage({
				uri: encodeURI(previewUrl),
				withShimmer: true,
				resizeMode: 'cover',
				wrapperStyle: {
					width: 40,
					height: 40,
				},
				style: {
					width: 40,
					height: 40,
				},
				renderPlaceholder: () => this.renderPlaceholder(richData),
			});
		}

		renderPlaceholder(richData)
		{
			const { type } = richData;

			const icons = {
				tasks: Icon.TASK,
				landing: Icon.KNOWLEDGE_BASE,
				post: Icon.MESSAGES,
				calendar: Icon.CALENDAR_WITH_SLOTS,
			};

			const icon = icons[type?.toLowerCase()] ?? Icon.LINK;

			return View(
				{
					style: {
						width: 40,
						height: 40,
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				IconView({
					icon,
					iconColor: Color.accentMainPrimary,
				}),
			);
		}
	}

	module.exports = { SidebarLinksTabContent };
});
