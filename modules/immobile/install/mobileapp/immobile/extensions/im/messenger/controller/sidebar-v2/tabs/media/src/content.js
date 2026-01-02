/**
 * @module im/messenger/controller/sidebar-v2/tabs/media/src/content
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/media/src/content', (require, exports, module) => {
	const { Color } = require('tokens');
	const { uniqBy } = require('utils/array');
	const { Moment } = require('utils/date/moment');
	const { monthYear } = require('utils/date/formats');
	const { assertDefined } = require('utils/validation');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { FileType } = require('im/messenger/const');
	const { DialogMediaGallery } = require('im/messenger/controller/dialog/lib/media-gallery');
	const { SidebarBaseTabContent } = require('im/messenger/controller/sidebar-v2/tabs/base/src/content');
	const { SidebarTabMediaItemModel } = require('im/messenger/controller/sidebar-v2/tabs/media/src/items/model');

	const isMediaGridAvailable = Boolean(MediaGrid);

	/**
	 * @class SidebarMediaTabContent
	 */
	class SidebarMediaTabContent extends SidebarBaseTabContent
	{
		constructor(props)
		{
			super(props);

			/** @type {SidebarFileService} */
			this.dataProvider = props.dataProvider;
			/** @type {SidebarWidgetNavigator} */
			this.widgetNavigator = props.widgetNavigator;

			assertDefined(this.dataProvider, 'dataProvider property is required');

			const { items, hasNextPage } = this.getItemsFromStore();

			this.state = {
				items,
				hasNextPage,
				pending: items === null,
			};

			this.bindMethods();
			this.subscribeStoreEvents();

			this.chatId = this.props.dialogData.chatId;
			this.mediaGridRef = null;
		}

		subscribeStoreEvents()
		{
			this.storeManager.on('sidebarModel/sidebarFilesModel/set', this.onSetSidebarFilesStore);
			this.storeManager.on('sidebarModel/sidebarFilesModel/delete', this.onDeleteSidebarFilesStore);
			this.storeManager.on('sidebarModel/sidebarFilesModel/setHasNextPage', this.onSetHasNextPage);
			this.storeManager.on('sidebarModel/sidebarFilesModel/deleteByChatId', this.onDeleteAllDataSidebarFilesStore);
		}

		unsubscribeStoreEvents()
		{
			this.storeManager.off('sidebarModel/sidebarFilesModel/set', this.onSetSidebarFilesStore);
			this.storeManager.off('sidebarModel/sidebarFilesModel/delete', this.onDeleteSidebarFilesStore);
			this.storeManager.off('sidebarModel/sidebarFilesModel/setHasNextPage', this.onSetHasNextPage);
			this.storeManager.off('sidebarModel/sidebarFilesModel/deleteByChatId', this.onDeleteAllDataSidebarFilesStore);
		}

		bindMethods()
		{
			this.onSetSidebarFilesStore = this.onSetSidebarFilesStore.bind(this);
			this.onDeleteSidebarFilesStore = this.onDeleteSidebarFilesStore.bind(this);
			this.onDeleteAllDataSidebarFilesStore = this.onDeleteAllDataSidebarFilesStore.bind(this);
			this.onSetHasNextPage = this.onSetHasNextPage.bind(this);
		}

		onSetHasNextPage(mutation)
		{
			const {
				chatId: eventChatId,
				subType,
				hasNextPage,
			} = mutation.payload.data;

			const isEqualChatId = eventChatId === this.chatId;
			const isEqualSubType = subType === this.dataProvider.getStoreKey();

			if (isEqualChatId && isEqualSubType && !hasNextPage)
			{
				this.disablePaginationLoader();
			}
		}

		getItemsFromStore()
		{
			const { chatId } = this.props.dialogData;

			const getSidebarFiles = this.store.getters['sidebarModel/sidebarFilesModel/get'];
			const data = getSidebarFiles(chatId, this.dataProvider.getStoreKey());

			const items = data.items;

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
		 * @protected
		 */
		onStoreItemsUpdated()
		{
			const { items, hasNextPage } = this.getItemsFromStore();

			if (this.isPending())
			{
				if (items !== null)
				{
					this.setState({
						items,
						hasNextPage,
						pending: false,
					});
				}
			}
			else
			{
				if (this.hasItems())
				{
					this.state.items = items;
					this.state.hasNextPage = hasNextPage;
					this.mediaGridRef?.updateOrInsert(this.getMediaGridData());

					return;
				}

				this.setState({
					items,
					hasNextPage,
				});
			}
		}

		/**
		 * @param {object} mutation
		 * @param {object} mutation.payload
		 * @param {SidebarFilesDeleteData} mutation.payload.data
		 */
		onDeleteSidebarFilesStore(mutation)
		{
			const { fileId, chatId: eventChatId } = mutation.payload.data;

			if (String(eventChatId) !== String(this.chatId))
			{
				return;
			}

			this.mediaGridRef?.delete([fileId]);
		}

		onDeleteAllDataSidebarFilesStore(mutation)
		{
			const { chatId } = mutation.payload.data;

			if (chatId !== this.chatId)
			{
				return;
			}

			const fileIds = this.getItems().map((item) => item.data.fileId);
			this.mediaGridRef?.delete(fileIds);
			this.setState({
				hasNextPage: false,
				items: [],
			});
		}

		renderContent()
		{
			return this.hasItems() && isMediaGridAvailable
				? this.renderItemsContainer()
				: this.renderEmptyScreen();
		}

		renderItemsContainer()
		{
			const { onScrollCalculated, onScroll, onOverscrollTop, onOverscrollBottom, testId } = this.props;

			return View(
				{
					testId: `${testId}-items-container`,
					style: {
						flex: 1,
					},
				},
				// eslint-disable-next-line no-undef
				MediaGrid({
					onScrollCalculated,
					onScroll,
					onOverscrollTop,
					onOverscrollBottom,
					testId: `${testId}-items-container-grid`,
					ref: this.onMediaGridControllerAvailable,
					style: {
						flex: 1,
					},
					getSection: this.getSection,
					sectionHeaderStyle: {
						font: {
							color: Color.base4.toHex(),
							size: 15,
						},
					},
					isScrollable: true,
					onLoadMore: this.onLoadMore,
					onItemClick: this.onItemClick,
				}),
			);
		}

		/**
		 * @protected
		 */
		onLoadMore = () => {
			const offset = this.getItems().length;

			if (this.isLastPage())
			{
				this.disablePaginationLoader();

				return;
			}

			this.dataProvider.loadPage(offset)
				.then((data) => {
					this.logger.info('onLoadMore', data);
				})
				.catch((error) => {
					this.logger.error('onLoadMore', error);
				});
		};

		/**
		 * @protected
		 */
		enablePaginationLoader()
		{
			this.mediaGridRef?.setNextPageLoaderVisibility(true);
		}

		/**
		 * @protected
		 */
		disablePaginationLoader()
		{
			this.mediaGridRef?.setNextPageLoaderVisibility(false);
		}

		onMediaGridControllerAvailable = (ref) => {
			if (ref && !this.mediaGridRef)
			{
				this.mediaGridRef = ref;
				this.mediaGridRef.setInitialMediaFiles(this.getMediaGridData());
				if (this.state.hasNextPage)
				{
					this.enablePaginationLoader();
				}
			}
		};

		onItemClick = ({ fileId, type, customData }) => {
			const mediaList = this.getItems().map((item) => {
				return {
					...item.file,
					id: item.data.fileId,
					messageId: item.data.messageId,
				};
			}).reverse();

			DialogMediaGallery.open({
				messageId: customData.messageId,
				direction: 'right',
				mediaId: fileId,
				mediaType: type,
				forceDelete: true,
				dialogLocator: this.dialogLocator,
				dialogModel: this.getDialog(),
				onBeforeAction: async () => {
					await this.widgetNavigator.backToChat();
					// To have time to see the result of the action after closing the widget
					await this.#pause(500);
				},
				mediaList,
			});
		};

		getImagesForNativeViewer(fileId)
		{
			/** @return {SidebarTabMediaItemModel[]} */
			return this.getItems()
				.filter((item) => item.getType() === FileType.image)
				.map((image) => ({
					url: image.getUrlShow(),
					default: image.getId() === fileId,
				}));
		}

		/**
		 * @param {String} sectionId - timestamp
		 * @returns {{sectionSortKey: number, title: string}}
		 */
		getSection = (sectionId) => {
			const timestamp = new Date(Number(sectionId));
			const moment = new Moment(timestamp);

			return {
				sectionSortKey: -Number(sectionId),
				title: moment.format(monthYear),
			};
		};

		getMediaGridData()
		{
			return this.getItems()
				.map((item) => item.toMediaGridView())
				.filter(Boolean);
		}

		/**
		 * @protected
		 * @return {SidebarTabMediaItemModel[]}
		 */
		getItems()
		{
			return Array.isArray(this.state.items) ? this.state.items : [];
		}

		/**
		 * @param {Map<number, SidebarFile>} map
		 * @return {Array<SidebarTabMediaItemModel>}
		 */
		convertToSortedList(map)
		{
			return uniqBy(
				[...map]
					.map(([_, value]) => (value))
					.sort((a, b) => new Date(b.dateCreate).getTime() - new Date(a.dateCreate).getTime())
					.map((data) => new SidebarTabMediaItemModel(this.store, data)),
				(item) => item.getId(),
			);
		}

		getEmptyScreenProps()
		{
			const emptyScreenProps = {
				testId: 'media',
				title: isMediaGridAvailable
					? Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TAB_MEDIA_EMPTY_SCREEN_TITLE')
					: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TAB_NOT_SUPPORTED_TITLE'),
				image: 'media.svg',
			};

			if (!isMediaGridAvailable)
			{
				emptyScreenProps.description = Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TAB_NOT_SUPPORTED_SUBTITLE');
			}

			return emptyScreenProps;
		}

		/**
		 * @public
		 * @param {boolean} animated
		 * @return {Promise}
		 */
		scrollToBegin(animated = true)
		{
			if (this.mediaGridRef?.scrollToBegin)
			{
				this.mediaGridRef.scrollToBegin(animated);

				return Promise.resolve();
			}

			if (this.isEmpty())
			{
				return Promise.resolve();
			}

			return Promise.reject(new Error('Scrollable ref not found'));
		}

		/**
		 * @return {DialoguesModelState|{}}
		 */
		getDialog()
		{
			return this.store.getters['dialoguesModel/getById'](this.dialogId) || {};
		}

		#pause = (delay) => new Promise((resolve) => {
			setTimeout(resolve, delay);
		});
	}

	module.exports = { SidebarMediaTabContent };
});
