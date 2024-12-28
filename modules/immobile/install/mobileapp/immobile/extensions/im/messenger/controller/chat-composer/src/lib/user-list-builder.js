/**
 * @module im/messenger/controller/chat-composer/lib/user-list-builder
 */
jn.define('im/messenger/controller/chat-composer/lib/user-list-builder', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ChatTitle, ChatAvatar, UserStatus } = require('im/messenger/lib/element');

	/**
	 * @class UserListBuilder
	 */
	class UserListBuilder
	{
		/**
		 * @return UserListBuilder
		 */
		static getInstance(options)
		{
			return new this(options);
		}

		constructor(options)
		{
			this.core = serviceLocator.get('core');
			this.store = this.core.getStore();
			/** @type {DialogId} */
			this.dialogId = options?.dialogId;
			/** @type {UsersModelState} */
			this.users = options?.users;
			/** @type {DialoguesModelState} */
			this.dialogModel = options?.dialogModel ?? this.getDialogModel();
		}

		/**
		 * @desc Build users data from UsersModelState list
		 * @param {Array<DialogId>} dialogId
		 * @param {Array<UsersModelState>} users
		 * @return {Object[]}
		 */
		static getBuildUsersDataList(dialogId, users)
		{
			return this.getInstance({ dialogId, users }).buildData();
		}

		/**
		 * @desc Build managers data by dialogId
		 * @param {DialogId} dialogId
		 * @return {Object[]}
		 */
		static getBuildManagersDataListByDialogId(dialogId)
		{
			const instance = this.getInstance({ dialogId });
			instance.users = instance.getManagersData();

			return instance.buildData();
		}

		/**
		 * @return {DialoguesModelState}
		 */
		getDialogModel()
		{
			return this.store.getters['dialoguesModel/getById'](this.dialogId);
		}

		/**
		 * @return {Array<UsersModelState>}
		 */
		getManagersData()
		{
			return this.dialogModel.managerList.map(
				(userId) => this.store.getters['usersModel/getById'](userId),
			);
		}

		/**
		 * @desc Build managers data from UsersModelState list
		 * @param {Array<UsersModelState>} users
		 * @return {Object[]}
		 */
		buildData(users = this.users)
		{
			const currentUserId = MessengerParams.getUserId();
			const dialogData = this.dialogModel;
			const youTitle = this.getYouTitle();

			return users.map((user) => {
				return this.prepareUserData(user, currentUserId, dialogData, youTitle);
			});
		}

		/**
		 * @return {string}
		 */
		getYouTitle()
		{
			return Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_IS_YOU');
		}

		/**
		 * @desc Returns prepared user-item object for tab listview participants
		 * @param {object} user - users data
		 * @param {number} currentUserId - for check is me
		 * @param {?DialoguesModelState} dialogData
		 * @param {string} youTitle
		 * @return {object}
		 */
		prepareUserData(user, currentUserId, dialogData, youTitle)
		{
			const ownerId = Type.isNumber(dialogData?.owner) ? dialogData?.owner : currentUserId;
			const userTitle = ChatTitle.createFromDialogId(user.id);
			const isYou = currentUserId === user.id;
			const userAvatar = ChatAvatar.createFromDialogId(user.id).getTitleParams();
			const statusSvg = UserStatus.getStatusByUserId(user.id, false);
			const isAdmin = ownerId === user.id;
			const crownStatus = this.getStatusCrown(isAdmin);
			const userId = user.id;

			return {
				id: userId,
				title: userTitle.getTitle(),
				isYouTitle: isYou ? youTitle : null,
				desc: userTitle.getDescription(),
				imageUrl: userAvatar.imageUrl,
				imageColor: userAvatar.imageColor,
				statusSvg,
				crownStatus,
				isAdmin,
				isYou,
			};
		}

		/**
		 * @desc Get svg status
		 * @param {boolean} isAdmin
		 * @return {string}
		 */
		getStatusCrown(isAdmin)
		{
			if (isAdmin)
			{
				return UserStatus.getStatusCrown();
			}

			return UserStatus.getStatusGreenCrown();
		}
	}

	module.exports = { UserListBuilder };
});
