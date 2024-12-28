/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/converter/search
 */
jn.define('im/messenger/lib/converter/search', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Feature } = require('im/messenger/lib/feature');
	const { ChatTitle, ChatAvatar } = require('im/messenger/lib/element');

	/**
	 * @class SearchConverter
	 */
	class SearchConverter
	{
		/**
		 *
		 * @param {UsersModelState} user
		 * @return {RecentCarouselItem}
		 */
		toUserCarouselItem(user)
		{
			/** @type {RecentCarouselItem} */
			const item = {
				params: {},
			};
			const preparedUser = this.prepareParams(user);
			const chatAvatar = ChatAvatar.createFromDialogId(preparedUser.id);
			item.type = 'info';
			item.id = `user/${preparedUser.id}`;
			item.params.id = preparedUser.id;
			item.params.externalAuthId = preparedUser.externalAuthId;
			/** @deprecated use to avatar title {AvatarDetail} */
			item.title = preparedUser.firstName;
			/** @deprecated use to avatar.uri {AvatarDetail} */
			item.imageUrl = chatAvatar.getAvatarUrl();

			if (!item.imageUrl && !preparedUser.lastActivityDate)
			{
				item.imageUrl = '/bitrix/mobileapp/immobile/extensions/im/messenger/assets/common/png/avatar_wait_air.png';
			}
			/** @deprecated use to avatar.placeholder.backgroundColor {AvatarDetail} */
			item.color = preparedUser.color;
			item.shortTitle = preparedUser.firstName ? preparedUser.firstName : preparedUser.name;
			item.subtitle = preparedUser.workPosition ? preparedUser.workPosition : '';

			item.styles = {
				title: {
					font: {
						color: ChatTitle.createFromDialogId(preparedUser.id).getTitleColor(),
						useColor: true,
					},
				},
			};

			item.avatar = chatAvatar.getRecentSearchCarouselAvatarProps();

			return item;
		}

		/**
		 *
		 * @param {UsersModelState} user
		 * @param {string} sectionCode
		 */
		toUserSearchItem(user, sectionCode)
		{
			const chatTitle = ChatTitle.createFromDialogId(user.id);
			const chatAvatar = ChatAvatar.createFromDialogId(user.id);
			const item = {
				id: `user/${user.id}`,
				/** @deprecated use to avatar {AvatarDetail} */
				title: user.name,
				subtitle: chatTitle.getDescription(),
				name: user.name,
				lastName: user.lastName,
				secondName: user.lastName,
				shortTitle: user.name,
				position: user.workPosition,
				sectionCode,
				height: 64,
				/** @deprecated use to avatar {AvatarDetail} */
				color: user.color,
				styles: {
					title: { font: { size: 16 } },
					subtitle: {},
				},
				useLetterImage: true,
				/** @deprecated use to avatar {AvatarDetail} */
				imageUrl: chatAvatar.getAvatarUrl(),
				params: {
					id: user.id,
				},
				showSwipeActions: false,
				actions: [],
				unselectable: true,
				type: 'info',
			};

			if (item.imageUrl !== '')
			{
				item.color = AppTheme.colors.bgContentPrimary;
			}

			item.styles = {
				title: {
					font: {
						/** @deprecated use to avatar {AvatarDetail} */
						color: chatTitle.getTitleColor(),
						useColor: true,
					},
				},
			};

			item.avatar = chatAvatar.getRecentSearchItemAvatarProps();

			return item;
		}

		/**
		 *
		 * @param {DialoguesModelState} dialog
		 * @param {string} sectionCode
		 */
		toDialogSearchItem(dialog, sectionCode)
		{
			const chatTitle = ChatTitle.createFromDialogId(dialog.dialogId);
			const chatAvatar = ChatAvatar.createFromDialogId(dialog.dialogId);
			const item = {
				/** @deprecated use to avatar {AvatarDetail} */
				title: dialog.name,
				subtitle: chatTitle.getDescription(),
				sectionCode,
				height: 60,
				/** @deprecated use to avatar {AvatarDetail} */
				color: dialog.color,
				styles: {
					title: { font: { size: 16 } },
					subtitle: {},
				},
				useLetterImage: true,
				id: `chat/${dialog.dialogId}`,
				/** @deprecated use to avatar {AvatarDetail} */
				imageUrl: chatAvatar.getAvatarUrl(),
				isSuperEllipseIcon: chatAvatar.getIsSuperEllipseIcon(),
				params: {
					id: dialog.dialogId,
				},
				type: 'info',
			};

			if (item.imageUrl !== '')
			{
				item.color = AppTheme.colors.bgContentPrimary;
			}

			item.styles = {
				title: {
					font: {
						color: chatTitle.getTitleColor(),
						useColor: true,
					},
				},
			};
			// for native support styles (isSuperEllipseIcon key will be deleted)
			if (item.isSuperEllipseIcon && Feature.isAvatarBorderStylesSupported)
			{
				item.styles.image = { image: { borderRadius: 15 } }; // borderRadius - is percent, no int
			}

			item.avatar = chatAvatar.getRecentSearchItemAvatarProps();

			return item;
		}

		/**
		 *
		 * @param {UsersModelState | RecentUser} user
		 * @return {RecentCarouselItemUser}
		 */
		prepareParams(user)
		{
			const result = {};

			result.id = user.id;
			result.externalAuthId = user.external_auth_id || user.externalAuthId;
			result.firstName = user.first_name || user.firstName;
			result.avatar = user.avatar;
			result.lastActivityDate = user.last_activity_date || user.lastActivityDate;
			result.color = user.color;
			result.name = user.name;
			result.workPosition = user.work_position || user.workPosition;
			result.extranet = user.extranet;

			return result;
		}
	}

	module.exports = {
		SearchConverter: new SearchConverter(),
	};
});
