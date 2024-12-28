/**
 * @module im/messenger/controller/users-read-message-list
 */
jn.define('im/messenger/controller/users-read-message-list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Logger } = require('im/messenger/lib/logger');
	const { Moment } = require('utils/date');
	const { RestMethod } = require('im/messenger/const/rest');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { UsersReadMessageListView } = require('im/messenger/controller/users-read-message-list/view');
	const { atomIcons } = require('im/messenger/assets/common');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { Theme } = require('im/lib/theme');
	const { runAction } = require('im/messenger/lib/rest');
	const { EventType } = require('im/messenger/const');
	const {
		ChatTitle,
		ChatAvatar,
	} = require('im/messenger/lib/element');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @desc This class provider calling backdrop widget with users of list who read message
	 */
	class UsersReadMessageList
	{
		/**
		 * @desc Open widget without instance
		 * @static
		 * @param {number} messageId
		 * @param {MapCache} [cache]
		 * @param {Function} [setCache] - for update new result after rest call
		 */
		static open(messageId, cache, setCache)
		{
			const instanceManger = new UsersReadMessageList(messageId, cache, setCache);
			instanceManger.open();
		}

		/**
		 * @constructor
		 * @param {number} messageId=0
		 * @param {MapCache} [cache]
		 * @param {Function} [setCache]
		 */
		constructor(messageId, cache, setCache)
		{
			this.messageId = messageId;
			this.items = [];
			this.cache = cache;
			this.setCache = setCache;
			this.store = serviceLocator.get('core').getStore();
			this.bindMethods();

			const messageData = this.store.getters['messagesModel/getById'](messageId);

			const { dialogId } = this.store.getters['dialoguesModel/getByChatId'](messageData.chatId);
			this.dialogId = dialogId;
		}

		open()
		{
			Logger.log('UsersReadMessageList.open');
			this.createWidget();
		}

		createWidget()
		{
			PageManager.openWidget(
				'layout',
				{
					backgroundColor: Theme.colors.bgContentPrimary,
					title: Loc.getMessage('IMMOBILE_MESSENGER_WIDGET_USERS_READ_MESSAGE_LIST_TITLE'),
					backdrop: {
						mediumPositionPercent: 60,
						horizontalSwipeAllowed: false,
					},
				},
			).then(
				(widget) => {
					this.widget = widget;
					this.onWidgetReady();
				},
			).catch((error) => {
				Logger.error('PageManager.openWidget.UsersReadMessageList.error', error);
			});
		}

		onWidgetReady()
		{
			this.checkCache();
			this.subscribeExternalEvents();
			this.createView();
			this.widget.showComponent(this.view);
			this.widget.on(EventType.view.close, this.closeHandler);
		}

		subscribeExternalEvents()
		{
			BX.addCustomEvent(EventType.messenger.openDialog, this.openDialogHandler);
			BX.addCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		unsubscribeExternalEvents()
		{
			BX.removeCustomEvent(EventType.messenger.openDialog, this.openDialogHandler);
			BX.removeCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		checkCache()
		{
			if (this.cache && this.cache.has(this.messageId) && this.cache.isFresh(this.messageId))
			{
				this.prepareDataFromCache();
			}
			else
			{
				this.prepareDataFromRest();
			}
		}

		/**
		 * @desc Prepare users data by result rest call method
		 * @return void
		 * @async
		 */
		async prepareDataFromRest()
		{
			const restResult = await this.getUserReadMessageList();
			await this.saveUsers(restResult.users);
			if (this.setCache)
			{
				this.setCache(restResult);
			}

			this.items = this.getUserDataItem(restResult);
			this.updateStateView();
		}

		/**
		 * @desc Prepare users data by cache
		 * @return void
		 */
		prepareDataFromCache()
		{
			const cacheRes = this.cache.get(this.messageId);
			this.items = this.getUserDataItem(cacheRes);
		}

		createView()
		{
			this.view = new UsersReadMessageListView({
				itemList: this.items,
				callbacks: { onItemClick: this.itemClickHandler },
			});
		}

		/**
		 * @desc Get users data by message id from rest method imV2ChatMessageTailViewers
		 * @return {Promise}
		 */
		getUserReadMessageList()
		{
			const tailViewersData = {
				id: this.messageId,
			};

			return runAction(RestMethod.imV2ChatMessageTailViewers, { data: tailViewersData })
				.catch((errors) => {
					Logger.error('UsersReadMessageList.getUserReadMessageList.result.error', errors);
				})
			;
		}

		async saveUsers(users)
		{
			return this.store.dispatch('usersModel/set', users);
		}

		/**
		 * @desc Get users item data from rest call method result
		 * @param {Object} restData
		 * @return {Array<Object|null>}
		 */
		getUserDataItem(restData)
		{
			if (!restData.views)
			{
				return [];
			}
			const currentUserId = MessengerParams.getUserId();
			const filteredViews = restData.views.filter((view) => view.userId !== currentUserId);

			const sortedViews = filteredViews.sort((viewA, viewB) => {
				const dateA = new Date(viewA.dateView).getTime();
				const dateB = new Date(viewB.dateView).getTime();

				return dateA - dateB;
			});

			return sortedViews.map((view) => {
				const userData = restData.users.find((user) => user.id === view.userId);
				const dataState = new Moment(view.dateView);
				const dataFriendly = new FriendlyDate({
					moment: dataState,
					showTime: true,
				});
				const dateText = dataFriendly.makeText(dataState);
				const avatarUri = Type.isStringFilled(userData.avatar) ? encodeURI(userData.avatar) : null;

				return {
					type: 'item',
					key: view.userId.toString(),
					userName: userData.name,
					color: userData.color,
					title: userData.name,
					subtitle: dateText,
					iconSubtitle: atomIcons.doubleCheck(),
					avatarUri,
					avatarColor: userData.color,
					avatar: ChatAvatar.createFromDialogId(userData.id).getListItemAvatarProps(),

					style: {
						parentView: {
							backgroundColor: Theme.colors.bgContentPrimary,
						},
						itemContainer: {
							flexDirection: 'row',
							alignItems: 'center',
							marginLeft: 20,
						},
						avatarContainer: {
							marginTop: 6,
							marginBottom: 6,
							paddingHorizontal: 2,
						},
						itemInfoContainer: {
							flexDirection: 'row',
							borderBottomWidth: 1,
							borderBottomColor: Theme.colors.bgSeparatorSecondary,
							flex: 1,
							alignItems: 'center',
							marginBottom: 6,
							marginTop: 6,
							height: '100%',
							marginLeft: 12,
						},
						itemInfo: {
							mainContainer: {
								flex: 1,
							},
							title: {
								marginBottom: 4,
								fontSize: 16,
								fontWeight: 400,
								color: ChatTitle.createFromDialogId(userData.id).getTitleColor(),
							},
							subtitle: {
								color: Theme.colors.base3,
								fontSize: 14,
								fontWeight: 400,
								textStyle: 'normal',
								align: 'baseline',
							},
							iconSubtitleStyle: {
								width: 14,
								height: 12,
								marginTop: 2,
								marginRight: 4,
								alignSelf: 'center',
							},
						},
					},
				};
			});
		}

		/**
		 * @desc Update view state by current items
		 * @return void
		 */
		updateStateView()
		{
			this.view.updateItemState(this.items);
		}

		/**
		 * @desc Item click handler
		 * @param {Object} event
		 * @return void
		 */
		itemClickHandler(event)
		{
			if (event.dialogTitleParams.key)
			{
				this.openUserProfile(Number(event.dialogTitleParams.key));
			}
		}

		openDialogHandler()
		{
			this.close();
		}

		/**
		 * @desc Handler is close widget
		 * @return void
		 */
		closeHandler()
		{
			this.unsubscribeExternalEvents();
		}

		close()
		{
			this.widget.close();
		}

		bindMethods()
		{
			this.itemClickHandler = this.itemClickHandler.bind(this);
			this.closeHandler = this.closeHandler.bind(this);
			this.deleteDialogHandler = this.deleteDialogHandler.bind(this);
			this.openDialogHandler = this.openDialogHandler.bind(this);
		}

		deleteDialogHandler({ dialogId })
		{
			if (String(this.dialogId) !== String(dialogId))
			{
				return;
			}

			this.close();
		}

		/**
		 * @desc Open component UserProfile in current navigation stack
		 * @param {Number} userId
		 * @return void
		 */
		openUserProfile(userId)
		{
			UserProfile.show(userId, {
				openingDialogId: this.dialogId,
				backdrop: true,
				parentWidget: this.widget,
			});
		}
	}

	module.exports = { UsersReadMessageList };
});
