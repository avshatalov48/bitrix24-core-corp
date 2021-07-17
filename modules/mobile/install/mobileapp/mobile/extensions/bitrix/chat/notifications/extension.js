(() =>
{
	var styles = {
		listView: {
			backgroundColor: '#ffffff'
		},
	}

	this.NotificationTypes = {
		confirm: 1,
		unread: 2,
		simple: 3,
		placeholder: 4
	};

	this.NewNotificationsComponent = class NewNotificationsComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.storage = Application.sharedStorage('notify');

			this.perPage = 50;
			this.currentDomain = currentDomain.replace('https', 'http');
			this.initialDataReceived = false;
			this.users = {};
			this.lastId = 0;
			this.lastType = 1; //confirm
			this.isLoadingNewPage = false;

			this.state = {
				total: 0,
				unreadCounter: 0,
				collection: [],
				isRefreshing: false
			};
		}

		initPullHandler()
		{
			BX.PULL.subscribe(
				new ImMobileNotificationsPullHandler({
					application: this,
				})
			);
		}

		componentDidMount()
		{
			this.initPullHandler();

			this.drawPlaceholders().then(() => {
				this.getInitialData();
			});

			BX.addCustomEvent('onAppActiveBefore', () =>
			{
				BX.onViewLoaded(() => {
					this.getInitialData();
				});
			});
		}

		drawPlaceholders()
		{
			const placeholders = this.generatePlaceholders(this.perPage);

			this.setState({
				collection: placeholders,
			});

			return new Promise((resolve, reject) => resolve());
		}

		generatePlaceholders(amount)
		{
			const placeholders = [];
			for (let i = 0; i < amount; i++)
			{
				placeholders.push({
					key: `placeholder${i}`,
					type: 'placeholder',
					commonType: 'placeholder',
				});
			}

			return placeholders;
		}

		itemClickHandler(id, type)
		{
			console.log('itemClickHandler', id, type);

			const { collection } = this.state;

			if (type === 'delete')
			{
				const filteredNotifications = collection.filter((item) => item.messageId !== id);

				//update counter
				BX.postComponentEvent('chatdialog::counter::change', [{
					dialogId: 'notify',
					counter: --this.state.unreadCounter,
				}, true], 'im.recent');

				this.setState({
					collection: filteredNotifications,
				});
			}

			if (type === 'changeReadStatus')
			{
				const itemIndex = collection.findIndex( item => item.messageId === id );
				collection[itemIndex].notifyRead = collection[itemIndex].notifyRead === 'N' ? 'Y' : 'N';

				const queryParams = {
					'ACTION': collection[itemIndex].notifyRead, //y - MarkNotifyRead
					'ID': id,
					'ONLY_CURRENT': 'Y'
				};

				BX.rest.callMethod('im.notify.read', queryParams)
					.then(res => {
						console.log('im.notify.read', res);
					})
					.catch(error => console.log(error));

				this.setState({
					collection: collection,
				});
			}
		}

		render()
		{
			//console.log('current state', this.state);
			const { collection, isRefreshing } = this.state;

			return ListView({
				style: styles.listView,
				data: [{
					items: collection,
				}],
				isRefreshing: isRefreshing,
				renderItem: (data) => {
					return new NotificationItem({
						notification: data,
						itemClickHandler: this.itemClickHandler.bind(this)
					});
				},
				onRefresh: () => {
					this.setState({ isRefreshing: true });

					this.getInitialStateFromServer()
						.then(state => {
							console.log('onRefresh: setState', state);
							this.setState(Object.assign(state, {isRefreshing: false}));
						});
				},
				onLoadMore: (this.initialDataReceived && this.getRemainingPages() > 0) && (() => {
					if (!this.isLoadingNewPage)
					{
						console.log('Starting new request');
						this.isLoadingNewPage = true;
						this.loadNextPage();
					}

				}),
				onViewableItemsChanged: (params) => {
					//console.log(params);
				}
			});
		}

		loadNextPage()
		{
			console.log(`Loading more notifications!`);

			const queryParams = {
				'LIMIT': this.perPage,
				'LAST_ID': this.lastId,
				'LAST_TYPE': this.lastType,
				'CONVERT_TEXT': 'Y'
			};

			BX.rest.callMethod('im.notify.get', queryParams)
				.then(newItems => {
					console.log('im.notify.get: new page results', newItems);
					return this.processNextPage(newItems.data());
				})
				.then(() => {
					this.isLoadingNewPage = false;
				})
				.catch(error => console.log(error));
		}

		getInitialData()
		{
			this.getInitialStateFromCache().then(state => {
				if (!this.initialDataReceived)
				{
					console.log('setState from CACHE', state);
					this.setState(state);
				}
			});

			this.getInitialStateFromServer().then(state => {
				console.log('setState from SERVER', state);
				this.setState(state);
				this.initialDataReceived = true;
			});
		}

		getInitialStateFromCache()
		{
			return new Promise((resolve, reject) => {
				const rawCachedNotifications = this.storage.get('collection');

				const cachedNotifications = rawCachedNotifications ? JSON.parse(rawCachedNotifications) : [];

				resolve({
					collection: cachedNotifications,
				});
			});
		}

		getInitialStateFromServer()
		{
			return new Promise((resolve, reject) => {
				const queryParams = {
					'LIMIT': this.perPage,
					'CONVERT_TEXT': 'Y',
				};

				BX.rest.callMethod('im.notify.get', queryParams)
					.then(result => {
						console.log('im.notify.get', result);
						const initialState = this.processInitialState(result.data());
						this.storage.set('collection', JSON.stringify(initialState.collection));

						resolve(initialState);
					})
					.catch(error => console.log(error));
			});
		}

		processInitialState(data)
		{
			if (!data)
			{
				return {};
			}

			this.lastId = this.getLastItemId(data.notifications);
			this.lastType = this.getLastItemType(data.notifications);

			data.users.forEach((user) => {
				this.users[user.id] = user;
			});

			let notificationsFromServer = []
			data.notifications.forEach(item => {
				notificationsFromServer.push(this.prepareNotification(item));
			});

			return {
				collection: notificationsFromServer,
				total: +data.total_count,
				unreadCounter: +data.total_unread_count
			}
		}

		processNextPage(newItems)
		{
			const { collection } = this.state;

			if (!newItems || newItems.notifications.length === 0)
			{
				this.setState({
					total: collection.length,
				})

				return;
			}

			newItems.users.forEach((user) => {
				this.users[user.id] = user;
			});

			this.lastId = this.getLastItemId(newItems.notifications);
			this.lastType = this.getLastItemType(newItems.notifications);

			//prepare notifications
			const preparedItems = newItems.notifications.map(notification => this.prepareNotification(notification));

			this.setState({
				collection: [...collection, ...preparedItems],
			});

			console.log(`Loading notifications is done!`);

			return new Promise((resolve, reject) => resolve());
		}

		prepareNotification(item)
		{
			return {
				key: 'item'+ item.id,
				type: item.notify_type === 1 ? 'confirm' : 'notification',
				commonType: item.notify_type === 1 ? 'confirm' : 'notification', //todo temp
				author: item.author_id === 0 ? '' : this.users[item.author_id].name, //todo
				avatarUrl: item.author_id === 0 ? '' : this.users[item.author_id].avatar.replace('https','http'), //todo delete hack
				text: item.text_converted,
				time: item.date,
				messageId: item.id,
				params: item.params,
				buttons: item.notify_buttons,
				chatId: item.chat_id,
				notifyRead: item.notify_read,
				notifyTag: item.notify_tag,
			};
		}

		getLastItemId(collection)
		{
			return collection[collection.length - 1].id;
		}
		getLastItemType(collection)
		{
			return this.getItemType(collection[collection.length - 1]);
		}
		getItemType(item)
		{
			if (item.notify_type === 1)
			{
				return NotificationTypes.confirm;
			}
			else if (item.notify_read === 'N')
			{
				return NotificationTypes.unread;
			}
			else
			{
				return NotificationTypes.simple;
			}
		}
		getRemainingPages()
		{
			return Math.ceil((this.state.total - this.state.collection.length) / this.perPage);
		}
	}
})();