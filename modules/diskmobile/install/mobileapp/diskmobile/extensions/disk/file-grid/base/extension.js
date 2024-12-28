/**
 * @module disk/file-grid/base
 */
jn.define('disk/file-grid/base', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Haptics } = require('haptics');
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { Type } = require('type');
	const { isEqual, isEmpty } = require('utils/object');
	const {
		openNativeViewer,
		getNativeViewerMediaType,
		getExtension,
		getMimeType,
	} = require('utils/file');
	const { qrauth } = require('qrauth/utils');
	const { showToast } = require('toast');
	const { Icon } = require('assets/icons');

	const { Box } = require('ui-system/layout/box');
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const { AhaMoment } = require('ui-system/popups/aha-moment');

	const { SearchLayout } = require('layout/ui/search-bar');
	const { TypeGenerator } = require('layout/ui/stateful-list/type-generator');
	const { StatefulList } = require('layout/ui/stateful-list');
	const {
		DiskPull,
		createCommandExistsMiddleware,
		createCommandAllowedMiddleware,
		createResolveObjectIdMiddleware,
		createResolveActionTypeMiddleware,
		createAddObjectsOnlyFromCurrentFolderMiddleware,
		createUpdateObjectsOnlyFromCurrentFolderMiddleware,
		createUpdateObjectsOnlyFromCurrentStatefulListMiddleware,
	} = require('disk/pull');
	const { ListItemType, ListItemsFactory } = require('disk/simple-list/items');

	const { usersUpserted, usersAdded } = require('statemanager/redux/slices/users');
	const { batchActions } = require('statemanager/redux/batched-actions');
	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const { filesAddedFromServer, filesUpsertedFromServer } = require('disk/statemanager/redux/slices/files');
	const { storagesAdded, storagesUpserted } = require('disk/statemanager/redux/slices/storages');
	const { observeListChange } = require('disk/statemanager/redux/slices/files/observers/stateful-list');
	const { selectById, selectEntities, selectRightsById } = require('disk/statemanager/redux/slices/files/selector');

	const { FolderContextType, FileType } = require('disk/enum');
	const { FileGridMoreMenu, FileGridSorting, FileGridFilter } = require('disk/file-grid/navigation');
	const { CreateFolderDialog } = require('disk/dialogs/create-folder');
	const { DiskUploader } = require('disk/uploader');

	/**
	 * @abstract
	 */
	class BaseFileGrid extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			/** @type {DiskStorage|null} */
			this.storage = null;
			this.parentWidget = this.props.parentWidget ?? PageManager;

			/** @type {StatefulList|null} */
			this.stateFulListRef = null;
			this.floatingButtonPointerRef = null;
			this.markAsRemovedFiles = new Set();
			this.tabReadyEventEmitted = false;
			this.forcedGlobalSearch = false;

			this.breadcrumbs = Array.isArray(this.props.breadcrumbs) ? this.props.breadcrumbs : [];
			if (this.props.folderId)
			{
				this.breadcrumbs.push(this.props.folderId);
			}

			this.state = {
				loading: true,
				folderId: this.props.folderId ?? null,
				isASC: false,
				sortingType: FileGridSorting.types.UPDATE_TIME,
			};

			this.searchFilter = new FileGridFilter();

			this.search = new SearchLayout({
				layout: this.parentWidget,
				id: 'file-grid',
				cacheId: `disk:file-grid.${env.userId}`,
				disablePresets: true,
				presetId: this.searchFilter.getPresetId(),
				searchDataAction: 'diskmobile.Filter.getSearchBarPresets',
				searchDataActionParams: {},
				onSearch: this.onSearch,
				onCancel: this.onSearchCancel,
				getDefaultPresetId: () => this.searchFilter.getDefaultPreset(),
			});

			this.sorting = new FileGridSorting({ type: FileGridSorting.types.UPDATE_TIME, isASC: this.state.isASC });

			this.state.folderRights = this.getFolderId() ? selectRightsById(store.getState(), this.getFolderId()) : {};

			this.moreMenu = new FileGridMoreMenu(
				this.sorting.getType(),
				this.sorting.getIsASC(),
				{
					onSelectSorting: this.onSelectSorting,
					onToggleOrder: this.onToggleOrder,
					onCreateFolder: this.canUserUploadToFolder() ? this.onFloatingButtonLongClick : undefined,
					onOpenTrashcan: this.onOpenTrashcan,
				},
			);
			if (this.state.folderRights)
			{
				this.moreMenu.setCanCreateFolder(this.state.folderRights.canAdd);
			}
		}

		componentDidMount()
		{
			this.fetchStorage();
			this.unsubscribeFilesObserver = observeListChange(store, this.onVisibleFilesChange);
		}

		componentWillUnmount()
		{
			if (this.unsubscribeFilesObserver)
			{
				this.unsubscribeFilesObserver();
			}

			if (this.moreMenu.unsubscribe)
			{
				this.moreMenu.unsubscribe();
			}
		}

		/**
		 * @protected
		 * @abstract
		 * @return {string}
		 */
		getId()
		{
			return '';
		}

		/**
		 * @protected
		 * @param {string} suffix
		 * @returns {string}
		 */
		getTestId(suffix)
		{
			const prefix = this.getId();

			return suffix ? `${prefix}_${suffix}` : prefix;
		}

		/**
		 * @protected
		 * @return {string}
		 */
		getCacheId()
		{
			return `disk:file-grid.${this.getId()}.${env.userId}.${this.getFolderId()}`;
		}

		/**
		 * @abstract
		 */
		fetchStorage() {}

		/**
		 * @protected
		 * @param {DiskStorageResponse} response
		 * @param {boolean} cached
		 */
		onStorageLoaded(response, cached)
		{
			if (this.handleStorageLoadFailure(response, cached))
			{
				return;
			}

			this.storage = response.data.storage;
			dispatch(storagesUpserted([this.storage]));
			const currentFolderId = this.getFolderId() ?? response.data.storage.rootObjectId;
			const folderRights = isEmpty(this.state.folderRights) ? response.data.rootFolderRights : this.state.folderRights;

			if (folderRights)
			{
				this.moreMenu.setCanCreateFolder(folderRights.canAdd);
			}

			this.setState({
				loading: false,
				folderId: currentFolderId,
				folderRights,
			}, () => {
				if (!this.tabReadyEventEmitted)
				{
					this.tabReadyEventEmitted = true;
					BX.postComponentEvent('disk.tabs:onTabReady', [this.getId()], 'disk.tabs');
					this.onTabReady();
				}
			});
		}

		/**
		 * @protected
		 * @param {DiskStorageResponse} response
		 * @param {boolean} cached
		 * @return {boolean}
		 */
		handleStorageLoadFailure(response, cached)
		{
			if (response.errors.length > 0)
			{
				if (!cached)
				{
					Alert.alert(
						Loc.getMessage('M_DISK_STORAGE_LOAD_ERROR_TITLE'),
						Loc.getMessage('M_DISK_STORAGE_LOAD_ERROR_TEXT'),
						() => {
							this.props.onStorageLoadFailure?.(response, this);
						},
						Loc.getMessage('M_DISK_COMMON_ERROR_OK'),
					);
				}

				return true;
			}

			return false;
		}

		/**
		 * @protected
		 */
		onTabReady()
		{}

		/**
		 * @protected
		 * @returns {number|null}
		 */
		getFolderId()
		{
			return this.state.folderId;
		}

		/**
		 * @protected
		 * @returns {number|null}
		 */
		getStorageId()
		{
			return this.storage?.id || null;
		}

		/**
		 * @protected
		 * @returns {boolean}
		 */
		isLoading()
		{
			return this.state.loading;
		}

		/**
		 * @protected
		 * @returns {boolean}
		 */
		isReady()
		{
			return !this.isLoading();
		}

		/**
		 * @protected
		 * @return {boolean}
		 */
		isSearching()
		{
			return !this.searchFilter.isEmpty();
		}

		/**
		 * @protected
		 * @returns {boolean}
		 */
		isRootFolder()
		{
			const currentFolder = this.getFolderId();
			const storageRoot = this.storage?.rootObjectId;

			if (currentFolder && storageRoot)
			{
				return currentFolder === storageRoot;
			}

			return true;
		}

		/**
		 * @protected
		 * @return {boolean}
		 */
		isCollabFolder()
		{
			const folder = selectById(store.getState(), this.getFolderId());

			return (folder?.folderContextType === FolderContextType.COLLAB);
		}

		/**
		 * @protected
		 * @return {boolean}
		 */
		showFolders()
		{
			return true;
		}

		/**
		 * @protected
		 * @return {boolean}
		 */
		isShowFloatingButton()
		{
			return true;
		}

		/**
		 * @protected
		 * @return {boolean|undefined}
		 */
		isFloatingButtonAccent()
		{}

		/**
		 * @protected
		 * @param {object} options
		 * @param {string} options.description
		 * @param {function} [options.onHide]
		 * @param {number} [options.delay=0]
		 */
		displayFloatingButtonAhaMoment({ description, onHide, delay = 0 })
		{
			setTimeout(() => {
				if (this.floatingButtonPointerRef)
				{
					AhaMoment.show({
						description,
						testId: this.getTestId('floating-button-aha-moment'),
						targetRef: this.floatingButtonPointerRef,
						closeButton: false,
						fadeInDuration: 300,
						onHide,
					});
				}
			}, delay);
		}

		showStorageName()
		{
			return false;
		}

		/**
		 * @protected
		 */
		setSorting(sortingType)
		{
			if (this.sorting.getType() !== sortingType)
			{
				this.sorting.setType(sortingType);
				this.moreMenu.setSelectedSorting(sortingType);
				this.setState({ sortingType }, this.reload);
			}
		}

		/**
		 * @protected
		 */
		setOrder(isASC)
		{
			this.sorting.setIsASC(isASC);
			this.setState({ isASC }, this.reload);
		}

		getListActions()
		{
			return {
				loadItems: 'diskmobile.Folder.getChildren',
			};
		}

		getListActionParams()
		{
			return {
				loadItems: {
					id: this.getFolderId(),
					search: this.getSearchParams().searchString,
					searchContext: this.getSearchContext(),
					sortingType: this.state.sortingType,
					order: {
						[this.state.sortingType]: this.state.isASC ? 'ASC' : 'DESC',
					},
					context: this.props.context || {
						storageId: this.getStorageId(),
					},
					showRights: true,
				},
			};
		}

		/**
		 * @protected
		 * @return {{ type: string, entities: string[], folderId: number|null } | null}
		 */
		getSearchContext()
		{
			return null;
		}

		/**
		 * @protected
		 */
		forceGlobalSearch()
		{
			this.forcedGlobalSearch = true;

			this.forceReload();
		}

		render()
		{
			return Box(
				{
					backgroundColor: Color.bgPrimary,
					resizableByKeyboard: true,
				},
				View(
					{
						style: {
							flex: 1,
						},
					},
					this.isLoading() && this.renderLoadingScreen(),
					this.isReady() && this.renderList(),
					this.renderFloatingButtonPointer(),
				),
			);
		}

		renderLoadingScreen()
		{
			return new LoadingScreenComponent({
				showAirStyle: true,
			});
		}

		renderList()
		{
			return new StatefulList({
				testId: 'disk-list',
				cacheName: this.getCacheId(),
				layout: this.parentWidget,
				typeGenerator: {
					generator: TypeGenerator.generators.bySelectedProperties,
					properties: [
						'name',
					],
					callbacks: {},
				},
				showAirStyle: true,
				menuButtons: this.getLayoutMenuButtons(),
				onPanListHandler: this.onPanList,
				onListReloaded: this.onListReloaded,
				ref: this.onListRef,
				sortingConfig: this.sorting.getSortingConfig(),
				itemType: ListItemType.FILE,
				itemFactory: ListItemsFactory,
				actions: this.getListActions(),
				actionParams: this.getListActionParams(),
				actionCallbacks: {
					loadItems: this.onItemsLoaded,
				},
				pull: {
					moduleId: 'disk',
					callback: this.onPullCallback,
					shouldReloadDynamically: true,
				},
				onBeforeItemsRender: this.onBeforeItemsRender,
				onBeforeItemsSetState: this.onBeforeItemsSetState,
				isShowFloatingButton: this.isShowFloatingButton(),
				isFloatingButtonAccent: this.isFloatingButtonAccent(),
				onFloatingButtonClick: this.onFloatingButtonClick,
				onFloatingButtonLongClick: this.onFloatingButtonLongClick,
				itemDetailOpenHandler: this.onItemPress,
				needInitMenu: true,
				getEmptyListComponent: this.getEmptyListComponent,
			});
		}

		renderFloatingButtonPointer()
		{
			return View(
				{
					style: {
						height: 1,
						width: 1,
						opacity: 0,
						position: 'absolute',
						bottom: 75,
						right: 52,
					},
					ref: (ref) => {
						this.floatingButtonPointerRef = ref;
					},
				},
			);
		}

		onSelectSorting = (sortingType) => {
			this.setSorting(sortingType);
		};

		onToggleOrder = (isASC) => {
			this.setOrder(isASC);
		};

		onOpenTrashcan = () => {
			qrauth.open({
				redirectUrl: this.getTrashWebUrl(),
				showHint: true,
				title: Loc.getMessage('M_DISK_FILE_GRID_TRASHCAN_TITLE'),
				layout: this.parentWidget,
				analyticsSection: 'files',
			}).catch((e) => console.error(e));
		};

		/**
		 * @protected
		 * @return {string}
		 */
		getTrashWebUrl()
		{
			return `${env.siteDir}${env.extranet ? 'contacts' : 'company'}/personal/user/${env.userId}/disk/trashcan/`;
		}

		onPanList = () => {
			this.search.close();
		};

		onListRef = (ref) => {
			if (ref)
			{
				this.stateFulListRef = ref;
			}
		};

		onSearch = ({ text, presetId }) => {
			this.searchFilter.setPresetId(presetId);
			this.searchFilter.setSearchString(text);

			this.setState({}, this.reload);
		};

		onSearchCancel = ({ text, presetId }) => {
			this.searchFilter.setPresetId(presetId);
			this.searchFilter.setSearchString(text);

			if (this.forcedGlobalSearch)
			{
				this.forcedGlobalSearch = false;

				this.forceReload();
			}
			else
			{
				this.setState({}, this.reload);
			}
		};

		getSearchParams()
		{
			return {
				presetId: this.searchFilter.getPresetId(),
				searchString: this.searchFilter.getSearchString(),
			};
		}

		getLayoutMenuButtons()
		{
			return [
				this.search.getSearchButton(),
				this.moreMenu.getMenuButton(),
			];
		}

		onItemsLoaded = (responseData, context) => {
			const { items, users, storages, currentFolderRights } = responseData || {};
			const isCache = context === 'cache';

			const actions = [];

			if (items && items.length > 0)
			{
				actions.push(isCache ? filesAddedFromServer(items) : filesUpsertedFromServer(items));
			}

			if (users && users.length > 0)
			{
				actions.push(isCache ? usersAdded(users) : usersUpserted(users));
			}

			if (storages && storages.length > 0)
			{
				actions.push(isCache ? storagesAdded(storages) : storagesUpserted(storages));
			}

			if (actions.length > 0)
			{
				dispatch(batchActions(actions));
			}

			if (
				!this.state?.folderRights
				|| (!isEmpty(currentFolderRights) && !isEqual(this.state.folderRights, currentFolderRights))
			)
			{
				this.setState({ folderRights: currentFolderRights });
				this.moreMenu.setCanCreateFolder(this.state.folderRights?.canAdd);
			}
		};

		getItemIds()
		{
			return (this.stateFulListRef?.getItems() ?? []).map((item) => item.id);
		}

		/**
		 * @protected
		 * @see disk/pull
		 * @return {string[]}
		 */
		getAllowedPullCommands()
		{
			return [
				DiskPull.Command.OBJECT_ADDED,
				DiskPull.Command.OBJECT_RENAMED,
				DiskPull.Command.CONTENT_UPDATED,
				DiskPull.Command.OBJECT_MARK_DELETED,
			];
		}

		/**
		 * @protected
		 * @return {array<function():Promise>}
		 */
		getPullCommandProcessors()
		{
			return [];
		}

		onPullCallback = (data) => {
			const commands = this.getAllowedPullCommands();

			const processors = [
				...this.getPullCommandProcessors(),
				createCommandExistsMiddleware(),
				createCommandAllowedMiddleware(commands),
				createResolveObjectIdMiddleware(),
				createResolveActionTypeMiddleware(),
				createAddObjectsOnlyFromCurrentFolderMiddleware(this.getFolderId()),
			];

			if (this.getFolderId())
			{
				processors.push(createUpdateObjectsOnlyFromCurrentFolderMiddleware(this.getFolderId()));
			}
			else
			{
				processors.push(createUpdateObjectsOnlyFromCurrentStatefulListMiddleware(this.getItemIds()));
			}

			return processors.reduce((prev, process) => prev.then((message) => process(message)), Promise.resolve(data))
				.then((message) => {
					const { objectId, actionType } = message;

					return {
						params: {
							eventName: actionType,
							items: [{ id: objectId }],
						},
					};
				});
		};

		onBeforeItemsSetState = (items, params) => {
			const fileEntities = selectEntities(store.getState());

			items.map((item) => {
				const preparedItem = item;
				if (typeof preparedItem.updateTime === 'string')
				{
					preparedItem.updateTime = (new Date(item.updateTime)).getTime() / 1000;
				}

				if (typeof preparedItem.createTime === 'string')
				{
					preparedItem.createTime = (new Date(item.createTime)).getTime() / 1000;
				}

				return preparedItem;
			});

			return items.filter(({ id }) => {
				const { isRemoved } = fileEntities[id] || {};

				return Type.isNil(isRemoved) || !isRemoved;
			});
		};

		onBeforeItemsRender = (items) => {
			return items.map((item, index) => ({
				...item,
				showBorder: index !== items.length - 1,
				order: {
					[this.state.sortingType]: this.state.isASC ? 'ASC' : 'DESC',
				},
				context: {
					storageId: this.getStorageId(),
				},
				parentWidget: this.parentWidget,
				showStorageName: this.showStorageName(),
			}));
		};

		onItemPress = (id) => {
			const item = selectById(store.getState(), id);
			if (item.isFolder)
			{
				this.openFolder(item);

				return;
			}

			if (item.typeFile === FileType.IMAGE)
			{
				openNativeViewer({
					fileType: 'image',
					url: item.links.download,
					name: item.name,
					images: this.getImagesForNativeViewer(item.id),
				});

				return;
			}

			openNativeViewer({
				fileType: getNativeViewerMediaType(getMimeType(getExtension(item.name), item.name)),
				url: item.links.download,
				name: item.name,
			});
		};

		getImagesForNativeViewer(defaultId)
		{
			const images = this.stateFulListRef?.getItems().filter((item) => item.typeFile === FileType.IMAGE);

			return images.map((image) => {
				return {
					url: image.links.download,
					default: image.id === defaultId,
				};
			});
		}

		onListReloaded = (pullToReload) => {
			if (!pullToReload)
			{
				this.markAsRemovedFiles?.clear();
			}
		};

		/**
		 * @abstract
		 * @param {Object} folder
		 * @param {number} folder.id
		 * @param {string} folder.name
		 */
		openFolder = (folder) => {
			const { parentWidget, groupId } = this.props;
			if (parentWidget)
			{
				parentWidget.openWidget(
					'layout',
					{
						title: folder.name,
						onReady: (layoutWidget) => {
							layoutWidget.showComponent(new this.constructor({
								storageId: this.getStorageId(),
								folderId: folder.id,
								parentWidget: layoutWidget,
								breadcrumbs: [...this.breadcrumbs],
								groupId,
							}));
						},
						onError: (error) => console.error(error),
					},
				);
			}
		};

		/**
		 * @return {StatusBlock}
		 */
		getEmptyListComponent = () => {
			const { imageUri, title, description, buttons } = this.getEmptyListComponentProps();

			const imageParams = {
				resizeMode: 'contain',
				style: {
					width: 339,
					height: 162,
				},
				svg: { uri: imageUri },
			};

			return StatusBlock({
				title,
				description,
				buttons,
				emptyScreen: true,
				image: Image(imageParams),
				onRefresh: this.onEmptyScreenPullToRefresh,
				testId: this.getTestId('EmptyScreen'),
			});
		};

		/**
		 * @abstract
		 * @return {{ imageUri: string, title: string, description: string|undefined, buttons: []|undefined }}
		 */
		getEmptyListComponentProps = () => {};

		onEmptyScreenPullToRefresh = () => {
			this.reload();
		};

		onFloatingButtonClick = () => {
			if (this.ensureUserCanUploadToFolder())
			{
				this.openUploaderDialog();
			}
		};

		onFloatingButtonLongClick = () => {
			if (this.ensureUserCanUploadToFolder())
			{
				this.openCreateFolderDialog();
			}
		};

		canUserUploadToFolder()
		{
			const preventByCollaber = this.currentUserIsCollaber() && !this.isCollabFolder();
			const preventByPermissions = this.state.folderRights?.canAdd === false;

			// eslint-disable-next-line sonarjs/prefer-single-boolean-return
			if (preventByCollaber || preventByPermissions)
			{
				return false;
			}

			return true;
		}

		ensureUserCanUploadToFolder()
		{
			if (!this.canUserUploadToFolder())
			{
				Haptics.notifyWarning();

				showToast({
					message: Loc.getMessage('M_DISK_UPLOAD_IS_POSSIBLE_ONLY_TO_COLLAB_DIR'),
					iconName: Icon.FOLDER_SUCCESS.getIconName(),
				}, this.parentWidget);

				return false;
			}

			return true;
		}

		currentUserIsCollaber()
		{
			return env.isCollaber;
		}

		openCreateFolderDialog = () => {
			void CreateFolderDialog.open({
				parentFolderId: this.getFolderId(),
				onCreate: () => {
					this.sendCreateFolderAnalytics();
					this.reload();
				},
			}, this.parentWidget);
		};

		openUploaderDialog = () => {
			DiskUploader.open({
				onCommit: (files) => {
					this.sendUploadFileAnalytics(files);
					this.reload();
				},
				folderId: this.getFolderId(),
				storageId: this.getStorageId(),
				layoutWidget: this.parentWidget,
			});
		};

		reload = () => {
			this.stateFulListRef?.reload();
		};

		forceReload = () => {
			const initialStateParams = {
				skipItems: true,
				force: true,
				actionParams: this.getListActionParams(),
				menuButtons: this.getLayoutMenuButtons(),
			};
			const loadItemsParams = {
				useCache: false,
			};

			this.stateFulListRef?.reload(initialStateParams, loadItemsParams);
		};

		onVisibleFilesChange = ({ moved, removed, added, created }) => {
			if (!this.stateFulListRef || this.stateFulListRef.isLoading())
			{
				// delay until list is loaded to prevent race-condition with addItems loading
				setTimeout(() => {
					this.onVisibleFilesChange({ moved, removed, added, created });
				}, 30);

				return;
			}

			const filterCurrentFolderFiles = (files) => files.filter(({ parentId }) => parentId === this.getFolderId());

			const filterFiles = (files) => files.filter(({ typeFile }) => {
				if (!this.showFolders())
				{
					return Boolean(typeFile);
				}

				return true;
			});

			if (removed.length > 0)
			{
				void this.removeFiles(removed);
			}

			if (added.length > 0)
			{
				void this.addOrRestoreFiles(filterCurrentFolderFiles(filterFiles(added)));
			}

			if (moved.length > 0)
			{
				void this.updateFiles(filterCurrentFolderFiles(filterFiles(moved)));
			}

			if (created.length > 0)
			{
				void this.replaceFiles(created);
			}
		};

		removeFiles(files)
		{
			if (files.length > 0)
			{
				const removedIds = files.map(({ id }) => id);
				const markAsRemovedFilesIds = files.filter((file) => file.isRemoved).map(({ id }) => id);

				if (markAsRemovedFilesIds.length > 0)
				{
					markAsRemovedFilesIds.forEach((id) => this.markAsRemovedFiles.add(id));
				}

				return this.stateFulListRef.deleteItem(removedIds);
			}

			return Promise.resolve();
		}

		async addOrRestoreFiles(files)
		{
			const restoredFiles = [];
			const addedFiles = [];

			files.forEach((file) => {
				if (this.markAsRemovedFiles.has(file.id))
				{
					restoredFiles.push(file);
				}
				else if (!this.stateFulListRef.hasItem(file.id))
				{
					addedFiles.push(file);
				}
			});

			if (restoredFiles.length > 0)
			{
				await this.stateFulListRef.updateItemsData(restoredFiles);
			}

			if (addedFiles.length > 0)
			{
				await this.stateFulListRef.updateItemsData(addedFiles);
			}

			files.forEach(({ id }) => this.markAsRemovedFiles.delete(id));
		}

		updateFiles(moved)
		{
			return setTimeout(() => this.stateFulListRef.updateItemsData(moved), 500);
		}

		replaceFiles(created) {}

		sendCreateFolderAnalytics() {}

		sendUploadFileAnalytics(files) {}
	}

	module.exports = { BaseFileGrid };
});
