(() => {
	const require = (ext) => jn.require(ext);
	const { downloadImages, makeLibraryImagePath } = require('asset-manager');
	const { KanbanAdapter, ListAdapter } = require('tasks/layout/dashboard');
	const { Loc } = require('loc');
	const { batchActions } = require('statemanager/redux/batched-actions');
	const store = require('statemanager/redux/store');
	const { usersUpserted, usersAdded, usersSelector } = require('statemanager/redux/slices/users');
	const {
		SettingsActionExecutor,
		NavigationTitle,
		Pull,
		TasksDashboardFilter,
		TasksDashboardMoreMenu,
		TasksDashboardSorting,
	} = require('tasks/dashboard');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { SearchLayout } = require('layout/ui/search-bar');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const { ActionMenu, TopMenuEngine } = require('tasks/layout/action-menu');
	const { ActionId, ActionMeta } = require('tasks/layout/action-menu/actions');
	const { Haptics } = require('haptics');
	const { fetchStages } = require('tasks/statemanager/redux/slices/kanban-settings');
	const { taskStageAdded, taskStageUpserted } = require('tasks/statemanager/redux/slices/tasks-stages');
	const { openTaskCreateForm } = require('tasks/layout/task/create/opener');
	const { CalendarSettings } = require('tasks/task/calendar');
	const { Pull: TasksPull } = require('layout/ui/stateful-list/pull');
	const { Views } = require('tasks/statemanager/redux/types');
	const { mergeImmutable } = require('utils/object');
	const { executeIfOnline } = require('tasks/layout/online');
	const { Feature } = require('feature');
	const { showToast } = require('toast');
	const { Type } = require('type');
	const { DeadlinePeriod, FeatureId } = require('tasks/enum');
	const { fetchDisabledTools } = require('settings/disabled-tools');
	const {
		tasksUpserted,
		tasksAdded,
		selectByTaskIdOrGuid,
		selectEntities,
		selectIsCreating,
		selectMarkedAsRemoved,
		selectWithCreationError,
		remove,
		taskRemoved,
		mapStateToTaskModel,
		readAllForRole,
		readAllForProject,
	} = require('tasks/statemanager/redux/slices/tasks');
	const { observeCounterChange } = require('tasks/statemanager/redux/slices/tasks/observers/counter-observer');
	const { observeCreationError } = require('tasks/statemanager/redux/slices/tasks/observers/creation-error-observer');
	const { observeListChange } = require('tasks/statemanager/redux/slices/tasks/observers/stateful-list-observer');
	const { StatusBlock } = require('ui-system/blocks/status-block');

	const { groupsUpserted, groupsAdded, selectGroupById } = require('tasks/statemanager/redux/slices/groups');
	const { dispatch } = store;
	const { selectTaskStageId } = require('tasks/statemanager/redux/slices/tasks-stages');
	const { increaseStageCounter, decreaseStageCounter } = require('tasks/statemanager/redux/slices/stage-counters');
	const { upsertFlows, addFlows } = require('tasks/statemanager/redux/slices/flows');
	const { getFeatureRestriction, tariffPlanRestrictionsReady } = require('tariff-plan-restriction');
	const { Alert, makeButton, makeDestructiveButton } = require('alert');
	const { AnalyticsEvent } = require('analytics');
	const { Link4, LinkMode, LinkDesign } = require('ui-system/blocks/link');

	const AIR_STYLE_SUPPORTED = Feature.isAirStyleSupported();

	class TasksDashboard extends LayoutComponent
	{
		// region initialize

		constructor(props)
		{
			super(props);

			this.showSearch = this.showSearch.bind(this);
			this.onItemsLoaded = this.onItemsLoaded.bind(this);
			this.onSearch = this.onSearch.bind(this);
			this.onCheckRestrictions = this.onCheckRestrictions.bind(this);
			this.onCounterClick = this.onCounterClick.bind(this);
			this.onSortingClick = this.onSortingClick.bind(this);
			this.onReadAllClick = this.onReadAllClick.bind(this);
			this.onItemClick = this.onItemClick.bind(this);
			this.onItemLongClick = this.onItemLongClick.bind(this);
			this.onFloatingButtonClick = this.onFloatingButtonClick.bind(this);
			this.onPanList = this.onPanList.bind(this);
			this.onPullToRefreshForEmptyScreen = this.onPullToRefreshForEmptyScreen.bind(this);
			this.getEmptyListComponent = this.getEmptyListComponent.bind(this);
			this.bindRef = this.bindRef.bind(this);
			this.onItemAdded = this.onItemAdded.bind(this);
			this.onItemDeleted = this.onItemDeleted.bind(this);
			this.onListReloaded = this.onListReloaded.bind(this);
			this.onBeforeItemsRender = this.onBeforeItemsRender.bind(this);
			this.onBeforeItemsSetState = this.onBeforeItemsSetState.bind(this);
			this.onVisibleTasksChange = this.onVisibleTasksChange.bind(this);
			this.onCounterChange = this.onCounterChange.bind(this);
			this.onCreationErrorChange = this.onCreationErrorChange.bind(this);
			this.skipTasksOperations = this.skipTasksOperations.bind(this);
			this.showTitleLoader = this.showTitleLoader.bind(this);
			this.hideTitleLoader = this.hideTitleLoader.bind(this);
			this.getNavigationTitleParams = this.getNavigationTitleParams.bind(this);
			this.openViewSwitcher = this.openViewSwitcher.bind(this);

			this.tasksDashboardFilter = new TasksDashboardFilter(
				this.props.currentUserId,
				this.props.ownerId,
				this.props.projectId,
				this.props.isTabsMode,
				this.props.tabsGuid,
				this.getInitialPresetId(),
				this.getInitialRole(),
				this.props.isRootComponent,
				this.props.siteId,
			);
			this.tasksDashboardFilter.updateCounters().then(() => this.updateMoreMenuButton()).catch(console.error);

			this.onPullCallback = this.onPullCallback.bind(this);
			this.pull = new Pull({
				onPullCallback: this.onPullCallback,
				eventCallbacks: {
					[Pull.events.USER_COUNTER]: (params) => {
						this.tasksDashboardFilter.updateCountersFromPullEvent(params)
							.then(() => this.updateMoreMenuButton())
							.catch(console.error)
						;
					},
					[Pull.events.TASK_REMOVE]: (params) => {
						dispatch(taskRemoved({ taskId: params.TASK_ID }));
					},
				},
				isTabsMode: this.props.isTabsMode,
			});

			const {
				loading,
				view,
				displayFields,
				calendarSettings,
				canCreateTask,
			} = this.getCachedSettings();

			this.sorting = new TasksDashboardSorting({
				type: TasksDashboardSorting.types.ACTIVITY,
				view,
			});

			const { currentUserId, ownerId, projectId } = this.props;

			this.moreMenu = new TasksDashboardMoreMenu(
				this.tasksDashboardFilter.getCountersByRole(),
				this.tasksDashboardFilter.getCounterType(),
				this.sorting.getType(),
				{
					onCounterClick: this.onCounterClick,
					onSortingClick: this.onSortingClick,
					onReadAllClick: this.onReadAllClick,
					getSelectedView: () => this.state.view,
					getProjectId: () => projectId,
					openViewSwitcher: this.openViewSwitcher,
					onListClick: () => this.setView(Views.LIST),
					onKanbanClick: () => this.setView(Views.KANBAN),
					onPlannerClick: () => this.setView(Views.PLANNER),
					onDeadlineClick: () => this.setView(Views.DEADLINE),
					getOwnerId: () => ownerId || currentUserId,
				},
				this.getAnalyticsLabel(),
			);

			this.search = new SearchLayout({
				layout,
				id: 'my_tasks',
				cacheId: `my_tasks_${env.userId}`,
				presetId: this.getInitialPresetId(),
				searchDataAction: 'tasksmobile.Filter.getSearchBarPresets',
				searchDataActionParams: {
					groupId: projectId,
				},
				onSearch: this.onSearch,
				onCancel: this.onSearch,
				onCheckRestrictions: this.onCheckRestrictions,
				getDefaultPresetId: this.getDefaultPresetId,
			});

			this.navigationTitle = new NavigationTitle(this.getNavigationTitleParams());

			/** @type {TasksDashboardBaseView} */
			this.currentView = null;

			/** @type {JSSharedStorage} */
			this.cache = Application.sharedStorage('TasksDashboard');

			const cachedSorting = this.cache.get(this.getId('sorting'));
			this.setSorting(cachedSorting);

			/** @type {SettingsActionExecutor} */
			this.settingsActionExecutor = null;

			this.markedAsRemoveTasks = new Set();

			CalendarSettings.setSettings(calendarSettings);

			this.state = {
				loading,
				displayFields,
				canCreateTask,
				view: this.getValidView(view),
				sorting: this.sorting.getType(),
				counter: this.tasksDashboardFilter.getCounterValue(),
			};
		}

		getInitialRole()
		{
			return TasksDashboardFilter.roleType.all;
		}

		getInitialPresetId = () => {
			return TasksDashboardFilter.presetType.default;
		};

		getDefaultPresetId = () => {
			return TasksDashboardFilter.presetType.default;
		};

		getNavigationTitleParams()
		{
			const { flowId, flowName, flowEfficiency, projectId, isCollab, isRootComponent } = this.props;
			const navigationTitleParams = {
				layout,
			};

			if (flowId > 0)
			{
				const flowSubtitleEfficiencyText = Loc.getMessage(
					'M_TASKS_VIEW_FLOW_EFFICIENCY_NAVIGATION_SUBTITLE',
					{
						'#EFFICIENCY#': flowEfficiency ?? '',
					},
				);

				navigationTitleParams.statusTitleParamsMap = {
					[NavigationTitle.ConnectionStatus.NONE]: {
						largeMode: false,
						text: flowName ?? Loc.getMessage('M_TASKS_VIEW_FLOW_DEFAULT_NAVIGATION_TITLE'),
						detailText: (Type.isNil(flowEfficiency) ? '' : flowSubtitleEfficiencyText),
					},
				};
			}
			else if (projectId > 0 && isCollab)
			{
				navigationTitleParams.statusTitleParamsMap = {
					[NavigationTitle.ConnectionStatus.NONE]: {
						text: Loc.getMessage('M_TASKS_DASHBOARD_COLLAB_TITLE'),
					},
				};
			}
			else if (isRootComponent)
			{
				navigationTitleParams.statusTitleParamsMap = {
					[NavigationTitle.ConnectionStatus.NONE]: {
						text: Loc.getMessage('M_TASKS_DASHBOARD_COLLABER_TITLE'),
					},
				};
			}

			return navigationTitleParams;
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
			this.unsubscribeCreationErrorObserver = observeCreationError(store, this.onCreationErrorChange);

			if (this.isMyDashboard())
			{
				if (this.props.isTabsMode)
				{
					BX.addCustomEvent('tasks.tabs:onTabSelected', (eventData) => this.onTabSelected(eventData));
					BX.addCustomEvent('tasks.tabs:onAppPaused', (eventData) => this.onAppPaused(eventData));
					BX.addCustomEvent('tasks.tabs:onAppActive', (eventData) => this.onAppActive(eventData));
				}
				else if (this.props.isRootComponent)
				{
					BX.addCustomEvent('onAppPaused', () => this.onAppPaused({ tabId: this.getTabName() }));
					BX.addCustomEvent('onAppActive', () => this.onAppActive({ tabId: this.getTabName() }));
				}
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
				canCreateTask,
			} = response?.data || {};

			const loading = (
				view === undefined
				|| displayFields === undefined
				|| calendarSettings === undefined
				|| canCreateTask === undefined
			);

			return {
				loading,
				view,
				displayFields,
				calendarSettings,
				canCreateTask,
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
				const { projectId, ownerId } = this.props;

				this.settingsActionExecutor = new SettingsActionExecutor({ projectId, ownerId });
			}

			return this.settingsActionExecutor;
		}

		redrawNewSettings(response)
		{
			const {
				view: viewFromResponse,
				displayFields: displayFieldsFromResponse,
				calendarSettings: calendarSettingsFromResponse,
				canCreateTask: canCreateTaskFromResponse,
			} = this.getPreparedSettings(response);

			CalendarSettings.setSettings(calendarSettingsFromResponse);

			const {
				view = viewFromResponse,
				displayFields = displayFieldsFromResponse,
				canCreateTask = canCreateTaskFromResponse,
			} = this.getCachedSettings();

			const validView = this.getValidView(view);

			this.sorting.setView(validView);

			return new Promise((resolve) => {
				this.setState({
					displayFields,
					canCreateTask,
					loading: false,
					view: validView,
				}, resolve);
			});
		}

		componentWillUnmount()
		{
			this.pull.unsubscribe();

			this.unsubscribeTasksObserver?.();
			this.unsubscribeCounterChangeObserver?.();
			this.unsubscribeCreationErrorObserver?.();

			this.removeTasksMarkedAsRemoved();
		}

		removeTasksMarkedAsRemoved()
		{
			const toRemoveTasks = selectMarkedAsRemoved(store.getState());
			if (toRemoveTasks && toRemoveTasks.length > 0)
			{
				toRemoveTasks.forEach((task) => {
					dispatch(remove({ taskId: task.id }));
				});
			}
		}

		// endregion

		// region handle UI events.

		onVisibleTasksChange({ moved, removed, added, created })
		{
			if (!this.currentView || this.currentView.isLoading())
			{
				// delay until list is loaded to prevent race-condition with addItems loading.
				setTimeout(() => {
					this.onVisibleTasksChange({ moved, removed, added, created });
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

			if (created.length > 0)
			{
				void this.replaceTasks(created);
			}
		}

		onCounterChange(counterChangeValue)
		{
			this.tasksDashboardFilter.updateCounterValue(this.tasksDashboardFilter.getCounterValue() + counterChangeValue);
		}

		onCreationErrorChange({ added, removed })
		{
			if (this.state.view !== Views.LIST)
			{
				added.forEach((task) => this.decreaseStageCounter(task));
				removed.forEach((task) => this.increaseStageCounter(task));
			}
		}

		replaceTasks(tasks)
		{
			return this.currentView.replaceItems(tasks);
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
			const restoredTasks = [];
			const creatingTasks = [];
			const otherTasksToAdd = [];

			tasks.forEach((task) => {
				if (this.markedAsRemoveTasks.has(task.id))
				{
					restoredTasks.push(task);
				}
				else if (
					selectIsCreating(task)
					&& this.tasksDashboardFilter.isTaskSuitDashboard(
						task,
						this.props.projectId,
						this.currentView.getActiveStage()?.id,
						this.state.view,
					)
				)
				{
					creatingTasks.push(task);
				}
				else
				{
					otherTasksToAdd.push(task);
				}
			});

			if (restoredTasks.length > 0)
			{
				await this.currentView.restoreItems(restoredTasks);
			}

			if (creatingTasks.length > 0)
			{
				await this.currentView.addCreatingItems(creatingTasks);
			}

			if (otherTasksToAdd.length > 0)
			{
				await this.currentView.addItems(otherTasksToAdd);
			}

			tasks.forEach(({ id }) => this.markedAsRemoveTasks.delete(id));
		}

		onCounterClick(newCounter)
		{
			const currentCounter = this.tasksDashboardFilter.getCounterType();
			const counter = (newCounter === currentCounter ? TasksDashboardFilter.counterType.none : newCounter);

			this.tasksDashboardFilter.setCounterType(counter);
			this.moreMenu.setSelectedCounter(counter);
			this.setState({ counter }, () => this.reload());
		}

		onSortingClick(sorting)
		{
			if (this.sorting.getType() === sorting)
			{
				return;
			}

			this.setSorting(sorting);
			this.setState({ sorting }, () => {
				this.cache.set(this.getId('sorting'), sorting);
				this.reload();
			});
		}

		setSorting(sorting)
		{
			this.sorting.setType(sorting);
			this.sorting.setIsASC(!(this.sorting.getType() === TasksDashboardSorting.types.ACTIVITY));

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

			if (!this.props.projectId || this.tasksDashboardFilter.getRole() !== TasksDashboardFilter.roleType.all)
			{
				fields.userId = (this.props.ownerId || this.props.currentUserId);
				fields.role = this.tasksDashboardFilter.getRole();

				action = readAllForRole({ fields });
			}

			const newCommentsCount = this.tasksDashboardFilter.getCountersByRole()[
				TasksDashboardFilter.counterType.newComments
			];
			this.tasksDashboardFilter.updateCounterValue(this.tasksDashboardFilter.getCounterValue() - newCommentsCount);

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
			const { users = [], items = [], groups = [], flows = [], tasksStages = [] } = responseData || {};
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

			if (flows.length > 0)
			{
				actions.push(isCache ? addFlows(flows) : upsertFlows(flows));
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
			this.tasksDashboardFilter.setPresetId(presetId);
			this.tasksDashboardFilter.setSearchString(text);
			this.updateMoreMenuButton();

			// todo avoid setting state and duplicated ajax request
			this.setState({}, () => this.reload());
		}

		onCheckRestrictions()
		{
			const { isRestricted, showRestriction } = getFeatureRestriction(FeatureId.SEARCH);
			if (isRestricted())
			{
				showRestriction({ parentWidget: layout });
			}

			return isRestricted();
		}

		onItemWithCreationErrorClick(task)
		{
			Alert.confirm(
				Loc.getMessage('M_TASKS_DASHBOARD_CREATION_ERROR_ALERT_TITLE'),
				task.creationErrorText,
				[
					makeButton(
						Loc.getMessage('M_TASKS_DASHBOARD_CREATION_ERROR_ALERT_TRY_AGAIN'),
						() => {
							const mapUser = (user) => (
								user
									? {
										id: user.id,
										name: user.fullName,
										image: user.avatarSize100,
										link: user.link,
										workPosition: user.workPosition,
									}
									: undefined
							);
							const state = store.getState();
							const taskCreateParameters = {
								initialTaskData: {
									id: task.id,
									guid: task.guid,
									title: task.name,
									description: task.description,
									deadline: task.deadline ? new Date(task.deadline * 1000) : null,
									groupId: task.groupId,
									group: selectGroupById(state, task.groupId),
									priority: String(task.priority),
									parentId: task.parentId,
									responsible: mapUser(usersSelector.selectById(state, task.responsible)),
									accomplices: task.accomplices.map((userId) => {
										return mapUser(usersSelector.selectById(state, userId));
									}),
									auditors: task.auditors.map((userId) => {
										return mapUser(usersSelector.selectById(state, userId));
									}),
									uploadedFiles: task.uploadedFiles,
									tags: task.tags,
									crm: task.crm,
									flowId: task.flowId,
									relatedTaskId: task.relatedTaskId,
									checklistFlatTree: task.checklistFlatTree,
									startDatePlan: task.startDatePlan,
									endDatePlan: task.endDatePlan,
									imChatId: task.imChatId,
									imMessageId: task.imMessageId,
								},
								layoutWidget: layout,
							};

							openTaskCreateForm(taskCreateParameters);
						},
					),
					makeDestructiveButton(
						Loc.getMessage('M_TASKS_DASHBOARD_CREATION_ERROR_ALERT_REMOVE'),
						() => {
							ActionMeta[ActionId.REMOVE].handleAction({
								layout,
								taskId: task.id,
								options: {
									shouldBackOnRemove: false,
								},
							});
						},
					),
				],
			);
		}

		onItemClick(id, data, params)
		{
			const task = selectByTaskIdOrGuid(store.getState(), id);
			if (!task)
			{
				return;
			}

			if (task.isCreationErrorExist)
			{
				this.onItemWithCreationErrorClick(task);
			}
			else
			{
				// eslint-disable-next-line no-undef
				const oldTaskModel = new Task({ id: this.props.currentUserId });
				oldTaskModel.setData(mapStateToTaskModel(task));
				oldTaskModel.open(layout, 'tasks.dashboard', {
					analyticsLabel: {
						...this.getAnalyticsLabel(),
						c_element: this.getAnalyticsLabel()?.c_element ?? 'title_click',
						c_sub_section: this.getAnalyticsLabel()?.c_sub_section ?? params.view?.toLowerCase(),
					},
					view: this.state.view,
					kanbanOwnerId: params.ownerId,
				});
			}
		}

		onItemLongClick(itemId, itemData, params)
		{
			const task = selectByTaskIdOrGuid(store.getState(), itemId);
			if (task.isCreationErrorExist)
			{
				return;
			}

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
				task,
				actions: Object.keys(actions),
				shouldBackOnRemove: false,
				layoutWidget: layout,
				engine: AIR_STYLE_SUPPORTED ? new TopMenuEngine() : null,
				analyticsLabel: {
					...this.getAnalyticsLabel(),
					module: 'tasks',
					currentView: this.state.view,
					isCurrentUser: this.props.ownerId === this.props.currentUserId ? 'Y' : 'N',
					isProject: Type.isNumber(this.props.projectId) ? 'Y' : 'N',
					c_sub_section: this.getAnalyticsLabel()?.c_sub_section ?? this.state.view?.toLowerCase(),
				},
			});
			const target = this.currentView.getItemMenuViewRef(itemId);

			actionMenu.show({ target });
		}

		onFloatingButtonClick()
		{
			const mapUser = (user) => {
				if (user)
				{
					return {
						id: user.id,
						name: user.fullName,
						image: user.avatarSize100,
						link: user.link,
						workPosition: user.workPosition,
					};
				}

				return null;
			};

			const stage = this.currentView.getActiveStage();
			const analyticsEvent = new AnalyticsEvent({
				...this.getAnalyticsLabel(),
				c_sub_section: this.getAnalyticsLabel().c_sub_section ?? this.state.view?.toLowerCase(),
				c_element: this.getAnalyticsLabel().c_element ?? 'floating_button',
			});
			const taskCreateParameters = {
				stage,
				initialTaskData: {
					responsible: mapUser(usersSelector.selectById(store.getState(), this.props.ownerId)),
				},
				view: this.state.view,
				loadStagesParams: this.getLoadStagesParams(),
				layoutWidget: layout,
				context: 'tasks.dashboard',
				analyticsLabel: analyticsEvent.exportToObject(),
			};

			if (this.isSelectedView(Views.DEADLINE) && stage)
			{
				let deadline = null;

				if (stage.statusId !== DeadlinePeriod.PERIOD_NO_DEADLINE)
				{
					deadline = (stage.deadline ? new Date(stage.deadline * 1000) : undefined);
				}

				taskCreateParameters.initialTaskData.deadline = deadline;
			}

			if (this.props.projectId > 0)
			{
				taskCreateParameters.initialTaskData.groupId = this.props.projectId;
				taskCreateParameters.initialTaskData.group = selectGroupById(store.getState(), this.props.projectId);
			}

			if (this.props.flowId > 0)
			{
				const { isRestricted, showRestriction } = getFeatureRestriction(FeatureId.FLOW);
				if (isRestricted())
				{
					showRestriction({
						parentWidget: layout,
						analyticsData: analyticsEvent,
					});

					return;
				}
				taskCreateParameters.initialTaskData.flowId = this.props.flowId;
			}

			openTaskCreateForm(taskCreateParameters);
		}

		onPanList()
		{
			this.search.close();
		}

		onTabSelected(data)
		{
			if (data.tabId === this.getTabName())
			{
				this.onAppActive(data);
			}
			else
			{
				this.onAppPaused(data, true);
			}
		}

		onAppPaused(data, forced = false)
		{
			if (!forced && data.tabId !== this.getTabName())
			{
				return;
			}

			this.pauseTime = Date.now();
		}

		onAppActive(data)
		{
			if (data.tabId !== this.getTabName())
			{
				return;
			}

			if (this.pauseTime)
			{
				const minutesPassed = Math.round((Date.now() - this.pauseTime) / 60000);
				if (minutesPassed >= 30)
				{
					this.tasksDashboardFilter.updateCounters().then(() => this.updateMoreMenuButton()).catch(console.error);
					this.reload(false);
				}
			}
		}

		getTabName()
		{
			return 'tasks.dashboard';
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

		reload(skipUseCache = true)
		{
			if (this.currentView && this.currentView.reload)
			{
				this.currentView.reload({
					menuButtons: this.getLayoutMenuButtons(),
					skipUseCache,
				});
			}
		}

		onPullToRefreshForEmptyScreen()
		{
			this.reload();
		}

		updateMoreMenuButton()
		{
			if (this.moreMenu)
			{
				this.moreMenu.setCounters(this.tasksDashboardFilter.getCountersByRole());
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
				flowId: this.props.flowId,
				creatorId: this.props.flowId > 0 ? env.userId : 0,
				presetId: this.tasksDashboardFilter.getPresetId(),
				counterId: this.tasksDashboardFilter.getCounterType(),
				searchString: this.tasksDashboardFilter.getSearchString(),
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

			this.sorting.setView(validView);

			this.setState({ view: validView, loading: false }, () => {
				if (validView === Views.DEADLINE)
				{
					setTimeout(() => {
						this.refreshStages();
					}, 100);
				}
				else
				{
					this.refreshStages();
				}
				this.updateViewInCache(validView);
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

		getAnalyticsLabel()
		{
			const { analyticsLabel, isCollab, projectId } = this.props;
			const analyticsEvent = new AnalyticsEvent(analyticsLabel);

			if (isCollab)
			{
				analyticsEvent.setSection('collab');
				analyticsEvent.setP2(
					env.isCollaber ? 'user_collaber' : (env.extranet ? 'user_extranet' : 'user_intranet'),
				);
				analyticsEvent.setP4(`collabId_${projectId}`);
			}

			return analyticsEvent.exportToObject();
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
				{
					resizableByKeyboard: true,
					testId: `TASKS_DASHBOARD_${this.getValidView(this.state.view)}`,
				},
				this.state.loading && new LoadingScreenComponent({ showAirStyle: AIR_STYLE_SUPPORTED }),
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
				isShowFloatingButton: this.state.canCreateTask && this.props.canCreateTask,
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

		onBeforeItemsSetState(items, params)
		{
			let resultItems = [...items];

			const { renderType, append, loadItemsParams = {} } = params;
			if (
				renderType === 'ajax'
				&& !append
				&& !Type.isStringFilled(loadItemsParams.searchParams?.searchString)
			)
			{
				resultItems = [
					...selectWithCreationError(store.getState()).sort(this.sorting.getSortItemsCallback()),
					...resultItems,
				];
			}

			const taskEntities = selectEntities(store.getState());

			// filter marked as removed tasks
			return resultItems.filter(({ id }) => {
				const { isRemoved } = taskEntities[id] || {};

				return Type.isNil(isRemoved) || !isRemoved;
			});
		}

		onBeforeItemsRender(items, { allItemsLoaded })
		{
			const taskEntities = Object.values(selectEntities(store.getState()));
			const pinnedItems = items.filter((item) => item.isPinned === true);
			const lastPinnedIndex = pinnedItems.length - 1;
			const lastIndex = items.length - 1;
			const sortingField = this.sorting.getConvertedType();

			return items.map((item, index) => {
				const newItem = {
					id: item.id,
					idToReplace: taskEntities.find((task) => task.id === item.id)?.guid,
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
			if (this.state.view === Views.LIST)
			{
				return;
			}

			const task = selectByTaskIdOrGuid(store.getState(), item.id);
			if (!task.isCreationErrorExist)
			{
				this.increaseStageCounter(item);
			}
		}

		onItemDeleted(item)
		{
			if (this.state.view === Views.LIST)
			{
				return;
			}

			const task = selectByTaskIdOrGuid(store.getState(), item.id);
			if (
				Type.isNil(task)
				|| (!task.isCreationErrorExist && task.isRemoved === true)
			)
			{
				this.decreaseStageCounter(item);
			}
		}

		increaseStageCounter(item)
		{
			this.changeStageCounter(item, increaseStageCounter);
		}

		decreaseStageCounter(item)
		{
			this.changeStageCounter(item, decreaseStageCounter);
		}

		changeStageCounter(item, actionToDispatch)
		{
			const newTaskStageId = selectTaskStageId(
				store.getState(),
				item.id,
				this.state.view,
				this.props.ownerId,
			);

			if (!Type.isNil(newTaskStageId))
			{
				dispatch(
					actionToDispatch({
						ownerId: this.props.ownerId,
						projectId: this.props.projectId,
						flowId: this.props.flowId,
						view: this.state.view,
						stageId: newTaskStageId,
					}),
				);
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
				this.search.getSearchButton(),
				this.moreMenu.getMenuButton(),
			];
		}

		getEmptyListImage(viewType, search = false)
		{
			const air = AIR_STYLE_SUPPORTED ? 'air-' : '';
			let fileName = `${air}${viewType}${search ? '-search' : ''}.svg`;

			if (AIR_STYLE_SUPPORTED && !search)
			{
				fileName = 'air-dashboard.svg';
			}

			return makeLibraryImagePath(fileName, 'empty-states', 'tasks');
		}

		getEmptyListComponent()
		{
			const { title, description, uri, buttons } = this.getEmptyListProps();

			const imageParams = {
				resizeMode: 'contain',
				style: {
					width: AIR_STYLE_SUPPORTED ? 327 : 172,
					height: AIR_STYLE_SUPPORTED ? 140 : 172,
				},
				svg: { uri },
			};

			return AIR_STYLE_SUPPORTED
				? StatusBlock({
					title,
					description,
					buttons,
					emptyScreen: true,
					image: Image(imageParams),
					onRefresh: this.onPullToRefreshForEmptyScreen,
					testId: 'TASKS_DASHBOARD_EMPTY_SCREEN',
				})
				: new EmptyScreen({
					title,
					description,
					image: imageParams,
					styles: {
						paddingHorizontal: 20,
					},
					onRefresh: this.onPullToRefreshForEmptyScreen,
				});
		}

		getEmptyListProps()
		{
			const listOrKanban = this.isSelectedView(Views.LIST) ? 'list' : 'kanban';

			let title = '';
			let description = '';
			let uri = '';
			let buttons = [];

			const isEmptySearchExceptPresets = (
				this.tasksDashboardFilter.isSearchStringEmpty()
				&& this.tasksDashboardFilter.isEmptyCounter()
				&& this.tasksDashboardFilter.isRoleForAll()
			);

			if (!this.tasksDashboardFilter.isSearchStringEmpty())
			{
				title = Loc.getMessage('M_TASKS_VIEW_ROUTER_EMPTY_LIST_SEARCH_TITLE_MSGVER_1');
				description = Loc.getMessage('M_TASKS_VIEW_ROUTER_EMPTY_LIST_SEARCH_DESCRIPTION_MSGVER_1');
				uri = this.getEmptyListImage(listOrKanban, true);
			}
			else if (isEmptySearchExceptPresets)
			{
				if (this.tasksDashboardFilter.isEmptyPreset())
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
				else if (this.tasksDashboardFilter.isDefaultPreset())
				{
					if (this.props.isCollab)
					{
						title = Loc.getMessage('M_TASKS_DASHBOARD_EMPTY_SEARCH_DEFAULT_PRESET_TITLE');
						description = Loc.getMessage('M_TASKS_DASHBOARD_EMPTY_SEARCH_DEFAULT_PRESET_DESCRIPTION');
						buttons = [
							Link4({
								text: Loc.getMessage('M_TASKS_DASHBOARD_EMPTY_SEARCH_DEFAULT_PRESET_BUTTON_MORE'),
								mode: LinkMode.SOLID,
								design: LinkDesign.LIGHT_GREY,
								onClick: () => helpdesk.openHelpArticle('23369568', 'helpdesk'),
							}),
						];
					}
					else
					{
						title = Loc.getMessage('M_TASKS_VIEW_ROUTER_EMPTY_IN_PROGRESS');
					}

					uri = this.getEmptyListImage(listOrKanban, false);
				}
				else if (this.tasksDashboardFilter.getPresetName())
				{
					description = Loc.getMessage('M_TASKS_VIEW_ROUTER_SELECTED_FILTER_TITLE', {
						'#FILTER_NAME#': this.tasksDashboardFilter.getPresetName(),
					});
					uri = this.getEmptyListImage(listOrKanban, true);
				}
			}

			if (!uri)
			{
				title = Loc.getMessage('M_TASKS_VIEW_ROUTER_EMPTY_LIST_SEARCH_TITLE_MSGVER_1');
				description = Loc.getMessage('M_TASKS_VIEW_ROUTER_EMPTY_LIST_SEARCH_DESCRIPTION_MSGVER_1');
				uri = this.getEmptyListImage(listOrKanban, true);
				buttons = [];
			}

			return { title, description, uri, buttons };
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

	const isRootComponent = BX.componentParameters.get('IS_ROOT_COMPONENT', false);
	if (isRootComponent)
	{
		void requireLazy('tasks:navigator').then(({ TasksNavigator }) => {
			const tasksNavigator = new TasksNavigator();

			tasksNavigator.unsubscribeFromPushNotifications();
			tasksNavigator.subscribeToPushNotifications();
		});
	}

	Promise.allSettled([
		fetchDisabledTools(),
		tariffPlanRestrictionsReady(),
	])
		.then(() => {
			const groupId = Number(BX.componentParameters.get('GROUP_ID', 0));
			const collabId = Number(BX.componentParameters.get('COLLAB_ID', 0));

			const component = new TasksDashboard({
				isRootComponent,
				currentUserId: Number(env.userId),
				ownerId: Number(BX.componentParameters.get('USER_ID', 0) || env.userId),
				projectId: collabId || groupId || null,
				isCollab: collabId > 0,
				flowId: Number(BX.componentParameters.get('FLOW_ID', 0)),
				flowName: BX.componentParameters.get('FLOW_NAME', null),
				flowEfficiency: BX.componentParameters.get('FLOW_EFFICIENCY', null),
				canCreateTask: BX.componentParameters.get('CAN_CREATE_TASK', true),
				isTabsMode: BX.componentParameters.get('IS_TABS_MODE', false),
				tabsGuid: BX.componentParameters.get('TABS_GUID', ''),
				analyticsLabel: BX.componentParameters.get('ANALYTICS_LABEL', { c_section: 'tasks' }),
				siteId: BX.componentParameters.get('SITE_ID', ''),
			});

			BX.onViewLoaded(() => {
				layout.showComponent(component);
			});
		})
		.catch(console.error)
	;
})();
