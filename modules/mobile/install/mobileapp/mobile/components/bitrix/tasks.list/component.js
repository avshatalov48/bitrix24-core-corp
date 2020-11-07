/**
 * @bxjs_lang_path component.php
 */

include('InAppNotifier');

(() => {
	const pathToComponent = '/bitrix/mobileapp/mobile/components/bitrix/tasks.list/';
	const userId = parseInt(BX.componentParameters.get('USER_ID', 0), 10);
	const apiVersion = Application.getApiVersion();
	const platform = Application.getPlatform();

	class Request
	{
		constructor(namespace = 'tasks.task.')
		{
			this.restNamespace = namespace;
		}

		call(methodName, params)
		{
			const method = this.restNamespace + methodName;

			this.abortCurrentRequest();

			return new Promise((resolve, reject) => {
				console.log({method, params});

				BX.rest.callMethod(method, params || {}, (response) => {
					if (response.error())
					{
						console.log(response.error());
						reject(response);
					}
					else
					{
						resolve(response.answer);
					}
				}, this.onRequestCreate.bind(this));
			});
		}

		onRequestCreate(ajax)
		{
			this.currentAjaxObject = ajax;
		}

		abortCurrentRequest()
		{
			if (this.currentAjaxObject != null)
			{
				this.currentAjaxObject.abort();
			}
		}
	}

	class SectionHandler
	{
		static getInstance()
		{
			if (SectionHandler.instance == null)
			{
				SectionHandler.instance = new SectionHandler();
			}

			return SectionHandler.instance;
		}

		constructor()
		{
			console.log('init SectionHandler');
			this.clear();
		}

		clear()
		{
			this.items = {
				new: {
					id: 'new',
					title: '',
					foldable: false,
					folded: false,
					badgeValue: 0,
					backgroundColor: '#FFFFFF',
					styles: {title: {font: {size: 18}}},
				},
				pinned: {
					id: 'pinned',
					title: '',
					foldable: false,
					folded: false,
					badgeValue: 0,
					sortItemParams: {activityDate: 'desc'},
					backgroundColor: '#FFFFFF',
					styles: {title: {font: {size: 18}}},
				},
				default: {
					id: 'default',
					title: '',
					foldable: false,
					folded: false,
					badgeValue: 0,
					sortItemParams: {activityDate: 'desc'},
					backgroundColor: '#FFFFFF',
					styles: {title: {font: {size: 18}}},
				},
				empty: {
					id: 'empty',
					title: '',
					foldable: false,
					folded: false,
					badgeValue: 0,
					backgroundColor: '#FFFFFF',
					styles: {title: {font: {size: 18}}},
				},
			};
		}

		add(item)
		{
			const {id} = item;

			this.items[id] = {
				id: id || 'default',
				title: item.title || '',
				foldable: item.foldable || false,
				folded: item.folded || false,
				badgeValue: (this.has(id) ? (this.items[id].badgeValue || 0) : 0) + (item.badgeValue || 0),
				backgroundColor: item.backgroundColor || '#FFFFFF',
				styles: {title: {font: {size: 18}}},
			};
		}

		setSortItemParams(sectionId, sortItemParams)
		{
			if (this.has(sectionId))
			{
				this.items[sectionId].sortItemParams = sortItemParams;
			}
		}

		has(id)
		{
			return id in this.items;
		}

		get list()
		{
			return Object.values(this.items);
		}
	}

	class Order
	{
		static get fields()
		{
			return {
				activityDate: [
					{field: 'ACTIVITY_DATE', direction: 'DESC'},
				],
				deadline: [
					{field: 'DEADLINE', direction: 'ASC,NULLS'},
					{field: 'ID', direction: 'DESC'},
				],
			};
		}

		static get sectionOrderFields()
		{
			return {
				activityDate: 'desc',
				deadline: 'asc',
			};
		}

		constructor()
		{
			this.order = 'activityDate';
		}

		get()
		{
			const order = {};

			Order.fields[this.order].forEach((fieldData) => {
				order[fieldData.field] = fieldData.direction;
			});

			return order;
		}

		getForSearch()
		{
			const order = {};

			Order.fields.activityDate.forEach((fieldData) => {
				order[fieldData.field] = fieldData.direction;
			});

			return order;
		}

		changeOrder()
		{
			this.order = Object.keys(Order.fields).filter(key => key !== this.order)[0];
		}

		isDeadline()
		{
			return this.checkOrder('deadline');
		}

		isActivityDate()
		{
			return this.checkOrder('activityDate');
		}

		checkOrder(order)
		{
			return this.order === order;
		}

		get order()
		{
			return this._order || 'activityDate';
		}

		set order(order)
		{
			this._order = order;
		}
	}

	class Filter
	{
		static getColumn(matrix, column)
		{
			const columnItems = [];

			for (let i = 0; i < matrix.length; i++)
			{
				columnItems.push(matrix[i][column]);
			}

			return columnItems;
		}

		static get presetsMap()
		{
			return {
				view_all: ['expired', 'new_comments'],
				view_role_responsible: ['expired', 'new_comments'],
				view_role_accomplice: ['expired', 'new_comments'],
				view_role_originator: ['expired', 'new_comments'],
				view_role_auditor: ['expired', 'new_comments'],
			};
		}

		constructor(list, currentUser, owner, groupId)
		{
			this.list = list;
			this.currentUser = currentUser;
			this.owner = owner;
			this.groupId = groupId || 0;

			this.counters = {};

			this.showCompleted = false;
			this.storageName = 'tasks.task.list.filter.counters';

			this.role = BX.componentParameters.get('ROLE', 'view_all');

			this.setCounterValue(Application.storage.getObject(this.storageName).counterValue || 0);
			this.setVisualCounters();

			BX.addCustomEvent('onAppData', this.updateCounters());
		}

		updateCounters()
		{
			if (!this.isMyList())
			{
				return;
			}

			console.log('UPDATE COUNTERS');

			const action = `${(new Request()).restNamespace}counters.get`;
			const batchOperations = {};

			Object.keys(Filter.presetsMap).forEach((role) => {
				batchOperations[role] = [action, {
					type: role,
					userId: this.owner.id || this.currentUser.id,
					groupId: this.groupId,
				}];
			});

			BX.rest.callBatch(batchOperations, (result) => {
				if (!result.view_all.answer.result)
				{
					return;
				}

				this.counters = {};

				Object.keys(Filter.presetsMap).forEach((role) => {
					const subPresets = Filter.presetsMap[role];
					const counter = result[role].answer.result;

					this.counters[role] = {};
					this.counters[`${role}_total`] = 0;

					subPresets.forEach((subPresetType) => {
						const subCounter = counter[subPresetType];
						const subCounterValue = (subCounter ? Number(subCounter.counter) : 0);

						this.counters[role][subPresetType] = subCounterValue;
						this.counters[`${role}_total`] += subCounterValue;
					});
				});

				this.setVisualCounters();
				this.saveCache();
			});
		}

		pseudoUpdateCounters(value, task = null)
		{
			if (!this.isMyList() || (task && task.isMuted))
			{
				return;
			}

			this.counters.view_all_total += value;
			if (this.counters.view_all_total < 0)
			{
				this.counters.view_all_total = 0;
			}

			this.setVisualCounters();
		}

		onUserCounter(data)
		{
			if (Number(this.currentUser.id) !== Number(data.userId) || !this.isMyList())
			{
				return;
			}

			const counters = data[0];

			if (!counters[this.role])
			{
				console.log({error: `${this.role} not found in counters`, counters});
			}

			this.counters = {};

			Object.keys(Filter.presetsMap).forEach((presetType) => {
				const subPresets = Filter.presetsMap[presetType];
				const counter = counters[presetType];

				this.counters[presetType] = counter;
				this.counters[`${presetType}_total`] = 0;

				subPresets.forEach((subPresetType) => {
					const subCounter = counter[subPresetType];
					const subCounterValue = (subCounter ? Number(subCounter) : 0);

					this.counters[`${presetType}_total`] += subCounterValue;
				});
			});

			this.setVisualCounters();
			this.saveCache();
		}

		setVisualCounters(value)
		{
			const counterValue = value || this.getCounterValue();

			this.setDownMenuTasksCounter(counterValue);
			this.setListTopButtonCounter(counterValue);
		}

		setDownMenuTasksCounter(value)
		{
			if (this.isMyList())
			{
				Application.setBadges({tasks: value});
			}
		}

		setListTopButtonCounter(value)
		{
			if (this.isMyList())
			{
				Application.setBadges({[`tasks_list_more_button_${this.owner.id}_${this.role}`]: value});
			}
		}

		getCounterValue(type = 'view_all', subType = 'total')
		{
			if (!this.counters[type] && !this.counters[`${type}_${subType}`])
			{
				return 0;
			}

			if (subType === 'total')
			{
				return this.counters[`${type}_${subType}`] || 0;
			}

			return this.counters[type][subType] || 0;
		}

		setCounterValue(value)
		{
			this.counters.view_all_total = (this.isMyList() ? value : 0);
		}

		saveCache()
		{
			if (this.isMyList())
			{
				Application.storage.setObject(this.storageName, {counterValue: this.getCounterValue()});
			}
		}

		get()
		{
			const filterRoleMap = {
				view_role_responsible: 'R',
				view_role_accomplice: 'A',
				view_role_auditor: 'U',
				view_role_originator: 'O',
			};
			const filter = {
				MEMBER: this.owner.id || this.currentUser.id,
				ZOMBIE: 'N',
				CHECK_PERMISSIONS: 'Y',
				IS_PINNED: 'N',
			};

			if (!this.isMyList())
			{
				delete filter.IS_PINNED;
			}

			if (this.groupId > 0)
			{
				filter.GROUP_ID = this.groupId;
				delete filter.MEMBER;
			}

			if (!this.showCompleted)
			{
				filter['::SUBFILTER-STATUS-OR'] = {
					'::LOGIC': 'OR',
					'::SUBFILTER-1': {
						REAL_STATUS: [2, 3, 4, 6],
					},
					'::SUBFILTER-2': {
						WITH_NEW_COMMENTS: 'Y',
					},
				};
			}

			if (filterRoleMap[this.role])
			{
				filter.ROLE = filterRoleMap[this.role];
			}

			return filter;
		}

		getForPinned()
		{
			const filter = this.get();
			filter.IS_PINNED = 'Y';

			return filter;
		}

		isMyList()
		{
			return Number(this.currentUser.id) === Number(this.owner.id) && !this.groupId;
		}

		get role()
		{
			return this._role;
		}

		set role(role)
		{
			this._role = role;
		}
	}

	class UserList
	{
		static getFormattedName(userData = {})
		{
			let name = `${userData.NAME || ''} ${userData.LAST_NAME || ''}`;
			if (name.trim() === '')
			{
				name = userData.EMAIL;
			}

			return name;
		}

		constructor(list, responsible, currentUser, handlers)
		{
			this.responsible = responsible;
			this.currentUser = currentUser;
			this.handlers = handlers;

			this.storageName = 'tasks.user.list';
			this.request = new Request('user.');
			this.users = new Map();
			this.usersSort = [];

			if (!this.loadUsersFromCache())
			{
				this.loadUsersFromComponent();
			}

			this.onUserTypeText = TaskList.debounce((event) => {
				if (event.text.length <= 2)
				{
					this.loadUsersFromCache();
					this.list.setItems(this.getList());
					return;
				}

				this.list.setItems([{type: 'loading', title: BX.message('TASKS_LIST_BUTTON_NEXT_PROCESS')}]);

				this.request.call('search', {
					IMAGE_RESIZE: 'small',
					SORT: 'LAST_NAME',
					ORDER: 'ASC',
					FILTER: {
						ACTIVE: 'Y',
						NAME_SEARCH: event.text,
					},
				}).then(
					(response) => {
						let userList = [];

						if (response.result.length)
						{
							userList = response.result.map(item => ({
								id: item.ID,
								title: UserList.getFormattedName(item),
								imageUrl: encodeURI(item.PERSONAL_PHOTO),
								color: '#5D5C67',
								useLetterImage: true,
								sectionCode: 'default',
							}));
						}
						else
						{
							userList.push({
								id: 0,
								title: BX.message('TASKS_LIST_SEARCH_EMPTY_RESULT'),
								sectionCode: 'default',
								type: 'button',
								unselectable: true,
							});
						}

						this.list.setItems(userList);
					},
					response => console.log(response)
				);
			}, 1000, this);

			list.showUserList(
				{
					title: BX.message('TASKS_LIST_POPUP_RESPONSIBLE'),
					limit: 1, // TODO
					items: this.getList(),
					sections: [{id: 'default'}],
				},
				(userList) => {
					this.list = userList;
					this.list.setListener((event, data) => {
						const eventHandlers = {
							onRecipientsReceived: this.onRecipientsReceived,
							onUserTypeText: this.onUserTypeText,
						};
						console.log(`Fire event: ${event}`);
						if (eventHandlers[event])
						{
							eventHandlers[event].apply(this, [data]);
						}
					});
				}
			);
		}

		loadUsersFromCache()
		{
			const cacheData = Application.storage.getObject(this.storageName);
			if (cacheData && cacheData.list && cacheData.sort)
			{
				cacheData.list.forEach((item) => {
					if (item.id && this.users.size < 50)
					{
						this.users.set(item.id.toString(), item);
					}
				});
				this.usersSort = cacheData.sort;

				return true;
			}

			return false;
		}

		loadUsersFromComponent()
		{
			const {userList} = result;

			Object.values(userList).forEach((user) => {
				if (this.users.size < 50)
				{
					this.usersSort.push(String(user.id));
					this.users.set(String(user.id), {
						id: user.id,
						title: user.name,
						imageUrl: user.icon,
						color: '#f0f0f0',
						useLetterImage: true,
						sectionCode: 'default',
					});
				}
			});

			this.saveCache();
		}

		saveCache()
		{
			Application.storage.setObject(this.storageName, {
				list: [...this.users.values()],
				sort: this.usersSort,
			});
		}

		getList()
		{
			const users = [];

			this.usersSort.forEach((row) => {
				users.push(this.users.get(row));
			});

			return users;
		}

		onRecipientsReceived(items)
		{
			const user = items[0];

			if (this.handlers.onSelect && user.id)
			{
				const userId = user.id.toString();

				if (!this.users.has(userId))
				{
					this.users.set(userId, {
						id: user.id,
						title: user.title,
						imageUrl: user.imageUrl,
						color: '#f0f0f0',
						useLetterImage: true,
						sectionCode: 'default',
					});
				}

				delete this.usersSort[this.usersSort.indexOf(userId)];
				this.usersSort.unshift(userId);

				this.saveCache();
				this.handlers.onSelect(items);
			}
		}
	}

	class Options
	{
		constructor()
		{
			const now = new Date();
			now.setDate(now.getDate() - 1);

			this.storageName = 'tasks.task.list.options';
			this.defaultOptions = {
				swipeShowHelper: {
					value: 0,
					limit: 2,
				},
				deadlines: {
					lastTime: now.getTime(),
					value: result.deadlines,
				},
			};
		}

		get()
		{
			return Application.storage.getObject(this.storageName, this.defaultOptions);
		}

		set(options)
		{
			Application.storage.setObject(this.storageName, options);
		}

		update(optionName, optionValue)
		{
			Application.storage.updateObject(this.storageName, {[optionName]: optionValue});
		}
	}

	class TaskList
	{
		static showError(message = null, title = '')
		{
			if (
				message !== null
				&& PageManager.getNavigator().isVisible()
				&& PageManager.getNavigator().isActiveTab()
			)
			{
				InAppNotifier.showNotification({message, title, backgroundColor: '#333333'});
			}
		}

		static resolveErrorMessageByAnswer(answer = null)
		{
			let messageError = BX.message('TASKS_SOME_THING_WENT_WRONG');

			if (answer && answer.ex)
			{
				switch (answer.ex.status)
				{
					case -2:
						messageError = BX.message('TASKS_NO_INTERNET_CONNECTION');
						break;

					case 0:
						messageError = BX.message('TASKS_UNEXPECTED_ANSWER');
						break;

					default:
						messageError = BX.message('TASKS_SOME_THING_WENT_WRONG');
						break;
				}
			}

			console.log('Error', answer);

			return messageError;
		}

		static debounce(fn, timeout, ctx)
		{
			let timer = 0;
			return function() {
				clearTimeout(timer);
				timer = setTimeout(() => {
					fn.apply(ctx || this, arguments);
				}, timeout);
			};
		}

		static getItemDataFromUser(user)
		{
			return {
				id: user.id,
				name: user.title,
				icon: user.imageUrl,
				link: '',
			};
		}

		static get selectFields()
		{
			return [
				'ID',
				'TITLE',
				'STATUS',
				'CREATED_BY',
				'ACTIVITY_DATE',
				'RESPONSIBLE_ID',
				'DEADLINE',
				'COMMENTS_COUNT',
				'AUDITORS',
				'ACCOMPLICES',
				'NEW_COMMENTS_COUNT',
				'FAVORITE',
				'NOT_VIEWED',
				'GROUP_ID',
				'IS_MUTED',
				'IS_PINNED',
			];
		}

		static get statusList()
		{
			return Task.statusList;
		}

		static get titles()
		{
			return {
				view_all: BX.message('TASKS_LIST_HEADER_ROLE_ALL'),
				view_role_responsible: BX.message('TASKS_LIST_HEADER_ROLE_RESPONSIBLE'),
				view_role_accomplice: BX.message('TASKS_LIST_HEADER_ROLE_ACCOMPLICE'),
				view_role_originator: BX.message('TASKS_LIST_HEADER_ROLE_ORIGINATOR'),
				view_role_auditor: BX.message('TASKS_LIST_HEADER_ROLE_AUDITOR'),
			};
		}

		constructor(list, owner)
		{
			console.log('taskList.constructor');

			this.list = list;
			this.owner = {id: owner};
			this.currentUser = result.settings.userInfo;

			this.storageName = `tasks.task.list_${owner}`;
			this.searchStorageName = `tasks.task.search_${owner}`;

			this.taskList = new Map();
			this.newTaskList = new Map();
			this.comments = new Map();

			this.searchText = '';
			this.searchMinSize = parseInt(BX.componentParameters.get('MIN_SEARCH_SIZE', 3), 10);
			this.searchTaskList = new Map();

			this.groupId = parseInt(BX.componentParameters.get('GROUP_ID', 0), 10);

			this.start = 0;
			this.total = 0;

			this.filter = new Filter(this.list, this.currentUser, this.owner, this.groupId);
			this.order = new Order();
			this.options = new Options();

			this.filter.showCompleted = BX.componentParameters.get('SHOW_COMPLETED', false);
			this.order.order = BX.componentParameters.get('ORDER', this.order.order);

			this.checkDeadlinesUpdate();

			this.canShowSwipeActions = true;
			this.searchDebounceFunction = this.getSearchDebounceFunction();

			BX.addCustomEvent('onPullEvent-tasks', (command, params) => {
				if (command === 'user_counter')
				{
					this.filter.onUserCounter.apply(this.filter, [params]);
				}
			});

			BX.onViewLoaded(() => {
				this.list.setItems([
					{
						type: 'loading',
						title: BX.message('TASKS_LIST_BUTTON_NEXT_PROCESS'),
					},
				]);

				this.updateTitle();
				this.setListListeners();
				this.bindEvents();
				this.setTopButtons();

				this.loadTasksFromCache();
				this.reload();
			});
		}

		isMyList()
		{
			return Number(this.currentUser.id) === Number(this.owner.id) && !this.groupId;
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
				onNewItem: {
					callback: this.onNewItem,
					context: this,
				},
				onUserTypeText: {
					callback: this.onUserTypeText,
					context: this,
				},
				onSearchItemSelected: {
					callback: this.onSearchItemSelected,
					context: this,
				},
				onSearchShow: {
					callback: this.onSearchShow,
					context: this,
				},
				onSearchHide: {
					callback: this.onSearchHide,
					context: this,
				},
				onItemSelected: {
					callback: this.onItemSelected,
					context: this,
				},
				onItemAction: {
					callback: this.onItemAction,
					context: this,
				},
				onItemChecked: {
					callback: this.onItemChecked,
					context: this,
				},
			};

			this.list.setListener((event, data) => {
				console.log(`Fire event: app.${event}`);
				if (eventHandlers[event])
				{
					eventHandlers[event].callback.apply(eventHandlers[event].context, [data]);
				}
			});
		}

		bindEvents()
		{
			BX.addCustomEvent('onAppActive', () => this.onAppActive());
			BX.addCustomEvent('onAppPaused', () => this.onAppPaused());
			BX.addCustomEvent('task.view.onCommentsRead', eventData => this.onCommentsRead(eventData));
			BX.addCustomEvent('task.list.onDeadlineSwitch', eventData => this.onDeadlineSwitch(eventData));
			BX.addCustomEvent('task.list.onToggleCompleted', () => this.onToggleCompleted());

			if (apiVersion >= 34)
			{
				BX.addCustomEvent('onTabsSelected', tabName => this.onTabsSelected(tabName));
			}

			this.bindTasksEvents();
		}

		bindTasksEvents()
		{
			const eventHandlers = {
				task_view: {
					method: this.onPullView,
					context: this,
				},
				task_add: {
					method: this.onPullAdd,
					context: this,
				},
				task_update: {
					method: this.onPullUpdate,
					context: this,
				},
				task_remove: {
					method: this.onPullDelete,
					context: this,
				},
				comment_add: {
					method: this.onPullComment,
					context: this,
				},
				comment_read_all: {
					method: this.onPullCommentReadAll,
					context: this,
				},
				user_option_changed: {
					method: this.onUserOptionChanged,
					context: this,
				},
				user_counter: {
					method: this.filter.onUserCounter,
					context: this.filter,
				},
			};

			BX.addCustomEvent('onPullEvent-tasks', (command, params) => {
				const {method, context} = eventHandlers[command];
				if (method)
				{
					method.apply(context, [params]);
				}
			});
		}

		setTopButtons()
		{
			this.list.setRightButtons([
				{
					type: 'search',
					callback: () => this.list.showSearchBar(),
				},
				{
					type: (this.order.isDeadline() ? 'more_active' : 'more'),
					badgeCode: `tasks_list_more_button_${this.owner.id}_${this.filter.role}`,
					callback: () => this.initPopupMenu(),
				},
			]);

			this.filter.setVisualCounters();
		}

		loadTasksFromCache()
		{
			console.log('loadTasksFromCache');

			BX.onViewLoaded(() => {
				const items = [];
				const cachedTasks = Application.storage.getObject(`${this.storageName}_${this.filter.role}`) || {};

				if (
					!cachedTasks
					|| Object.keys(cachedTasks).length === 0
					|| (Object.keys(cachedTasks).length === 1 && cachedTasks[0] === null)
				)
				{
					console.log('tasks cache is empty');
					return;
				}

				Object.keys(cachedTasks).forEach((key) => {
					const task = new Task(this.currentUser);
					task.setData(cachedTasks[key]);
					items.push(this.getItemDataFromTask(task));
				});

				this.list.setItems(items, null, true);
			});
		}

		reload(taskListOffset = 0, showLoading = false)
		{
			BX.onViewLoaded(() => {
				console.log('reload');

				if (showLoading)
				{
					this.showLoading();
				}

				if (!taskListOffset)
				{
					this.comments.clear();
					this.taskList.clear();
					this.newTaskList.clear();
				}

				let select = TaskList.selectFields;
				if (!this.isMyList())
				{
					select = select.filter(field => field !== 'IS_PINNED');
				}

				const {restNamespace} = new Request();
				const batchOperations = {
					all: [`${restNamespace}list`, {
						select,
						filter: this.filter.get(),
						order: this.order.get(),
						start: taskListOffset,
						params: {
							RETURN_ACCESS: 'Y',
							RETURN_USER_INFO: 'Y',
							RETURN_GROUP_INFO: 'Y',
							SEND_PULL: 'Y',
						},
					}],
				};

				if (!taskListOffset && this.isMyList())
				{
					batchOperations.pinned = [`${restNamespace}list`, {
						select: TaskList.selectFields,
						filter: this.filter.getForPinned(),
						order: this.order.get(),
						params: {
							RETURN_ACCESS: 'Y',
							RETURN_USER_INFO: 'Y',
							RETURN_GROUP_INFO: 'Y',
							SEND_PULL: 'Y',
						},
					}];
				}

				BX.rest.callBatch(batchOperations, (result) => {
					this.onReloadSuccess(result, showLoading, taskListOffset);
				});
			});
		}

		onReloadSuccess(response, showLoading, taskListOffset)
		{
			console.log('onReloadSuccess', response);

			const isEmptyOffset = (taskListOffset === 0);

			const {all, pinned} = response;
			const {next, total, result} = all.answer;
			const {tasks} = result;
			const pinnedTasks = (pinned ? pinned.answer.result.tasks : []) || [];
			const allTasks = pinnedTasks.concat(tasks);

			let items = [];

			this.start = next;
			this.total = total;

			if (isEmptyOffset)
			{
				this.fillCache(allTasks);
			}

			this.updateSections(isEmptyOffset);

			allTasks.forEach((row) => {
				const task = new Task(this.currentUser);
				task.setData(row);
				const item = this.getItemDataFromTask(task);

				items.push(item);
				this.taskList.set(task.id, task);
			});

			if (items.length > 0)
			{
				if (isEmptyOffset)
				{
					items = this.handleSwipeActionsShow(items);

					this.list.setItems(items, null, true);
					setTimeout(
						() => this.list.updateItem({id: items[0].id}, {showSwipeActions: false}),
						300
					);
				}
				else
				{
					this.list.removeItem({id: '-more-'});
					this.list.addItems(items);
				}

				if (next)
				{
					this.list.addItems([{
						id: '-more-',
						title: BX.message('TASKS_LIST_BUTTON_NEXT'),
						type: 'button',
						sectionCode: 'more',
					}]);
				}
			}
			else
			{
				this.list.setItems([{
					id: '-none-',
					title: BX.message('TASKS_LIST_NOTHING_NOT_FOUND'),
					type: 'button',
				}]);
			}

			if (showLoading)
			{
				this.hideLoading();
			}
			this.list.stopRefreshing();
		}

		fillCache(list)
		{
			console.log('fillCache');
			Application.storage.setObject(`${this.storageName}_${this.filter.role}`, list);
		}

		updateSections(clear = true)
		{
			const sectionHandler = SectionHandler.getInstance();

			if (clear)
			{
				console.log('sections.clear');
				sectionHandler.clear();
			}

			sectionHandler.setSortItemParams('default', {
				[this.order.order]: Order.sectionOrderFields[this.order.order],
			});
			sectionHandler.setSortItemParams('pinned', {
				[this.order.order]: Order.sectionOrderFields[this.order.order],
			});

			if (!sectionHandler.has('more'))
			{
				sectionHandler.add({id: 'more'});
			}

			this.list.setSections(sectionHandler.list);
		}

		handleSwipeActionsShow(items)
		{
			const {swipeShowHelper} = this.options.get();

			if ((swipeShowHelper.value < swipeShowHelper.limit) && this.canShowSwipeActions)
			{
				this.canShowSwipeActions = false;

				items[0].showSwipeActions = true;
				swipeShowHelper.value += 1;

				const name = (obj => Object.keys(obj)[0]);
				this.options.update(name({swipeShowHelper}), swipeShowHelper);
			}

			return items;
		}

		showLoading()
		{
			if (apiVersion >= 34)
			{
				dialogs.showSpinnerIndicator({
					color: '#777777',
					backgroundColor: '#77FFFFFF',
				});
			}
		}

		hideLoading()
		{
			if (apiVersion >= 34)
			{
				dialogs.hideSpinnerIndicator();
			}
		}

		onAppActive()
		{
			const now = new Date();

			this.canShowSwipeActions = true;

			if (this.time)
			{
				const minutesPassed = Math.abs(Math.round((now.getTime() - this.time.getTime()) / 60000));
				if (minutesPassed > 30)
				{
					this.filter.updateCounters();
					this.reload();
				}
			}

			this.checkDeadlinesUpdate();
		}

		checkDeadlinesUpdate()
		{
			const now = new Date();
			const lastDeadlinesGetTime = new Date(this.options.get().deadlines.lastTime);
			const dayChanged = now.getDate() !== lastDeadlinesGetTime.getDate();

			if (dayChanged)
			{
				(new Request('mobile.tasks.'))
					.call('deadlines.get')
					.then(response => this.options.update('deadlines', {
						lastTime: now.getTime(),
						value: response.result,
					}));
			}
		}

		onAppPaused()
		{
			this.time = new Date();
		}

		onTabsSelected(tabName)
		{
			if (tabName !== 'tasks')
			{
				if (this.filter.role !== 'view_all')
				{
					setTimeout(() => this.list.back(), 300);
				}
				if (this.order.isDeadline())
				{
					setTimeout(() => this.onDeadlineSwitch({role: this.filter.role}), 300);
				}
			}
		}

		onCommentsRead(eventData)
		{
			console.log('task.view.onCommentsRead', eventData);

			const taskId = String(eventData.taskId);
			const newCommentsCount = 0;

			if (this.taskList.has(taskId))
			{
				const task = this.taskList.get(taskId);
				task.newCommentsCount = newCommentsCount;

				this.updateItem(taskId, {newCommentsCount});
			}
		}

		onUserOptionChanged(data)
		{
			if (Number(data.USER_ID) !== Number(this.currentUser.id))
			{
				return;
			}

			const taskId = data.TASK_ID.toString();
			const added = data.ADDED;

			switch (Number(data.OPTION))
			{
				case Task.userOptions.muted:
					this.onMuteChanged(taskId, added);
					break;

				case Task.userOptions.pinned:
					this.onPinChanged(taskId, added);
					break;

				default:
					break;
			}
		}

		onMuteChanged(taskId, added)
		{
			if (this.taskList.has(taskId))
			{
				this.updateItem(taskId, {isMuted: added});
			}
		}

		onPinChanged(taskId, added)
		{
			if (this.taskList.has(taskId))
			{
				this.updateItem(taskId, {isPinned: added});
			}
			else if (added)
			{
				(new Request())
					.call('get', {taskId})
					.then(
						response => this.updateTaskListItem(taskId, response.result.task, 'pinned'),
						response => console.log(response)
					);
			}
		}

		onPullView(data)
		{
			if (Number(data.USER_ID) !== Number(this.currentUser.id))
			{
				return;
			}

			const taskId = data.TASK_ID.toString();

			if (this.taskList.has(taskId))
			{
				this.updateItem(taskId, {newCommentsCount: 0});
			}
		}

		onPullComment(data)
		{
			console.log('onPullComment', data);

			const [entityType, entityId] = data.entityXmlId.split('_');
			const {messageId} = data;
			const isMyComment = (Number(data.ownerId) === Number(this.currentUser.id));

			if (!this.comments.has(entityId))
			{
				this.comments.set(entityId, new Set());
			}
			const taskComments = this.comments.get(entityId);

			if (entityType !== 'TASK' || taskComments.has(messageId))
			{
				return;
			}

			taskComments.add(messageId);
			this.comments.set(entityId, taskComments);

			if (this.taskList.has(entityId))
			{
				const task = this.taskList.get(entityId);

				if (!isMyComment && data.pullComment)
				{
					task.newCommentsCount += 1;
				}

				this.updateItem(entityId, {
					newCommentsCount: task.newCommentsCount,
					activityDate: Date.now(),
				});
			}
			else
			{
				(new Request())
					.call('get', {taskId: entityId})
					.then(
						response => this.updateTaskListItem(entityId, response.result.task),
						response => console.log(response)
					);
			}
		}

		onPullCommentReadAll()
		{
			const items = [];

			this.taskList.forEach((task) => {
				task.newCommentsCount = 0;
				items.push(this.getItemDataFromTask(task));
			});
			this.list.setItems(items, null, true);
		}

		onPullAdd(data)
		{
			console.log('onPullAdd');
			const taskId = data.TASK_ID.toString();

			if (!this.taskList.has(taskId))
			{
				(new Request())
					.call('get', {taskId})
					.then(
						response => this.updateTaskListItem(taskId, response.result.task),
						response => console.log(response)
					);
			}
		}

		onPullUpdate(data)
		{
			console.log('onPullUpdate', data);

			const taskId = data.TASK_ID.toString();
			const select = TaskList.selectFields.filter(field => field !== 'NEW_COMMENTS_COUNT');

			(new Request())
				.call('get', {taskId, select})
				.then(
					response => this.updateTaskListItem(taskId, response.result.task),
					response => console.log('onPullUpdate.get.error', response)
				);
		}

		updateTaskListItem(taskId, row, sectionId = 'default')
		{
			const isMyNewTask = this.newTaskList.has(row.guid) || this.newTaskList.has(row.id);

			if (this.taskList.has(taskId))
			{
				const task = this.taskList.get(taskId);
				task.setData(row);

				if (this.isMatchRole(task) || isMyNewTask)
				{
					this.updateItem(taskId, task);
				}
				else
				{
					this.removeItem(taskId);
				}
			}
			else if (!isMyNewTask)
			{
				const task = new Task(this.currentUser);
				task.setData(row);

				if (this.isMatchRole(task))
				{
					if (this.taskList.has(task.id))
					{
						this.updateItem(task.id, task);
					}
					else
					{
						this.addItem(task, sectionId);
					}
				}
			}
		}

		isMatchRole(task)
		{
			const role = this.filter.role;
			const userId = this.currentUser.id;

			if (!task.isMember(userId))
			{
				return false;
			}

			if (role === 'view_all')
			{
				return true;
			}

			const roleMap = {
				view_role_responsible: task.isResponsible(userId),
				view_role_accomplice: task.isAccomplice(userId),
				view_role_originator: task.isCreator(userId) && !task.isResponsible(userId),
				view_role_auditor: task.isAuditor(userId),
			};

			return roleMap[role];
		}

		onPullDelete(data)
		{
			console.log('onPullDelete');
			const taskId = data.TASK_ID.toString();

			if (this.searchTaskList.has(taskId))
			{
				this.searchTaskList.delete(taskId);
				this.searchListRender();
			}

			this.removeItem(taskId);
		}

		onNewItem(title)
		{
			console.log('onNewItem');

			const task = new Task(this.currentUser);
			this.newTaskList.set(task.guid);

			task.title = title;
			task.creator = this.currentUser;
			task.responsible = this.currentUser;
			task.groupId = this.groupId;
			task.activityDate = Date.now();

			this.addItem(task, 'new');

			const oldTaskId = task.id;
			task.save().then(() => {
				this.newTaskList.set(task.id);
				this.updateItem(oldTaskId, {id: task.id});
			}, e => console.error(e));
		}

		getItemDataFromTask(task, withActions = true)
		{
			let itemData = task.getTaskInfo(withActions);

			itemData.backgroundColor = Task.backgroundColors.default;
			itemData.sectionCode = 'default';
			itemData.sortValues = {
				deadline: task.deadline || 9999999999999,
				activityDate: task.activityDate,
			};

			if (task.isPinned)
			{
				itemData.backgroundColor = Task.backgroundColors.pinned;
				itemData.sectionCode = 'pinned';
			}

			if (withActions)
			{
				itemData = this.handleItemActions(itemData);
			}

			return itemData;
		}

		handleItemActions(itemData)
		{
			let {actions} = itemData;

			if (!this.isMyList())
			{
				actions = actions.filter(action => !['pin', 'unpin'].includes(action.identifier));
			}

			if (platform === 'ios')
			{
				const leftSwipeActions = actions.filter(action => action.position === 'left');

				if (actions.length > 4 + leftSwipeActions.length)
				{
					const swipeActions = leftSwipeActions.concat([Task.actions.more]);
					const popupActions = [];

					actions.filter(action => !leftSwipeActions.includes(action)).forEach((action) => {
						if (swipeActions.length < 4 + leftSwipeActions.length)
						{
							swipeActions.push(action);
						}
						else
						{
							popupActions.push({
								id: action.identifier,
								title: action.title,
								iconUrl: Task.popupImageUrls[action.identifier],
								sectionCode: 'default',
							});
						}
					});

					popupActions.push(Task.actions.cancel);

					actions = swipeActions;
					itemData.params.popupActions = popupActions;
				}
			}
			else
			{
				actions = actions.map((action) => {
					action.iconUrl = Task.popupImageUrls[action.identifier];
					return action;
				});

				itemData.menuMode = 'dialog';
			}

			itemData.actions = actions;

			return itemData;
		}

		addItems(items, sectionId = 'default')
		{
			BX.onViewLoaded(() => {
				this.removeItem('-none-');

				const taskItems = [];
				items.forEach((task) => {
					if (!this.taskList.has(task.id))
					{
						const taskData = this.getItemDataFromTask(task);
						taskData.sectionCode = sectionId;

						if (sectionId === 'new')
						{
							taskData.actions = taskData.actions.filter(item => item.identifier === 'changeResponsible');
						}

						this.taskList.set(task.id, task);
						taskItems.push(taskData);
					}
				});

				this.list.addItems(taskItems);
			});
		}

		addItem(item, sectionId = 'default')
		{
			this.addItems([item], sectionId);
		}

		updateItem(id, fields)
		{
			BX.onViewLoaded(() => {
				const taskId = id.toString();

				if (!this.taskList.has(taskId))
				{
					return;
				}

				const task = this.taskList.get(taskId);
				const has = Object.prototype.hasOwnProperty;

				if (fields.id && fields.id !== taskId)
				{
					this.taskList.delete(taskId);
					this.taskList.set(fields.id.toString(), task);
				}

				Object.keys(fields).forEach((key) => {
					if (has.call(task, key) || has.call(Object.getPrototypeOf(task), key))
					{
						task[key] = fields[key];
					}
				});

				const taskData = this.getItemDataFromTask(task);
				taskData.sectionCode = (
					this.newTaskList.has(task.guid) || this.newTaskList.has(task.id) ? 'new' : taskData.sectionCode
				);

				console.log(`updateItem #${id}`, taskData);
				this.list.updateItem({id}, taskData);
			});
		}

		removeItem(id)
		{
			BX.onViewLoaded(() => {
				this.taskList.delete(id);
				this.list.removeItem({id});
			});
		}

		onItemSelected(item)
		{
			const taskId = item.id.toString();

			if (taskId === '-more-')
			{
				this.list.updateItem(
					{id: '-more-'},
					{
						type: 'loading',
						title: BX.message('TASKS_LIST_BUTTON_NEXT_PROCESS'),
					}
				);
				this.reload(this.start);
			}
			else if (this.taskList.has(taskId))
			{
				const task = this.taskList.get(taskId);

				this.filter.pseudoUpdateCounters(-task.newCommentsCount, task);
				this.updateItem(taskId, {newCommentsCount: 0});

				task.open();
			}
		}

		onItemChecked(item)
		{
			const task = this.taskList.get(item.id.toString());

			if (task.isCompletedCounts)
			{
				this.updateItem(task.id, {
					status: Task.statusList.pending,
					activityDate: Date.now(),
				});
				task.renew();
			}
			else
			{
				this.updateItem(task.id, {
					status: Task.statusList.completed,
					activityDate: Date.now(),
				});
				task.complete();
			}
		}

		onItemAction(event)
		{
			const task = this.taskList.get(event.item.id);

			switch (event.action.identifier)
			{
				case 'changeDeadline':
					this.onChangeDeadlineAction(task);
					break;

				case 'changeResponsible':
					this.onChangeResponsibleAction(task);
					break;

				case 'start':
					this.onStartAction(task);
					break;

				case 'pause':
					this.onPauseAction(task);
					break;

				case 'pin':
					this.onPinAction(task);
					break;

				case 'unpin':
					this.onUnpinAction(task);
					break;

				case 'read':
					this.onReadAction(task);
					break;

				case 'mute':
					this.onMuteAction(task);
					break;

				case 'unmute':
					this.onUnmuteAction(task);
					break;

				case 'unfollow':
					this.onUnfollowAction(task);
					break;

				case 'remove':
					this.onRemoveAction(task);
					break;

				case 'more':
					this.onMoreAction(task);
					return;

				default:
					break;
			}

			this.updateItem(task.id, {});
		}

		onChangeDeadlineAction(task)
		{
			const pickerParams = {
				title: BX.message('TASKS_LIST_POPUP_SELECT_DATE'),
				type: 'datetime',
				value: task.deadline,
			};

			if (apiVersion >= 34)
			{
				pickerParams.items = [];

				Object.keys(Task.deadlines).forEach((key) => {
					const {deadlines} = this.options.get();
					pickerParams.items.push({
						name: Task.deadlines[key].name,
						value: deadlines.value[key] * 1000,
					});
				});
			}

			dialogs.showDatePicker(pickerParams, (eventName, ts) => {
				if (ts > 0 && ts !== task.deadline)
				{
					const oldCounterValue = task.getMessageInfo().count;
					this.updateItem(task.id, {deadline: ts, activityDate: Date.now()});
					const newCounterValue = task.getMessageInfo().count;
					this.filter.pseudoUpdateCounters(newCounterValue - oldCounterValue, task);
					task.saveDeadline();
				}
			});
		}

		onStartAction(task)
		{
			if (task.status !== TaskList.statusList.inprogress)
			{
				task.rawAccess.start = false;
				task.rawAccess.pause = true;
				task.start().then(() => this.updateItem(task.id, {status: task.status}));
				this.updateItem(task.id, {status: task.status, activityDate: Date.now()});
			}
		}

		onPauseAction(task)
		{
			if (task.status !== TaskList.statusList.pending)
			{
				task.rawAccess.start = true;
				task.rawAccess.pause = false;
				task.pause().then(() => this.updateItem(task.id, {status: task.status}));
				this.updateItem(task.id, {status: task.status, activityDate: Date.now()});
			}
		}

		onChangeResponsibleAction(task)
		{
			return new UserList(this.list, task.responsible, this.currentUser, {
				onSelect: (items) => {
					task.responsible = TaskList.getItemDataFromUser(items[0]);
					if (!task.isMember(this.currentUser.id))
					{
						this.removeItem(task.id);
						this.filter.pseudoUpdateCounters(-task.getMessageInfo().count, task);
					}
					else
					{
						this.updateItem(task.id, {
							responsibleIcon: task.responsible.icon,
							activityDate: Date.now(),
						});
					}
					task.save();
				},
			});
		}

		onMoreAction(task)
		{
			const taskItemData = this.getItemDataFromTask(task);
			const actionsPopup = dialogs.createPopupMenu();
			actionsPopup.setData(taskItemData.params.popupActions, [{id: 'default'}], (eventName, item) => {
				if (eventName === 'onItemSelected')
				{
					this.onActionsPopupItemSelected(item, task);
				}
			});
			actionsPopup.setPosition('center');
			actionsPopup.show();
		}

		onActionsPopupItemSelected(item, task)
		{
			switch (item.id)
			{
				case 'pin':
					this.onPinAction(task);
					break;

				case 'unpin':
					this.onUnpinAction(task);
					break;

				case 'read':
					this.onReadAction(task);
					break;

				case 'mute':
					this.onMuteAction(task);
					break;

				case 'unmute':
					this.onUnmuteAction(task);
					break;

				case 'unfollow':
					this.onUnfollowAction(task);
					break;

				case 'remove':
					this.onRemoveAction(task);
					break;

				case 'cancel':
					return;

				default:
					break;
			}

			this.updateItem(task.id, {});
		}

		onPinAction(task)
		{
			this.updateItem(task.id, {isPinned: true});
			task.pin();
		}

		onUnpinAction(task)
		{
			this.updateItem(task.id, {isPinned: false});
			task.unpin();
		}

		onReadAction(task)
		{
			this.filter.pseudoUpdateCounters(-task.newCommentsCount, task);
			this.updateItem(task.id, {newCommentsCount: 0});
			task.read();
		}

		onMuteAction(task)
		{
			this.updateItem(task.id, {isMuted: true});
			this.filter.pseudoUpdateCounters(-task.getMessageInfo().count);
			task.mute();
		}

		onUnmuteAction(task)
		{
			this.updateItem(task.id, {isMuted: false});
			this.filter.pseudoUpdateCounters(task.getMessageInfo().count);
			task.unmute();
		}

		onUnfollowAction(task)
		{
			task.auditors = task.auditors.filter(userId => userId !== Number(this.currentUser.id));

			if (!task.isMember(this.currentUser.id))
			{
				this.removeItem(task.id);
				this.filter.pseudoUpdateCounters(-task.getMessageInfo().count, task);
			}

			task.stopWatch();
		}

		onRemoveAction(task)
		{
			dialogs.showActionSheet({
				title: BX.message('TASKS_CONFIRM_DELETE'),
				callback: (item) => {
					if (item.code === 'YES')
					{
						this.removeItem(task.id);
						this.filter.pseudoUpdateCounters(-task.getMessageInfo().count, task);

						(new Request())
							.call('delete', {taskId: task.id})
							.then(response => {}, response => console.log(response));
					}
				},
				items: [
					{title: BX.message('TASKS_CONFIRM_DELETE_YES'), code: 'YES'},
					{title: BX.message('TASKS_CONFIRM_DELETE_NO'), code: 'NO'},
				],
			});
		}

		// popupMenu
		initPopupMenu()
		{
			const urlPrefix = `${pathToComponent}images/mobile-tasks-list-popup-`;
			const menuItems = [
				{
					id: 'readAll',
					title: BX.message('TASKS_POPUP_MENU_READ_ALL'),
					iconName: 'read',
					sectionCode: 'default',
				},
				{
					id: 'deadline',
					title: BX.message('TASKS_POPUP_MENU_DEADLINE'),
					iconName: 'term',
					sectionCode: 'default',
					checked: this.order.isDeadline(),
					counterValue: this.filter.getCounterValue(),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: 'toggleCompletedTasks',
					title: BX.message(`TASKS_POPUP_MENU_${(!this.filter.showCompleted ? 'SHOW' : 'HIDE')}_CLOSED_TASKS`),
					iconUrl: `${urlPrefix}${(!this.filter.showCompleted ? 'show' : 'hide')}-completed.png`,
					sectionCode: 'default',
					disable: this.order.isDeadline(),
				},
				{
					id: 'view_role_responsible',
					title: BX.message('TASKS_POPUP_MENU_ROLE_RESPONSIBLE'),
					iconUrl: `${urlPrefix}role-responsible.png`,
					sectionCode: 'default',
					checked: this.filter.role === 'view_role_responsible',
					showTopSeparator: true,
					counterValue: this.filter.getCounterValue('view_role_responsible'),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: 'view_role_accomplice',
					title: BX.message('TASKS_POPUP_MENU_ROLE_ACCOMPLICE'),
					iconUrl: `${urlPrefix}role-accomplice.png`,
					sectionCode: 'default',
					checked: this.filter.role === 'view_role_accomplice',
					counterValue: this.filter.getCounterValue('view_role_accomplice'),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: 'view_role_originator',
					title: BX.message('TASKS_POPUP_MENU_ROLE_ORIGINATOR'),
					iconUrl: `${urlPrefix}role-originator.png`,
					sectionCode: 'default',
					checked: this.filter.role === 'view_role_originator',
					counterValue: this.filter.getCounterValue('view_role_originator'),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
				{
					id: 'view_role_auditor',
					title: BX.message('TASKS_POPUP_MENU_ROLE_AUDITOR'),
					iconUrl: `${urlPrefix}role-auditor.png`,
					sectionCode: 'default',
					checked: this.filter.role === 'view_role_auditor',
					counterValue: this.filter.getCounterValue('view_role_auditor'),
					counterStyle: {
						backgroundColor: '#FF5752',
					},
				},
			];
			const menuSections = [{id: 'default'}];

			const popupMenu = dialogs.createPopupMenu();
			popupMenu.setData(menuItems, menuSections, (eventName, item) => {
				if (eventName === 'onItemSelected')
				{
					this.onPopupMenuItemSelected(item);
				}
			});
			popupMenu.show();
		}

		onPopupMenuItemSelected(item)
		{
			switch (item.id)
			{
				case 'readAll':
					this.onReadAllClick();
					break;

				case 'deadline':
					BX.postComponentEvent('task.list.onDeadlineSwitch', [{role: this.filter.role}]);
					break;

				case 'toggleCompletedTasks':
					BX.postComponentEvent('task.list.onToggleCompleted', []);
					break;

				case 'view_role_responsible':
				case 'view_role_accomplice':
				case 'view_role_originator':
				case 'view_role_auditor':
					this.onRoleClick(item.id);
					break;

				default:
					this.reload(0, true);
					break;
			}
		}

		onReadAllClick()
		{
			const items = [];
			let newCommentsRead = 0;

			this.taskList.forEach((task) => {
				newCommentsRead += task.newCommentsCount;
				task.newCommentsCount = 0;
				items.push(this.getItemDataFromTask(task));
			});
			this.list.setItems(items, null, true);
			this.filter.pseudoUpdateCounters(-newCommentsRead);

			(new Request())
				.call('comment.readAll', {
					groupId: this.groupId || null,
					userId: this.owner.id || this.currentUser.id,
				})
				.then(response => console.log(response));
		}

		onToggleCompleted()
		{
			this.filter.showCompleted = !this.filter.showCompleted;
			this.reload();
		}

		onDeadlineSwitch(eventData)
		{
			this.order.changeOrder();
			this.updateTitle();

			if (this.order.isDeadline())
			{
				this.filter.showCompleted = false;
			}

			this.setTopButtons();
			this.reload(0, (this.filter.role === eventData.role));
		}

		updateTitle()
		{
			const subHeader = (this.order.isDeadline() ? BX.message('TASKS_LIST_SUB_HEADER_DEADLINES') : '');
			const newTitle = TaskList.titles[this.filter.role].replace('#DEADLINES#', subHeader);

			this.list.setTitle({text: newTitle});
		}

		onRoleClick(role)
		{
			const map = {
				view_role_responsible: 'TASKS_POPUP_MENU_ROLE_RESPONSIBLE',
				view_role_accomplice: 'TASKS_POPUP_MENU_ROLE_ACCOMPLICE',
				view_role_originator: 'TASKS_POPUP_MENU_ROLE_ORIGINATOR',
				view_role_auditor: 'TASKS_POPUP_MENU_ROLE_AUDITOR',
			};
			const {siteId, siteDir, languageId} = env;

			if (this.filter.role === role)
			{
				this.list.back();
				return;
			}

			if (this.filter.role !== 'view_all')
			{
				this.filter.role = role;

				this.updateTitle();
				this.setTopButtons();
				this.reload(0, true);

				return;
			}

			const params = {
				COMPONENT_CODE: 'tasks.list',
				ROLE: role,
				ORDER: this.order.order,
				SHOW_COMPLETED: this.filter.showCompleted,
				SITE_ID: siteId,
				LANGUAGE_ID: languageId,
				SITE_DIR: siteDir,
			};

			if (this.groupId > 0)
			{
				params.GROUP_ID = this.groupId;
			}
			else
			{
				params.USER_ID = this.owner.id;
			}

			PageManager.openComponent('JSStackComponent', {
				canOpenInDefault: false,
				scriptPath: availableComponents['tasks.list'].publicUrl,
				componentCode: 'tasks.list',
				params,
				title: BX.message(map[role]),
				rootWidget: {
					name: 'tasks.list',
					settings: {
						objectName: 'list',
						filter: role,
						useSearch: true,
						useLargeTitleMode: true,
					},
				},
			});
		}

		// search
		getSearchDebounceFunction()
		{
			return TaskList.debounce((text) => {
				console.log('fnUserTypeText');

				const searchResultItems = [];
				this.searchTaskList.forEach((task) => {
					searchResultItems.push(this.getItemDataFromTask(task, false));
				});
				searchResultItems.push({
					type: 'loading',
					title: BX.message('TASKS_LIST_BUTTON_NEXT_PROCESS'),
					sectionCode: 'default',
				});
				this.list.setSearchResultItems(searchResultItems, [{id: 'pinned'}, {id: 'default'}]);

				const filter = {
					SEARCH_INDEX: text,
					MEMBER: this.currentUser.id,
				};

				if (!this.filter.showCompleted)
				{
					filter['::SUBFILTER-STATUS-OR'] = {
						'::LOGIC': 'OR',
						'::SUBFILTER-1': {
							REAL_STATUS: [2, 3, 4, 6],
						},
						'::SUBFILTER-2': {
							WITH_NEW_COMMENTS: 'Y',
						},
					};
				}

				(new Request())
					.call('list', {
						select: TaskList.selectFields,
						filter,
						order: this.order.getForSearch(),
						params: {
							RETURN_ACCESS: 'Y',
							RETURN_USER_INFO: 'Y',
							RETURN_GROUP_INFO: 'Y',
							SEND_PULL: 'Y',
						},
					})
					.then(
						(response) => {
							this.searchTaskList.clear();
							if (response.result.tasks.length)
							{
								this.fillSearchTaskList(response.result.tasks);
								this.fillSearchCache(response.result.tasks);
							}
							this.searchListRender();
						},
						response => console.log('Error task search', response)
					);
			}, 100, this);
		}

		fillSearchTaskList(rows)
		{
			rows.forEach((row) => {
				const rowId = row.id.toString();
				let task;

				if (this.taskList.has(rowId))
				{
					task = this.taskList.get(rowId);
				}
				else
				{
					task = new Task(this.currentUser);
					task.setData(row);
				}
				this.searchTaskList.set(task.id, task);
			});
		}

		fillSearchCache(list)
		{
			console.log('fillSearchCache');
			Application.storage.updateObject(this.searchStorageName, {last: list});
		}

		searchListRender()
		{
			const searchResultItems = [];
			const sectionHandler = SectionHandler.getInstance();

			sectionHandler.add({id: 'default'});
			sectionHandler.add({id: 'empty'});

			console.log('searchListRender', this.searchTaskList.size);

			if (this.searchTaskList.size > 0)
			{
				this.searchTaskList.forEach((task) => {
					const item = this.getItemDataFromTask(task, false);
					searchResultItems.push(item);
				});
			}
			else
			{
				console.log('Empty');
				searchResultItems.push({
					id: 0,
					title: BX.message('TASKS_LIST_SEARCH_EMPTY_RESULT'),
					sectionCode: 'empty',
					type: 'button',
					unselectable: true,
				});
			}

			console.log({title: 'search', searchResultItems, sections: sectionHandler.list});
			this.list.setSearchResultItems(searchResultItems, sectionHandler.list);
		}

		onUserTypeText(event)
		{
			console.log('onUserTypeText');

			BX.onViewLoaded(() => {
				const text = event.text.trim();

				if (text === '' || text === this.searchText)
				{
					return;
				}

				if (text.length < this.searchMinSize)
				{
					this.searchTaskList.clear();

					const localSearchedTasks = this.getLocalSearchedTasks(text);
					if (localSearchedTasks.length)
					{
						this.fillSearchTaskList(localSearchedTasks);
					}
					this.searchListRender();

					return;
				}

				this.searchText = text;
				this.searchDebounceFunction(text);
			});
		}

		getLocalSearchedTasks(text)
		{
			const localSearchedTasks = [];

			this.taskList.forEach((task) => {
				const searchString = `${task.title} ${task.creator.name} ${task.responsible.name}`.toLowerCase();
				if (searchString.search(text.toLowerCase()) === 0)
				{
					localSearchedTasks.push(task);
				}
			});

			return localSearchedTasks;
		}

		onSearchShow()
		{
			if (!this.loadTasksFromSearchCache() && !this.searchTaskList.keys().length)
			{
				this.list.setSearchResultItems(
					[
						{
							id: 0,
							type: 'button',
							unselectable: true,
							title: BX.message('TASKS_LIST_SEARCH_HINT'),
							sectionCode: 'default',
						},
					],
					[
						{id: 'default'},
					]
				);
			}
		}

		loadTasksFromSearchCache()
		{
			console.log('loadTasksFromSearchCache');

			const cache = Application.storage.getObject(this.searchStorageName);
			const tasks = cache.last || [];

			if (tasks.length)
			{
				this.fillSearchTaskList(tasks);
				this.searchListRender();

				return true;
			}

			return false;
		}

		onSearchHide()
		{
			this.searchTaskList.clear();
		}

		onSearchItemSelected(event)
		{
			const taskId = event.id.toString();

			if (this.searchTaskList.has(taskId))
			{
				this.searchTaskList.get(taskId).open();
			}
		}
	}

	return new TaskList(list, userId);
})();