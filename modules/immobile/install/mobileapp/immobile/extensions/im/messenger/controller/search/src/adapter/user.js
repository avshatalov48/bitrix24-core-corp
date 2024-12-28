/**
 * @module im/messenger/controller/search/adapter/user
 */
jn.define('im/messenger/controller/search/adapter/user', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { SelectorDialogListAdapter } = require('im/chat/selector/adapter/dialog-list');
	const { ChatTitle, ChatAvatar } = require('im/messenger/lib/element');
	const { ObjectUtils } = require('im/messenger/lib/utils');

	class UserAdapter extends SelectorDialogListAdapter
	{
		/**
		 * @param {MessengerList} list
		 */
		constructor(list)
		{
			super(list);

			this.store = serviceLocator.get('core').getStore();
		}

		onScopeSelected(data)
		{
			this.selectorListener('onScopeChanged', data);
		}

		onUserTypeText(data)
		{
			this.selectorListener('onListFill', data);
		}

		onSearchItemSelected(data)
		{
			this.selectorListener('onItemSelected', {
				item: data,
			});
		}

		setTitle(title)
		{}

		setScopes(scopes)
		{}

		setItems(items)
		{
			const filteredItems = items.filter((item) => {
				if (item.type !== 'info' || item.id === 'loading')
				{
					return false;
				}

				if (item.params.entityId === 'im-bot')
				{
					return item.params.entityType === 'human' || item.params.entityType === 'bot';
				}

				return true;
			});

			const itemsData = filteredItems.map((item) => ObjectUtils.convertKeysToCamelCase(item.params.customData.imUser));
			this.store.dispatch('usersModel/set', itemsData);

			const renderingItems = this.prepareItems(itemsData);
			const loaderItem = items.find((item) => item.id === 'loading');

			const withLoader = typeof loaderItem !== 'undefined';

			this.list.setItems(renderingItems, withLoader);
		}

		show()
		{
			return Promise.resolve();
		}

		close(callback)
		{
			this.list.setSearchScopes([]);

			callback();
		}

		setSelected(items)
		{}

		allowMultipleSelection(allow)
		{}

		setSections(sections)
		{}

		searchSectionButtonClick(data)
		{}

		prepareItems(items)
		{
			const preparedItems = [];
			for (const item of items)
			{
				const preparedItem = this.prepareItem(item);
				if (preparedItem)
				{
					preparedItems.push(preparedItem);
				}
			}

			return preparedItems;
		}

		prepareItem(item)
		{
			const chatTitle = ChatTitle.createFromDialogId(item.id, { showItsYou: true });
			const chatAvatar = ChatAvatar.createFromDialogId(item.id);

			return {
				data: {
					id: item.id,
					title: chatTitle.getTitle() || item.title,
					subtitle: chatTitle.getDescription() || item.subtitle,
					avatarUri: chatAvatar.getAvatarUrl(),
					avatarColor: item.color,
					avatar: chatAvatar.getListItemAvatarProps(),
				},
				type: 'chats',
				selected: false,
				disable: false,
			};
		}
	}

	module.exports = { UserAdapter };
});
