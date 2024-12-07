/**
 * @module tasks/layout/flow/list
 */
jn.define('tasks/layout/flow/list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { StatefulList } = require('layout/ui/stateful-list');
	const { TypeGenerator } = require('layout/ui/stateful-list/type-generator');
	const { batchActions } = require('statemanager/redux/batched-actions');
	const store = require('statemanager/redux/store');
	const { ListItemType, FlowListItemsFactory } = require('tasks/flow-list/simple-list/items');
	const { upsertFlows, addFlows } = require('tasks/statemanager/redux/slices/flows');
	const { groupsUpserted, groupsAdded } = require('tasks/statemanager/redux/slices/groups');
	const { usersUpserted, usersAdded } = require('statemanager/redux/slices/users');
	const { NavigationTitle, Pull, TasksFlowListFilter, TasksFlowListMoreMenu } = require(
		'tasks/flow-list',
	);
	const { TasksFlowListSorting } = require('tasks/flow-list/src/sorting');
	const { downloadImages, makeLibraryImagePath } = require('asset-manager');
	const { ListType } = require('tasks/layout/flow/list/type');
	const { SearchLayout } = require('layout/ui/search-bar');
	const { CalendarSettings } = require('tasks/task/calendar');
	const { Pull: TasksPull } = require('layout/ui/stateful-list/pull');
	const {
		selectById,
		mapStateToTaskModel,
	} = require('tasks/statemanager/redux/slices/tasks');
	const { dispatch } = store;
	const { set } = require('utils/object');
	const { AnalyticsEvent } = require('analytics');

	const FLOWS_INFO_ITEM_ID = 'flows-info-item';

	class TasksFlowList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.showSearch = this.showSearch.bind(this);
			this.onItemsLoaded = this.onItemsLoaded.bind(this);
			this.onSearch = this.onSearch.bind(this);
			this.onItemClick = this.onItemClick.bind(this);
			this.onPanList = this.onPanList.bind(this);
			this.onPullToRefresh = this.onPullToRefresh.bind(this);
			this.getEmptyListComponent = this.getEmptyListComponent.bind(this);
			this.showTitleLoader = this.showTitleLoader.bind(this);
			this.hideTitleLoader = this.hideTitleLoader.bind(this);
			this.onCounterClick = this.onCounterClick.bind(this);
			this.onBeforeItemsRender = this.onBeforeItemsRender.bind(this);
			this.getItemType = this.getItemType.bind(this);
			this.getItemProps = this.getItemProps.bind(this);
			this.onFlowsInfoItemCloseButtonClick = this.onFlowsInfoItemCloseButtonClick.bind(this);
			this.onTabSelected = this.onTabSelected.bind(this);

			this.flowListFilter = new TasksFlowListFilter(
				this.props.currentUserId,
			);
			if (this.isFlowsList())
			{
				this.flowListFilter.loadCountersFromServer().then(() => this.updateMoreMenuButton()).catch(console.error);
			}

			this.onPullCallback = this.onPullCallback.bind(this);
			this.pull = new Pull({
				onPullCallback: this.onPullCallback,
				eventCallbacks: {
					[Pull.events.USER_COUNTER]: (params) => {
						this.flowListFilter.updateCountersFromPullEvent(params)
							.then(() => this.updateMoreMenuButton())
							.catch(console.error)
						;
					},
				},
				isTabsMode: this.props.isTabsMode,
			});

			this.sorting = new TasksFlowListSorting({
				type: TasksFlowListSorting.types.ACTIVITY,
			});

			if (this.isFlowsList())
			{
				this.moreMenu = new TasksFlowListMoreMenu(
					this.flowListFilter.getCountersByRole(),
					this.flowListFilter.getCounterType(),
					this.sorting.getType(),
					{
						onCounterClick: this.onCounterClick,
					},
				);

				this.search = new SearchLayout({
					layout: this.layout,
					id: 'my_tasks_flow',
					cacheId: `my_tasks_flow_${env.userId}`,
					presetId: TasksFlowListFilter.presetType.none,
					searchDataAction: 'tasksmobile.Flow.getSearchBarPresets',
					searchDataActionParams: {},
					onSearch: this.onSearch,
					onCancel: this.onSearch,
				});

				this.navigationTitle = new NavigationTitle({ layout: this.layout });
			}

			/** @type {StatefulList} */
			this.listRef = null;

			this.state = {
				counter: this.flowListFilter.getFlowTotalCounterValue(),
			};
		}

		get layout()
		{
			return this.props.layout ?? layout;
		}

		isFlowsList()
		{
			return this.props.listType === ListType.FLOWS;
		}

		onCounterClick(newCounter)
		{
			const currentCounter = this.flowListFilter.getCounterType();
			const counter = (newCounter === currentCounter ? TasksFlowListFilter.counterType.none : newCounter);

			this.flowListFilter.setCounterType(counter);
			this.moreMenu.setSelectedCounter(counter);
			this.setState({ counter }, () => this.reload());
		}

		updateMoreMenuButton()
		{
			if (!this.isFlowsList())
			{
				return;
			}

			if (this.moreMenu)
			{
				this.moreMenu.setCounters(this.flowListFilter.getCountersByRole());
			}

			if (this.listRef)
			{
				this.listRef.initMenu(null, this.getLayoutMenuButtons());
			}
		}

		/**
		 * @private
		 * @param {StatefulList|undefined} ref
		 */
		bindRef = (ref) => {
			if (ref)
			{
				this.listRef = ref;
			}
		};

		async componentDidMount()
		{
			this.subscribe();

			setTimeout(async () => {
				this.prefetchAssets();
				await CalendarSettings.loadSettings();
			}, 1000);
		}

		subscribe()
		{
			this.pull.subscribe();
			if (this.props.isTabsMode && this.isMyDashboard())
			{
				BX.addCustomEvent('tasks.tabs:onAppPaused', (eventData) => this.onAppPaused(eventData));
				BX.addCustomEvent('tasks.tabs:onAppActive', (eventData) => this.onAppActive(eventData));
			}

			BX.addCustomEvent('tasks.tabs:onTabSelected', this.onTabSelected);
		}

		prefetchAssets()
		{
			void downloadImages([
				this.getEmptyListImage(),
			]);
		}

		componentWillUnmount()
		{
			this.pull.unsubscribe();

			if (this.unsubscribeTasksObserver)
			{
				this.unsubscribeTasksObserver();
			}

			if (this.unsubscribeCounterChangeObserver)
			{
				this.unsubscribeCounterChangeObserver();
			}

			BX.removeCustomEvent('tasks.tabs:onTabSelected', this.onTabSelected);
		}

		onTabSelected(eventData)
		{
			if (eventData?.tabId === 'tasks.flow.list')
			{
				new AnalyticsEvent({
					tool: 'tasks',
					category: 'flows',
					event: 'flows_view',
					c_section: 'tasks',
					c_sub_section: 'flows',
					c_element: 'section_button',
				}).send();
			}
		}

		/**
		 * @private
		 * @param {object|undefined} responseData
		 * @param {'ajax'|'cache'} context
		 */
		onItemsLoaded(responseData, context)
		{
			const { items = [], users = [], groups = [], showflowsinfo = true } = responseData || {};
			const isCache = context === 'cache';

			const actions = [];

			if (items.length > 0)
			{
				actions.push(isCache ? addFlows(items) : upsertFlows(items));
			}

			if (users.length > 0)
			{
				actions.push(isCache ? usersAdded(users) : usersUpserted(users));
			}

			if (groups.length > 0)
			{
				actions.push(isCache ? groupsAdded(groups) : groupsUpserted(groups));
			}

			if (actions.length > 0)
			{
				dispatch(batchActions(actions));
			}

			if (showflowsinfo
				&& this.isFlowsList()
				&& items.length > 0
				&& items.findIndex((item) => item.id === FLOWS_INFO_ITEM_ID) < 0
				&& this.listRef)
			{
				items.unshift(this.getFlowInfoItemData());
			}
		}

		deleteFlowsInfoItemToList()
		{
			if (this.listRef && this.listRef.hasItem(FLOWS_INFO_ITEM_ID))
			{
				this.listRef.processItemsGroupsByData({
					delete: [FLOWS_INFO_ITEM_ID],
				}).then(() => {
					const cache = this.listRef.cache.runActionExecutor.getCache();
					const modifiedCache = set(cache.getData() || {}, ['data', 'showflowsinfo'], false);
					cache.saveData(modifiedCache);
				})
					.catch(console.error);
			}
		}

		onFlowsInfoItemCloseButtonClick()
		{
			this.deleteFlowsInfoItemToList();
			void this.disableShowFlowsFeatureInfoFlagInDB();
		}

		disableShowFlowsFeatureInfoFlagInDB()
		{
			return new Promise((resolve) => {
				const handler = (response) => {
					if (response
						&& response.status === 'success'
						&& response.errors.length === 0)
					{
						resolve();
					}
					else
					{
						console.error(response.errors);
					}
				};

				(new RunActionExecutor('tasksmobile.Flow.disableShowFlowsFeatureInfoFlagInDB'))
					.setHandler(handler)
					.call(true)
				;
			});
		}

		getFlowInfoItemData()
		{
			const fakeActivityDate = new Date(2100, 0);

			return {
				id: FLOWS_INFO_ITEM_ID,
				key: FLOWS_INFO_ITEM_ID,
				type: ListItemType.FLOWS_INFO,
				active: true,
				demo: false,
				isLast: false,
				activity: fakeActivityDate.getTime(),
			};
		}

		onSearch({ text, presetId })
		{
			this.flowListFilter.setPresetId(presetId);
			this.flowListFilter.setSearchString(text);
			this.updateMoreMenuButton();

			// todo avoid setting state and duplicated ajax request
			this.setState({}, () => this.reload());
		}

		onItemClick(id)
		{
			const row = selectById(store.getState(), id);
			if (row)
			{
				// eslint-disable-next-line no-undef
				const task = new Task({ id: this.props.currentUserId });
				task.setData(mapStateToTaskModel(row));
				task.open(null);
			}
		}

		onAppPaused()
		{
			this.pauseTime = Date.now();
		}

		onAppActive()
		{
			if (this.pauseTime)
			{
				const minutesPassed = Math.round((Date.now() - this.pauseTime) / 60000);
				if (minutesPassed >= 30)
				{
					if (this.isFlowsList())
					{
						this.flowListFilter.loadCountersFromServer().then(() => this.updateMoreMenuButton()).catch(console.error);
					}
					this.reload();
				}
			}
		}

		reload()
		{
			this.listRef?.reload({
				menuButtons: this.getLayoutMenuButtons(),
				skipUseCache: true,
			});
		}

		onPullToRefresh()
		{
			this.reload();
			void this.pull.subscribeUserToFlowsPull();
		}

		getSearchParams()
		{
			return {
				creatorId: this.props.creatorId ?? 0,
				excludedFlowId: this.props.excludedFlowId ?? 0,
				presetId: this.flowListFilter.getPresetId(),
				counterId: this.flowListFilter.getCounterType(),
				searchString: this.flowListFilter.getSearchString(),
			};
		}

		/**
		 * @private
		 * @param {string} prefix
		 * @return {string}
		 */
		getId(prefix)
		{
			return prefix;
		}

		showSearch()
		{
			if (this.search)
			{
				this.search.show();
			}
		}

		onPullCallback(data)
		{
			return new Promise((resolve) => {
				const commands = {
					flow_add: TasksPull.command.ADDED,
					flow_update: TasksPull.command.UPDATED,
					flow_delete: TasksPull.command.DELETED,
					comment_add: TasksPull.command.UPDATED,
					task_add: TasksPull.command.UPDATED,
					task_update: TasksPull.command.UPDATED,
					task_view: TasksPull.command.UPDATED,
					task_remove: TasksPull.command.UPDATED,
				};
				if (commands[data.command])
				{
					const flowId = this.parseFlowId(data.params);

					if (flowId > 0)
					{
						resolve({
							params: {
								eventName: commands[data.command],
								items: [
									{
										id: flowId,
									},
								],
							},
						});
					}
				}
			});
		}

		parseFlowId(data)
		{
			return (data.FLOW_ID ?? data.flowId ?? data?.AFTER?.FLOW_ID ?? 0).toString();
		}

		render()
		{
			return View(
				{
					resizableByKeyboard: true,
					style: {
						width: '100%',
						height: '100%',
					},
				},
				this.renderList(),
			);
		}

		getItemType = (item) => {
			if (item.id === FLOWS_INFO_ITEM_ID)
			{
				return ListItemType.FLOWS_INFO;
			}

			if (this.isFlowsList())
			{
				if (item.demo)
				{
					return ListItemType.PROMO_FLOW;
				}

				if (item.active)
				{
					return ListItemType.FLOW;
				}

				return ListItemType.DISABLED_FLOW;
			}

			return ListItemType.SIMILAR_FLOW;
		};

		getItemProps = (item) => {
			const type = this.getItemType(item);

			return {
				onCloseButtonClick: type === ListItemType.FLOWS_INFO
					? this.onFlowsInfoItemCloseButtonClick
					: null,
				type,
			};
		};

		renderList()
		{
			const statefulList = new StatefulList({
				layout: this.layout,
				testId: 'task-list',
				showAirStyle: true,
				itemsLoadLimit: 10,
				itemType: this.getItemProps,
				itemFactory: FlowListItemsFactory,
				typeGenerator: {
					generator: TypeGenerator.generators.bySelectedProperties,
					properties: [
						'id',
						'demo',
						'active',
						'myTasksCounter',
						'myTasksTotal',
						'pending',
						'atWork',
						'completed',
						'isLast',
					],
				},
				needInitMenu: this.isFlowsList(),
				menuButtons: this.getLayoutMenuButtons(),
				actionParams: {
					loadItems: {
						flowSearchParams: this.getSearchParams(),
						order: this.sorting.getType(),
					},
				},
				actions: {
					loadItems: 'tasksmobile.Flow.loadItems',
				},
				actionCallbacks: {
					loadItems: this.onItemsLoaded,
				},
				pull: this.pull.getPullConfig(),
				sortingConfig: this.sorting.getSortingConfig(),
				isShowFloatingButton: false,
				getEmptyListComponent: this.getEmptyListComponent,
				itemDetailOpenHandler: this.onItemClick,
				onBeforeItemsRender: this.onBeforeItemsRender,
				onPanListHandler: this.onPanList,
				showTitleLoader: this.showTitleLoader,
				hideTitleLoader: this.hideTitleLoader,
				ref: this.bindRef,
				itemLayoutOptions: {},
				animationTypes: {
					insertRows: 'fade',
					updateRows: 'none',
					deleteRow: 'fade',
					moveRow: true,
				},
				analyticsLabel: {
					c_section: 'flows',
					c_sub_section: 'flows_grid',
					c_element: 'flows_grid_button',
				},
			});

			return View(
				{
					style: {
						width: '100%',
						height: '100%',
					},
				},
				statefulList,
			);
		}

		onPanList = () => {
			if (this.search)
			{
				this.search.close();
			}
		};

		onBeforeItemsRender(items, { allItemsLoaded })
		{
			const sortingField = this.sorting.getConvertedType();

			return items.map((item, index) => {
				return {
					id: item.id,
					key: item.key,
					type: item.type,
					active: item.active,
					demo: item.demo,
					isLast: allItemsLoaded && index === items.length - 1,
					[sortingField]: item[sortingField],
				};
			});
		}

		showTitleLoader({ useCache, isDefaultBlockPage })
		{
			if (!isDefaultBlockPage || !this.navigationTitle)
			{
				return;
			}

			const status = useCache
				? NavigationTitle.ConnectionStatus.CONNECTION
				: NavigationTitle.ConnectionStatus.SYNC
			;

			this.navigationTitle.setDashboardStatus(status);
		}

		hideTitleLoader(isCache)
		{
			if (!this.navigationTitle)
			{
				return;
			}

			const status = isCache
				? NavigationTitle.ConnectionStatus.SYNC
				: NavigationTitle.ConnectionStatus.NONE
			;

			this.navigationTitle.setDashboardStatus(status);
		}

		getLayoutMenuButtons()
		{
			if (this.isFlowsList())
			{
				return [
					this.search.getSearchButton(),
					this.moreMenu.getMenuButton(),
				];
			}

			return [];
		}

		getEmptyListImage()
		{
			return makeLibraryImagePath('flow-list.svg', 'empty-states', 'tasks');
		}

		getEmptyListComponent()
		{
			const { title, description, uri } = this.getEmptyListProps();

			const imageParams = {
				resizeMode: 'contain',
				style: {
					width: 327,
					height: 140,
				},
				svg: { uri },
			};

			return StatusBlock({
				testId: 'flow-status-block',
				title,
				description,
				emptyScreen: true,
				image: Image(imageParams),
				onRefresh: this.onPullToRefresh,
			});
		}

		getEmptyListProps()
		{
			let title = '';
			let description = '';
			const uri = this.getEmptyListImage();

			if (this.isFlowsList())
			{
				const isEmptySearch = (
					this.flowListFilter.isSearchStringEmpty()
					&& this.flowListFilter.isEmptyCounter()
					&& this.flowListFilter.isRoleForAll()
					&& this.flowListFilter.isEmptyPreset()
				);
				if (isEmptySearch)
				{
					title = Loc.getMessage('M_TASKS_FLOW_LIST_EMPTY_TITLE');
					description = Loc.getMessage('M_TASKS_FLOW_LIST_EMPTY_DESCRIPTION');
				}
				else
				{
					title = Loc.getMessage('M_TASKS_FLOW_LIST_TITLE');
					description = Loc.getMessage('M_TASKS_FLOW_LIST_DESCRIPTION');
				}
			}
			else
			{
				title = Loc.getMessage('M_TASKS_FLOW_LIST_SIMILAR_EMPTY_TITLE');
				description = Loc.getMessage('M_TASKS_FLOW_LIST_SIMILAR_EMPTY_DESCRIPTION');
			}

			return { title, description, uri };
		}
	}

	setTimeout(() => requireLazy('tasks:layout/flow/detail', false), 1000);

	module.exports = { TasksFlowList, ListType };
});
