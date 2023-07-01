(() => {

	const require = (ext) => jn.require(ext);

	const { Loc } = require('loc');
	const { RefsContainer } = require('layout/ui/kanban/refs-container');
	const {
		clone,
		merge,
		mergeImmutable,
		isEqual,
	} = require('utils/object');
	const { useCallback } = require('utils/function');
	const { PureComponent } = require('layout/pure-component');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { Type } = require('type');

	let RequiredFields;
	let CategoryStorage;

	try
	{
		RequiredFields = require('crm/required-fields').RequiredFields;
		CategoryStorage = require('crm/storage/category').CategoryStorage;
	}
	catch (e)
	{
		console.warn(e);
	}

	const animationTypes = {
		default: 'fade',
		left: 'left',
		right: 'right',
		top: 'top',
	};

	const UPDATE_ACTION = 'update';
	const CREATE_ACTION = 'create';
	const EXCLUDE_ACTION = 'excludeEntity';
	const DELETE_ACTION = 'deleteEntity';

	/**
	 * @class UI.Kanban
	 * @property { ToolbarFactory } props.toolbarFactory
	 */
	class Kanban extends PureComponent
	{
		constructor(props)
		{
			super(props);

			/** @type RefsContainer */
			this.refsContainer = new RefsContainer();

			this.init(props);

			this.props.layout.on('onViewShown', () => {
				if (this.needExecuteOnNotViewableHandler)
				{
					this.needExecuteOnNotViewableHandler = false;
					this.props.onNotViewableHandler();
				}
			});

			this.getRuntimeParams = this.getRuntimeParamsHandler.bind(this);
			this.changeColumn = this.changeColumnHandler.bind(this);
			this.reloadCurrentColumn = this.reloadCurrentColumnHandler.bind(this);
			this.changeItemStageHandler = this.changeItemStage.bind(this);
			this.reloadListCallbackHandler = this.initCounters.bind(this);
			this.blinkItemHandler = this.blinkItem.bind(this);
			this.onUpdateItemHandler = this.onUpdateItem.bind(this);
			this.onCreateItemHandler = this.onCreateItem.bind(this);
			this.onAccessDeniedItemHandler = this.onAccessDeniedItem.bind(this);
		}

		initSlides(props = null)
		{
			this.slides = new Map([
				[this.getSlideName('', props), this.getPreparedActionParams(props)],
			]);
		}

		componentDidMount()
		{
			BX.addCustomEvent('DetailCard::onUpdate', this.onUpdateItemHandler);
			BX.addCustomEvent('DetailCard::onCreate', this.onCreateItemHandler);
			BX.addCustomEvent('DetailCard::onAccessDenied', this.onAccessDeniedItemHandler);

			CategoryStorage && CategoryStorage
				.subscribeOnChange(() => this.fillSlidesOrReload())
				.markReady()
			;
		}

		fillSlidesOrReload()
		{
			if (this.slides.size === this.getColumnsFromCurrentCategory().size + 1)
			{
				this.fillSlides();
			}
			else
			{
				this.reload(this.getCurrentSlideName());
			}
		}

		componentWillUnmount()
		{
			BX.removeCustomEvent('DetailCard::onUpdate', this.onUpdateItemHandler);
			BX.removeCustomEvent('DetailCard::onCreate', this.onCreateItemHandler);
			BX.removeCustomEvent('DetailCard::onAccessDenied', this.onAccessDeniedItemHandler);
		}

		onUpdateItem(uid, params)
		{
			this.animateItemAfterBackFromDetail(params.actionName || UPDATE_ACTION, params);
		}

		onCreateItem(uid, params)
		{
			this.animateItemAfterBackFromDetail(CREATE_ACTION, params);
		}

		animateItemAfterBackFromDetail(action, params)
		{
			const { entityTypeId, onBeforeReload, getMenuButtons } = this.props;
			const owner = BX.prop.getObject(params, 'owner', {});
			const statefulList = this.getCurrentStatefulList();
			const isBackFromSlaveEntityType = (owner.id && statefulList.hasItem(owner.id));

			if (entityTypeId === params.entityTypeId || isBackFromSlaveEntityType)
			{
				const data = onBeforeReload ? onBeforeReload() : {};
				const id = isBackFromSlaveEntityType ? owner.id : params.entityId;
				const currentColumnId = this.getCurrentColumnId();

				if (
					!data.reload
					&& entityTypeId === params.entityTypeId
					&& (
						action === EXCLUDE_ACTION
						|| action === DELETE_ACTION
						|| (currentColumnId && currentColumnId !== params.entityModel.STAGE_ID)
						|| this.itemCategoryIsChanged(params)
					)
				)
				{
					void statefulList.deleteItem(id);

					return;
				}

				if (!data.reload && action === UPDATE_ACTION)
				{
					void statefulList.updateItems([id], BX.prop.getBoolean(data, 'animate', true), false, false);

					return;
				}

				statefulList.addToAnimateIds(id);

				const reloadParams = {};
				if (getMenuButtons)
				{
					reloadParams.menuButtons = getMenuButtons();
				}
				this.reload(this.currentSlideName, true, reloadParams);
			}
		}

		itemCategoryIsChanged(params)
		{
			const category = this.getCurrentCategory();

			if (!category || !category.categoriesEnabled)
			{
				return false;
			}

			const { filterParams } = this.props;

			return (
				Type.isNumber(filterParams.CATEGORY_ID)
				&& params.categoryId !== filterParams.CATEGORY_ID
			);
		}

		onAccessDeniedItem(uid, params)
		{
			if (this.props.entityTypeId === params.entityTypeId)
			{
				this.needExecuteOnNotViewableHandler = true;
			}
		}

		componentWillReceiveProps(newProps)
		{
			this.actions = newProps.actions;
			this.slidePage = 0;

			if (this.props.entityTypeName !== newProps.entityTypeName)
			{
				this.refsContainer.statefulLists = new Map();
				this.refsContainer.toolbar = null;

				this.init(newProps);
			}

			this.slides.forEach(slide => {
				const presetId = BX.prop.getString(newProps.actionParams.loadItems.extra.filterParams, 'FILTER_PRESET_ID', null);
				if (presetId)
				{
					slide.loadItems.extra.filterParams.FILTER_PRESET_ID = presetId;
				}
				return slide;
			});
		}

		init(props)
		{
			this.actions = props.actions || {};
			this.currentSlideName = null;
			this.columns = new Map();
			this.needExecuteOnNotViewableHandler = false;
			this.slidePage = 0;
			this.filter = null;

			this.state = {
				currentColumnId: null,
			};

			this.initSlides(props);
		}

		/**
		 * @param {number} newColumnId
		 */
		changeColumnHandler(newColumnId)
		{
			if (newColumnId === null)
			{
				return;
			}

			let slideName;
			if (newColumnId === 0)
			{
				slideName = this.getSlideName();
			}
			else
			{
				const newColumn = this.getColumnById(newColumnId);

				if (newColumn === undefined)
				{
					const columns = this.getColumnsFromCurrentCategory();
					for (const column of columns.values())
					{
						if (column.id === newColumnId)
						{
							const slideName = this.getSlideNameByColumn(column);
							this.reload(slideName, true);
							break;
						}
					}

					return;
				}

				slideName = this.getSlideNameByColumn(newColumn);
			}

			const reloadParams = {
				updateToolbarColumnId: true,
				force: false,
			};

			this.reloadStatefulList(slideName, reloadParams, () => {
				const page = Array.from(this.slides.keys()).indexOf(slideName);
				this.scrollToPage(page);
			});
		}

		reloadCurrentColumnHandler()
		{
			this.reload(this.getCurrentSlideName());
		}

		/**
		 * @param {Number|null} columnId
		 * @returns {Object|null}
		 */
		getColumnById(columnId)
		{
			if (columnId === null)
			{
				return null;
			}

			const columns = Array.from(this.columns.values());
			return columns.find(column => Number(column.id) === columnId);
		}

		/**
		 * @param {Number} itemId
		 * @param {String} columnName
		 */
		updateItemColumn(itemId, columnName)
		{
			const column = this.getColumnByName(columnName);
			const item = this.getCurrentStatefulList().getItemComponent(itemId);
			item.updateColumnId(column.id);
		}

		getCurrentColumnId()
		{
			return this.getColumnIdByName(this.getColumnStatusIdFromSlideName(this.getCurrentSlideName()));
		}

		/**
		 * @param {String} columnName
		 * @returns {null|Number}
		 */
		getColumnIdByName(columnName)
		{
			const column = this.getColumnByName(columnName);
			if (column)
			{
				return column.id;
			}

			return null;
		}

		/**
		 * @param {String} columnName
		 * @returns {Object|undefined}
		 */
		getColumnByName(columnName)
		{
			return this.columns.get(columnName);
		}

		/**
		 * @param {Number} page
		 */
		scrollToPage(page)
		{
			if (this.slidePage !== page)
			{
				this.slidePage = page;
				this.refsContainer.getSlider().scrollToPage(page);
			}
		}

		/**
		 * @param {Number} stageId
		 * @param {Object} category
		 * @param {Object|null} data
		 * @returns {Promise}
		 */
		changeItemStage(stageId, category, { itemId })
		{
			return this.moveItem(itemId, stageId);
		}

		/**
		 * @param {Number} itemId
		 * @param {Number} columnId
		 * @returns {Promise}
		 */
		moveItem(itemId, columnId)
		{
			const actionParams = this.getPreparedActionParams();
			const oldItem = clone(this.getCurrentStatefulList().getItem(itemId));
			const { entityTypeId, entityTypeName: entityType } = this.props;
			const requiredParams = { entityId: itemId, entityTypeId };
			return new Promise((resolve, reject) => {
				if (!this.getColumnById(columnId))
				{
					this.fillSlides();
				}

				this.setLoadingOfItem(itemId);

				BX.ajax.runAction(this.actions.updateItemStage, {
					data: {
						id: itemId,
						stageId: columnId,
						entityType,
						extra: actionParams.loadItems.extra || {},
					},
				}).then((response) => {
					if (response.errors.length)
					{
						const action = this.errorProcessing(response.errors, requiredParams) ? resolve : reject;
						action(columnId);
					}

					let resolveParams = { columnId };

					const item = this.getCurrentStatefulList().getItem(itemId);
					const oldColumnStatusId = item.data.columnId;
					const column = this.getColumnById(columnId);
					item.data.columnId = column.statusId;

					if (this.slidePage)
					{
						const animationType = this.getAnimationType(oldColumnStatusId, columnId);

						this.deleteItemFromStatefulList(itemId, animationType);
						resolveParams = {
							action: 'delete',
							id: itemId,
							columnId,
						};
					}

					BX.postComponentEvent('UI.Kanban::onItemMoved', [{
						item,
						oldItem,
						resolveParams,
					}]);

					resolve(resolveParams);
				}).catch((response) => {
					this.errorProcessing(response.errors, requiredParams, {
						itemId,
						columnId,
					}).then(resolve);
				});
			});
		}

		errorProcessing(errors, params = {}, payload)
		{
			return new Promise((resolve, reject) => {
				const { itemId } = payload;
				if (RequiredFields && RequiredFields.hasRequiredFields(errors))
				{
					const { columnId } = payload;
					RequiredFields.show({
						errors,
						params: { ...params, uid: itemId },
						onSave: () => this.moveItem(itemId, columnId).then(resolve),
						onCancel: () => {
							this.unsetLoadingOfItem(itemId, false);
							reject();
						},
					});
					return false;
				}

				const error = this.getPublicError(errors);
				let title = Loc.getMessage('M_UI_KANBAN_ERROR_ON_SAVE');
				if (!error)
				{
					title = Loc.getMessage('M_UI_KANBAN_ERROR_ON_SAVE_INTERNAL');
				}

				ErrorNotifier
					.showErrors(error, {
						title,
						defaultErrorText: Loc.getMessage('M_UI_KANBAN_ERROR_ON_SAVE_INTERNAL_TEXT'),
						addDefaultIfEmpty: true,
					})
					.then(() => this.unsetLoadingOfItem(itemId, false));
				reject();
			});
		}

		getPublicError(errors)
		{
			const error = errors.find(({ customData, message }) => customData && customData.public && message);

			return error ? [error] : null;
		}

		deleteItem(itemId, params = {})
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction(this.actions.deleteItem, {
					data: {
						id: itemId,
						entityType: this.props.entityTypeName,
						params,
					},
				}).then(response => {
					if (response.errors && response.errors.length)
					{
						reject({
							errors: response.errors,
							showErrors: true,
						});
					}

					resolve({
						action: 'delete',
						id: itemId,
					});
				}).catch(response => {
					ErrorNotifier.showErrors(response.errors);
					reject({
						action: 'delete',
						id: itemId,
					});
				});
			});
		}

		deleteItemFromStatefulList(itemId, animationType = animationTypes.top)
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				void statefulList.deleteItem(itemId, animationType);
			}
		}

		/**
		 * @returns {null|StatefulList}
		 */
		getCurrentStatefulList()
		{
			const toolbar = this.refsContainer.getToolbar();
			if (!toolbar)
			{
				return this.getStatefulList(this.getSlideName());
			}

			const currentColumnId = toolbar.getActiveStageId();

			if (!currentColumnId)
			{
				return this.getStatefulList(this.getSlideName());
			}

			const column = this.getColumnById(currentColumnId);
			if (column)
			{
				const slideName = this.getSlideName(column.statusId);
				return this.getStatefulList(slideName);
			}

			return null;
		}

		getStatefulList(slideName)
		{
			return this.refsContainer.getColumn(slideName);
		}

		getAnimationType(oldColumnStatusId, newColumnId)
		{
			const newColumnStatusId = this.getColumnById(newColumnId).statusId;

			for (const columnId of this.columns.keys())
			{
				if (columnId === newColumnStatusId)
				{
					return animationTypes.left;
				}

				if (columnId === oldColumnStatusId)
				{
					return animationTypes.right;
				}
			}

			return animationTypes.default;
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				...this.renderKanban(),
			);
		}

		renderKanban()
		{
			const { entityTypeName } = this.props;

			if (this.props.config.forbidden)
			{
				return [
					this.renderForbiddenScreen(),
				];
			}

			if (!entityTypeName)
			{
				return [
					new LoadingScreenComponent(),
				];
			}

			return [
				Slider(
					{
						style: {
							flex: 1,
							marginTop: this.isEnabledKanbanToolbar() ? 52 : 0,
						},
						swipeEnabled: false,
						onPageWillChange: (page, direction) => {
							// Since we can't use the slider's internal pointer (page variable) due
							// to kanban direction changes, we are forced to use a class field (this.slidePage)
							let slidePage;
							if (direction === 'right' && this.slides.size > this.slidePage + 1)
							{
								slidePage = this.slidePage + 1;
							}
							else if (direction === 'left' && this.slidePage)
							{
								slidePage = this.slidePage - 1;
							}
							else
							{
								return;
							}

							const slideName = this.getSlideNameByPage(slidePage);
							const reloadParams = {
								updateToolbarColumnId: false,
							};
							this.reloadStatefulList(slideName, reloadParams);
						},
						onPageChange: (page) => {
							this.slidePage = page;
							const slideName = this.getSlideNameByPage(page);
							this.updateToolbarColumnId(slideName);
						},
						ref: ref => {
							this.refsContainer.setSlider(ref);
						},
					},
					...this.renderStatefulLists(),
				),
				this.renderKanbanToolbar(),
			];
		}

		renderForbiddenScreen()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
						alignItems: 'center',
						flex: 1,
					},
				},
				Text({
					text: BX.message('M_UI_KANBAN_FORBIDDEN_FOR_ALL_CATEGORIES'),
				}),
			);
		}

		/**
		 * @param {Number} page
		 * @returns {Object}
		 */
		getSlideNameByPage(page)
		{
			return Array.from(this.slides.keys())[page];
		}

		getSlideNameByColumn(column = null)
		{
			const columnId = (column ? column.statusId : '');
			return this.getSlideName(columnId);
		}

		/**
		 * @param {string} slideName
		 * @returns {number}
		 */
		getPageBySlideName(slideName)
		{
			return Array.from(this.slides.keys()).indexOf(slideName);
		}

		renderStatefulLists()
		{
			const { entityTypeName } = this.props;
			if (!this.slides.size && entityTypeName)
			{
				const actionParams = this.getPreparedActionParams();
				this.slides.set(this.getSlideName(), actionParams);
			}

			const params = {
				needInitMenu: this.props.needInitMenu,
				itemParams: this.getPreparedItemParams(),
			};

			const statefulLists = [];

			this.slides.forEach(actionParams => {
				statefulLists.push(this.renderStatefulListInstance(actionParams, params));
				params.needInitMenu = false;
			});

			return statefulLists;
		}

		/**
		 * @returns {Object}
		 */
		getPreparedActionParams(props = null)
		{
			const actionParams = this.getDefaultActionParams(props);

			actionParams.loadItems.extra = (actionParams.loadItems.extra || {});
			actionParams.loadItems.extra.filterParams = (actionParams.loadItems.extra.filterParams || {});

			if (this.state.currentColumnId)
			{
				actionParams.loadItems.extra.filterParams.stageId = this.state.currentColumnId;
			}
			else
			{
				delete actionParams.loadItems.extra.filterParams.stageId;
			}

			return actionParams;
		}

		/**
		 * @returns {Object}
		 */
		getDefaultActionParams(props = null)
		{
			props = props || this.props;

			const actionParams = {
				loadItems: {
					entityType: props.entityTypeName,
				},
			};

			return mergeImmutable(props.actionParams, actionParams);
		}

		isEnabledKanbanToolbar()
		{
			return (
				this.props.toolbarFactory
				&& this.props.toolbarFactory.has(this.props.entityTypeName)
			);
		}

		renderKanbanToolbar()
		{
			if (this.isEnabledKanbanToolbar())
			{
				return this.props.toolbarFactory.create(
					this.props.entityTypeName,
					{
						entityTypeName: this.props.entityTypeName,
						entityTypeId: this.props.entityTypeId,
						filter: this.filter,
						filterParams: this.props.filterParams,
						loadAction: this.actions.loadEntityStages,
						loadActionParams: this.props.actionParams.loadItems,
						changeColumn: this.changeColumn,
						reloadColumn: this.reloadCurrentColumn,
						changeItemStage: this.changeItemStageHandler,
						blinkItem: this.blinkItemHandler,
						layout: this.props.layout,
						ref: ref => {
							if (ref)
							{
								this.refsContainer.setToolbar(ref);
							}
						},
					},
				);
			}

			console.warn('Toolbar factory not found.');

			return null;
		}

		/**
		 * @param {?Object} actionParams
		 * @param {?Object} params
		 * @returns {StatefulList}
		 */
		renderStatefulListInstance(actionParams, params)
		{
			actionParams = (actionParams || this.getPreparedActionParams());
			params = (params || {});

			const columnId = (
				actionParams.loadItems.extra
					? actionParams.loadItems.extra.filterParams.stageId
					: ''
			);

			const slideName = this.getSlideName(columnId);
			const testId = `KANBAN_${slideName}`.toUpperCase();

			return new StatefulList({
				testId,
				actions: this.actions,
				actionParams: actionParams,
				itemLayoutOptions: this.props.itemLayoutOptions,
				itemDetailOpenHandler: this.props.itemDetailOpenHandler,
				itemCounterLongClickHandler: this.props.itemCounterLongClickHandler,
				getItemCustomStyles: this.getItemCustomStyles,
				isShowFloatingButton: BX.prop.getBoolean(this.props, 'isShowFloatingButton', true),
				floatingButtonClickHandler: this.props.floatingButtonClickHandler,
				floatingButtonLongClickHandler: this.props.floatingButtonLongClickHandler,
				needInitMenu: (params.hasOwnProperty('needInitMenu') ? params.needInitMenu : true),
				itemActions: (this.props.itemActions || []),
				emptyListText: BX.message('M_UI_KANBAN_EMPTY_LIST_TEXT'),
				emptySearchText: BX.message('M_UI_KANBAN_EMPTY_SEARCH_TEXT'),
				layout: this.props.layout,
				layoutOptions: this.props.layoutOptions,
				cacheName: this.getStatefulListCacheName(columnId),
				layoutMenuActions: this.props.layoutMenuActions,
				menuButtons: (this.props.menuButtons || []),
				itemType: 'Kanban',
				itemParams: (params.itemParams || {}),
				getEmptyListComponent: this.props.getEmptyListComponent || null,
				getRuntimeParams: this.getRuntimeParams,
				showEmptySpaceItem: this.isEnabledKanbanToolbar(),
				pull: (this.props.pull || null),
				onDetailCardUpdateHandler: this.props.onDetailCardUpdateHandler || null,
				onDetailCardCreateHandler: this.props.onDetailCardCreateHandler || null,
				onPanListHandler: this.props.onPanListHandler || null,
				onNotViewableHandler: this.props.onNotViewableHandler || null,
				reloadListCallbackHandler: this.reloadListCallbackHandler,
				skipRenderIfEmpty: true,
				context: {
					slideName,
				},
				ref: useCallback((ref) => {
					this.refsContainer.setColumn(slideName, ref);
				}, [slideName]),
				analyticsLabel: this.props.analyticsLabel || {}
			});
		}

		getItemCustomStyles(item, section, row)
		{
			if (row > 1 || Application.getPlatform() !== 'android')
			{
				return {};
			}

			return {
				wrapper: {
					paddingTop: 20,
				},
			};
		}

		/**
		 * @param {string} columnId
		 * @returns {string}
		 */
		getStatefulListCacheName(columnId = 0)
		{
			return `${this.props.cacheName}.${String(columnId)}`;
		}

		/**
		 * @returns {Object}
		 */
		getPreparedItemParams()
		{
			const itemParams = clone(this.props.itemParams);
			itemParams.onChange = this.changeItemStageHandler;
			itemParams.useStageFieldInSkeleton = true;

			return mergeImmutable(itemParams, this.getAdditionalParamsForItem());
		}

		getAdditionalParamsForItem()
		{
			if (this.refsContainer.hasToolbar())
			{
				return this.refsContainer.getToolbar().getAdditionalParamsForItem();
			}

			return {};
		}

		getRuntimeParamsHandler(data)
		{
			const cancelSearch = true;

			return {
				cancelSearch,
			};
		}

		setFilter(filter)
		{
			this.filter = filter;
		}

		/**
		 * @param {string} slideName
		 * @param {boolean} force
		 * @param {object} params
		 */
		reload(slideName = '', force = false, params = {})
		{
			if (params.skipFillSlides)
			{
				this.slides.forEach(slide => {
					const { extra } = slide.loadItems;
					extra.filter = this.filter;
					extra.filterParams.FILTER_PRESET_ID = this.filter.presetId || null;
				});
			}
			else
			{
				this.fillSlides();
			}

			if (this.slides.size <= 1)
			{
				return;
			}

			const currentColumnId = (slideName ? this.getColumnIdByName(this.getColumnStatusIdFromSlideName(slideName)) : null);

			const menuButtons = BX.prop.getArray(params, 'menuButtons', null);
			const skipUseCache = BX.prop.getBoolean(params, 'skipUseCache', false);
			const skipInitCounters = BX.prop.getBoolean(params, 'skipInitCounters', false);
			const updateToolbarColumnId = BX.prop.getBoolean(params, 'updateToolbarColumnId', true);
			const initMenu = BX.prop.getBoolean(params, 'initMenu', false);
			const forcedShowSkeleton = BX.prop.getBoolean(params, 'forcedShowSkeleton', false);

			if (params.skipFillSlides)
			{
				this.state.currentColumnId = currentColumnId;
				this.state.filterParams = this.props.filterParams;
				this.synchronizeActionParams();

				const reloadParams = {
					updateToolbarColumnId,
					force,
					menuButtons,
					skipUseCache,
					forcedShowSkeleton,
				};

				this.reloadStatefulList(slideName, reloadParams, () => {
					if (!skipInitCounters)
					{
						this.initCounters();
					}

					if (initMenu)
					{
						this.getCurrentStatefulList().initMenu();
					}
				});
			}
			else
			{
				this.setState({
					currentColumnId,
					filterParams: this.props.filterParams,
				}, () => {

					const reloadParams = {
						updateToolbarColumnId: true,
						force,
						menuButtons,
						skipUseCache,
					};

					this.reloadStatefulList(slideName, reloadParams, () => {
						slideName = (slideName || this.getSlideName());

						const newSlidePage = this.getPageBySlideName(slideName);
						if (this.slidePage !== newSlidePage)
						{
							this.refsContainer.getSlider().scrollToPage(this.getPageBySlideName(slideName));
						}
						if (!skipInitCounters)
						{
							this.initCounters();
						}
					});
				});
			}
		}

		synchronizeActionParams()
		{
			this.refsContainer.statefulLists.forEach((statefulList, name) => {
				statefulList.state.actionParams = this.slides.get(name);
			});
		}

		fillSlides()
		{
			const columns = this.getColumnsFromCurrentCategory();
			if (columns && !isEqual(this.columns, columns))
			{
				this.columns = columns;

				const actionParams = this.getPreparedActionParams();

				for (const columnId of this.columns.keys())
				{
					const params = clone(actionParams);
					const stageFilter = {
						filterParams: {
							stageId: columnId,
						},
					};
					params.loadItems.extra = merge(params.loadItems.extra, stageFilter);
					this.slides.set(this.getSlideName(columnId), params);
				}
			}
		}

		initCounters()
		{
			if (this.props.initCountersHandler)
			{
				this.props.initCountersHandler({ filter: this.filter });
			}
		}

		getColumnsFromCurrentCategory()
		{
			const columns = new Map();
			const category = this.getCurrentCategory();

			if (!category)
			{
				return columns;
			}

			const stages = [
				...category.processStages,
				...category.successStages,
				...category.failedStages,
			];

			stages.map(stage => {
				columns.set(stage.statusId, stage);
			});

			return columns;
		}

		getCurrentCategory()
		{
			return CategoryStorage && CategoryStorage.getCategory(
				this.props.entityTypeId,
				this.props.filterParams.CATEGORY_ID,
			);
		}

		/**
		 * @param {String} slideName
		 * @param {Object} params
		 * @param {Function|null} callback
		 */
		reloadStatefulList(
			slideName = '',
			params = {},
			callback = null,
		)
		{
			slideName = slideName || this.getSlideName();

			const force = (params.force || false);
			if (!force && slideName === this.currentSlideName && this.getColumnStatusIdFromSlideName(slideName))
			{
				return;
			}

			if (params.updateToolbarColumnId)
			{
				this.updateToolbarColumnId(slideName);
			}

			if (typeof callback !== 'function')
			{
				callback = () => {
				};
			}

			const statefulList = this.getStatefulList(slideName);
			if (!statefulList)
			{
				return;
			}

			const initialStateParams = {
				itemParams: this.getPreparedItemParams(),
				forcedShowSkeleton: BX.prop.getBoolean(params, 'forcedShowSkeleton', false),
			};

			if (params.menuButtons)
			{
				initialStateParams.menuButtons = params.menuButtons;
			}

			const useCache = (params.skipUseCache ? false : this.canUseCache());

			statefulList.reload(
				initialStateParams,
				{
					useCache,
				},
				callback,
			);
		}

		canUseCache()
		{
			if (!this.filter)
			{
				return false;
			}
			// @todo maybe need use individual cache for all preset exclude 'tmp_filter'
			return !(Boolean(this.filter.currentFilterId) || Boolean(this.filter.search));
		}

		/**
		 * @param {String} slideName
		 */
		updateToolbarColumnId(slideName = '')
		{
			const toolbar = this.refsContainer.getToolbar();
			if (!toolbar)
			{
				return;
			}

			this.currentSlideName = slideName;
			const newColumnStatusId = this.getColumnStatusIdFromSlideName(slideName);
			const newColumn = this.columns.get(newColumnStatusId);
			const newColumnId = (newColumn ? newColumn.id : null);

			toolbar.updateCurrentColumnId(newColumnId);
		}

		/**
		 * @param {string} columnId
		 * @returns {string}
		 */
		getSlideName(columnId = '', props = null)
		{
			props = props || this.props;
			return props.entityTypeName + '-' + columnId;
		}

		/**
		 * @param {string} slideName
		 */
		getColumnStatusIdFromSlideName(slideName)
		{
			return slideName.replace(this.props.entityTypeName + '-', '');
		}

		/**
		 * @returns {String|Null}
		 */
		getCurrentSlideName()
		{
			return this.currentSlideName;
		}

		blinkItem(itemId, showUpdated = true)
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				statefulList.blinkItem(itemId, showUpdated);
			}
		}

		setLoadingOfItem(itemId)
		{
			this.getCurrentStatefulList().setLoadingOfItem(itemId);
		}

		unsetLoadingOfItem(itemId, blink = true)
		{
			this.getCurrentStatefulList().unsetLoadingOfItem(itemId, blink);
		}
	}

	this.UI = (this.UI || {});
	this.UI.Kanban = Kanban;
})();
