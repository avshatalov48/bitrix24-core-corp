/**
 * @module im/messenger/controller/sidebar/chat/tabs/files/view
 */
jn.define('im/messenger/controller/sidebar/chat/tabs/files/view', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--files-view');
	const { withPressed } = require('utils/color');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { SidebarFilesItem } = require('im/messenger/controller/sidebar/chat/tabs/files/item');
	const { SidebarFilesService } = require('im/messenger/controller/sidebar/chat/tabs/files/service');
	const { Theme } = require('im/lib/theme');
	const { LoaderItem } = require('im/messenger/lib/ui/base/loader');
	const { SidebarFileType } = require('im/messenger/const');
	const { icon } = require('im/messenger/controller/sidebar/lib/assets/icons');
	const { SidebarTab } = require('im/messenger/const');
	const { BaseSidebarTabView } = require('im/messenger/controller/sidebar/chat/tabs/base/view');

	/**
	 * @class SidebarFilesView
	 * @typedef {LayoutComponent<SidebarFilesViewProps, SidebarFilesViewState>} SidebarFilesView
	 */
	class SidebarFilesView extends BaseSidebarTabView
	{
		#core;
		#store;
		#storeManager;
		#chatId;
		#filesService;
		#listViewRef;

		constructor(props)
		{
			super(props);

			this.#core = serviceLocator.get('core');
			this.#store = this.#core.getStore();
			this.#storeManager = this.#core.getStoreManager();
			const dialog = this.#store.getters['dialoguesModel/getById'](props.dialogId);
			this.#chatId = dialog?.chatId;
			this.#filesService = new SidebarFilesService(this.#chatId);
			this.isHistoryLimitExceeded = this.#store.getters['sidebarModel/sidebarFilesModel/isHistoryLimitExceeded'](this.#chatId, SidebarFileType.document);

			this.state = {
				files: null,
				hasNextPage: true,
			};

			this.loader = new LoaderItem({
				enable: false,
				text: '',
			});
		}

		componentDidMount()
		{
			super.componentDidMount();
			this.#updateFilesFromStore();
		}

		render()
		{
			logger.log(`${this.constructor.name}.render`);

			const { files } = this.state;
			const isEmptyState = Type.isArray(files) && !Type.isArrayFilled(files);

			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						alignItems: 'center',
						minWidth: '100%',
						backgroundColor: Theme.colors.bgContentPrimary,
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
							paddingHorizontal: 24,
							marginBottom: 10,
						},
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_FILES_EMPTY_STATE_TITLE'),
					}),
					Text({
						style: {
							color: Theme.colors.base2,
							fontSize: 16,
							fontWeight: 400,
							textAlign: 'center',
							paddingHorizontal: 24,
						},
						text: Loc.getMessage(
							'IMMOBILE_DIALOG_SIDEBAR_FILES_EMPTY_STATE_SUBTITLE',
							{ '#BR#': '\n' },
						),
					}),
				),
			);
		}

		#renderListView()
		{
			const files = this.state.files ? this.#prepareFilesForRender(this.state.files) : [];
			logger.log(`${this.constructor.name}.renderListView`, files);

			return ListView({
				ref: (ref) => {
					this.#listViewRef = ref;
				},
				style: {
					flex: 1,
					flexDirection: 'column',
				},
				data: [{ items: files }],
				renderItem: (file) => {
					return View(
						{
							style: {
								paddingTop: 12,
								paddingHorizontal: 14,
								backgroundColor: withPressed(Theme.colors.bgContentPrimary),
							},
						},
						this.#renderFile(file),
						this.#renderSeparator(),
					);
				},
				onLoadMore: this.#onLoadScrollItems.bind(this),
				renderLoadMore: this.#renderLoadMore.bind(this),
			});
		}

		/**
		 * @param {SidebarFile} file
		 */
		#renderFile(file)
		{
			const data = { ...file, dialogId: this.props.dialogId };
			logger.log(`${this.constructor.name}.renderFile`, data);

			return new SidebarFilesItem(data);
		}

		#renderSeparator()
		{
			return View(
				{
					style: {
						marginTop: 8,
						alignSelf: 'flex-end',
						width: '81%',
						height: 1,
						backgroundColor: Theme.colors.bgSeparatorSecondary,
					},
				},
			);
		}

		/**
		 * @desc Handler load more event by scroll down ( staring rest call files with files )
		 * @void
		 */
		#onLoadScrollItems()
		{
			const { files, hasNextPage } = this.state;

			if (this.isHistoryLimitExceeded || !hasNextPage)
			{
				return;
			}

			const lastId = files?.length > 0 ? files[files.length - 1].id : null;

			this.loader.enable();
			this.#filesService.loadNextPage(lastId)
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
			this.onSetSidebarFilesStore = this.#onSetSidebarFilesStore.bind(this);
			this.onDeleteSidebarFilesStore = this.#onDeleteSidebarFilesStore.bind(this);
		}

		/**
		 * @param {object} mutation
		 * @param {SidebarFilesSetHistoryLimitExceededData} mutation.payload.data
		 */
		#onSetHistoryLimitExceeded(mutation)
		{
			logger.info(`${this.constructor.name}.onSetHistoryLimitExceeded---------->`, mutation);

			const {
				chatId: eventChatId,
				isHistoryLimitExceeded,
				subType,
			} = mutation.payload.data;

			const isEqualChatId = eventChatId === this.#chatId;
			const isEqualType = subType === SidebarFileType.document;

			if (isEqualChatId && isEqualType)
			{
				this.isHistoryLimitExceeded = isHistoryLimitExceeded;
			}
		}

		/**
		 * @param {object} mutation
		 * @param {object} mutation.payload
		 * @param {SidebarFilesSetData} mutation.payload.data
		 */
		#onSetSidebarFilesStore(mutation)
		{
			logger.info(`${this.constructor.name}.onAddSidebarFilesStore---------->`, mutation);

			const {
				chatId: eventChatId,
				files,
				subType,
			} = mutation.payload.data;

			const isEqualChatId = eventChatId === this.#chatId;
			const isEqualSubType = subType === SidebarFileType.document;

			if (isEqualChatId && isEqualSubType)
			{
				const sortedFiles = this.#convertMapToArrayAndSort(files);
				const hasNextPage = this.#store.getters['sidebarModel/sidebarFilesModel/hasNextPage'](this.#chatId, SidebarFileType.document);
				const hasFiles = this.state.files?.length > 0;
				this.state.hasNextPage = hasNextPage;

				if (hasFiles)
				{
					this.#addRowsToListView(mutation.payload.actionName, sortedFiles);
				}
				else
				{
					this.setState({ files: sortedFiles });
				}
			}
		}

		/**
		 * @param {SidebarFilesSetActions} actionName
		 * @param {Array<SidebarFile>} files
		 */
		async #addRowsToListView(actionName, files)
		{
			const newFiles = [];
			this.#prepareFilesForRender(files).forEach((newFile) => {
				const filesId = this.state.files.map((file) => file.id);
				const hasFileFromList = filesId.includes(newFile.id);

				if (!hasFileFromList)
				{
					newFiles.push(newFile);
				}
			});

			switch (actionName)
			{
				case 'setFromPagination':
					await this.#listViewRef.appendRows(newFiles, 'none');
					this.state.files.unshift(...newFiles);
					break;
				case 'set':
					await this.#listViewRef.prependRows(newFiles, 'none');
					this.state.files.push(...newFiles);
					break;
				default:
					await this.#listViewRef.prependRows(newFiles, 'none');
					this.state.files.push(...newFiles);
			}
		}

		/**
		 * @param {object} mutation
		 * @param {object} mutation.payload
		 * @param {SidebarFilesDeleteData} mutation.payload.data
		 */
		#onDeleteSidebarFilesStore(mutation)
		{
			logger.info(`${this.constructor.name}.onDeleteSidebarFilesStore---------->`, mutation);

			const { id } = mutation.payload.data;
			const position = this.#listViewRef.getElementPosition(String(id));

			if (position)
			{
				this.#listViewRef.deleteRow(position.section, position.index, 'automatic', () => {
					const hasLastFile = this.state.files?.length === 1;

					if (hasLastFile)
					{
						this.setState({ files: [] });
					}
					else
					{
						this.state.files = this.state.files.filter((file) => file.id !== id);
					}
				});
			}
		}

		subscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.subscribeStoreEvents`);
			this.#storeManager.on('sidebarModel/sidebarFilesModel/setHistoryLimitExceeded', this.onSetHistoryLimitExceeded);
			this.#storeManager.on('sidebarModel/sidebarFilesModel/set', this.onSetSidebarFilesStore);
			this.#storeManager.on('sidebarModel/sidebarFilesModel/delete', this.onDeleteSidebarFilesStore);
		}

		unsubscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.unsubscribeStoreEvents`);
			this.#storeManager.off('sidebarModel/sidebarFilesModel/setHistoryLimitExceeded', this.onSetHistoryLimitExceeded);
			this.#storeManager.off('sidebarModel/sidebarFilesModel/set', this.onSetSidebarFilesStore);
			this.#storeManager.off('sidebarModel/sidebarFilesModel/delete', this.onDeleteSidebarFilesStore);
		}

		#updateFilesFromStore()
		{
			const { items, hasNextPage } = this.#store.getters['sidebarModel/sidebarFilesModel/get'](this.#chatId, SidebarFileType.document);

			if (items)
			{
				const sortedFiles = this.#convertMapToArrayAndSort(items);
				this.setState({
					files: sortedFiles,
					hasNextPage,
				});
			}
		}

		/**
		 * @param {Array<SidebarFile>} files
		 */
		#prepareFilesForRender(files)
		{
			return files.map((file) => ({
				...file,
				type: SidebarTab.document,
				key: String(file.id),
			}));
		}

		/**
		 * @param {Map<number, SidebarFile>} map
		 * @return {Array<SidebarFile>}
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

	module.exports = { SidebarFilesView };
});
