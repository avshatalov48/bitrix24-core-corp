(() => {
	const require = (ext) => jn.require(ext);

	const AppTheme = require('apptheme');
	const { downloadImages } = require('asset-manager');
	const { KanbanAdapter, ListAdapter } = require('tasks/layout/dashboard');
	const { Loc } = require('loc');
	const { magnifierWithMenuAndDot } = require('assets/common');
	const { TaskFilter } = require('tasks/filter/task');
	const { batchActions } = require('statemanager/redux/batched-actions');
	const store = require('statemanager/redux/store');
	const { usersUpserted, usersAdded, usersSelector } = require('statemanager/redux/slices/users');
	const { Filter, MoreMenu, NavigationTitle, Pull, Sorting } = require('tasks/dashboard');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { SearchLayout } = require('layout/ui/search-bar');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const { ActionMenu } = require('tasks/layout/action-menu');
	const { Haptics } = require('haptics');
	const { fetchStages } = require('tasks/statemanager/redux/slices/kanban-settings');
	const { taskStageAdded, taskStageUpserted } = require('tasks/statemanager/redux/slices/tasks-stages');
	const { TaskCreate } = require('tasks/layout/task/create');
	const { CalendarSettings } = require('tasks/task/calendar');
	const { Pull: TasksPull } = require('layout/ui/stateful-list/pull');
	const { Views } = require('tasks/statemanager/redux/types');
	const { mergeImmutable } = require('utils/object');
	const { executeIfOnline } = require('tasks/layout/online');
	const { Feature } = require('feature');
	const { showToast } = require('toast');
	const { Type } = require('type');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const {
		tasksUpserted,
		tasksAdded,
		selectById,
		selectEntities,
		mapStateToTaskModel,
		readAllForRole,
		readAllForProject,
	} = require('tasks/statemanager/redux/slices/tasks');
	const { observeCounterChange } = require('tasks/statemanager/redux/slices/tasks/observers/counter-observer');
	const { observeListChange } = require('tasks/statemanager/redux/slices/tasks/observers/stateful-list-observer');

	const { groupsUpserted, groupsAdded, selectGroupById } = require('tasks/statemanager/redux/slices/groups');
	const { dispatch } = store;
	const { selectTaskStageId } = require('tasks/statemanager/redux/slices/tasks-stages');
	const { increaseStageCounter, decreaseStageCounter } = require('tasks/statemanager/redux/slices/stage-counters');

	class TasksDashboard extends LayoutComponent
	{
		// region initialize

		constructor(props)
		{
			super(props);

			this.showSearch = this.showSearch.bind(this);
			this.openViewSwitcher = this.openViewSwitcher.bind(this);
			this.onItemsLoaded = this.onItemsLoaded.bind(this);
			this.onSearch = this.onSearch.bind(this);
			this.onCounterClick = this.onCounterClick.bind(this);
			this.onSortingClick = this.onSortingClick.bind(this);
			this.onReadAllClick = this.onReadAllClick.bind(this);
			this.onItemClick = this.onItemClick.bind(this);
			this.onItemLongClick = this.onItemLongClick.bind(this);
			this.onFloatingButtonClick = this.onFloatingButtonClick.bind(this);
			this.onPanList = this.onPanList.bind(this);
			this.onPullToRefresh = this.onPullToRefresh.bind(this);
			this.getEmptyListComponent = this.getEmptyListComponent.bind(this);
			this.bindRef = this.bindRef.bind(this);
			this.onItemAdded = this.onItemAdded.bind(this);
			this.onItemDeleted = this.onItemDeleted.bind(this);
			this.onListReloaded = this.onListReloaded.bind(this);
			this.onBeforeItemsRender = this.onBeforeItemsRender.bind(this);
			this.onBeforeItemsSetState = this.onBeforeItemsSetState.bind(this);
			this.onVisibleTasksChange = this.onVisibleTasksChange.bind(this);
			this.onCounterChange = this.onCounterChange.bind(this);
			this.skipTasksOperations = this.skipTasksOperations.bind(this);
			this.showTitleLoader = this.showTitleLoader.bind(this);
			this.hideTitleLoader = this.hideTitleLoader.bind(this);

			this.tasksFilter = new Filter(
				this.props.currentUserId,
				this.props.ownerId,
				this.props.projectId,
				this.props.isTabsMode,
				this.props.tabsGuid,
			);
			this.tasksFilter.updateCounters().then(() => this.updateMoreMenuButton()).catch(console.error);

			this.onPullCallback = this.onPullCallback.bind(this);
			this.pull = new Pull({
				onPullCallback: this.onPullCallback,
				eventCallbacks: {
					[Pull.events.USER_COUNTER]: (params) => {
						this.tasksFilter.updateCountersFromPullEvent(params)
							.then(() => this.updateMoreMenuButton())
							.catch(console.error)
						;
					},
				},
			});

			this.sorting = new Sorting();

			const {
				loading,
				view,
				displayFields,
				calendarSettings,
			} = this.getCachedSettings();

			this.moreMenu = new MoreMenu(
				this.tasksFilter.getCountersByRole(),
				this.tasksFilter.getCounterType(),
				this.sorting.getType(),
				{
					onCounterClick: this.onCounterClick,
					onSortingClick: this.onSortingClick,
					onReadAllClick: this.onReadAllClick,
				},
			);

			this.search = new SearchLayout({
				layout,
				id: 'my_tasks',
				cacheId: `my_tasks_${env.userId}`,
				presetId: TaskFilter.presetType.default,
				searchDataAction: 'tasksmobile.Filter.getSearchBarPresets',
				searchDataActionParams: {
					groupId: this.props.projectId,
				},
				onSearch: this.onSearch,
				onCancel: this.onSearch,
			});

			this.navigationTitle = new NavigationTitle({ layout });

			/** @type {TasksDashboardBaseView} */
			this.currentView = null;

			/** @type {JSSharedStorage} */
			this.cache = Application.sharedStorage('TasksDashboard');

			const cachedSorting = this.cache.get(this.getId('sorting'));
			this.setSorting(cachedSorting);

			/** @type {RunActionExecutor} */
			this.settingsActionExecutor = null;

			this.markedAsRemoveTasks = new Set();

			CalendarSettings.setSettings(calendarSettings);

			this.state = {
				loading,
				view: this.getValidView(view),
				displayFields,
				sorting: this.sorting.getType(),
				counter: this.tasksFilter.getCounterValue(),
			};
		}

		async componentDidMount()
		{
			if (this.state.loading)
			{
				await this.fetchSettings();
			}

			this.refreshStages();
			this.updateMoreMenuButton();
			this.subscribe();

			setTimeout(async () => {
				this.prefetchAssets();

				await CalendarSettings.loadSettings();
				this.updateCalendarSettingsInCache(CalendarSettings.getSettings());
			}, 1000);
		}

		subscribe()
		{
			this.pull.subscribe();
			this.unsubscribeCounterChangeObserver = observeCounterChange(store, this.onCounterChange);
			this.unsubscribeTasksObserver = observeListChange(store, this.onVisibleTasksChange);

			if (this.props.isTabsMode && this.isMyDashboard())
			{
				BX.addCustomEvent('tasks.tabs:onAppPaused', (eventData) => this.onAppPaused(eventData));
				BX.addCustomEvent('tasks.tabs:onAppActive', (eventData) => this.onAppActive(eventData));
			}
		}

		prefetchAssets()
		{
			const viewIcons = Object.values(SvgIcons).filter((icon) => icon !== null);

			void downloadImages([
				...viewIcons,
				this.getEmptyListImage('list', false),
				this.getEmptyListImage('list', true),
				this.getEmptyListImage('kanban', true),
				this.getEmptyListImage('kanban', false),
			]);
		}

		getCachedSettings()
		{
			const response = this.getSettingsActionExecutor().getCache().getData();

			return this.getPreparedSettings(response);
		}

		updateViewInCache(view)
		{
			this.updateSettingsCache({ view });
		}

		updateCalendarSettingsInCache(calendarSettings)
		{
			this.updateSettingsCache({ calendarSettings });
		}

		updateSettingsCache(settings)
		{
			const cache = this.getSettingsActionExecutor().getCache();
			const response = cache.getData();

			cache.saveData(mergeImmutable(response, {
				data: {
					...response.data,
					...settings,
				},
			}));
		}

		getPreparedSettings(response)
		{
			const {
				view,
				displayFields,
				calendarSettings,
			} = response?.data || {};

			const loading = (
				view === undefined
				|| displayFields === undefined
				|| calendarSettings === undefined
			);

			return {
				loading,
				view,
				displayFields,
				calendarSettings,
			};
		}

		async fetchSettings()
		{
			const response = await this.getSettingsActionExecutor().call(false);

			await this.redrawNewSettings(response);
		}

		getSettingsActionExecutor()
		{
			if (!this.settingsActionExecutor)
			{
				const { projectId } = this.props;

				this.settingsActionExecutor = new RunActionExecutor(
					'tasksmobile.Task.getDashboardSettings',
					{ projectId },
				);
			}

			return this.settingsActionExecutor;
		}

		redrawNewSettings(response)
		{
			const {
				view: viewFromResponse,
				displayFields: displayFieldsFromResponse,
				calendarSettings: calendarSettingsFromResponse,
			} = this.getPreparedSettings(response);

			CalendarSettings.setSettings(calendarSettingsFromResponse);

			const {
				view = viewFromResponse,
				displayFields = displayFieldsFromResponse,
			} = this.getCachedSettings();

			return new Promise((resolve) => {
				this.setState({
					loading: false,
					view: this.getValidView(view),
					displayFields,
				}, resolve);
			});
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
		}

		// endregion

		// region handle UI events

		onVisibleTasksChange({ moved, removed, added })
		{
			if (!this.currentView || this.currentView.isLoading())
			{
				// delay until list is loaded to prevent race-condition with addItems loading
				setTimeout(() => {
					this.onVisibleTasksChange({ moved, removed, added });
				}, 30);

				return;
			}

			if (removed.length > 0)
			{
				void this.removeTasks(removed);
			}

			if (added.length > 0)
			{
				void this.addOrRestoreTasks(added);
			}

			if (moved.length > 0)
			{
				void this.updateTasks(moved);
			}
		}

		onCounterChange(counterChangeValue)
		{
			this.tasksFilter.updateCounterValue(this.tasksFilter.getCounterValue() + counterChangeValue);
		}

		/**
		 * @param {TaskReduxModel[]} tasks
		 * @return {Promise}
		 */
		updateTasks(tasks)
		{
			return this.currentView.updateItemsData(tasks);
		}

		/**
		 * @param {TaskReduxModel[]} tasks
		 * @return {Promise}
		 */
		removeTasks(tasks)
		{
			const markAsRemovedTaskIds = tasks.filter((task) => task.isRemoved).map(({ id }) => id);
			if (markAsRemovedTaskIds.length > 0)
			{
				markAsRemovedTaskIds.forEach((id) => this.markedAsRemoveTasks.add(id));
			}

			return this.currentView.removeItems(tasks);
		}

		/**
		 * @param {TaskReduxModel[]} tasks
		 */
		async addOrRestoreTasks(tasks)
		{
			const restoredTasks = tasks.filter(({ id }) => this.markedAsRemoveTasks.has(id));
			if (restoredTasks.length > 0)
			{
				await this.currentView.restoreItems(restoredTasks);
			}

			const otherTasksToAdd = tasks.filter(({ id }) => !this.markedAsRemoveTasks.has(id));
			if (otherTasksToAdd.length > 0)
			{
				await this.currentView.addItems(otherTasksToAdd);
			}

			tasks.forEach(({ id }) => this.markedAsRemoveTasks.delete(id));
		}

		onCounterClick(newCounter)
		{
			const currentCounter = this.tasksFilter.getCounterType();
			const counter = (newCounter === currentCounter ? TaskFilter.counterType.none : newCounter);

			this.tasksFilter.setCounterType(counter);
			this.moreMenu.setSelectedCounter(counter);
			this.setState({ counter }, () => this.reload());
		}

		onSortingClick(sorting)
		{
			if (this.sorting.getType() !== sorting)
			{
				this.setSorting(sorting);
				this.setState({ sorting }, () => {
					this.cache.set(this.getId('sorting'), sorting);
					this.reload();
				});
			}
		}

		setSorting(sorting)
		{
			this.sorting.setType(sorting);
			// Note: is temporary solution, until user will be able to configure order, too.
			this.sorting.setOrder(!(this.sorting.getType() === Sorting.type.ACTIVITY));

			this.moreMenu.setSelectedSorting(this.sorting.getType());
		}

		onReadAllClick()
		{
			executeIfOnline(() => this.readAll());
		}

		readAll()
		{
			const fields = {
				groupId: (this.props.projectId || null),
			};
			let action = readAllForProject({ fields });

			if (!this.props.projectId || this.tasksFilter.getRole() !== TaskFilter.roleType.all)
			{
				fields.userId = (this.props.ownerId || this.props.currentUserId);
				fields.role = this.tasksFilter.getRole();

				action = readAllForRole({ fields });
			}

			const newCommentsCount = this.tasksFilter.getCountersByRole()[TaskFilter.counterType.newComments];
			this.tasksFilter.updateCounterValue(this.tasksFilter.getCounterValue() - newCommentsCount);

			if (Feature.isToastSupported())
			{
				showToast(
					{
						code: 'readAll',
						message: Loc.getMessage('M_TASKS_VIEW_ROUTER_COMMENTS_READ'),
						svg: {
							url: SvgIcons.readAll,
						},
					},
					layout,
				);
			}
			else
			{
				// eslint-disable-next-line no-undef
				Notify.showMessage('', Loc.getMessage('M_TASKS_VIEW_ROUTER_COMMENTS_READ'));
			}

			dispatch(action);
		}

		/**
		 * @private
		 * @param {object|undefined} responseData
		 * @param {'ajax'|'cache'} context
		 */
		onItemsLoaded(responseData, context)
		{
			const { users = [], items = [], groups = [], tasksStages = [] } = responseData || {};
			const isCache = context === 'cache';

			const actions = [];

			if (items.length > 0)
			{
				actions.push(isCache ? tasksAdded(items) : tasksUpserted(items));
			}

			if (users.length > 0)
			{
				actions.push(isCache ? usersAdded(users) : usersUpserted(users));
			}

			if (groups.length > 0)
			{
				actions.push(isCache ? groupsAdded(groups) : groupsUpserted(groups));
			}

			if (tasksStages.length > 0)
			{
				actions.push(isCache ? taskStageAdded(tasksStages) : taskStageUpserted(tasksStages));
			}

			if (actions.length > 0)
			{
				dispatch(batchActions(actions));
			}
		}

		onSearch({ text, presetId })
		{
			this.tasksFilter.setPreset(presetId);
			this.tasksFilter.setSearchString(text);
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

				task.open();
			}
		}

		onItemLongClick(itemId, itemData, params)
		{
			Haptics.impactLight();

			const actions = {
				[ActionMenu.action.complete]: true,
				[ActionMenu.action.renew]: true,
				[ActionMenu.action.approve]: true,
				[ActionMenu.action.disapprove]: true,
				[ActionMenu.action.ping]: true,
				[ActionMenu.action.pin]: true,
				[ActionMenu.action.unpin]: true,
				[ActionMenu.action.mute]: true,
				[ActionMenu.action.unmute]: true,
				[ActionMenu.action.unfollow]: true,
				[ActionMenu.action.read]: true,
				[ActionMenu.action.share]: true,
				[ActionMenu.action.remove]: true,
			};

			if (!this.isSelectedView(Views.LIST))
			{
				delete actions[ActionMenu.action.pin];
				delete actions[ActionMenu.action.unpin];
			}

			const actionMenu = new ActionMenu({
				actions: Object.keys(actions),
				layoutWidget: layout,
				taskId: itemId,
				analyticsLabel: {
					module: 'tasks',
					event: 'dashboard-item-menu-click',
					currentView: this.state.view,
					isCurrentUser: this.props.ownerId === this.props.currentUserId ? 'Y' : 'N',
					isProject: Type.isNumber(this.props.projectId) ? 'Y' : 'N',
				},
			});

			actionMenu.show();
		}

		onFloatingButtonClick()
		{
			// eslint-disable-next-line consistent-return
			const mapUser = (user) => {
				if (user)
				{
					return {
						id: user.id,
						name: user.fullName,
						icon: user.avatarSize100,
						link: user.link,
						workPosition: user.workPosition,
					};
				}
			};

			const taskCreateParameters = {
				initialTaskData: {
					responsible: mapUser(usersSelector.selectById(store.getState(), this.props.ownerId)),
				},
			};

			if (this.props.projectId > 0)
			{
				taskCreateParameters.initialTaskData.groupId = this.props.projectId;
				taskCreateParameters.initialTaskData.group = selectGroupById(store.getState(), this.props.projectId);
			}

			TaskCreate.open(taskCreateParameters);
		}

		onPanList()
		{
			this.search.close();
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
					this.tasksFilter.updateCounters().then(() => this.updateMoreMenuButton()).catch(console.error);
					this.reload();
				}
			}
		}

		// endregion

		// region internal api

		refreshStages()
		{
			if (this.state.view !== Views.LIST)
			{
				dispatch(fetchStages(this.getLoadStagesParams()));
			}
		}

		reload()
		{
			if (this.currentView && this.currentView.reload)
			{
				this.currentView.reload({
					menuButtons: this.getLayoutMenuButtons(),
					skipUseCache: true,
				});
			}
		}

		onPullToRefresh()
		{
			this.reload();
		}

		updateMoreMenuButton()
		{
			if (this.moreMenu)
			{
				this.moreMenu.setCounters(this.tasksFilter.getCountersByRole());
			}

			if (this.currentView)
			{
				this.currentView.updateTopButtons(this.getLayoutMenuButtons());
			}
		}

		getLoadStagesParams()
		{
			return {
				view: this.state.view,
				projectId: this.props.projectId,
				searchParams: this.getSearchParams(),
			};
		}

		getSearchParams()
		{
			return {
				ownerId: this.props.ownerId,
				presetId: this.tasksFilter.getPreset(),
				counterId: this.tasksFilter.getCounterType(),
				searchString: this.tasksFilter.getSearchString(),
			};
		}

		/**
		 * @private
		 * @param {TasksDashboardBaseView|undefined} ref
		 */
		bindRef(ref)
		{
			if (ref)
			{
				this.currentView = ref;
			}
		}

		/**
		 * @private
		 * @param {string} prefix
		 * @return {string}
		 */
		getId(prefix)
		{
			return `${prefix}-${this.props.ownerId}-${this.props.projectId}`;
		}

		showSearch()
		{
			this.search.show();
		}

		openViewSwitcher()
		{
			const items = Object.values(Views)
				.filter((view) => !(view === Views.KANBAN && this.props.projectId === null))
				.map((view) => ({
					id: view,
					onClickCallback: () => this.setView(view),
					title: Loc.getMessage(`M_TASKS_VIEW_ROUTER_MENU_TITLE_${view}`),
					data: {
						svgUri: SvgIcons[view],
					},
					isSelected: this.state.view === view,
					showSelectedImage: true,
				}));

			const menu = new ContextMenu({
				testId: 'TASKS_VIEW_SWITCHER',
				actions: items,
				params: {
					title: Loc.getMessage('M_TASKS_VIEW_ROUTER_MENU_TITLE'),
					showCancelButton: true,
					isRawIcon: true,
				},
				analyticsLabel: {
					module: 'tasks',
					event: 'dashboard-view-switcher-click',
					currentView: this.state.view,
					isCurrentUser: this.props.ownerId === this.props.currentUserId ? 'Y' : 'N',
					isProject: Type.isNumber(this.props.projectId) ? 'Y' : 'N',
				},
			});

			void menu.show();
		}

		setView(view)
		{
			const validView = this.getValidView(view);

			this.setState({ view: validView, loading: false }, () => {
				this.updateViewInCache(validView);
				this.refreshStages();
			});
		}

		getValidView(view)
		{
			return Object.values(Views).includes(view) ? view : Views.LIST;
		}

		isMyDashboard()
		{
			return (!this.isProjectDashboard() && !this.isAnotherUserDashboard());
		}

		isProjectDashboard()
		{
			return (this.props.projectId > 0);
		}

		isAnotherUserDashboard()
		{
			return (this.props.currentUserId !== this.props.ownerId);
		}

		// endregion

		// region push-and-pull

		onPullCallback(data)
		{
			return new Promise((resolve) => {
				const commands = {
					task_add: TasksPull.command.ADDED,
					task_update: TasksPull.command.UPDATED,
					task_view: TasksPull.command.UPDATED,
					user_option_changed: TasksPull.command.UPDATED,
					comment_add: TasksPull.command.UPDATED,
					comment_read_all: TasksPull.command.RELOAD,
					task_remove: TasksPull.command.DELETED,
					task_result_create: TasksPull.command.UPDATED,
					task_result_delete: TasksPull.command.UPDATED,
					task_timer_start: TasksPull.command.UPDATED,
					task_timer_stop: TasksPull.command.UPDATED,
				};
				if (commands[data.command])
				{
					const taskId = this.parseTaskId(data.params);

					resolve({
						params: {
							eventName: commands[data.command],
							items: [
								{
									id: taskId,
									data: {
										id: taskId,
										name: data.params.AFTER?.TITLE || taskId,
									},
								},
							],
						},
					});
				}
			});
		}

		parseTaskId(data)
		{
			return (data.TASK_ID ?? data.taskId ?? data.entityXmlId ?? 0).toString();
		}

		// endregion

		// region render

		render()
		{
			return View(
				{ resizableByKeyboard: true },
				this.state.loading && new LoadingScreenComponent({}),
				this.isSelectedView(Views.PLANNER) && this.renderPlannerView(),
				this.isSelectedView(Views.DEADLINE) && this.renderDeadlineView(),
				this.isSelectedView(Views.KANBAN) && this.renderKanbanView(),
				this.isSelectedView(Views.LIST) && this.renderListView(),
			);
		}

		renderKanbanView()
		{
			return new KanbanAdapter(this.prepareViewProperties({
				id: this.getId('personal-kanban-kanban'),
				title: Loc.getMessage('M_TASKS_VIEW_ROUTER_MENU_TITLE_KANBAN'),
				actions: {
					loadItems: 'tasksmobile.Task.getProjectKanbanTasks',
					updateItemStage: 'tasksmobile.Task.updateProjectKanbanTaskStage',
					deleteItem: '',
				},
			}));
		}

		renderPlannerView()
		{
			return new KanbanAdapter(this.prepareViewProperties({
				id: this.getId('personal-kanban-planner'),
				title: Loc.getMessage('M_TASKS_VIEW_ROUTER_MENU_TITLE_PLANNER'),
				actions: {
					loadItems: this.props.projectId === null ? 'tasksmobile.Task.getUserPlannerTasks' : 'tasksmobile.Task.getProjectPlannerTasks',
					updateItemStage: this.props.projectId === null
						? 'tasksmobile.Task.updateUserPlannerTaskStage'
						: 'tasksmobile.Task.updateProjectPlannerTaskStage',
					deleteItem: '',
				},
			}));
		}

		renderDeadlineView()
		{
			return new KanbanAdapter(this.prepareViewProperties({
				id: this.getId('personal-kanban-deadline'),
				title: Loc.getMessage('M_TASKS_VIEW_ROUTER_MENU_TITLE_DEADLINE'),
				actions: {
					loadItems: this.props.projectId === null ? 'tasksmobile.Task.getUserDeadlineTasks' : 'tasksmobile.Task.getProjectDeadlineTasks',
					updateItemStage: this.props.projectId === null
						? 'tasksmobile.Task.updateUserDeadlineTaskStage'
						: 'tasksmobile.Task.updateProjectDeadlineTaskStage',
					deleteItem: '',
				},
			}));
		}

		renderListView()
		{
			return new ListAdapter(this.prepareViewProperties({
				id: this.getId('task-list'),
				itemsLoadLimit: 30,
				actions: {
					loadItems: this.props.projectId === null ? 'tasksmobile.Task.getUserListTasks' : 'tasksmobile.Task.getProjectListTasks',
				},
			}));
		}

		/**
		 * @private
		 * @param {object} overrides
		 * @return {object}
		 */
		prepareViewProperties(overrides = {})
		{
			const defaults = {
				layout,
				ownerId: this.props.ownerId,
				loadStagesParams: this.getLoadStagesParams(),
				layoutMenuButtons: this.getLayoutMenuButtons(),
				actionParams: {
					loadItems: {
						projectId: this.props.projectId,
						searchParams: this.getSearchParams(),
						order: this.sorting.getType(),
					},
					updateItemStage: {
						projectId: this.props.projectId,
						searchParams: this.getSearchParams(),
						order: this.sorting.getType(),
					},
				},
				actionCallbacks: {
					loadItems: this.onItemsLoaded,
				},
				pull: this.pull.getPullConfig(),
				sortingConfig: this.sorting.getSortingConfig(),
				getEmptyListComponent: this.getEmptyListComponent,
				onItemClick: this.onItemClick,
				onItemLongClick: this.onItemLongClick,
				onFloatingButtonClick: this.onFloatingButtonClick,
				onPanList: this.onPanList,
				onItemAdded: this.onItemAdded,
				onItemDeleted: this.onItemDeleted,
				onListReloaded: this.onListReloaded,
				onBeforeItemsSetState: this.onBeforeItemsSetState,
				onBeforeItemsRender: this.onBeforeItemsRender,
				changeItemsOperations: this.skipTasksOperations,
				showTitleLoader: this.showTitleLoader,
				hideTitleLoader: this.hideTitleLoader,
				ref: this.bindRef,
				itemLayoutOptions: {
					canBePinned: true,
					displayFields: this.state.displayFields ? this.state.displayFields[this.state.view] : {},
				},
				animationTypes: {
					insertRows: 'fade',
					updateRows: 'none',
					deleteRow: 'fade',
					moveRow: true,
				},
			};

			return mergeImmutable(defaults, overrides);
		}

		onBeforeItemsSetState(items)
		{
			const taskEntities = selectEntities(store.getState());

			// filter marked as removed tasks
			return items.filter(({ id }) => {
				const { isRemoved } = taskEntities[id] || {};

				return Type.isNil(isRemoved) || !isRemoved;
			});
		}

		onBeforeItemsRender(items, { allItemsLoaded })
		{
			const pinnedItems = items.filter((item) => item.isPinned === true);
			const lastPinnedIndex = pinnedItems.length - 1;
			const lastIndex = items.length - 1;
			const sortingField = this.sorting.getConvertedType();

			return items.map((item, index) => {
				const newItem = {
					id: item.id,
					key: item.key,
					type: item.type,
					[sortingField]: item[sortingField],
				};

				if (this.isSelectedView(Views.LIST))
				{
					newItem.showBorder = !allItemsLoaded || index !== lastIndex;
					newItem.isLastPinned = index === lastPinnedIndex;
				}

				return newItem;
			});
		}

		skipTasksOperations(prevItemsState, nextItemsState, groupedOperations)
		{
			if (groupedOperations.toUpdateItems.length > 0)
			{
				const newToUpdateItems = groupedOperations.toUpdateItems.filter((item) => {
					const oldItem = prevItemsState.find((element) => element.id === item.id);
					const newItem = nextItemsState.find((element) => element.id === item.id);

					return oldItem.isLastPinned !== newItem.isLastPinned
						|| oldItem.showBorder !== newItem.showBorder;
				});

				return {
					...groupedOperations,
					toUpdateItems: newToUpdateItems,
				};
			}

			return groupedOperations;
		}

		showTitleLoader({ useCache, isDefaultBlockPage })
		{
			if (!isDefaultBlockPage)
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
			const status = isCache
				? NavigationTitle.ConnectionStatus.SYNC
				: NavigationTitle.ConnectionStatus.NONE
			;

			this.navigationTitle.setDashboardStatus(status);
		}

		onItemAdded(item)
		{
			if (this.state.view !== Views.LIST)
			{
				const newTaskStageId = selectTaskStageId(store.getState(), item.id, this.state.view, this.props.ownerId);
				if (!Type.isNil(newTaskStageId))
				{
					dispatch(increaseStageCounter({
						ownerId: this.props.ownerId,
						projectId: this.props.projectId,
						view: this.state.view,
						stageId: newTaskStageId,
					}));
				}
			}
		}

		onItemDeleted(item)
		{
			if (this.state.view !== Views.LIST)
			{
				const task = selectById(store.getState(), item.id);
				const newTaskStageId = selectTaskStageId(store.getState(), item.id, this.state.view, this.props.ownerId);
				if (!Type.isNil(newTaskStageId) && (Type.isNil(task) || task.isRemoved === true))
				{
					dispatch(decreaseStageCounter({
						ownerId: this.props.ownerId,
						projectId: this.props.projectId,
						view: this.state.view,
						stageId: newTaskStageId,
					}));
				}
			}
		}

		onListReloaded(pullToReload)
		{
			if (!pullToReload)
			{
				this.markedAsRemoveTasks.clear();
			}

			this.refreshStages();
		}

		/**
		 * @private
		 * @param {string} view
		 * @return {boolean}
		 */
		isSelectedView(view)
		{
			if (this.state.loading)
			{
				return false;
			}

			return this.state.view === view;
		}

		getLayoutMenuButtons()
		{
			return [
				this.getSearchButton(),
				this.getToggleViewButton(),
				this.moreMenu.getMenuButton(),
			];
		}

		getSearchButton()
		{
			return {
				type: 'search',
				badgeCode: 'search',
				callback: this.showSearch,
				svg: {
					content: magnifierWithMenuAndDot(
						AppTheme.colors.base4,
						this.search.getSearchButtonBackgroundColor(),
					),
				},
			};
		}

		getToggleViewButton()
		{
			return {
				type: `view-switcher-${this.state.view}`,
				callback: this.openViewSwitcher,
				svg: {
					uri: SvgIcons[this.state.view],
				},
			};
		}

		getEmptyListImage(viewType, search = false)
		{
			return EmptyScreen.makeLibraryImagePath(`${viewType}${search ? '-search' : ''}.svg`, 'tasks');
		}

		getEmptyListComponent()
		{
			const { title, description, uri } = this.getEmptyListProps();

			return new EmptyScreen({
				title,
				description,
				styles: {
					paddingHorizontal: 20,
				},
				image: {
					resizeMode: 'contain',
					style: {
						width: 172,
						height: 172,
					},
					svg: { uri },
				},
				onRefresh: this.onPullToRefresh,
			});
		}

		getEmptyListProps()
		{
			const listOrKanban = this.isSelectedView(Views.LIST) ? 'list' : 'kanban';

			let title = '';
			let description = '';
			let uri = '';

			const isEmptySearchExceptPresets = (
				this.tasksFilter.isSearchStringEmpty()
				&& this.tasksFilter.isEmptyCounter()
				&& this.tasksFilter.isRoleForAll()
			);

			if (!this.tasksFilter.isSearchStringEmpty())
			{
				title = Loc.getMessage('M_TASKS_VIEW_ROUTER_EMPTY_LIST_SEARCH_TITLE_MSGVER_1');
				description = Loc.getMessage('M_TASKS_VIEW_ROUTER_EMPTY_LIST_SEARCH_DESCRIPTION_MSGVER_1');
				uri = this.getEmptyListImage(listOrKanban, true);
			}
			else if (isEmptySearchExceptPresets)
			{
				if (this.tasksFilter.isEmptyPreset())
				{
					if (this.currentView.isAllStagesDisplayed())
					{
						title = Loc.getMessage('M_TASKS_VIEW_ROUTER_ALL_STAGES_TITLE_MSGVER_1');
						description = Loc.getMessage('M_TASKS_VIEW_ROUTER_ALL_STAGES_DESCRIPTION');
						uri = this.getEmptyListImage(listOrKanban, false);
					}
					else if (this.isSelectedView(Views.DEADLINE))
					{
						title = Loc.getMessage('M_TASKS_VIEW_ROUTER_KANBAN_FILTER_TITLE');
						description = Loc.getMessage(`M_TASKS_VIEW_ROUTER_DEADLINE_STAGE_${this.currentView.getActiveStage()?.statusId}`);
						uri = this.getEmptyListImage(listOrKanban, true);
					}
					else
					{
						title = Loc.getMessage('M_TASKS_VIEW_ROUTER_KANBAN_FILTER_TITLE');
						description = Loc.getMessage('M_TASKS_VIEW_ROUTER_KANBAN_FILTER_DESCRIPTION');
						uri = this.getEmptyListImage(listOrKanban, true);
					}
				}
				else if (this.tasksFilter.isDefaultPreset())
				{
					title = Loc.getMessage('M_TASKS_VIEW_ROUTER_EMPTY_IN_PROGRESS');
					uri = this.getEmptyListImage(listOrKanban, true);
				}
				else if (this.tasksFilter.getPresetName())
				{
					const message = Loc.getMessage('M_TASKS_VIEW_ROUTER_SELECTED_FILTER_TITLE', {
						'#FILTER_NAME#': this.tasksFilter.getPresetName(),
					});

					title = () => BBCodeText({
						style: {
							color: AppTheme.colors.base1,
							fontSize: 25,
							textAlign: 'center',
							marginBottom: 12,
						},
						value: message,
					});
					uri = this.getEmptyListImage(listOrKanban, true);
				}
			}

			if (!uri)
			{
				title = Loc.getMessage('M_TASKS_VIEW_ROUTER_EMPTY_LIST_SEARCH_TITLE_MSGVER_1');
				description = Loc.getMessage('M_TASKS_VIEW_ROUTER_EMPTY_LIST_SEARCH_DESCRIPTION_MSGVER_1');
				uri = this.getEmptyListImage(listOrKanban, true);
			}

			return { title, description, uri };
		}

		// endregion
	}

	const pathToIcons = `${currentDomain}/bitrix/mobileapp/tasksmobile/components/tasks/tasks.dashboard/icons`;
	const viewIconsPrefix = `${pathToIcons}/view`;

	const SvgIcons = {
		[Views.LIST]: `${viewIconsPrefix}-list.svg`,
		[Views.KANBAN]: `${viewIconsPrefix}-kanban.svg`,
		[Views.PLANNER]: `${viewIconsPrefix}-planner.svg`,
		[Views.DEADLINE]: `${viewIconsPrefix}-deadline.svg`,
		readAll: `${pathToIcons}/read-all.svg`,
	};

	const projectId = Number(BX.componentParameters.get('GROUP_ID', 0));

	layout.showComponent(
		new TasksDashboard({
			currentUserId: Number(env.userId),
			ownerId: Number(BX.componentParameters.get('USER_ID', 0) || env.userId),
			projectId: projectId > 0 ? projectId : null,
			isTabsMode: BX.componentParameters.get('IS_TABS_MODE', false),
			tabsGuid: BX.componentParameters.get('TABS_GUID', ''),
		}),
	);
})();
