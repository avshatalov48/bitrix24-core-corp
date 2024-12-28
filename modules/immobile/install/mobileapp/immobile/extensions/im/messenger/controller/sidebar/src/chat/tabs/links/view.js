/**
 * @module im/messenger/controller/sidebar/chat/tabs/links/view
 */
jn.define('im/messenger/controller/sidebar/chat/tabs/links/view', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--links-view');
	const { withPressed } = require('utils/color');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { SidebarLinksService } = require('im/messenger/controller/sidebar/chat/tabs/links/service');
	const { SidebarLinksItem } = require('im/messenger/controller/sidebar/chat/tabs/links/item');
	const { Theme } = require('im/lib/theme');
	const { LoaderItem } = require('im/messenger/lib/ui/base/loader');
	const { icon } = require('im/messenger/controller/sidebar/lib/assets/icons');
	const { SidebarTab } = require('im/messenger/const');
	const { BaseSidebarTabView } = require('im/messenger/controller/sidebar/chat/tabs/base/view');

	/**
	 * @class SidebarLinksView
	 * @typedef {LayoutComponent<SidebarLinksViewProps, SidebarLinksViewState>} SidebarLinksView
	 */
	class SidebarLinksView extends BaseSidebarTabView
	{
		#core;
		#store;
		#storeManager;
		#chatId;
		#linksService;
		#listViewRef;

		constructor(props)
		{
			super(props);

			this.#core = serviceLocator.get('core');
			this.#store = this.#core.getStore();
			this.#storeManager = this.#core.getStoreManager();
			const dialog = this.#store.getters['dialoguesModel/getById'](props.dialogId);
			this.#chatId = dialog?.chatId;
			this.#linksService = new SidebarLinksService(this.#chatId);
			this.isHistoryLimitExceeded = this.#store.getters['sidebarModel/sidebarLinksModel/isHistoryLimitExceeded'](this.#chatId);

			this.state = {
				links: null,
				hasNextPage: true,
			};

			this.loader = new LoaderItem({
				enable: false,
				text: '',
			});
			this.#getLinksFromStore();
		}

		render()
		{
			logger.log(`${this.constructor.name}.render`);

			if (!this.#chatId)
			{
				return;
			}

			const { links } = this.state;
			const isEmptyState = Type.isArray(links) && !Type.isArrayFilled(links);

			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						alignItems: 'center',
						minWidth: '100%',
					},
				},
				isEmptyState ? this.#renderEmptyState() : this.#renderListView(),
			);
		}

		#renderEmptyState()
		{
			return ScrollView(
				{
					style: {
						flex: 1,
						alignItems: 'center',
						minWidth: '100%',
					},
				},
				View(
					{
						style: {
							marginTop: 24,
							flexDirection: 'column',
							alignItems: 'center',
							width: '100%',
							maxWidth: '100%',
						},
					},
					Image({
						style: {
							width: 327,
							height: 140,
							alignSelf: 'center',
							marginBottom: 18,
						},
						svg: {
							content: icon.emptyState,
						},
					}),
					Text({
						style: {
							color: Theme.colors.base1,
							fontSize: 19,
							fontWeight: 500,
							textAlign: 'center',
							marginHorizontal: 24,
							marginBottom: 10,
						},
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_LINKS_EMPTY_STATE_TITLE'),
					}),
					Text({
						style: {
							color: Theme.colors.base2,
							fontSize: 16,
							fontWeight: 400,
							textAlign: 'center',
							marginHorizontal: 24,
						},
						text: Loc.getMessage(
							'IMMOBILE_DIALOG_SIDEBAR_LINKS_EMPTY_STATE_SUBTITLE',
							{ '#BR#': '\n' },
						),
					}),
				),
			);
		}

		#renderListView()
		{
			const links = this.state.links ? this.#prepareLinksForRender(this.state.links) : [];

			return ListView({
				ref: (ref) => {
					this.#listViewRef = ref;
				},
				style: {
					flex: 1,
					flexDirection: 'column',
				},
				data: [{ items: links }],
				renderItem: (link) => {
					return View(
						{
							style: {
								paddingTop: 12,
								paddingHorizontal: 14,
								backgroundColor: withPressed(Theme.colors.bgContentPrimary),
							},
						},
						this.#renderLink(link),
						this.#renderSeparator(),
					);
				},
				onLoadMore: this.#onLoadScrollItems.bind(this),
				renderLoadMore: this.#renderLoadMore.bind(this),
			});
		}

		/**
		 * @param {SidebarLink} link
		 */
		#renderLink(link)
		{
			const data = { ...link, dialogId: this.props.dialogId };

			return new SidebarLinksItem(data);
		}

		#renderSeparator()
		{
			return View(
				{
					style: {
						marginTop: 8,
						alignSelf: 'flex-end',
						width: '83%',
						height: 1,
						backgroundColor: Theme.colors.bgSeparatorSecondary,
					},
				},
			);
		}

		/**
		 * @desc Handler load more event by scroll down ( staring rest call links )
		 * @void
		 */
		#onLoadScrollItems()
		{
			const { links, hasNextPage } = this.state;

			if (this.isHistoryLimitExceeded || !hasNextPage)
			{
				return;
			}

			this.loader.enable();
			this.#linksService.loadNextPage(links?.length)
				.then((data) => {
					logger.info(`${this.constructor.name}.onLoadScrollItems:`, data);
				})
				.catch((error) => {
					logger.error(`${this.constructor.name}.onLoadScrollItems:`, error);
				})
				.finally(() => {
					this.loader.disable();
				});
		}

		#renderLoadMore()
		{
			return this.loader;
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindMethods()
		{
			super.bindMethods();
			this.onSetHistoryLimitExceeded = this.#onSetHistoryLimitExceeded.bind(this);
			this.onSetSidebarLinksStore = this.#onSetSidebarLinksStore.bind(this);
			this.onDeleteSidebarLinksStore = this.#onDeleteSidebarLinksStore.bind(this);
		}

		/**
		 * @param {object} mutation
		 * @param {SidebarLinksSetHistoryLimitExceededData} mutation.payload.data
		 */
		#onSetHistoryLimitExceeded(mutation)
		{
			logger.info(`${this.constructor.name}.onSetHistoryLimitExceeded---------->`, mutation);

			const {
				chatId: eventChatId,
				isHistoryLimitExceeded,
			} = mutation.payload.data;

			const isEqualChatId = eventChatId === this.#chatId;

			if (isEqualChatId)
			{
				this.isHistoryLimitExceeded = isHistoryLimitExceeded;
			}
		}

		/**
		 * @param {object} mutation
		 * @param {MutationPayload<SidebarLinksSetData, SidebarLinksSetActions>} mutation.payload
		 * @param {SidebarLinksSetData} mutation.payload.data
		 */
		#onSetSidebarLinksStore(mutation)
		{
			logger.info(`${this.constructor.name}.onAddSidebarLinksStore---------->`, mutation);

			const {
				chatId: eventChatId,
				links,
			} = mutation.payload.data;

			const isEqualChatId = eventChatId === this.#chatId;

			if (isEqualChatId)
			{
				const sortedLinks = this.#convertMapToArrayAndSort(links);
				const hasNextPage = this.#store.getters['sidebarModel/sidebarLinksModel/hasNextPage'](this.#chatId);
				const hasLinks = this.state.links?.length > 0;
				this.state.hasNextPage = hasNextPage;

				if (hasLinks)
				{
					this.#addRowsToListView(mutation.payload.actionName, sortedLinks);
				}
				else
				{
					this.setState({ links: sortedLinks });
				}
			}
		}

		/**
		 * @param {SidebarLinksSetActions} actionName
		 * @param {Array<SidebarLink>} links
		 */
		async #addRowsToListView(actionName, links)
		{
			const newLinks = [];
			this.#prepareLinksForRender(links).forEach((newLink) => {
				const linksId = this.state.links.map((link) => link.id);
				const hasLinkFromList = linksId.includes(newLink.id);

				if (!hasLinkFromList)
				{
					newLinks.push(newLink);
				}
			});

			switch (actionName)
			{
				case 'setFromPagination':
					await this.#listViewRef.appendRows(newLinks, 'none');
					this.state.links.unshift(...newLinks);
					break;
				case 'set':
					await this.#listViewRef.prependRows(newLinks, 'none');
					this.state.links.push(...newLinks);
					break;
				default:
					await this.#listViewRef.prependRows(newLinks, 'none');
					this.state.links.push(...newLinks);
			}
		}

		/**
		 * @param {object} mutation
		 * @param {MutationPayload<SidebarLinksDeleteData, SidebarLinksDeleteActions>} mutation.payload
		 * @param {SidebarLinksDeleteData} mutation.payload.data
		 */
		#onDeleteSidebarLinksStore(mutation)
		{
			logger.info(`${this.constructor.name}.onDeleteSidebarLinksStore---------->`, mutation);

			const { id } = mutation.payload.data;
			const position = this.#listViewRef.getElementPosition(String(id));

			if (position)
			{
				this.#listViewRef.deleteRow(position.section, position.index, 'automatic', () => {
					const hasLastLink = this.state.links?.length === 1;

					if (hasLastLink)
					{
						this.setState({ links: [] });
					}
					else
					{
						this.state.links = this.state.links.filter((file) => file.id !== id);
					}
				});
			}
		}

		subscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.subscribeStoreEvents`);
			this.#storeManager.on('sidebarModel/sidebarLinksModel/setHistoryLimitExceeded', this.onSetHistoryLimitExceeded);
			this.#storeManager.on('sidebarModel/sidebarLinksModel/set', this.onSetSidebarLinksStore);
			this.#storeManager.on('sidebarModel/sidebarLinksModel/delete', this.onDeleteSidebarLinksStore);
		}

		unsubscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.unsubscribeStoreEvents`);
			this.#storeManager.off('sidebarModel/sidebarLinksModel/setHistoryLimitExceeded', this.onSetHistoryLimitExceeded);
			this.#storeManager.off('sidebarModel/sidebarLinksModel/set', this.onSetSidebarLinksStore);
			this.#storeManager.off('sidebarModel/sidebarLinksModel/delete', this.onDeleteSidebarLinksStore);
		}

		#getLinksFromStore()
		{
			const { links, hasNextPage } = this.#store.getters['sidebarModel/sidebarLinksModel/get'](this.#chatId);

			if (links)
			{
				const sortedLinks = this.#convertMapToArrayAndSort(links);
				this.setState({
					links: sortedLinks,
					hasNextPage,
				});
			}
		}

		/**
		 * @param {Array<SidebarLink>} links
		 */
		#prepareLinksForRender(links)
		{
			return links.map((link) => ({
				...link,
				type: SidebarTab.link,
				key: String(link.id),
			}));
		}

		/**
		 * @param {Map<linkId, SidebarLink>} map
		 * @return {Array<SidebarLink>}
		 */
		#convertMapToArrayAndSort(map)
		{
			return [...map]
				.map(([_, value]) => (value))
				.sort((a, b) => new Date(b.dateCreate).getTime() - new Date(a.dateCreate).getTime());
		}

		scrollToBegin()
		{
			this.#listViewRef?.scrollToBegin(true);
		}
	}

	module.exports = { SidebarLinksView };
});
