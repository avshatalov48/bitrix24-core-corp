/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants/src/items/user
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants/src/items/user', (require, exports, module) => {
	const { Color } = require('tokens');
	const { isEmpty } = require('utils/object');
	const { UserProfile } = require('user-profile');

	const { Loc } = require('im/messenger/loc');
	const { UserHelper } = require('im/messenger/lib/helper');
	const { UserStatus } = require('im/messenger/lib/element/user-status');
	const { ParticipantBaseItem } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/items/base');

	/**
	 * @class ParticipantUserItem
	 */
	class ParticipantUserItem extends ParticipantBaseItem
	{
		constructor(props)
		{
			super(props);

			this.chatTitle = this.createChatTitle(this.getUserId());
			this.userHelper = UserHelper.createByUserId(this.getUserId());
		}

		createTitle()
		{
			const title = this.chatTitle.getTitle({ isNotes: false });
			const text = this.isYou()
				? `${title} [COLOR=${Color.base4.toHex()}]${Loc.getMessage('IMMOBILE_SIDEBAR_V2_PARTICIPANTS_IS_YOU')}[/COLOR]`
				: title;

			return {
				text,
				style: {
					color: this.chatTitle.getTitleColor(),
				},
			};
		}

		createSubtitle()
		{
			return {
				text: this.chatTitle.getDescription({ isNotes: false }),
			};
		}

		createAvatar()
		{
			return {
				testId: 'participant-user-item',
				dialogId: this.getUserId(),
				statusIcons: this.renderStatusIcons(),
			};
		}

		renderStatusIcons()
		{
			const userStatus = this.getUserStatusIcon();
			const crownStatus = this.isAdmin() || this.isManager() ? this.getStatusCrownIcon() : null;
			const statuses = [userStatus, crownStatus].filter(Boolean);

			if (isEmpty(statuses))
			{
				return null;
			}

			return statuses.map((statusIcon) => ({
				statusIcon: Image({
					style: {
						width: 18,
						height: 18,
					},
					svg: {
						content: statusIcon,
					},
					onFailure: console.error,
				}),
			}));
		}

		/**
		 * @desc Get svg string for content image by dialog/user id
		 * @return {string}
		 */
		getUserStatusIcon()
		{
			return UserStatus.getStatusByUserId(this.getUserId(), false);
		}

		getTestId()
		{
			return 'sidebar-tab-participant-item';
		}

		handleOnClick()
		{
			if (this.userHelper.isBot)
			{
				return;
			}

			void UserProfile.open({
				ownerId: this.getUserId(),
			});
		}

		getUserId()
		{
			const { userId } = this.props;

			return userId;
		}

		isManager()
		{
			const { isManager } = this.props;

			return Boolean(isManager);
		}

		shouldShowMenu()
		{
			return true;
		}
	}

	module.exports = {
		ParticipantUserItem,
	};
});
