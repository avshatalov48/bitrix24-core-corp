(() => {
	const require = (ext) => jn.require(ext);

	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { Feature } = require('feature');
	const { debounce } = require('utils/function');
	const { Logger, LogType } = require('utils/logger');
	const { PresetList } = require('tasks/layout/presetList');
	const { Project } = require('tasks/project');
	const { StorageCache } = require('storage-cache');
	const { WorkgroupUtil } = require('project/utils');
	const { RunActionExecutor } = require('rest/run-action-executor');

	const platform = Application.getPlatform();
	const isAirStyleSupported = Feature.isAirStyleSupported();

	const Mode = {
		PROJECT: 'tasks_project',
		SCRUM: 'tasks_scrum',
	};

	const logger = new Logger([LogType.LOG, LogType.ERROR]);

	class Loading
	{
		/**
		 * @param {ProjectList} list
		 */
		constructor(list)
		{
			this.list = list.list;
		}

		showForList()
		{
			if (!this.isShowedForList)
			{
				dialogs.showSpinnerIndicator({
					color: AppTheme.colors.base3,
					backgroundColor: AppTheme.colors.bgContentPrimary,
				});
				this.isShowedForList = true;
			}
		}

		hideForList()
		{
			if (this.isShowedForList)
			{
				dialogs.hideSpinnerIndicator();
				this.isShowedForList = false;
			}
		}

		showForTitle()
		{
			this.list.setTitle({
				useProgress: true,
				largeMode: true,
				text: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_HEADER_PROJECTS'),
			});
		}

		hideForTitle()
		{
			this.list.setTitle({
				useProgress: false,
				largeMode: true,
				text: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_HEADER_PROJECTS'),
			});
		}
	}

	class WelcomeScreen
	{
		/**
		 * @param {ProjectList} list
		 */
		constructor(list)
		{
			this.list = list;
		}

		show()
		{
			this.list.list.welcomeScreen.show({
				upperText: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_WELCOME_SCREEN_TITLE'),
				lowerText: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_WELCOME_SCREEN_SUBTITLE'),
				iconName: 'ws_open_project',
			});
		}

		hide()
		{
			this.list.list.welcomeScreen.hide();
		}
	}

	class Section
	{
		static get type()
		{
			return {
				pinned: 'pinned',
				default: 'default',
				more: 'more',
			};
		}

		static get()
		{
			const defaultSectionParams = {
				title: '',
				foldable: false,
				folded: false,
				badgeValue: 0,
				sortItemParams: { activityDate: 'desc' },
				backgroundColor: AppTheme.colors.bgContentPrimary,
				styles: {
					title: {
						font: {
							size: 18,
						},
					},
				},
			};

			return [
				{ id: Section.type.pinned, ...defaultSectionParams },
				{ id: Section.type.default, ...defaultSectionParams },
				{ id: Section.type.more, ...defaultSectionParams },
			];
		}
	}

	class Cache extends StorageCache
	{
		/**
		 * @param {Array<Object>} projects
		 */
		setProjects(projects)
		{
			const cachedProjects = this.get();
			if (Object.prototype.hasOwnProperty.call(cachedProjects, Filter.counterTypes.none))
			{
				return;
			}

			projects.forEach((project) => {
				if (Object.keys(cachedProjects).includes(project.id))
				{
					cachedProjects[project.id] = project;
				}
			});
			this.set(cachedProjects);
		}

		removeProject(projectId)
		{
			const cachedProjects = this.get();
			if (Object.prototype.hasOwnProperty.call(cachedProjects, Filter.counterTypes.none))
			{
				return;
			}

			if (Object.keys(cachedProjects).includes(projectId))
			{
				delete cachedProjects[projectId];
				this.set(cachedProjects);
			}
		}
	}

	class Filter
	{
		static get presetTypes()
		{
			return {
				none: 'none',
				default: 'my',
			};
		}

		static get counterTypes()
		{
			return {
				none: 'none',
				sonetTotalExpired: 'sonetTotalExpired',
				sonetTotalComments: 'sonetTotalComments',
				sonetForeignExpired: 'sonetForeignExpired',
				sonetForeignComments: 'sonetForeignComments',
				scrumTotalComments: 'scrumTotalComments',
				scrumForeignComments: 'scrumForeignComments',
			};
		}

		/**
		 * @param {ProjectList} list
		 * @param {Integer} userId
		 */
		constructor(list, userId)
		{
			this.list = list;
			this.userId = userId;

			this.preset = Filter.presetTypes.default;
			this.counter = Filter.counterTypes.none;
			this.counters = {};
			this.searchText = '';
			this.isShowMine = false;

			this.cache = new StorageCache(this.list.mode, 'filterCounters');
			this.total = this.cache.get().counterValue || 0;
		}

		updateCounters()
		{
			logger.log('ProjectList.Filter.updateCounters');

			(new RunActionExecutor('tasksmobile.Task.Counter.getByType'))
				.setHandler((response) => {
					this.counters = {};
					this.total = 0;

					Object.entries(response.data).forEach(([type, value]) => {
						this.counters[type] = value;

						const typesToCollectInTotal = (
							this.list.isScrum()
								? [Filter.counterTypes.scrumTotalComments]
								: [Filter.counterTypes.sonetTotalExpired, Filter.counterTypes.sonetTotalComments]
						);
						if (typesToCollectInTotal.includes(type))
						{
							this.total += value;
						}
					});

					this.setVisualCounters();
					this.saveCache();
				})
				.call(false)
			;
		}

		pseudoUpdateCounters(value)
		{
			this.total += value;
			this.total = (this.total < 0 ? 0 : this.total);

			this.setVisualCounters();
		}

		setVisualCounters()
		{
			Application.setBadges({
				[`${this.list.mode}_MoreButton`]: this.total,
			});
			BX.postComponentEvent(`${this.list.getTabName()}:setVisualCounter`, [{ value: this.total }], 'tasks.tabs');
		}

		saveCache()
		{
			this.cache.set({ counterValue: this.total });
		}

		get()
		{
			const filter = {};

			if (this.searchText)
			{
				filter.FIND = this.searchText;
			}

			if (this.isShowMine)
			{
				filter.MEMBER = this.userId;
			}

			switch (this.counter)
			{
				case Filter.counterTypes.sonetTotalExpired:
					filter.COUNTERS = 'EXPIRED';
					break;

				case Filter.counterTypes.sonetTotalComments:
				case Filter.counterTypes.scrumTotalComments:
					filter.COUNTERS = 'NEW_COMMENTS';
					break;

				case Filter.counterTypes.sonetForeignExpired:
					filter.COUNTERS = 'PROJECT_EXPIRED';
					break;

				case Filter.counterTypes.sonetForeignComments:
				case Filter.counterTypes.scrumForeignComments:
					filter.COUNTERS = 'PROJECT_NEW_COMMENTS';
					break;

				case Filter.counterTypes.none:
				default:
					break;
			}

			return filter;
		}

		getCounterValue(type)
		{
			return this.counters[type] || 0;
		}

		isDefaultPreset()
		{
			return (this.preset === Filter.presetTypes.default);
		}

		isDefaultCounter()
		{
			return (this.counter === Filter.counterTypes.none);
		}

		getSearchText()
		{
			return this.searchText;
		}

		setSearchText(searchText)
		{
			this.searchText = searchText;
		}

		getPreset()
		{
			return this.preset;
		}

		setPreset(preset)
		{
			this.preset = preset;
		}

		getCounter()
		{
			return this.counter;
		}

		setCounter(counter)
		{
			this.counter = counter;
		}

		getIsShowMine()
		{
			return this.isShowMine;
		}

		setIsShowMine(isShowMine)
		{
			this.isShowMine = isShowMine;
		}
	}

	const DEFAULT_SECTION = 'default';
	const MY_COUNTERS_SECTION = 'my-counters';
	const OTHER_COUNTERS_SECTION = 'other-counters';

	class MoreMenu
	{
		static get counterColors()
		{
			if (isAirStyleSupported)
			{
				return {
					gray: AppTheme.realColors.base4,
					green: AppTheme.realColors.accentMainSuccess,
					red: AppTheme.realColors.accentMainAlert,
				};
			}

			return {
				gray: AppTheme.colors.base4,
				green: AppTheme.colors.accentMainSuccess,
				red: AppTheme.colors.accentMainAlert,
			};
		}

		/**
		 * @param {ProjectList} list
		 */
		constructor(list)
		{
			this.list = list;
			this.filter = list.filter;
		}

		show()
		{
			const menuItems = this.prepareItems();
			const menuSections = [
				{
					id: MY_COUNTERS_SECTION,
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_MY_COUNTERS_SECTION'),
				},
				{
					id: OTHER_COUNTERS_SECTION,
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_OTHER_COUNTERS_SECTION'),
				},
				{ id: DEFAULT_SECTION },
			];

			if (!this.popupMenu)
			{
				this.popupMenu = dialogs.createPopupMenu();
			}
			this.popupMenu.setData(menuItems, menuSections, (eventName, item) => {
				if (eventName === 'onItemSelected')
				{
					this.onItemSelected(item);
				}
			});
			this.popupMenu.show();
		}

		prepareItems()
		{
			const projectListItems = [
				{
					id: Filter.counterTypes.sonetTotalExpired,
					title: (
						isAirStyleSupported
							? Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_EXPIRED')
							: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_MY_EXPIRED')
					),
					sectionCode: MY_COUNTERS_SECTION,
					checked: (this.filter.getCounter() === Filter.counterTypes.sonetTotalExpired),
					counterValue: this.filter.getCounterValue(Filter.counterTypes.sonetTotalExpired),
					counterStyle: {
						backgroundColor: MoreMenu.counterColors.red,
					},
				},
				{
					id: Filter.counterTypes.sonetTotalComments,
					title: (
						isAirStyleSupported
							? Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_COMMENTS')
							: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_MY_NEW_COMMENTS')
					),
					sectionCode: MY_COUNTERS_SECTION,
					checked: (this.filter.getCounter() === Filter.counterTypes.sonetTotalComments),
					counterValue: this.filter.getCounterValue(Filter.counterTypes.sonetTotalComments),
					counterStyle: {
						backgroundColor: MoreMenu.counterColors.green,
					},
				},
				{
					id: Filter.counterTypes.sonetForeignExpired,
					title: (
						isAirStyleSupported
							? Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_EXPIRED')
							: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_OTHER_EXPIRED')
					),
					sectionCode: OTHER_COUNTERS_SECTION,
					checked: (this.filter.getCounter() === Filter.counterTypes.sonetForeignExpired),
					counterValue: this.filter.getCounterValue(Filter.counterTypes.sonetForeignExpired),
					counterStyle: {
						backgroundColor: MoreMenu.counterColors.gray,
					},
				},
				{
					id: Filter.counterTypes.sonetForeignComments,
					title: (
						isAirStyleSupported
							? Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_COMMENTS')
							: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_OTHER_NEW_COMMENTS')
					),
					sectionCode: OTHER_COUNTERS_SECTION,
					checked: (this.filter.getCounter() === Filter.counterTypes.sonetForeignComments),
					counterValue: this.filter.getCounterValue(Filter.counterTypes.sonetForeignComments),
					counterStyle: {
						backgroundColor: MoreMenu.counterColors.gray,
					},
				},
			];
			const scrumListItems = [
				{
					id: Filter.counterTypes.scrumTotalComments,
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_MY_NEW_COMMENTS'),
					sectionCode: DEFAULT_SECTION,
					checked: (this.filter.getCounter() === Filter.counterTypes.scrumTotalComments),
					counterValue: this.filter.getCounterValue(Filter.counterTypes.scrumTotalComments),
					counterStyle: {
						backgroundColor: MoreMenu.counterColors.green,
					},
				},
				{
					id: Filter.counterTypes.scrumForeignComments,
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_FILTER_COUNTER_OTHER_NEW_COMMENTS'),
					sectionCode: DEFAULT_SECTION,
					checked: (this.filter.getCounter() === Filter.counterTypes.scrumForeignComments),
					counterValue: this.filter.getCounterValue(Filter.counterTypes.scrumForeignComments),
					counterStyle: {
						backgroundColor: MoreMenu.counterColors.gray,
					},
				},
			];
			const items = (this.list.isScrum() ? scrumListItems : projectListItems);

			items.push({
				id: 'readAll',
				title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_READ_ALL'),
				iconName: isAirStyleSupported ? 'chats_with_check' : 'read',
				sectionCode: DEFAULT_SECTION,
				showTopSeparator: true,
			});

			return items;
		}

		onItemSelected(item)
		{
			switch (item.id)
			{
				case Filter.counterTypes.sonetTotalExpired:
				case Filter.counterTypes.sonetTotalComments:
				case Filter.counterTypes.sonetForeignExpired:
				case Filter.counterTypes.sonetForeignComments:
				case Filter.counterTypes.scrumTotalComments:
				case Filter.counterTypes.scrumForeignComments:
					this.onCounterChange(item.id);
					break;

				case 'toggleShowMine':
					this.onToggleShowMineAction();
					break;

				case 'readAll':
					this.onReadAllAction();
					break;

				default:
					break;
			}
		}

		onCounterChange(counter)
		{
			const newCounter = (this.filter.getCounter() === counter ? Filter.counterTypes.none : counter);
			this.filter.setCounter(newCounter);

			this.list.setTopButtons();
			this.list.reload(0, true);
		}

		onToggleShowMineAction()
		{
			this.filter.setIsShowMine(!this.filter.getIsShowMine());

			this.list.setTopButtons();
			this.list.reload(0, true);
		}

		onReadAllAction()
		{
			this.list.pseudoReadProjects([...this.list.projectList.keys()]);

			const methodName = `tasks.viewedGroup.${this.list.isScrum() ? 'scrum' : 'project'}.markAsRead`;

			(new RequestExecutor(methodName, { fields: { groupId: 0 } }))
				.call()
				.then((response) => {
					if (response.result === true)
					{
						Notify.showMessage(
							'',
							Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_NOTIFICATION_READ_ALL'),
							{
								time: 1,
							},
						);
					}
				})
				.catch(console.error);
		}
	}

	class Action
	{
		static get swipeActions()
		{
			const imagePrefix = `${component.path}images/mobile-tasks-projectlist-swipe-`;

			return {
				about: {
					identifier: 'about',
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_ABOUT'),
					iconName: 'action_project',
					iconUrl: `${imagePrefix}about.png`,
					color: AppTheme.colors.accentMainWarning,
					position: 'right',
				},
				members: {
					identifier: 'members',
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_MEMBERS'),
					iconName: 'action_userlist',
					iconUrl: `${imagePrefix}members.png`,
					color: AppTheme.colors.accentMainLinks,
					position: 'right',
				},
				join: {
					identifier: 'join',
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_JOIN'),
					iconName: 'action_accept',
					iconUrl: `${imagePrefix}join.png`,
					color: AppTheme.colors.accentMainLinks,
					position: 'right',
				},
				// leave: {
				// 	identifier: 'leave',
				// 	title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_LEAVE'),
				// 	iconName: 'action_skip',
				// 	iconUrl: `${imagePrefix}leave.png`,
				// 	color: AppTheme.colors.base4,
				// 	position: 'right',
				// },
				read: {
					identifier: 'read',
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_READ'),
					iconName: 'action_read',
					iconUrl: `${imagePrefix}read.png`,
					color: AppTheme.colors.accentExtraPink,
					position: 'left',
				},
				pin: {
					identifier: 'pin',
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_PIN'),
					iconName: 'action_pin',
					iconUrl: `${imagePrefix}pin.png`,
					color: AppTheme.colors.accentMainLinks,
					position: 'left',
				},
				unpin: {
					identifier: 'unpin',
					title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_ACTION_UNPIN'),
					iconName: 'action_unpin',
					iconUrl: `${imagePrefix}unpin.png`,
					color: AppTheme.colors.accentMainLinks,
					position: 'left',
				},
			};
		}

		static fill(itemData, project)
		{
			const result = {
				...itemData,
				menuMode: (platform === 'ios' ? 'swipe' : 'dialog'),
				actions: Object.values(Action.swipeActions).filter((action) => project.getActions()[action.identifier]),
			};

			if (!project.isOpened)
			{
				const joinActionIndex = result.actions.findIndex((action) => action.identifier === 'join');
				if (joinActionIndex >= 0)
				{
					delete result.actions[joinActionIndex];
				}
			}

			return result;
		}

		/**
		 * @param {ProjectList} list
		 */
		constructor(list)
		{
			this.list = list;
		}

		onItemAction(event)
		{
			const project = this.list.projectList.get(event.item.id);

			switch (event.action.identifier)
			{
				case 'pin':
					this.onPinAction(project);
					break;

				case 'unpin':
					this.onUnpinAction(project);
					break;

				case 'read':
					this.onReadAction(project);
					break;

				case 'about':
					this.onAboutAction(project);
					break;

				case 'members':
					this.onMembersAction(project);
					break;

				case 'join':
					this.onJoinAction(project);
					break;

				case 'leave':
					this.onLeaveAction(project);
					break;

				default:
					break;
			}

			this.list.updateItem(project.id);
		}

		/**
		 * @param {Project} project
		 */
		onPinAction(project)
		{
			void project.pin(this.list.mode);
		}

		/**
		 * @param {Project} project
		 */
		onUnpinAction(project)
		{
			void project.unpin(this.list.mode);
		}

		/**
		 * @param {Project} project
		 */
		onReadAction(project)
		{
			this.list.filter.pseudoUpdateCounters(-project.getNewCommentsCount());
			void project.read();
		}

		/**
		 * @param {Project} project
		 */
		onAboutAction(project)
		{
			ProjectViewManager.open(this.list.userId, project.id);
		}

		/**
		 * @param {Project} project
		 */
		onMembersAction(project)
		{
			void PageManager.openWidget('list', {
				backdrop: {
					bounceEnable: false,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
				},
				useSearch: true,
				useLargeTitleMode: true,
				title: 'Project members',
				onReady: (list) => {
					new ProjectMemberList(list, this.list.userId, project.id, {
						isOwner: project.isOwner(),
						isExtranet: project.isExtranet,
						canInvite: project.getActions().invite,
						minSearchSize: 3,
					});
				},
				onError: (error) => logger.log(error),
			});
		}

		/**
		 * @param {Project} project
		 */
		onJoinAction(project)
		{
			const projectId = String(project.id);

			if (project.isOpened)
			{
				project.joinProject().then(() => this.list.updateItem(projectId)).catch(console.error);

				const projectItem = ProjectList.prepareListItem(project);
				projectItem.joinButtonState = 'animated';
				this.list.list.updateItem({ id: projectId }, projectItem);
			}
		}

		/**
		 * @param {Project} project
		 */
		onLeaveAction(project)
		{
			void project.leaveProject();
		}
	}

	class Pull
	{
		static get commonEvents()
		{
			return [
				'project_read_all',
				'comment_read_all',
				'scrum_read_all',
			];
		}

		static get counterEvents()
		{
			return [
				'onAfterTaskAdd',
				'onAfterTaskDelete',
				'onAfterTaskRestore',
				'onAfterTaskView',
				'onAfterTaskMute',
				'onAfterCommentAdd',
				'onAfterCommentDelete',
				'onProjectPermUpdate',
			];
		}

		static get userOptions()
		{
			return {
				pinned: 2,
			};
		}

		/**
		 * @param {ProjectList} list
		 * @param {Integer} userId
		 */
		constructor(list, userId)
		{
			this.list = list;
			this.userId = userId;

			this.queue = new Set();
			this.counterEventData = new Map();

			this.canExecute = true;

			this.extendWatch();
			this.startWatch().then(() => this.subscribe()).catch(console.error);
		}

		getEventHandlers()
		{
			return {
				project_add: {
					method: this.onProjectAdd,
					context: this,
				},
				project_update: {
					method: this.onProjectUpdate,
					context: this,
				},
				project_remove: {
					method: this.onProjectRemove,
					context: this,
				},
				project_user_add: {
					method: this.onProjectUserAdd,
					context: this,
				},
				project_user_update: {
					method: this.onProjectUserUpdate,
					context: this,
				},
				project_user_remove: {
					method: this.onProjectUserRemove,
					context: this,
				},
				project_user_option_changed: {
					method: this.onProjectUserOptionChanged,
					context: this,
				},
				project_read_all: {
					method: this.onProjectCommentsReadAll,
					context: this,
				},
				comment_read_all: {
					method: this.onProjectCommentsReadAll,
					context: this,
				},
				scrum_read_all: {
					method: this.onProjectCommentsReadAll,
					context: this,
				},
				project_counter: {
					method: this.onProjectCounter,
					context: this,
				},
			};
		}

		startWatch()
		{
			return new Promise((resolve, reject) => {
				new RunActionExecutor('tasksmobile.Project.startWatchList')
					.setHandler((response) => (response.status === 'success' ? resolve(response.data) : reject(response)))
					.call(false)
				;
			});
		}

		extendWatch()
		{
			BX.PULL.extendWatch('TASKS_PROJECTS', true);
			setTimeout(() => this.extendWatch(), 29 * 60 * 1000);
		}

		subscribe()
		{
			BX.PULL.subscribe({
				moduleId: 'tasks',
				callback: (data) => this.processPullEvent(data),
			});
		}

		processPullEvent(data)
		{
			if (this.getCanExecute())
			{
				void this.executePullEvent(data);
			}
			else
			{
				this.queue.add(data);
			}
		}

		executePullEvent(data)
		{
			return new Promise((resolve, reject) => {
				const has = Object.prototype.hasOwnProperty;
				const eventHandlers = this.getEventHandlers();
				const { command, params } = data;
				if (has.call(eventHandlers, command))
				{
					const { method, context } = eventHandlers[command];
					if (method)
					{
						method.apply(context, [params]).then(() => resolve(), () => reject()).catch(() => reject());
					}
				}
			});
		}

		freeQueue()
		{
			const clearDuplicates = (accumulator, event) => {
				const result = accumulator;
				if (
					typeof accumulator[event.command] === 'undefined'
					|| event.extra.server_time_ago < accumulator[event.command].extra.server_time_ago
				)
				{
					result[event.command] = event;
				}

				return result;
			};

			this.queue = new Set([...this.queue].filter((event) => Pull.commonEvents.includes(event.command)));
			this.queue = new Set(
				Object.values([...this.queue].reduce((accumulator, event) => clearDuplicates(accumulator, event), {})),
			);

			const promises = [...this.queue].map((event) => this.executePullEvent(event));

			return Promise.allSettled(promises);
		}

		clear()
		{
			this.queue.clear();
		}

		getProjectsToUpdateFromEvents()
		{
			const processed = [];

			this.queue.forEach((event) => {
				const has = Object.prototype.hasOwnProperty;
				const eventHandlers = this.getEventHandlers();
				const { command, params } = event;

				if (has.call(eventHandlers, command))
				{
					const projectId = (params.ID || params.GROUP_ID || 0);
					if (projectId)
					{
						processed.push(String(projectId));
						this.queue.delete(event);
					}
				}
			});

			return processed;
		}

		getCanExecute()
		{
			return this.canExecute;
		}

		setCanExecute(canExecute)
		{
			this.canExecute = canExecute;
		}

		onProjectAdd(data)
		{
			return this.list.updateProjects([String(data.ID)]);
		}

		onProjectUpdate(data)
		{
			return this.list.updateProjects([String(data.ID)]);
		}

		onProjectRemove(data)
		{
			return new Promise((resolve) => {
				this.list.removeItem(String(data.ID));
				resolve();
			});
		}

		onProjectUserAdd(data)
		{
			return this.list.updateProjects([String(data.GROUP_ID)]);
		}

		onProjectUserUpdate(data)
		{
			return this.list.updateProjects([String(data.GROUP_ID)]);
		}

		onProjectUserRemove(data)
		{
			return this.list.updateProjects([String(data.GROUP_ID)]);
		}

		onProjectUserOptionChanged(data)
		{
			return new Promise((resolve, reject) => {
				if (Number(data.USER_ID) !== Number(this.userId))
				{
					resolve();

					return;
				}

				if (data.OPTION === Pull.userOptions.pinned)
				{
					this.onProjectPinChanged(String(data.PROJECT_ID), data.ADDED)
						.then(() => resolve())
						.catch(() => reject())
					;
				}
			});
		}

		onProjectPinChanged(projectId, added)
		{
			return new Promise((resolve, reject) => {
				if (this.list.projectList.has(projectId))
				{
					this.list.updateItem(projectId, { isPinned: (added ? 'Y' : 'N') });
					resolve();
				}
				else if (added)
				{
					this.list.updateProjects([projectId])
						.then(() => resolve(), () => reject())
						.catch(() => reject())
					;
				}
			});
		}

		onProjectCommentsReadAll(data)
		{
			return new Promise((resolve) => {
				const userId = Number(data.USER_ID);
				if (userId > 0 && userId !== this.userId)
				{
					resolve();

					return;
				}

				const projectId = String(data.GROUP_ID);
				if (Number(projectId) > 0)
				{
					if (this.list.projectList.has(projectId))
					{
						this.list.pseudoReadProjects([projectId]);
					}
				}
				else
				{
					this.list.pseudoReadProjects([...this.list.projectList.keys()]);
				}

				resolve();
			});
		}

		onProjectCounter(data)
		{
			this.list.filter.updateCounters();

			const projectId = String(data.GROUP_ID);
			const event = data.EVENT;

			if (!Pull.counterEvents.includes(event))
			{
				return;
			}

			if (!this.timer)
			{
				this.timer = setTimeout(() => {
					this.freeCounterQueue();
				}, 1000);
			}

			if (!this.counterEventData.has(projectId))
			{
				this.counterEventData.set(projectId, event);
			}
		}

		freeCounterQueue()
		{
			void this.list.updateProjects([...this.counterEventData.keys()]);
			this.counterEventData.clear();
			this.timer = null;
		}

		onAppActive()
		{
			this.clear();
			this.extendWatch();
			this.startWatch().then(() => this.setCanExecute(true)).catch(console.error);
		}
	}

	/**
	 * @class ProjectList
	 */
	class ProjectList
	{
		static get backgroundColors()
		{
			return {
				default: AppTheme.colors.accentMainLinks,
				pinned: AppTheme.colors.bgContentTertiary,
			};
		}

		static get counterColors()
		{
			return {
				danger: AppTheme.colors.accentMainAlert,
				gray: AppTheme.colors.base4,
				success: AppTheme.colors.accentMainSuccess,
			};
		}

		static get select()
		{
			return [
				'ID',
				'NAME',
				'IMAGE_ID',
				'AVATAR_TYPE',
				'NUMBER_OF_MODERATORS',
				'NUMBER_OF_MEMBERS',
				'OPENED',
				'CLOSED',
				'VISIBLE',
				'ACTIVITY_DATE',
				'IS_PINNED',
				'SCRUM_MASTER_ID',
				'IS_EXTRANET',
				'ACTIONS',
				'MEMBERS',
				'COUNTERS',
			];
		}

		static get order()
		{
			return {
				IS_PINNED: 'DESC',
				ACTIVITY_DATE: 'DESC',
				ID: 'DESC',
			};
		}

		static get avatarTypes()
		{
			return {
				public: 'status_task_public',
				private: 'status_task_private',
				secret: 'status_task_secret',
				extranet: 'status_task_extranet',
			};
		}

		/**
		 * @param {Project} project
		 * @param {bool} withActions
		 */
		static prepareListItem(project, withActions = true)
		{
			let itemData = {
				id: String(project.id),
				title: project.name || '',
				imageUrl: project.image || '',
				date: project.activityDate / 1000,
				messageCount: project.getCounter().value,
				joinButtonState: (project.getActions().join && project.isOpened ? 'showed' : 'hidden'),
				creatorIcons: project.getHeadIcons(),
				creatorCount: project.getHeadCount(),
				responsibleIcons: project.getMemberIcons(),
				responsibleCount: project.getMemberCount(),
				styles: {
					counter: {
						backgroundColor: ProjectList.counterColors[project.getCounter().color],
					},
					date: {
						image: {
							name: (project.isPinned ? 'message_pin' : ''),
						},
						font: {
							size: 13,
						},
					},
					avatar: {
						image: {
							name: ProjectList.avatarTypes[project.getType()],
						},
					},
				},
				backgroundColor: ProjectList.backgroundColors.default,
				sectionCode: Section.type.default,
				sortValues: {
					activityDate: project.activityDate,
				},
				type: 'project',
			};

			if (project.getCounter().isHidden)
			{
				itemData.messageCount = 0;
			}

			if (project.isPinned)
			{
				itemData.backgroundColor = ProjectList.backgroundColors.pinned;
				itemData.sectionCode = Section.type.pinned;
			}

			if (withActions)
			{
				itemData = Action.fill(itemData, project);
			}

			return itemData;
		}

		constructor(list, userId, params)
		{
			logger.log(`${params.mode}.constructor`, userId);

			this.list = list;
			this.userId = userId;
			this.mode = params.mode;
			this.newsPathTemplate = (params.projectNewsPathTemplate || '');
			this.calendarWebPathTemplate = (params.projectCalendarWebPathTemplate || '');

			this.start = 0;
			this.pageSize = 50;

			this.projectList = new Map();

			this.cache = new Cache(this.mode, `projectList_${this.userId}`);
			this.filter = new Filter(this, this.userId);
			this.moreMenu = new MoreMenu(this);
			this.welcomeScreen = new WelcomeScreen(this);
			this.loading = new Loading(this);
			this.action = new Action(this);
			this.pull = new Pull(this, this.userId);

			this.debounceSearch = debounce(
				(text) => {
					this.filter.setSearchText(text);
					this.setTopButtons();
					this.reload(0, true);
				},
				500,
				this,
			);
			this.getPresets();

			BX.onViewLoaded(() => {
				this.list.setItems([
					{
						type: 'loading',
						title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_LOADING'),
					},
				]);
				this.list.setSections(Section.get());

				this.setTopButtons();
				this.setFloatingButton();
				this.setListListeners();
				this.bindEvents();

				this.filter.updateCounters();

				this.loadProjectsFromCache();
				this.reload();
			});
		}

		isScrum()
		{
			return (this.mode === Mode.SCRUM);
		}

		getTabName()
		{
			return (this.isScrum() ? 'tasks.scrum.list' : 'tasks.project.list');
		}

		getPresets()
		{
			const methodName = (this.isScrum() ? 'getScrumListPresets' : 'getProjectListPresets');

			new RunActionExecutor(`tasksmobile.Filter.${methodName}`)
				.setHandler((response) => {
					this.presets = response.data;
					if (this.searchLayout)
					{
						this.searchLayout.updateState({
							presets: this.presets,
							currentPreset: this.filter.getPreset(),
						});
					}
				})
				.call(false)
			;
		}

		setTopButtons()
		{
			const isDefaultSearch = (this.filter.getPreset() === Filter.presetTypes.default && !this.filter.getSearchText());

			this.list.setRightButtons([
				{
					type: 'search',
					badgeCode: `${this.mode}_SearchButton`,
					accent: !isDefaultSearch,
					callback: () => this.onSearchClick(),
				},
				{
					type: 'more',
					accent: this.filter.getCounter() !== Filter.counterTypes.none,
					badgeCode: `${this.mode}_MoreButton`,
					callback: () => this.moreMenu.show(),
				},
			]);
			this.filter.setVisualCounters();
		}

		onSearchClick()
		{
			if (!this.isSearchInit)
			{
				this.isSearchInit = true;

				this.list.search.mode = 'layout';
				this.list.search.on('textChanged', ({ text }) => this.debounceSearch(text));
				this.list.search.on('cancel', () => {
					if (
						this.filter.getSearchText()
						|| this.filter.getPreset() !== Filter.presetTypes.default
					)
					{
						this.filter.setSearchText('');
						this.filter.setPreset(Filter.presetTypes.default);

						this.setTopButtons();
						this.reload(0, true);
					}
				});
			}
			this.searchLayout = new PresetList({
				presets: this.presets,
				currentPreset: this.filter.getPreset(),
			});
			this.searchLayout.on('presetSelected', (preset) => {
				if (preset.id === this.filter.getPreset())
				{
					this.filter.setPreset(Filter.presetTypes.none);
				}
				else
				{
					this.filter.setPreset(preset.id);
					this.filter.setCounter(Filter.counterTypes.none);
				}
				this.setTopButtons();
				this.reload(0, true);
			});
			this.list.search.text = this.filter.getSearchText();
			this.list.search.show(this.searchLayout, 46);
		}

		setFloatingButton()
		{
			(new RequestExecutor('socialnetwork.api.workgroup.getCanCreate'))
				.call()
				.then(
					(response) => this.renderFloatingButton(response.result),
					(response) => logger.error(response),
				)
				.catch((response) => logger.error(response));
		}

		renderFloatingButton(isExist = false)
		{
			if (isExist)
			{
				this.list.setFloatingButton({
					icon: 'plus',
					callback: () => this.addProject(),
				});
			}
			else
			{
				this.list.setFloatingButton({});
			}
		}

		setListListeners()
		{
			const eventHandlers = {
				onRefresh: {
					callback: () => {
						this.reload();
						this.filter.updateCounters();
					},
					context: this,
				},
				onItemSelected: {
					callback: this.onItemSelected,
					context: this,
				},
				onItemAction: {
					callback: this.action.onItemAction,
					context: this.action,
				},
				eventJoin: {
					callback: this.action.onJoinAction,
					context: this.action,
				},
				onScroll: {
					callback: () => {
						this.list.search.close();
					},
					context: this,
				},
			};

			this.list.setListener((event, data) => {
				logger.log(`ProjectList.appEvent.${event}`);
				if (eventHandlers[event])
				{
					eventHandlers[event].callback.apply(eventHandlers[event].context, [data]);
				}
			});
		}

		bindEvents()
		{
			BX.addCustomEvent('tasks.tabs:onTabSelected', (eventData) => this.onTabSelected(eventData));
			BX.addCustomEvent('tasks.tabs:onAppActive', (eventData) => this.onAppActive(eventData));
			BX.addCustomEvent('tasks.tabs:onAppPaused', (eventData) => this.onAppPaused(eventData));
		}

		loadProjectsFromCache()
		{
			BX.onViewLoaded(() => {
				const projects = this.cache.get();

				if (Object.prototype.hasOwnProperty.call(projects, Filter.counterTypes.none)) // old cache
				{
					return;
				}

				if (Object.keys(projects).length === 0)
				{
					logger.log('Cache is empty');

					return;
				}

				this.list.setItems(Object.values(projects), null, false);
			});
		}

		reload(offset = 0, showLoading = false)
		{
			if (showLoading)
			{
				this.loading.showForList();
			}
			this.loading.showForTitle();

			const params = {
				mode: this.mode,
			};

			params.siftThroughFilter = {
				presetId: this.filter.getPreset(),
			};

			BX.rest.callMethod(
				'tasksmobile.Project.list',
				{
					select: ProjectList.select,
					filter: this.filter.get(),
					order: ProjectList.order,
					start: offset,
					params,
				},
				(response) => this.onReloadSuccess(response, showLoading, offset),
			);
		}

		onReloadSuccess(response, showLoading, offset)
		{
			logger.log('ProjectList.onReloadSuccess', response);

			this.start = offset + this.pageSize;

			const isFirstPage = (offset === 0);
			if (isFirstPage)
			{
				this.projectList.clear();
			}

			const { projects } = response.answer.result;
			const items = [];
			projects.forEach((row) => {
				const project = new Project(this.userId);
				project.setData(row);

				this.projectList.set(String(project.id), project);
				items.push(ProjectList.prepareListItem(project));
			});

			logger.log('ProjectList.onReloadSuccess:items', items);

			const isNextPageExist = (this.projectList.size < response.answer.total);

			this.fillCache(items, isFirstPage);
			this.renderProjectListItems(items, isFirstPage, isNextPageExist);

			if (showLoading)
			{
				this.loading.hideForList();
			}
			this.loading.hideForTitle();

			this.list.stopRefreshing();
		}

		fillCache(list, isFirstPage)
		{
			if (
				isFirstPage
				&& this.filter.isDefaultPreset()
				&& this.filter.isDefaultCounter()
			)
			{
				const projects = {};
				list.forEach((project) => {
					projects[project.id] = project;
				});
				this.cache.set(projects);
			}
		}

		renderProjectListItems(items, isFirstPage, isNextPageExist)
		{
			if (items.length <= 0)
			{
				this.welcomeScreen.show();

				return;
			}

			if (isFirstPage)
			{
				this.welcomeScreen.hide();
				this.list.setItems(items);
			}
			else
			{
				this.list.removeItem({ id: '-more-' });
				this.list.addItems(items);
			}

			if (isNextPageExist)
			{
				this.list.addItems([
					{
						id: '-more-',
						title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_NEXT_PAGE'),
						type: 'button',
						sectionCode: Section.type.more,
					},
				]);
			}
		}

		onItemSelected(item)
		{
			const projectId = item.id.toString();

			if (projectId === '-more-')
			{
				this.list.updateItem(
					{ id: '-more-' },
					{
						type: 'loading',
						title: Loc.getMessage('MOBILE_TASKS_PROJECT_LIST_LOADING'),
					},
				);
				this.reload(this.start);
			}
			else if (this.projectList.has(projectId))
			{
				const project = this.projectList.get(projectId);
				const projectItem = {
					id: project.id,
					title: project.name,
					params: {
						avatar: project.image,
						initiatedByType: project.additionalData.initiatedByType,
						features: (project.additionalData.features || []),
						membersCount: (project.getHeadCount() + project.getMemberCount()),
						role: project.additionalData.role,
						opened: project.isOpened,
					},
				};
				const params = {
					projectId: project.id,
					siteId: BX.componentParameters.get('SITE_ID', env.siteId),
					siteDir: BX.componentParameters.get('SITE_DIR', env.siteDir),
					newsPathTemplate: this.newsPathTemplate,
					calendarWebPathTemplate: this.calendarWebPathTemplate,
					currentUserId: parseInt(this.userId || 0, 10),
				};

				void WorkgroupUtil.openProject(projectItem, params);
			}
		}

		addProject()
		{
			ProjectCreateManager.open(this.userId);
		}

		addItem(projectData)
		{
			BX.onViewLoaded(() => {
				const projectId = String(projectData.id);

				if (this.projectList.has(projectId))
				{
					return;
				}

				const project = new Project(this.userId);
				project.setData(projectData);
				this.projectList.set(projectId, project);

				this.welcomeScreen.hide();

				const projectItem = ProjectList.prepareListItem(project);
				this.list.addItems([projectItem]);
			});
		}

		updateItem(id, projectData = {})
		{
			BX.onViewLoaded(() => {
				const projectId = id.toString();

				if (!this.projectList.has(projectId))
				{
					return;
				}

				const project = this.projectList.get(projectId);
				project.updateData(projectData);

				const projectItem = ProjectList.prepareListItem(project);
				this.list.updateItem({ id: projectId }, projectItem);
				this.cache.setProjects([projectItem]);
			});
		}

		removeItem(id)
		{
			BX.onViewLoaded(() => {
				this.projectList.delete(id);
				this.list.removeItem({ id });
				this.cache.removeProject(id);

				if (this.projectList.size === 0)
				{
					this.welcomeScreen.show();
				}
			});
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

		onAppPaused(data, force = false)
		{
			if (!force && data.tabId !== this.getTabName())
			{
				return;
			}

			this.pauseTime = new Date();

			this.pull.setCanExecute(false);
			this.pull.clear();
		}

		onAppActive(data)
		{
			if (data.tabId !== this.getTabName())
			{
				return;
			}

			this.activationTime = new Date();

			if (this.pauseTime)
			{
				const timePassed = this.activationTime.getTime() - this.pauseTime.getTime();
				const minutesPassed = Math.abs(Math.round(timePassed / 60000));

				if (minutesPassed > 29)
				{
					this.runOnAppActiveRepeatedActions();
				}
				else
				{
					this.updateProjectsFromEvents();
				}
			}
		}

		runOnAppActiveRepeatedActions()
		{
			this.setFloatingButton();
			this.getPresets();

			this.filter.updateCounters();
			this.reload();

			this.pull.onAppActive();
		}

		updateProjectsFromEvents()
		{
			this.loading.showForTitle();

			setTimeout(() => {
				const projectIds = this.pull.getProjectsToUpdateFromEvents();
				if (projectIds.length > 15)
				{
					this.runOnAppActiveRepeatedActions();

					return;
				}

				const promises = [
					new Promise((resolve, reject) => {
						this.pull.extendWatch();
						this.pull.startWatch()
							.then(() => {
								this.pull.setCanExecute(true);
								this.pull.freeQueue().then(() => resolve()).catch(() => reject());
							})
							.catch(() => reject());
					}),
				];
				if (projectIds.length > 0)
				{
					promises.push(
						new Promise((resolve) => {
							this.filter.updateCounters();
							resolve();
						}),
						this.updateProjects(projectIds),
					);
				}
				Promise.allSettled(promises)
					.then(() => this.loading.hideForTitle())
					.catch(() => this.loading.hideForTitle());
			}, 1000);
		}

		updateProjects(projectIds)
		{
			return new Promise((resolve, reject) => {
				const params = {
					mode: this.mode,
				};

				params.siftThroughFilter = {
					presetId: this.filter.getPreset(),
				};

				(new RequestExecutor('tasksmobile.Project.list', {
					select: ProjectList.select,
					filter: { ...this.filter.get(), ID: projectIds },
					params,
				}))
					.call()
					.then(
						(response) => {
							this.onUpdateProjectsSuccess(projectIds, response.result.projects);
							resolve();
						},
						(response) => {
							logger.error(response);
							reject();
						},
					)
					.catch((response) => {
						logger.error(response);
						reject();
					});
			});
		}

		onUpdateProjectsSuccess(projectIds, projects)
		{
			projectIds.forEach((projectId) => {
				const projectData = projects.find((project) => Number(project.id) === Number(projectId));
				if (projectData)
				{
					if (this.projectList.has(projectId))
					{
						this.updateItem(projectId, projectData);
					}
					else
					{
						this.addItem(projectData);
					}
				}
				else if (this.projectList.has(projectId))
				{
					this.removeItem(projectId);
				}
			});
		}

		pseudoReadProjects(projectIds)
		{
			const items = [];
			const projects = [];
			let newCommentsRead = 0;

			this.projectList.forEach((project) => {
				const projectId = String(project.id);
				if (projectIds.includes(projectId))
				{
					newCommentsRead += project.getNewCommentsCount();
					project.pseudoRead();

					const projectItem = ProjectList.prepareListItem(project);
					items.push({
						filter: { id: projectId },
						element: projectItem,
					});
					projects.push(projectItem);
				}
			});
			this.list.updateItems(items);
			this.cache.setProjects(projects);
			this.filter.pseudoUpdateCounters(-newCommentsRead);
		}
	}

	return new ProjectList(
		list,
		parseInt(BX.componentParameters.get('USER_ID', 0), 10),
		{
			mode: BX.componentParameters.get('MODE', Mode.PROJECT),
			projectNewsPathTemplate: BX.componentParameters.get('PROJECT_NEWS_PATH_TEMPLATE', ''),
			projectCalendarWebPathTemplate: BX.componentParameters.get('PROJECT_CALENDAR_WEB_PATH_TEMPLATE', ''),
		},
	);
})();
