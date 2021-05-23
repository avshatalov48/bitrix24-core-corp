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
	 * @class TaskUserList
	 */
	class TaskUserList
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

		constructor(defaultUsers, handlers)
		{
			this.handlers = handlers;

			this.userList = new Map();
			this.maxListSize = 20;
			this.debounceFunction = this.getDebounceFunction();
			this.cache = new Cache('tasks.user.list_2');

			this.list = dialogs.createRecipientPicker();

			this.setListeners();

			if (!this.loadUsersFromCache())
			{
				this.loadUsersFromComponent(defaultUsers);
			}

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
				this.loadUsersFromCache();
				this.list.setItems(this.getList(), {id: 'default'});
				return;
			}
			this.list.setItems([{type: 'loading'}]);
			this.debounceFunction(text);
		}

		getDebounceFunction()
		{
			return Util.debounce((text) => {
				(new Request('user.')).call('search', {
					IMAGE_RESIZE: 'small',
					SORT: 'LAST_NAME',
					ORDER: 'ASC',
					FILTER: {
						ACTIVE: 'Y',
						NAME_SEARCH: text,
					},
				}).then(
					(response) => {
						if (response.result.length)
						{
							this.clearList();
							this.fillList(
								response.result.map(item => ({
									id: item.ID,
									name: TaskUserList.getFormattedName(item),
									icon: encodeURI(item.PERSONAL_PHOTO),
								}))
							);
							this.list.setItems(this.getList());
						}
						else
						{
							this.list.setItems([{
								id: 0,
								title: BX.message('MOBILE_TASKS_USER_LIST_SEARCH_EMPTY'),
								sectionCode: 'default',
								type: 'button',
								unselectable: true,
							}]);
						}
					},
					response => console.log(response)
				);
			}, 500, this);
		}

		onItemSelected(item)
		{
			const user = item.item;

			if (this.handlers.onSelect && user.id)
			{
				const cacheUser = {
					id: user.id,
					name: user.title,
					icon: user.imageUrl,
				};

				let users = this.cache.get().users || [];
				users = users.filter(item => Number(item.id) !== Number(cacheUser.id));

				this.cache.set({users: [cacheUser].concat(users.slice(0, this.maxListSize))});
				this.handlers.onSelect(user);

				this.list.close();
			}
		}

		loadUsersFromCache()
		{
			const users = this.cache.get().users || [];
			if (users.length > 0)
			{
				this.fillList(users);
				return true;
			}

			return false;
		}

		loadUsersFromComponent(defaultUsers)
		{
			Object.values(defaultUsers).forEach((user) => {
				this.userList.set(String(user.id), {
					id: user.id,
					name: user.name,
					icon: user.icon,
				});
			});

			if (this.userList.size > 0)
			{
				this.cache.set({users: [...this.userList.values()]});
			}
		}

		clearList()
		{
			this.userList.clear();
		}

		fillList(users)
		{
			users.forEach(user => this.userList.set(user.id, user));
		}

		getList()
		{
			const users = [];

			this.userList.forEach((user) => {
				users.push({
					id: user.id,
					title: user.name,
					imageUrl: user.icon,
					type: 'info',
					color: '#f0f0f0',
					useLetterImage: true,
					sectionCode: 'default',
				});
			})

			return users;
		}
	}

	jnexport([TaskUserList, 'TaskUserList']);
})();
