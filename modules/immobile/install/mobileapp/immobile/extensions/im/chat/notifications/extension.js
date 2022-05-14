(() =>
{
	var styles = {
		listView: {
			backgroundColor: '#ffffff',
			flex: 1
		},
	}

	this.NewNotificationsComponent = class NewNotificationsComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.userId = parseInt(BX.componentParameters.get('USER_ID', 0));
			this.largeTitle = BX.componentParameters.get('LARGE_TITLE', true);
			this.storage = Application.sharedStorage(`notify_${this.userId}`);

			this.perPage = 50;
			this.initialDataReceived = false;
			this.users = {};
			this.notificationsToRead = new Set();

			// set of notifications id, which was read while loading notifications from the server.
			this.tempNotificationsToRead = new Set();
			this.lastId = 0;
			this.lastType = Const.NotificationTypes.confirm;
			this.isLoadingNewPage = false;
			this.notificationsToDelete = [];
			this.firstUnreadNotificationOnInit = null;

			this.state = {
				total: 0,
				unreadCounter: 0,
				collection: [],
				isRefreshing: false
			};

			this.readNotificationsQueue = new Set();
		}

		initPullHandler()
		{
			BX.PULL.subscribe(
				new ImMobileNotificationsPullHandler({
					application: this,
				})
			);

			BX.addCustomEvent("notification::push::get", (params) => {
				BX.PULL.emit({
					type: BX.PullClient.SubscriptionType.Server,
					moduleId: 'im',
					data: {command: 'notifyAdd', params: params}
				});
			});

			let storedEvents = BX.componentParameters.get('STORED_EVENTS', []);

			if (storedEvents.length > 0)
			{
				//sort events and get first 50
				storedEvents = storedEvents.sort(Utils.sortByType);
				storedEvents = storedEvents.slice(0, 50);

				setTimeout(() => {
					storedEvents = storedEvents.filter(event => {
						BX.onCustomEvent('notification::push::get', [event]);
						return false;
					});
				}, 50);
			}
		}

		componentDidMount()
		{
			this.initPullHandler();

			const rawCachedNotifications = this.storage.get('collection');
			const hasCachedNotifications = !!rawCachedNotifications;

			layoutWidget.setTitle({text: BX.message('IM_NOTIFY_TITLE'), useProgress:true, largeMode: this.largeTitle});
			if (hasCachedNotifications)
			{
				this.getInitialData();
			}
			else
			{
				this.drawPlaceholders().then(() => {
					this.getInitialData();
				});
			}

			BX.addCustomEvent('onAppActiveBefore', () =>
			{
				BX.onViewLoaded(() => {
					this.getInitialData();
				});
			});

			BX.addCustomEvent("onViewShown", () => {
				if (this.initialDataReceived)
				{
					layoutWidget.setTitle({text: BX.message('IM_NOTIFY_TITLE'), useProgress:true, largeMode: this.largeTitle});
					this.getInitialStateFromServer()
						.then(state => {
							console.log('onViewShown: setState', state);
							this.setState(state);
							layoutWidget.setTitle({text: BX.message('IM_NOTIFY_TITLE'), useProgress:false, largeMode: this.largeTitle});
						})
						.catch((error) => {
							console.log(error);
							layoutWidget.setTitle({text: BX.message('IM_NOTIFY_TITLE'), useProgress:false, largeMode: this.largeTitle});
						});
				}
			});

			this.readVisibleNotificationsDelayed = ChatUtils.debounce(this.readVisibleNotifications, 50, this);
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
					commonType: Const.NotificationTypes.placeholder,
				});
			}

			return placeholders;
		}

		readAll()
		{
			const { collection, unreadCounter } = this.state;
			if (unreadCounter === 0)
			{
				return;
			}

			let needToUpdate = false;
			let newCounter = unreadCounter;
			const collectionReaded = collection.map(item => {
				if (item.notifyRead === 'N' && item.commonType !== Const.NotificationTypes.confirm)
				{
					item.notifyRead = 'Y';
					needToUpdate = true;
					newCounter = newCounter > 0 ? newCounter - 1 : newCounter;
				}
				return item;
			});

			if (!needToUpdate)
			{
				return;
			}

			//update counter
			BX.postComponentEvent('chatdialog::counter::change', [{
				dialogId: 'notify',
				counter: newCounter,
			}, true], 'im.recent');

			this.setState({
				collection: collectionReaded,
				unreadCounter: newCounter,
			});

			BX.postComponentEvent('chatbackground::task::action', [
				'readNotification',
				'readNotification|0',
				{
					id: 0,
					action: 'Y'
				},
			], 'background');
		}

		itemClickHandler(id, type)
		{
			//console.log('itemClickHandler', id, type);

			const { collection } = this.state;

			if (type === 'delete')
			{
				this.notificationsToDelete.push(id);
				const originalCounterBeforeUpdate = this.state.unreadCounter;
				const deleteItemIndex = collection.findIndex( item => item.messageId === id );
				if (collection[deleteItemIndex] && collection[deleteItemIndex].notifyRead === 'N')
				{
					//update counter if we delete unread notification
					BX.postComponentEvent('chatdialog::counter::change', [{
						dialogId: 'notify',
						counter: --this.state.unreadCounter,
					}, true], 'im.recent');
				}

				const filteredNotifications = collection.filter((item) => item.messageId !== id);
				this.setState({
					collection: filteredNotifications,
				});
				this.storage.set('collection', JSON.stringify(filteredNotifications));

				ChatTimer.stop('notification', 'delete', true);
				ChatTimer.start('notification', 'delete', 1000, () => {
					const idsToDelete = this.notificationsToDelete;
					this.notificationsToDelete = [];

					BX.rest.callMethod('im.notify.delete', { id: idsToDelete })
						.then(res => {
							console.log('im.notify.delete', res);
						})
						.catch(error => {
							console.log(error);
							// restore counter and collection
							BX.postComponentEvent('chatdialog::counter::change', [{
								dialogId: 'notify',
								counter: originalCounterBeforeUpdate,
							}, true], 'im.recent');
							this.setState({
								collection: collection,
							});
							this.storage.set('collection', JSON.stringify(collection));
						});
				});
			}
		}

		onItemHeightChange(fromHeight, toHeight) {
			if (!this.listView || fromHeight < toHeight) return;

			this.listView.scrollBy({ x: 0, y: toHeight - fromHeight, animated: true, duration: 200 })
		}

		render()
		{
			const { collection } = this.state;
			const isStub = collection.length === 0 && this.initialDataReceived;

			return View(
				{},
				isStub ? this.renderStub() : this.renderList(),
			)
		}

		renderStub()
		{
			return View(
				{},
				Image({
					style: {
						width: 224,
						height: 224,
						alignSelf: 'center'
					},
					uri: `${currentDomain}/bitrix/templates/mobile_app/images/notification-block/notif-empty-v2.png`
				}),
				Text({
					style: {
						color: '#99afbc',
						fontSize: 19,
						textAlign: 'center'
					},
					text: BX.message('IM_NOTIFY_EMPTY_LIST')
				})
			)
		}

		renderList()
		{
			//console.log('current state', this.state);
			const { collection, isRefreshing } = this.state;

			return ListView({
				ref: ref => this.listView = ref,
				style: styles.listView,
				data: [{
					items: collection,
				}],
				isRefreshing: isRefreshing,
				renderItem: (data, section, index) => {
					return new NotificationItem({
						notification: data,
						itemClickHandler: this.itemClickHandler.bind(this),
						onHeightWillChange: (fromHeight, toHeight) => this.onItemHeightChange(fromHeight, toHeight),
						onRemove: (notification) => {
							this.listView.deleteRow(section, index, "middle", () => {
								this.itemClickHandler(notification.messageId, 'delete');
							})
						}
					});
				},
				onRefresh: () => {
					this.setState({ isRefreshing: true });

					this.getInitialStateFromServer()
						.then(state => {
							console.log('onRefresh: setState', state);
							this.setState(Object.assign(state, {isRefreshing: false}));
						})
						.catch((error) => {
							console.error(error);
							Utils.showError(
								BX.message['MOBILE_EXT_CONFIRM_ITEM_BUTTONS_ERROR_TITLE'],
								BX.message['MOBILE_EXT_CONFIRM_ITEM_BUTTONS_ERROR_TEXT'],
								'#affb0000'
							);
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
					const messagesToRead = params[0].items

					const { collection } = this.state;

					messagesToRead.forEach(itemIndex => {
						const notification = collection[itemIndex];
						if (
							notification !== undefined
							&& notification.commonType !== Const.NotificationTypes.placeholder
							&& notification.commonType !== Const.NotificationTypes.confirm
							&& notification.notifyRead !== 'Y'
						)
						{
							this.readNotificationsQueue.add(notification.messageId);
						}
					});

					this.readVisibleNotificationsDelayed();
				},
				viewabilityConfig: {
					itemVisiblePercentThreshold: 95,
					waitForInteraction: false
				},
			});
		}

		loadNextPage()
		{
			console.log(`Loading more notifications!`);

			const queryParams = {
				'LIMIT': this.perPage,
				'LAST_ID': this.lastId,
				'LAST_TYPE': this.lastType,
				'BB_CODE': 'Y'
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
				this.tempNotificationsToRead.clear();
				this.firstUnreadNotificationOnInit = this.getFirstUnreadNotificationOnInit();
				layoutWidget.setTitle({text: BX.message('IM_NOTIFY_TITLE'), useProgress:false, largeMode: this.largeTitle});
			}).catch((error) => {
				console.error(error);
				layoutWidget.setTitle({text: BX.message('IM_NOTIFY_TITLE'), useProgress:false, largeMode: this.largeTitle});
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
					'BB_CODE': 'Y',
				};

				BX.rest.callMethod('im.notify.get', queryParams)
					.then(result => {
						console.log('im.notify.get', result);
						const initialState = this.processInitialState(result.data());
						this.storage.set('collection', JSON.stringify(initialState.collection));

						resolve(initialState);
					})
					.catch(error => {
						console.log(error);
						reject(error);
					});
			});
		}

		processInitialState(data)
		{
			if (!data || data.length === 0)
			{
				return {
					collection: [],
					total: 0,
					unreadCounter: 0
				};
			}

			this.lastId = this.getLastItemId(data.notifications);
			this.lastType = this.getLastItemType(data.notifications);

			data.users.forEach((user) => {
				this.users[user.id] = user;
			});

			const notificationsFromServer = [];
			const { collection } = this.state;
			data.notifications.forEach(item => {
				const preparedItemItem = this.prepareNotification(item);

				// merge local read status with server read status,
				// only if "reading" was while loading notifications from the server.
				const itemIndex = collection.findIndex(item => item.messageId === preparedItemItem.messageId);
				if (
					itemIndex >= 0
					&& collection[itemIndex].notifyRead !== preparedItemItem.notifyRead
					&& this.tempNotificationsToRead.has(preparedItemItem.messageId)
				)
				{
					preparedItemItem.notifyRead = collection[itemIndex].notifyRead;
				}

				notificationsFromServer.push(preparedItemItem);
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
			const type = Utils.getListItemType(item);

			return {
				key: 'item'+ item.id,
				type: type,
				commonType: this.getItemType(item),
				author: item.author_id === 0 ? '' : this.users[item.author_id].name,
				avatarUrl: item.author_id === 0 ? '' : this.users[item.author_id].avatar,
				avatarColor: item.author_id === 0 ? '' : this.users[item.author_id].color,
				text: Utils.htmlspecialcharsback(item.text),
				time: (new Date(item.date)).getTime(),
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
				return Const.NotificationTypes.confirm;
			}
			else
			{
				return Const.NotificationTypes.simple;
			}
		}
		getRemainingPages()
		{
			return Math.ceil((this.state.total - this.state.collection.length) / this.perPage);
		}
		readVisibleNotifications()
		{
			if (this.readNotificationsQueue.size === 0)
			{
				return;
			}

			this.readNotificationsQueue.forEach(notificationId => {
				this.readNotification(parseInt(notificationId, 10));
			});
			this.readNotificationsQueue.clear();
		}
		readNotification(id)
		{
			const { collection, unreadCounter } = this.state;

			const itemIndex = collection.findIndex(item => item.messageId === id);
			if (!collection[itemIndex] || collection[itemIndex].notifyRead === 'Y')
			{
				return;
			}

			this.notificationsToRead.add(id);
			if (!this.initialDataReceived)
			{
				this.tempNotificationsToRead.add(id);
			}
			collection[itemIndex].notifyRead = 'Y';
			const counterValueAfterRead = unreadCounter - 1;

			this.setState({
				collection: collection,
				unreadCounter: counterValueAfterRead,
			});
			this.storage.set('collection', JSON.stringify(collection));

			BX.postComponentEvent('chatdialog::counter::change', [{
				dialogId: 'notify',
				counter: counterValueAfterRead,
			}, true], 'im.recent');

			ChatTimer.stop('notification', 'read', true);
			ChatTimer.start('notification', 'read', 1000, () => {
				const idsToRead = [...this.notificationsToRead];
				this.notificationsToRead.clear();

				// we can read all notifications from some ID, only if we have not received new notifications
				// (otherwise we will read notifications at the top that we are not actually seeing)
				let canReadFromId = false;
				if (this.firstUnreadNotificationOnInit !== null)
				{
					canReadFromId = Math.max(...idsToRead) <= this.firstUnreadNotificationOnInit;
				}

				if (canReadFromId)
				{
					const readFromId = Math.min(...idsToRead);

					BX.postComponentEvent('chatbackground::task::action', [
						'readNotification',
						'readNotification|'+readFromId,
						{
							action: 'Y',
							id: readFromId
						},
					], 'background');
				}
				else
				{
					BX.postComponentEvent('chatbackground::task::action', [
						'readNotificationList',
						'readNotificationList|'+idsToRead.join(),
						{
							action: 'Y',
							ids: idsToRead
						},
					], 'background');
				}
			});
		}

		getFirstUnreadNotificationOnInit()
		{
			const { collection, unreadCounter } = this.state;

			if (unreadCounter <= 0)
			{
				return null;
			}

			let unreadId = null;
			const maxNotificationIndex = collection.length - 1;

			for (let i = 0; i <= maxNotificationIndex; i++)
			{
				if (collection[i].notifyRead === 'N' && collection[i].commonType === Const.NotificationTypes.simple)
				{
					unreadId = collection[i].messageId;
					break;
				}
			}

			return unreadId;
		}
	}
})();