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
			console.log('handleNotify', params, extra);

			if (extra.server_time_ago > 30)
			{
				return false;
			}

			const newNotification = {
				key: 'item'+ params.id,
				commonType: params.type === 1 ? 'confirm' : 'notification',
				author: params.userName ? Utils.htmlspecialcharsback(params.userName) : '', //todo
				avatarUrl: params.userAvatar ? (currentDomain + params.userAvatar).replace('https','http') : '', //todo
				messageId: params.id,
				text: Utils.htmlspecialcharsback(params.text),
				time: params.date,
				params: params.params,
				notifyRead: 'N',
				notifyTag: params.notify_tag,
			};
			if (params.buttons)
			{
				newNotification.buttons = JSON.stringify(params.buttons);
			}

			const { collection } = this.application.state;

			if (newNotification.commonType === 'confirm')
			{
				collection.unshift(newNotification)
			}
			else if (newNotification.commonType === 'notification')
			{
				let firstNotificationIndex = null;
				for (let index = 0; collection.length > index; index++)
				{
					if (collection[index].commonType !== 'confirm')
					{
						firstNotificationIndex = index;
						break;
					}
				}

				//if we didn't find any simple notification and its index, then add new one to the end.
				if (!firstNotificationIndex)
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
			});

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
					collection: filteredCollection
				});
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
			params.list.forEach((listItem) => {
				const itemIndex = collection.findIndex( item => item.messageId === listItem );
				collection[itemIndex].notifyRead = 'Y';
			});

			this.application.setState({
				collection: collection
			});

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
			params.list.forEach((listItem) => {
				const itemIndex = collection.findIndex( item => item.messageId === listItem );
				collection[itemIndex].notifyRead = 'N';
			});

			this.application.setState({
				collection: collection
			});

			this.updateCounter(params.counter);
		}

		handleNotifyDelete(params, extra)
		{
			console.log('handleDeleteNotifies', params, extra);

			if (extra.server_time_ago > 30)
			{
				return false;
			}

			const idToDelete = +Object.keys(params.id)[0];
			const { collection } = this.application.state;

			//notifications
			let needToUpdateState = false;
			const filteredNotifications = collection.filter((item) => {
				if (item.messageId === idToDelete)
				{
					needToUpdateState = true;
				}
				return item.messageId !== idToDelete;
			});

			if (needToUpdateState)
			{
				this.application.setState({
					collection: filteredNotifications,
					unreadCounter: params.counter,
				});
			}

			this.updateCounter(params.counter);
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