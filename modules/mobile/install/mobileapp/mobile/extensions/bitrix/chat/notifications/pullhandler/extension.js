(() =>
{
	this.ImMobileNotificationsPullHandler = class ImMobileNotificationsPullHandler
	{
		constructor(params = {})
		{
			if (typeof params.application === 'object' && params.application)
			{
				this.application = params.application;
			}
		}

		getModuleId()
		{
			return 'im';
		}

		getSubscriptionType()
		{
			return BX.PullClient.SubscriptionType.Server;
		}

		handleNotifyAdd(params, extra)
		{
			console.log('handleNotifyAdd', params, extra);

			if (extra !== undefined && extra.server_time_ago > 30 || params.onlyFlash === true)
			{
				return false;
			}

			const type = Utils.getListItemType(params);

			const newNotification = {
				key: 'item'+ params.id,
				type: type,
				commonType: params.type === 1 ? Const.NotificationTypes.confirm : Const.NotificationTypes.simple,
				author: params.userName ? Utils.htmlspecialcharsback(params.userName) : '',
				avatarUrl: Utils.getAvatarUrl(params),
				avatarColor: params.userColor,
				text: Utils.htmlspecialcharsback(params.text),
				time: (new Date(params.date)).getTime(),
				messageId: params.id,
				params: params.params,
				notifyRead: 'N',
				notifyTag: params.originalTag || params.original_tag,
			};
			if (params.buttons)
			{
				newNotification.buttons = JSON.stringify(params.buttons);
			}

			const { collection } = this.application.state;

			for (let index = 0; collection.length > index; index++)
			{
				if (collection[index].messageId === newNotification.messageId)
				{
					return;
				}
			}

			if (newNotification.commonType === Const.NotificationTypes.confirm)
			{
				collection.unshift(newNotification)
			}
			else if (newNotification.commonType === Const.NotificationTypes.simple)
			{
				let firstNotificationIndex = null;
				for (let index = 0; collection.length > index; index++)
				{
					if (collection[index].commonType !== Const.NotificationTypes.confirm)
					{
						firstNotificationIndex = index;
						break;
					}
				}

				//if we didn't find any simple notification and its index, then add new one to the end.
				if (firstNotificationIndex === null)
				{
					collection.push(newNotification);
				}
				else //otherwise, put it right before first simple notification.
				{
					collection.splice(firstNotificationIndex, 0, newNotification);
				}
			}

			this.application.setState({
				collection: collection,
				unreadCounter: params.counter,
			});
			this.application.storage.set('collection', JSON.stringify(collection));

			this.updateCounter(params.counter);
		}

		handleNotifyConfirm(params, extra)
		{
			console.log('handleConfirmNotify params', params);

			if (extra.server_time_ago > 30)
			{
				return false;
			}

			let needToUpdateState = false;
			const { collection } = this.application.state;
			const filteredCollection = collection.filter((notification) => {
				if (notification.messageId === params.id)
				{
					needToUpdateState = true;
				}
				return notification.messageId !== params.id;
			});

			if (needToUpdateState)
			{
				this.application.setState({
					collection: filteredCollection,
					unreadCounter: params.counter,
				});
				this.application.storage.set('collection', JSON.stringify(collection));
			}

			this.updateCounter(params.counter);
		}

		handleNotifyRead(params, extra)
		{
			console.log('handleReadNotifyList', params);

			if (extra.server_time_ago > 30)
			{
				return false;
			}

			const { collection } = this.application.state;
			let needToUpdateState = false;
			params.list.forEach((listItem) => {
				const itemIndex = collection.findIndex( item => item.messageId === listItem );
				if (itemIndex >= 0 && collection[itemIndex].notifyRead !== 'Y')
				{
					needToUpdateState = true;
					collection[itemIndex].notifyRead = 'Y';
				}
			});

			if (!needToUpdateState)
			{
				return false;
			}

			this.application.setState({
				collection: collection,
				unreadCounter: params.counter,
			});
			this.application.storage.set('collection', JSON.stringify(collection));
			this.updateCounter(params.counter);
		}

		handleNotifyUnread(params, extra)
		{
			console.log('handleUnreadNotifyList', params, extra);

			if (extra.server_time_ago > 30)
			{
				return false;
			}

			const { collection } = this.application.state;
			let needToUpdateState = false;

			params.list.forEach((listItem) => {
				const itemIndex = collection.findIndex( item => item.messageId === listItem );
				if (itemIndex >= 0 && collection[itemIndex].notifyRead !== 'N')
				{
					needToUpdateState = true;
					collection[itemIndex].notifyRead = 'N';
				}
			});
			if (!needToUpdateState)
			{
				return false;
			}

			this.application.setState({
				collection: collection,
				unreadCounter: params.counter,
			});
			this.application.storage.set('collection', JSON.stringify(collection));
			this.updateCounter(params.counter);
		}

		handleNotifyDelete(params, extra)
		{
			console.log('handleDeleteNotifies', params, extra);

			if (extra.server_time_ago > 30)
			{
				return false;
			}

			const idsToDelete = Object.keys(params.id).map(item => +item);
			const { collection } = this.application.state;

			let needToUpdateState = false;
			const filteredNotifications = collection.filter(item => {
				if (idsToDelete.includes(item.messageId))
				{
					needToUpdateState = true;
					return false;
				}

				return true;
			});

			if (needToUpdateState)
			{
				this.application.setState({
					collection: filteredNotifications,
					unreadCounter: params.counter,
				});
				this.application.storage.set('collection', JSON.stringify(filteredNotifications));
				this.updateCounter(params.counter);
			}
		}

		updateCounter(counter)
		{
			BX.postComponentEvent("chatdialog::counter::change", [{
				dialogId: 'notify',
				counter: counter,
			}, true], 'im.recent');
		}
	}
})();