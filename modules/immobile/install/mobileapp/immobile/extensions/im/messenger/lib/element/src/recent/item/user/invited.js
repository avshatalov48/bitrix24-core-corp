/**
 * @module im/messenger/lib/element/recent/item/user/invited
 */
jn.define('im/messenger/lib/element/recent/item/user/invited', (require, exports, module) => {
	const { Loc } = require('loc');

	const { Theme } = require('im/lib/theme');
	const { UserItem } = require('im/messenger/lib/element/recent/item/user');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const {
		InviteResendAction,
		InviteCancelAction,
	} = require('im/messenger/lib/element/recent/item/action/action');

	/**
	 * @class InvitedUserItem
	 */
	class InvitedUserItem extends UserItem
	{
		/**
		 * @param {RecentModelState} modelItem
		 * @param {object} options
		 */
		constructor(modelItem = {}, options = {})
		{
			super(modelItem, options);
		}

		createSubtitle()
		{
			this.subtitle = Loc.getMessage('IMMOBILE_ELEMENT_RECENT_USER_INVITED_3');

			return this;
		}

		createSubtitleStyle()
		{
			this.styles.subtitle = {
				font: {
					size: '14',
					color: Theme.colors.accentSoftElementBlue,
					useColor: true,
					fontStyle: 'medium',
				},
				cornerRadius: 12,
				backgroundColor: Theme.colors.accentSoftBlue1,
				padding: {
					top: 3.5,
					right: 12,
					bottom: 3.5,
					left: 12,
				},
			};

			return this;
		}

		createActions()
		{
			const item = this.getModelItem();
			const isInvitedByCurrentUser = item && item.invitation.originator === serviceLocator.get('core').getUserId();
			if (isInvitedByCurrentUser && item.invitation.canResend === true)
			{
				this.actions.push(InviteResendAction);
			}

			if (isInvitedByCurrentUser)
			{
				this.actions.push(InviteCancelAction);
			}

			return this;
		}
	}

	module.exports = {
		InvitedUserItem,
	};
});
