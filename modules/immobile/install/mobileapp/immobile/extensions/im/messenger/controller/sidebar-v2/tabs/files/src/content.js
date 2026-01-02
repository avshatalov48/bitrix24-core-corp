/**
 * @module im/messenger/controller/sidebar-v2/tabs/files/src/content
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/files/src/content', (require, exports, module) => {
	const {
		SidebarBaseTabListContent,
		SidebarTabListItemModel,
	} = require('im/messenger/controller/sidebar-v2/tabs/base');
	const { FileItem } = require('im/messenger/controller/sidebar-v2/tabs/files/src/items/file');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');

	/**
	 * @class SidebarFilesTabContent
	 */
	class SidebarFilesTabContent extends SidebarBaseTabListContent
	{
		/**
		 * @param props
		 * @param {DialoguesModelState} props.dialogData
		 * @param {WidgetNavigator} props.widgetNavigator
		 */
		constructor(props)
		{
			super(props);

			this.chatId = this.props.dialogData.chatId;
		}

		subscribeStoreEvents()
		{
			this.storeManager.on('sidebarModel/sidebarFilesModel/set', this.onSetSidebarFilesStore);
			this.storeManager.on('sidebarModel/sidebarFilesModel/delete', this.onDeleteSidebarFilesStore);
			this.storeManager.on('sidebarModel/sidebarFilesModel/deleteByChatId', this.onDeleteAllDataSidebarFilesStore);
		}

		unsubscribeStoreEvents()
		{
			this.storeManager.off('sidebarModel/sidebarFilesModel/set', this.onSetSidebarFilesStore);
			this.storeManager.off('sidebarModel/sidebarFilesModel/delete', this.onDeleteSidebarFilesStore);
			this.storeManager.off('sidebarModel/sidebarFilesModel/deleteByChatId', this.onDeleteAllDataSidebarFilesStore);
		}

		bindMethods()
		{
			this.onSetSidebarFilesStore = this.onSetSidebarFilesStore.bind(this);
			this.onDeleteSidebarFilesStore = this.onDeleteSidebarFilesStore.bind(this);
			this.onDeleteAllDataSidebarFilesStore = this.onDeleteAllDataSidebarFilesStore.bind(this);
		}

		getItemsFromStore()
		{
			const { chatId } = this.props.dialogData;

			const getSidebarFiles = this.store.getters['sidebarModel/sidebarFilesModel/get'];
			const data = getSidebarFiles(chatId, this.dataProvider.getStoreKey());

			const items = data?.items ?? [];

			return {
				items: items ? this.convertToSortedList(items) : null,
				hasNextPage: data.hasNextPage,
			};
		}

		/**
		 * @param {object} mutation
		 * @param {object} mutation.payload
		 * @param {SidebarFilesSetData} mutation.payload.data
		 */
		onSetSidebarFilesStore(mutation)
		{
			const {
				chatId: eventChatId,
				subType,
			} = mutation.payload.data;

			const isEqualChatId = eventChatId === this.chatId;
			const isEqualSubType = subType === this.dataProvider.getStoreKey();

			if (isEqualChatId && isEqualSubType)
			{
				this.onStoreItemsUpdated();
			}
		}

		/**
		 * @param {object} mutation
		 * @param {object} mutation.payload
		 * @param {SidebarFilesDeleteData} mutation.payload.data
		 */
		onDeleteSidebarFilesStore(mutation)
		{
			const { id, chatId: eventChatId } = mutation.payload.data;

			if (eventChatId !== this.props.chatId)
			{
				return;
			}

			void this.deleteItemById(id);
		}

		/**
		 * @param {object} mutation
		 * @param {object} mutation.payload
		 * @param {SidebarFilesDeleteByChatIdData} mutation.payload.data
		 */
		onDeleteAllDataSidebarFilesStore(mutation)
		{
			const { chatId } = mutation.payload.data;

			if (chatId !== this.chatId)
			{
				return;
			}

			void this.deleteAllData();
		}

		/**
		 * @param {Map<number, SidebarFile>} map
		 * @return {Array<SidebarFile>}
		 */
		convertToSortedList(map)
		{
			return [...map]
				.map(([_, value]) => (value))
				.sort((a, b) => new Date(b.dateCreate).getTime() - new Date(a.dateCreate).getTime())
				.map((data) => new SidebarTabListItemModel(data));
		}

		/**
		 * @param {SidebarTabListItemModel} item
		 * @param {SidebarFile} item.data
		 * @returns {FileItem}
		 */
		renderItem(item)
		{
			return new FileItem({
				...item.data,
				dialogId: this.props.dialogData.dialogId,
				widgetNavigator: this.props.widgetNavigator,
			});
		}

		getEmptyScreenProps()
		{
			return {
				testId: 'files',
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TAB_FILES_EMPTY_SCREEN_TITLE'),
				image: 'files.svg',
			};
		}
	}

	module.exports = { SidebarFilesTabContent };
});
