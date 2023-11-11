/**
 * @module im/messenger/controller/users-read-message-list
 */
jn.define('im/messenger/controller/users-read-message-list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Logger } = require('im/messenger/lib/logger');
	const { Moment } = require('utils/date');
	const { RestMethod } = require('im/messenger/const/rest');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { UsersReadMessageListView } = require('im/messenger/controller/users-read-message-list/view');
	const { atomIcons } = require('im/messenger/assets/common');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { FriendlyDate } = require('layout/ui/friendly-date');

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
			this.onItemClick = this.onItemClick.bind(this);
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
			this.createView();
			this.widget.showComponent(this.view);
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
				callbacks: { onItemClick: this.onItemClick },
			});
		}

		/**
		 * @desc Get users data by message id from rest method imV2ChatMessageTailViewers
		 * @return {Promise}
		 */
		getUserReadMessageList()
		{
			return new Promise((resolve, reject) => {
				BX.rest.callMethod(
					RestMethod.imV2ChatMessageTailViewers,
					{
						id: this.messageId,
					},
					(result) => {
						if (result.error())
						{
							Logger.error('UsersReadMessageList.getUserReadMessageList.result.error', result.error());
							reject(result.error());
						}

						const data = result.data();
						resolve(data);
					},
				);
			});
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

			return filteredViews.map((view) => {
				const userData = restData.users.find((user) => user.id === view.userId);
				const dataState = new Moment(view.dateView);
				const dataFriendly = new FriendlyDate({
					moment: dataState,
					showTime: true,
				});
				const dateText = dataFriendly.makeText(dataState);

				return {
					type: 'item',
					key: view.userId.toString(),
					userName: userData.name,
					color: userData.color,
					title: userData.name,
					subtitle: dateText,
					iconSubtitle: atomIcons.doubleCheck(),
					avatarUri: userData.avatar,
					avatarColor: userData.color,

					style: {
						parentView: {
							backgroundColor: '#FFFFFF',
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
							borderBottomColor: '#e9e9e9',
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
								color: '#333',
							},
							subtitle: {
								color: '#A8ADB4',
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
		onItemClick(event)
		{
			if (event.dialogTitleParams.key)
			{
				this.openUserProfile(Number(event.dialogTitleParams.key));
			}
		}

		/**
		 * @desc Open component UserProfile in current navigation stack
		 * @param {Number} userId
		 * @return void
		 */
		openUserProfile(userId)
		{
			UserProfile.show(userId, { backdrop: true, parentWidget: this.widget });
		}
	}

	module.exports = { UsersReadMessageList };
});
