/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/converter/search
 */
jn.define('im/messenger/lib/converter/search', (require, exports, module) => {
	const { ChatTitle, ChatAvatar } = require('im/messenger/lib/element');
	const { Theme } = require('im/lib/theme');
	/**
	 * @class SearchConverter
	 */
	class SearchConverter
	{
		/**
		 *
		 * @param {UsersModelState || RecentUser} user
		 * @return {RecentCarouselItem}
		 */
		toUserCarouselItem(user)
		{
			/** @type {RecentCarouselItem} */
			const item = {
				params: {},
			};
			const preparedUser = this.prepareParams(user);

			item.type = 'info';
			item.id = `user/${preparedUser.id}`;
			item.params.id = preparedUser.id;
			item.params.externalAuthId = preparedUser.externalAuthId;

			item.title = preparedUser.firstName;

			item.imageUrl = ChatAvatar.createFromDialogId(preparedUser.id).getAvatarUrl();

			if (!item.imageUrl && !preparedUser.lastActivityDate)
			{
				item.imageUrl = `${component.path}images` + '/avatar_wait_x3.png';
			}

			item.color = preparedUser.color;
			item.shortTitle = preparedUser.firstName ? preparedUser.firstName : preparedUser.name;
			item.subtitle = preparedUser.workPosition ? preparedUser.workPosition : '';

			if (preparedUser.extranet)
			{
				item.styles = {
					title: {
						font: {
							color: '#ca8600',
						},
					},
				};
			}

			return item;
		}

		/**
		 *
		 * @param {UsersModelState} user
		 * @param {string} sectionCode
		 */
		toUserSearchItem(user, sectionCode)
		{
			const item = {
				id: `user/${user.id}`,
				title: user.name,
				subtitle: ChatTitle.createFromDialogId(user.id).getDescription(),
				name: user.name,
				lastName: user.lastName,
				secondName: user.lastName,
				shortTitle: user.name,
				position: user.workPosition,
				sectionCode,
				height: 64,
				color: user.color,
				styles: {
					title: { font: { size: 16 } },
					subtitle: {},
				},
				useLetterImage: true,
				imageUrl: ChatAvatar.createFromDialogId(user.id).getAvatarUrl(),
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
				item.color = Theme.getInstance().isSupported
					? Theme.getInstance().getColors().bgContentPrimary
					: '#FFFFFF'
				;
			}

			if (user.extranet)
			{
				item.styles = {
					title: {
						font: {
							color: '#ca8600',
						},
					},
				};
			}

			return item;
		}

		/**
		 *
		 * @param {DialoguesModelState} dialog
		 * @param {string} sectionCode
		 */
		toDialogSearchItem(dialog, sectionCode)
		{
			const item = {
				title: dialog.name,
				subtitle: ChatTitle.createFromDialogId(dialog.dialogId).getDescription(),
				sectionCode,
				height: 64,
				color: dialog.color,
				styles: {
					title: { font: { size: 16 } },
					subtitle: {},
				},
				useLetterImage: true,
				id: `chat/${dialog.dialogId}`,
				imageUrl: ChatAvatar.createFromDialogId(dialog.dialogId).getAvatarUrl(),
				params: {
					id: dialog.dialogId,
				},
				type: 'info',
			};

			if (item.imageUrl !== '')
			{
				item.color = Theme.getInstance().isSupported
					? Theme.getInstance().getColors().bgContentPrimary
					: '#FFFFFF'
				;
			}

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
