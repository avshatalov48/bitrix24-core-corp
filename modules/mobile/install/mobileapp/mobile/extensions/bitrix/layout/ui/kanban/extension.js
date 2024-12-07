/**
 * @module layout/ui/kanban
 */
jn.define('layout/ui/kanban', (require, exports, module) => {
	const { Loc } = require('loc');
	const { RefsContainer } = require('layout/ui/kanban/refs-container');
	const {
		clone,
		mergeImmutable,
		set,
	} = require('utils/object');
	const { PureComponent } = require('layout/pure-component');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { ListItemType, ListItemsFactory } = require('layout/ui/simple-list/items');
	const { Type } = require('type');
	const { Alert } = require('alert');
	const { assertDefined, assertFunction } = require('utils/validation');

	const nothing = () => {};

	const animationTypes = {
		default: 'fade',
		left: 'left',
		right: 'right',
		top: 'top',
	};

	/**
	 * @class Kanban
	 * @typedef {LayoutComponent<KanbanProps, KanbanState>}
	 */
	class Kanban extends PureComponent
	{
		// region initialize

		/**
		 * @public
		 * @param {KanbanProps} props
		 */
		constructor(props)
		{
			super(props);

			/** @type RefsContainer */
			this.refsContainer = new RefsContainer();

			this.init(props);

			this.getRuntimeParams = this.getRuntimeParams.bind(this);
			this.setActiveStage = this.setActiveStage.bind(this);
			this.onChangeItemStage = this.onChangeItemStage.bind(this);
			this.onStatefulListReload = this.initCounters.bind(this);
			this.bindRef = this.bindRef.bind(this);
			this.bindToolbarRef = this.bindToolbarRef.bind(this);
		}

		/**
		 * @private
		 * @param {KanbanProps} newProps
		 */
		componentWillReceiveProps(newProps)
		{
			if (this.props.id !== newProps.id)
			{
				this.init(newProps);
			}
		}

		/**
		 * @private
		 * @param {KanbanProps} props
		 */
		init(props)
		{
			this.actions = props.actions || {};

			assertDefined(this.actions.updateItemStage, '\'updateItemStage\' action must be defined');
			assertDefined(this.actions.deleteItem, '\'deleteItem\' action must be defined');
			assertDefined(this.actions.loadItems, '\'loadItems\' action must be defined');
			assertFunction(props.stagesProvider, 'Expect \'stagesProvider\' property to be function');

			/** @type {Filter|null} */
			this.filter = null;

			this.state = {
				activeStageId: null,
			};
		}

		// endregion

		// region stages api

		/**
		 * @public
		 * @return {KanbanStage[]}
		 */
		getStages()
		{
			return this.props.stagesProvider();
		}

		/**
		 * @public
		 * @param {number} id
		 * @return {KanbanStage|undefined}
		 */
		getStageById(id)
		{
			const stages = this.getStages();

			return stages.find((stage) => stage.id === id);
		}

		/**
		 * @public
		 * @param {string} code
		 * @return {KanbanStage|undefined}
		 */
		getStageByCode(code)
		{
			const stages = this.getStages();

			return stages.find((stage) => stage.statusId === code);
		}

		/**
		 * @public
		 * @returns {null|KanbanStage}
		 */
		getActiveStage()
		{
			const stages = this.getStages();

			if (stages.length === 0 || !this.getActiveStageId())
			{
				return null;
			}

			return this.getStageById(this.getActiveStageId()) || null;
		}

		/**
		 * @public
		 * @returns {null|Number}
		 */
		getActiveStageId()
		{
			return this.state.activeStageId;
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isAllStagesDisplayed()
		{
			return this.getActiveStage() === null;
		}

		/**
		 * @public
		 * @param {number|null} newStageId
		 */
		setActiveStage(newStageId)
		{
			const stage = this.getStageById(newStageId);
			const activeStageId = stage ? stage.id : null;

			this.setState({ activeStageId }, () => this.reloadStatefulList());
		}

		// endregion

		// region items interactions

		/**
		 * @public
		 * @param {Number} itemId
		 * @param {String} stageCode
		 */
		updateItemColumn(itemId, stageCode)
		{
			const stage = this.getStageByCode(stageCode);
			const item = this.getCurrentStatefulList().getItemComponent(itemId);
			if (stage && item)
			{
				item.updateColumnId(stage.id);
			}
		}

		/**
		 * @public
		 * @param {number} itemId
		 * @param {object} params
		 * @return {Promise}
		 */
		deleteItem(itemId, params = {})
		{
			const { deleteItem: deleteItemActionParams = {} } = this.getPreparedActionParams();

			return new Promise((resolve, reject) => {
				BX.ajax.runAction(this.actions.deleteItem, {
					data: {
						...deleteItemActionParams,
						id: itemId,
						params,
					},
				}).then((response) => {
					if (response.errors && response.errors.length > 0)
					{
						// eslint-disable-next-line prefer-promise-reject-errors
						reject({
							errors: response.errors,
							showErrors: true,
						});
					}
					else
					{
						resolve({
							action: 'delete',
							id: itemId,
						});
					}
				}).catch((response) => {
					this.handleAjaxErrors(response.errors).finally(() => {
						// eslint-disable-next-line prefer-promise-reject-errors
						reject({
							action: 'delete',
							id: itemId,
						});
					});
				});
			});
		}

		/**
		 * @public
		 * @param {Number} itemId
		 * @param {Number} columnId
		 * @returns {Promise}
		 */
		moveItem(itemId, columnId)
		{
			const oldItem = clone(this.getCurrentStatefulList().getItem(itemId));
			const { updateItemStage = {}, loadItems = {} } = this.getPreparedActionParams();

			return new Promise((resolve) => {
				const item = this.getCurrentStatefulList().getItem(itemId);
				const prevStage = this.getItemStage(item);
				const nextStage = this.getStageById(columnId);

				this.setLoadingOfItem(itemId);

				BX.ajax.runAction(this.actions.updateItemStage, {
					data: mergeImmutable(updateItemStage, {
						id: itemId,
						stageId: columnId,
						extra: loadItems.extra || {},
					}),
				}).then((response) => {
					if (response.errors && response.errors.length > 0)
					{
						throw new Error(response);
					}

					this.mutateItemStage(item, nextStage);

					let resolveParams = { columnId };

					if (this.getActiveStageId() === prevStage.id)
					{
						const animationType = this.getAnimationType(prevStage.statusId, nextStage.statusId);

						this.deleteItemFromStatefulList(itemId, animationType);
						resolveParams = {
							action: 'delete',
							id: itemId,
							columnId,
						};
					}

					BX.postComponentEvent('UI.Kanban::onItemMoved', [
						{
							item,
							oldItem,
							resolveParams,
							kanbanId: this.props.id,
						},
					]);

					resolve(resolveParams);
				}).catch((response) => this.onMoveItemError({
					errors: response.errors,
					itemId,
					prevStage,
					nextStage,
				})).finally(() => {
					this.unsetLoadingOfItem(itemId, false);
				});
			});
		}

		/**
		 * @public
		 * @param {number} itemId
		 * @param {boolean} showUpdated
		 */
		blinkItem(itemId, showUpdated = true)
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				statefulList.blinkItem(itemId, showUpdated);
			}
		}

		/**
		 * @public
		 * @param {number} itemId
		 */
		setLoadingOfItem(itemId)
		{
			this.getCurrentStatefulList().setLoadingOfItem(itemId);
		}

		/**
		 * @public
		 * @param {number} itemId
		 * @param {boolean} blink
		 */
		unsetLoadingOfItem(itemId, blink = true)
		{
			this.getCurrentStatefulList().unsetLoadingOfItem(itemId, blink);
		}

		/**
		 * @public
		 * @returns {Object[]|null}
		 */
		getItems()
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				return statefulList.getItems();
			}

			return null;
		}

		/**
		 * @public
		 * @param {string|number} id
		 * @return {boolean}
		 */
		hasItem(id)
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				return statefulList.hasItem(id);
			}

			return false;
		}

		/**
		 * @public
		 * @param {number[]|string[]} ids
		 * @return {Promise}
		 */
		updateItems(ids = [])
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				return statefulList.updateItems(ids);
			}

			return Promise.resolve();
		}

		/**
		 * @public
		 * @param {object[]} items
		 * @return {Promise}
		 */
		updateItemsData(items)
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				return statefulList.updateItemsData(items);
			}

			return Promise.resolve();
		}

		replaceItems(items)
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				return statefulList.replaceItems(items);
			}

			return Promise.resolve();
		}

		/**
		 * @public
		 * @param itemId
		 * @returns {Promise}
		 */
		removeItem(itemId)
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				return statefulList.removeItem(itemId);
			}

			return Promise.resolve();
		}

		/**
		 * @private
		 * @param {object} item
		 * @return {KanbanStage|undefined}
		 */
		getItemStage(item)
		{
			if (this.props.selectItemStageId)
			{
				const stageId = this.props.selectItemStageId(item);

				return this.getStageById(stageId);
			}

			const stageCode = item.data.columnId;

			return this.getStageByCode(stageCode);
		}

		/**
		 * @private
		 * @param {object} item
		 * @param {KanbanStage} stage
		 */
		mutateItemStage(item, stage)
		{
			if (this.props.mutateItemStage)
			{
				this.props.mutateItemStage(item, stage);

				return;
			}

			// eslint-disable-next-line no-param-reassign
			item.data.columnId = stage.statusId;
		}

		// endregion

		// region render

		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				View(
					{
						style: {
							flex: 1,
							marginTop: this.isToolbarEnabled() ? 52 : 0,
						},
					},
					this.renderCurrentStage(),
				),
				this.renderToolbar(),
			);
		}

		renderCurrentStage()
		{
			return new StatefulList({
				testId: 'KANBAN_STAGE',
				actions: this.actions,
				actionParams: this.getPreparedActionParams(),
				itemsLoadLimit: this.props.itemsLoadLimit,
				actionCallbacks: this.props.actionCallbacks,
				itemLayoutOptions: this.props.itemLayoutOptions,
				itemDetailOpenHandler: this.props.itemDetailOpenHandler,
				onItemLongClick: this.props.onItemLongClick,
				itemCounterLongClickHandler: this.props.itemCounterLongClickHandler,
				getItemCustomStyles: this.getItemCustomStyles,
				isShowFloatingButton: BX.prop.getBoolean(this.props, 'isShowFloatingButton', true),
				onFloatingButtonClick: this.props.onFloatingButtonClick,
				onFloatingButtonLongClick: this.props.onFloatingButtonLongClick,
				needInitMenu: this.props.needInitMenu,
				popupItemMenu: this.props.popupItemMenu || false,
				itemActions: this.props.itemActions || [],
				emptyListText: Loc.getMessage('M_UI_KANBAN_EMPTY_LIST_TEXT'),
				emptySearchText: Loc.getMessage('M_UI_KANBAN_EMPTY_SEARCH_TEXT'),
				forcedShowSkeleton: this.props.forcedShowSkeleton ?? true,
				layout: this.props.layout,
				layoutOptions: this.props.layoutOptions,
				layoutMenuActions: this.props.layoutMenuActions || [],
				menuButtons: this.props.menuButtons || [],
				itemType: this.props.itemType || ListItemType.EXTENDED,
				itemFactory: this.props.itemFactory || ListItemsFactory,
				itemParams: this.getPreparedItemParams(),
				getEmptyListComponent: this.props.getEmptyListComponent,
				getRuntimeParams: this.getRuntimeParams,
				showEmptySpaceItem: this.isToolbarEnabled(),
				pull: this.props.pull,
				sortingConfig: this.props.sortingConfig,
				onDetailCardUpdateHandler: this.props.onDetailCardUpdateHandler,
				onDetailCardCreateHandler: this.props.onDetailCardCreateHandler,
				onPanListHandler: this.props.onPanListHandler,
				onNotViewableHandler: this.props.onNotViewableHandler,
				onItemAdded: this.props.onItemAdded,
				onItemDeleted: this.props.onItemDeleted,
				changeItemsOperations: this.props.changeItemsOperations,
				onBeforeItemsRender: this.props.onBeforeItemsRender,
				onBeforeItemsSetState: this.props.onBeforeItemsSetState,
				reloadListCallbackHandler: this.onStatefulListReload,
				ref: this.bindRef,
				analyticsLabel: this.props.analyticsLabel || {},
				animationTypes: this.props.animationTypes,
				onListReloaded: this.props.onListReloaded,
				showTitleLoader: this.props.showTitleLoader,
				hideTitleLoader: this.props.hideTitleLoader,
			});
		}

		bindRef(ref)
		{
			this.refsContainer.setCurrentStage(ref);
		}

		addItem(item, animationType)
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				void statefulList.addItem(item, animationType);
			}
		}

		renderToolbar()
		{
			if (this.isToolbarEnabled())
			{
				const { componentClass: Toolbar, props = {} } = this.props.toolbar;

				return Toolbar({
					...props,
					layout: this.props.layout,
					onChangeStage: this.setActiveStage,
					ref: this.bindToolbarRef,
				});
			}

			return null;
		}

		isToolbarEnabled()
		{
			const { enabled } = (this.props.toolbar || {});

			return enabled;
		}

		bindToolbarRef(ref)
		{
			if (ref)
			{
				this.refsContainer.setToolbar(ref);
			}
		}

		getItemCustomStyles(item, section, row)
		{
			return {};
		}

		// endregion

		// region error handling

		/**
		 * @private
		 * @param {KanbanBackendError[]} errors
		 * @param {number} itemId
		 * @param {KanbanStage} prevStage
		 * @param {KanbanStage} nextStage
		 * @return {Promise}
		 */
		onMoveItemError({ errors, itemId, prevStage, nextStage })
		{
			if (this.props.onMoveItemError)
			{
				return Promise.resolve(this.props.onMoveItemError({
					errors,
					itemId,
					prevStage,
					nextStage,
					kanbanInstance: this,
				}));
			}

			return this.handleAjaxErrors(errors);
		}

		/**
		 * @public
		 * @param {KanbanBackendError[]} errors
		 * @return {Promise}
		 */
		handleAjaxErrors(errors)
		{
			const error = this.getPublicError(errors);
			const title = error
				? Loc.getMessage('M_UI_KANBAN_PUBLIC_ERROR_TITLE')
				: Loc.getMessage('M_UI_KANBAN_INTERNAL_ERROR_TITLE');

			const message = error ? error.message : Loc.getMessage('M_UI_KANBAN_INTERNAL_ERROR_TEXT');

			return new Promise((resolve) => {
				Alert.alert(
					title,
					message,
					resolve,
					Loc.getMessage('M_UI_KANBAN_INTERNAL_ERROR_GOT_IT'),
				);
			});
		}

		/**
		 * @private
		 * @param {KanbanBackendError[]} errors
		 * @return {KanbanBackendError|undefined}
		 */
		getPublicError(errors)
		{
			return errors.find(({ customData, message }) => customData && customData.public && message);
		}

		// endregion

		// region cache

		/**
		 * @private
		 * @return {boolean}
		 */
		canUseCache()
		{
			if (this.filter)
			{
				if (this.filter.hasSelectedNotDefaultPreset())
				{
					return false;
				}

				if (Type.isStringFilled(this.filter.getSearchString()))
				{
					return false;
				}
			}

			return this.isAllStagesDisplayed();
		}

		// endregion

		/**
		 * @public
		 * @param {number} itemId
		 * @param {string} animationType
		 */
		deleteItemFromStatefulList(itemId, animationType = animationTypes.top)
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				void statefulList.deleteItem(itemId, animationType);
			}
		}

		/**
		 * @public
		 * @returns {null|StatefulList}
		 */
		getCurrentStatefulList()
		{
			return this.refsContainer.getCurrentStage();
		}

		/**
		 * @private
		 * @param {Number} stageId
		 * @param {Object} category
		 * @param {Object|null} data
		 * @returns {Promise}
		 */
		onChangeItemStage(stageId, category, { itemId })
		{
			return this.moveItem(itemId, stageId);
		}

		/**
		 * @public
		 * @param {any} prevStageCode
		 * @param {any} nextStageCode
		 * @param {function|undefined} selectStageCode
		 * @return {string}
		 */
		getAnimationType(prevStageCode, nextStageCode, selectStageCode)
		{
			const defaultSelector = (item) => item.statusId;
			const stageCodes = this.getStages().map(selectStageCode || defaultSelector);

			for (const code of stageCodes)
			{
				if (code === nextStageCode)
				{
					return animationTypes.left;
				}

				if (code === prevStageCode)
				{
					return animationTypes.right;
				}
			}

			return animationTypes.default;
		}

		/**
		 * @private
		 * @returns {Object}
		 */
		getPreparedActionParams(props = null)
		{
			const { actionParams = {} } = (props || this.props);
			const stage = this.getActiveStage();
			const statusId = stage ? stage.statusId : null;

			set(actionParams, 'loadItems.stageId', stage ? stage.id : null);
			set(actionParams, 'loadItems.stageCode', statusId);

			// todo remove backward compatibility
			set(actionParams, 'loadItems.extra.filterParams.stageId', statusId);
			if (!stage)
			{
				delete actionParams.loadItems.extra.filterParams.stageId;
			}

			if (this.filter)
			{
				set(actionParams, 'loadItems.extra.filter', this.filter);
				set(actionParams, 'loadItems.extra.filterParams.FILTER_PRESET_ID', this.filter.getPresetId());
			}

			return actionParams;
		}

		/**
		 * @private
		 * @returns {Object}
		 */
		getPreparedItemParams()
		{
			const columns = new Map();
			this.getStages().forEach((stage) => {
				columns.set(stage.statusId, stage);
			});

			const itemParams = mergeImmutable(clone(this.props.itemParams), {
				columns,
				onChangeItemStage: this.onChangeItemStage,
				useStageFieldInSkeleton: true,
				activeStageId: this.getActiveStageId(),
			});

			if (this.props.onPrepareItemParams)
			{
				return this.props.onPrepareItemParams(itemParams);
			}

			return itemParams;
		}

		/**
		 * @private
		 * @param {object} data
		 * @return {{cancelSearch: boolean}}
		 */
		getRuntimeParams(data)
		{
			return {
				cancelSearch: true,
			};
		}

		/**
		 * @public
		 * @param {Filter} filter
		 */
		setFilter(filter)
		{
			this.filter = filter;
		}

		/**
		 * @public
		 * @param {boolean} force
		 * @param {object} params
		 */
		reload(force = false, params = {})
		{
			const menuButtons = BX.prop.getArray(params, 'menuButtons', null);
			const skipUseCache = BX.prop.getBoolean(params, 'skipUseCache', false);
			const skipInitCounters = BX.prop.getBoolean(params, 'skipInitCounters', false);
			const initMenu = BX.prop.getBoolean(params, 'initMenu', false);
			const forcedShowSkeleton = BX.prop.getBoolean(params, 'forcedShowSkeleton', skipUseCache);

			const reloadParams = {
				force,
				menuButtons,
				skipUseCache,
				forcedShowSkeleton,
			};

			this.reloadStatefulList(reloadParams, () => {
				if (!skipInitCounters)
				{
					this.initCounters({ force });
				}

				if (initMenu)
				{
					this.getCurrentStatefulList().initMenu();
				}
			});
		}

		/**
		 * @private
		 * @param {{ menuButtons: [], forcedShowSkeleton: boolean, skipUseCache: boolean }} params
		 * @param {Function|null} callback
		 */
		reloadStatefulList(params = {}, callback = null)
		{
			const statefulList = this.getCurrentStatefulList();
			if (!statefulList)
			{
				return;
			}

			const useCache = (params.skipUseCache ? false : this.canUseCache());

			const initialStateParams = {
				actionParams: this.getPreparedActionParams(),
				itemParams: this.getPreparedItemParams(),
				forcedShowSkeleton: BX.prop.getBoolean(params, 'forcedShowSkeleton', !useCache),
				force: BX.prop.getBoolean(params, 'force', false),
			};

			if (params.menuButtons)
			{
				initialStateParams.menuButtons = params.menuButtons;
			}

			statefulList.reload(
				initialStateParams,
				{
					useCache,
				},
				typeof callback === 'function' ? callback : nothing,
			);
		}

		/**
		 * @private
		 */
		initCounters({ force = false })
		{
			if (this.props.initCountersHandler)
			{
				this.props.initCountersHandler({ filter: this.filter }, force);
			}
		}

		updateTopButtons(buttons)
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				statefulList.initMenu(null, buttons);
			}
		}

		isLoading()
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				return statefulList.isLoading();
			}

			return true;
		}

		async scrollToTopItem(itemIds, animated = true, blink = false)
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				await statefulList.scrollToTopItem(itemIds, animated, blink);
			}
		}

		getItemRef(itemId)
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				return statefulList.getItemRef(itemId);
			}

			return null;
		}

		getItemRootViewRef(itemId)
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				return statefulList.getItemRootViewRef(itemId);
			}

			return null;
		}

		getItemMenuViewRef(itemId)
		{
			const statefulList = this.getCurrentStatefulList();
			if (statefulList)
			{
				return statefulList.getItemMenuViewRef(itemId);
			}

			return null;
		}
	}

	module.exports = { Kanban };
});
