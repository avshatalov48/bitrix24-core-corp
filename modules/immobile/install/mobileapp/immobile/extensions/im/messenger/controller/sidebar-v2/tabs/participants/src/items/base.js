/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants/src/items/base
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants/src/items/base', (require, exports, module) => {
	const { isFunction } = require('utils/object');
	const { UserStatus } = require('im/messenger/lib/element/user-status');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { ListItem } = require('im/messenger/controller/sidebar-v2/ui/layout/list-item');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class ParticipantBaseItem
	 */
	class ParticipantBaseItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.store = serviceLocator.get('core').getStore();

			this.handleOnClick = this.handleOnClick.bind(this);
		}

		/**
		 * @abstract
		 */
		getTestId()
		{
			return 'sidebar-tab-participant-item';
		}

		render()
		{
			const listItemProps = {
				testId: this.getTestId(),
				title: this.createTitle(),
				subtitle: this.createSubtitle(),
				leftIcon: this.createAvatar(),
				onClick: this.handleOnClick,
			};

			if (this.shouldShowMenu())
			{
				listItemProps.onShowMenu = this.handleOnShowMenu;
			}

			return new ListItem(listItemProps);
		}

		/**
		 * @abstract
		 */
		createTitle()
		{}

		/**
		 * @abstract
		 */
		createSubtitle()
		{}

		/**
		 * @abstract
		 */
		createAvatar()
		{}

		/**
		 * @abstract
		 */
		handleOnClick()
		{}

		handleOnShowMenu = (params) => {
			const { onShowMenu } = this.props;

			onShowMenu?.(params);
		};

		shouldShowMenu = () => {
			const { onShowMenu } = this.props;

			return isFunction(onShowMenu);
		};

		/**
		 * @param {DialogId} dialogId
		 * @returns {ChatTitle}
		 */
		createChatTitle(dialogId)
		{
			return ChatTitle.createFromDialogId(dialogId);
		}

		getDialogId()
		{
			const { dialogId } = this.props;

			return dialogId;
		}

		/**
		 * @desc Get svg status
		 * @return {string}
		 */
		getStatusCrownIcon()
		{
			return this.isAdmin()
				? UserStatus.getStatusCrown()
				: UserStatus.getStatusGreenCrown();
		}

		isAdmin()
		{
			const { isAdmin } = this.props;

			return Boolean(isAdmin);
		}

		isYou()
		{
			const { isYou } = this.props;

			return Boolean(isYou);
		}
	}

	module.exports = {
		ParticipantBaseItem,
	};
});
