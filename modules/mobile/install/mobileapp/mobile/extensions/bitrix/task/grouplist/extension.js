(() => {
	class Util
	{
		static debounce(fn, timeout, ctx)
		{
			let timer = 0;
			return function() {
				clearTimeout(timer);
				timer = setTimeout(() => fn.apply(ctx, arguments), timeout);
			};
		}
	}

	class Request
	{
		constructor(namespace = 'tasks.task.')
		{
			this.restNamespace = namespace;
		}

		call(methodName, params)
		{
			const method = this.restNamespace + methodName;

			this.currentAnswer = null;
			this.abortCurrentRequest();

			return new Promise((resolve, reject) => {
				console.log({method, params});
				BX.rest.callMethod(method, params || {}, (response) => {
					this.currentAnswer = response;
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

	class Cache
	{
		constructor(storageName)
		{
			this.storageName = storageName;
			this.defaultData = {};
		}

		get()
		{
			return Application.storage.getObject(this.storageName, this.defaultData);
		}

		set(data)
		{
			Application.storage.setObject(this.storageName, data);
		}

		update(key, value)
		{
			const currentCache = this.get();
			currentCache[key] = value;
			this.set(currentCache);
		}

		setDefaultData(defaultData)
		{
			this.defaultData = defaultData;
		}
	}

	/**
	 * @class TaskGroupList
	 */
	class TaskGroupList
	{
		static get maxListSize()
		{
			return 15;
		}

		static get cacheKeys()
		{
			return {
				lastSearchedProjects: 'lastSearchedProjects',
				lastActiveProjects: 'lastActiveProjects',
			};
		}

		static loadLastActiveProjects()
		{
			const cache = new Cache('tasks.group.list');
			const cacheKey = TaskGroupList.cacheKeys.lastActiveProjects;

			(new Request('mobile.tasks.'))
				.call('group.lastActive.get')
				.then(response => cache.update(cacheKey, response.result.slice(0, TaskGroupList.maxListSize)));
		}

		static validateLastSearchedProjects()
		{
			const cache = new Cache('tasks.group.list');
			const cacheKey = TaskGroupList.cacheKeys.lastSearchedProjects;
			const lastSearchedProjects = cache.get()[cacheKey] || [];

			if (lastSearchedProjects.length > 0)
			{
				const ids = lastSearchedProjects.map(project => Number(project.id));

				(new Request('mobile.tasks.'))
					.call('group.lastSearched.validate', {ids})
					.then(response => cache.update(cacheKey, response.result));
			}
		}

		constructor(handlers)
		{
			this.handlers = handlers;

			this.groupList = new Map();
			this.debounceFunction = this.getDebounceFunction();

			this.list = dialogs.createRecipientPicker();

			this.setListeners();

			this.initCache();
			this.loadGroupsFromCache();

			this.list.setItems(this.getList());
			this.list.show();
		}

		setListeners()
		{
			const eventHandlers = {
				onListFill: {
					callback: this.onListFill,
					context: this,
				},
				onItemSelected: {
					callback: this.onItemSelected,
					context: this,
				},
			};

			this.list.setListener((event, data) => {
				if (eventHandlers[event])
				{
					eventHandlers[event].callback.apply(eventHandlers[event].context, [data]);
				}
			});
		}

		onListFill(event)
		{
			const text = event.text.trim();
			if (text.length <= 2)
			{
				this.clearList();
				this.loadGroupsFromCache();
				this.list.setItems(this.getList(), {id: 'default'});
				return;
			}
			this.list.setItems(
				[{type: 'loading', title: BX.message('MOBILE_TASKS_GROUP_LIST_SEARCH_LOADING')}],
				{id: 'default'}
			);
			this.debounceFunction(text);
		}

		getDebounceFunction()
		{
			return Util.debounce((text) => {
				(new Request('mobile.tasks.group.'))
					.call('search', {searchText: text})
					.then(
						(response) => {
							if (response.result.length)
							{
								this.clearList();
								this.fillList(response.result);
								this.list.setItems(this.getList(), {id: 'default'});
							}
							else
							{
								this.list.setItems(
									[{
										id: 0,
										title: BX.message('MOBILE_TASKS_GROUP_LIST_SEARCH_EMPTY'),
										sectionCode: 'default',
										type: 'button',
										unselectable: true,
									}],
									{id: 'default'}
								);
							}
						},
						response => console.log(response)
					);
			}, 500, this)
		}

		onItemSelected(item)
		{
			const project = item.item;

			if (this.handlers.onSelect && project.id)
			{
				const cacheProject = {
					id: project.id,
					name: project.title,
					image: project.imageUrl,
				};

				const cacheKey = TaskGroupList.cacheKeys.lastSearchedProjects;
				let projects = this.cache.get()[cacheKey] || [];
				projects = projects.filter(item => Number(item.id) !== Number(cacheProject.id));

				this.cache.update(cacheKey, [cacheProject].concat(projects.slice(0, TaskGroupList.maxListSize)));
				this.handlers.onSelect(project);

				this.list.close();
			}
		}

		initCache()
		{
			this.cache = new Cache('tasks.group.list');
		}

		loadGroupsFromCache()
		{
			const cache = this.cache.get();
			const lastSearchedProjects = cache[TaskGroupList.cacheKeys.lastSearchedProjects] || [];
			let lastActiveProjects = cache[TaskGroupList.cacheKeys.lastActiveProjects] || [];

			const ids = lastSearchedProjects.map(project => Number(project.id));
			lastActiveProjects = lastActiveProjects.filter(project => !ids.includes(Number(project.id)));

			let projects = lastSearchedProjects;
			if (projects.length < TaskGroupList.maxListSize)
			{
				const count = TaskGroupList.maxListSize - projects.length + 1;
				projects = projects.concat(lastActiveProjects.slice(0, count));
			}

			if (projects.length > 0)
			{
				this.fillList(projects);
				return true;
			}

			return false;
		}

		clearList()
		{
			this.groupList.clear();
		}

		fillList(groups)
		{
			groups.forEach(group => this.groupList.set(group.id, group));
		}

		getList()
		{
			const groups = [];

			this.groupList.forEach((group) => {
				groups.push({
					id: group.id,
					title: group.name,
					imageUrl: group.image,
					type: 'info',
					color: '#f0f0f0',
					useLetterImage: true,
					sectionCode: 'default',
				});
			});

			return groups;
		}
	}

	jnexport([TaskGroupList, 'TaskGroupList']);
})();
