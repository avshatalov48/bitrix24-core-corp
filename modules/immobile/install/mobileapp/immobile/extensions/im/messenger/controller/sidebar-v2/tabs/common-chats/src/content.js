/**
 * @module im/messenger/controller/sidebar-v2/tabs/common-chats/src/content
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/common-chats/src/content', (require, exports, module) => {
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType, ComponentCode } = require('im/messenger/const');
	const { ListItem } = require('im/messenger/controller/sidebar-v2/ui/layout/list-item');
	const {
		SidebarBaseTabListContent,
		SidebarTabListItemModel,
	} = require('im/messenger/controller/sidebar-v2/tabs/base');

	class SidebarCommonChatsTabContent extends SidebarBaseTabListContent
	{
		getItemsFromStore()
		{
			const { chatId } = this.props;

			const {
				chats,
				hasNextPage,
			} = this.store.getters['sidebarModel/sidebarCommonChatsModel/get'](chatId);

			const items = chats ? this.convertToSortedList(chats) : null;

			return { items, hasNextPage };
		}

		bindMethods()
		{
			this.onStoreSetSidebarCommonChats = this.onStoreSetSidebarCommonChats.bind(this);
			this.onStoreDeleteSidebarCommonChats = this.onStoreDeleteSidebarCommonChats.bind(this);
		}

		subscribeStoreEvents()
		{
			this.storeManager.on('sidebarModel/sidebarCommonChatsModel/set', this.onStoreSetSidebarCommonChats);
			this.storeManager.on('sidebarModel/sidebarCommonChatsModel/delete', this.onStoreDeleteSidebarCommonChats);
		}

		unsubscribeStoreEvents()
		{
			this.storeManager.off('sidebarModel/sidebarCommonChatsModel/set', this.onStoreSetSidebarCommonChats);
			this.storeManager.off('sidebarModel/sidebarCommonChatsModel/delete', this.onStoreDeleteSidebarCommonChats);
		}

		onStoreDeleteSidebarCommonChats(mutation)
		{
			const { id, chatId: eventChatId } = mutation.payload.data;
			const { chatId } = this.props;

			if (eventChatId !== chatId)
			{
				return;
			}

			void this.deleteItemById(id);
		}

		onStoreSetSidebarCommonChats(mutation)
		{
			const { chatId: eventChatId } = mutation.payload.data;
			const { chatId } = this.props;

			if (eventChatId !== chatId)
			{
				return;
			}

			this.onStoreItemsUpdated();
		}

		convertToSortedList(map)
		{
			return [...map]
				.map(([_, value]) => (value))
				.sort((a, b) => new Date(b.dateMessage).getTime() - new Date(a.dateMessage).getTime())
				.map((data) => new SidebarTabListItemModel(data));
		}

		renderItem(item)
		{
			const dialogId = this.getDialogId(item);
			const chatTitle = ChatTitle.createFromDialogId(dialogId);

			return new ListItem({
				testId: 'common-chat',
				title: {
					text: chatTitle.getTitle(),
					style: {
						color: chatTitle.getTitleColor(),
					},
				},
				subtitle: {
					text: chatTitle.getDescription(),
				},
				onClick: this.openDialog(dialogId),
				leftIcon: {
					testId: 'common-chat',
					dialogId,
					size: 40,
				},
			});
		}

		getDialogId(item)
		{
			const { data } = item;

			return data?.dialogId;
		}

		openDialog = (dialogId) => () => {
			MessengerEmitter.emit(
				EventType.messenger.openDialog,
				{ dialogId },
				ComponentCode.imMessenger,
			);
		};

		getEmptyScreenProps()
		{
			return {
				testId: 'common-chat',
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TAB_COMMON_CHATS_EMPTY_SCREEN_TITLE'),
				image: 'common-chats.svg',
			};
		}
	}

	module.exports = { SidebarCommonChatsTabContent };
});
