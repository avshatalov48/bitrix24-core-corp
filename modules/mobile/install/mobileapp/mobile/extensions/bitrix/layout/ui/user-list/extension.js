/**
 * @module layout/ui/user-list
 */
jn.define('layout/ui/user-list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BottomSheet } = require('bottom-sheet');
	const { UserList } = require('layout/ui/user-list/src/list');
	const { ListItem } = require('layout/ui/user-list/src/list-item');

	const DEFAULT_HEIGHT = 50;
	/**
	 * @typedef {UserListProps} UserListManagerProps
	 * @property {string} [title]
	 * @property {PageManager} [layoutWidget]
	 *
	 * @class UserListManager
	 */
	class UserListManager
	{
		/**
		 * @public
		 * @param {Object} props
		 * @param {Array} props.users
		 * @param {Function} [props.renderCustomDescription]
		 * @param {Object} [props.layoutWidget]
		 * @param {string} [props.title]
		 * @param {string} [props.testId]
		 */
		static open(props)
		{
			const title = UserListManager.#getTitle(props.title);

			UserListManager.openBottomSheet({ ...props, title });
		}

		/**
		 * @param {Object} props
		 * @param {number} [props.backdropMediumPositionPercent]
		 * @param {Array} props.users
		 * @param {string} [props.testId]
		 * @param {string} [props.title]
		 * @param {Object} [props.parentWidget=PageManager]
		 * @param {Function} [props.renderCustomDescription]
		 */
		static openBottomSheet(props)
		{
			const mediumPositionPercent = props.backdropMediumPositionPercent ?? DEFAULT_HEIGHT;
			const bottomSheet = new BottomSheet({
				titleParams: {
					text: props.title || Loc.getMessage('MOBILE_LAYOUT_UI_USER_LIST_DEFAULT_TITLE'),
					type: 'dialog',
				},
				component: (layout) => {
					return new UserList({
						users: props.users,
						testId: (props.testId || ''),
						parentWidget: layout,
						renderCustomDescription: props.renderCustomDescription || null,
					});
				},
			});

			void bottomSheet
				.setParentWidget(props.parentWidget || PageManager)
				.enableSwipe()
				.enableBounce()
				.disableShowOnTop()
				.enableResizeContent()
				.disableHorizontalSwipe()
				.setMediumPositionPercent(mediumPositionPercent)
				.disableOnlyMediumPosition()
				.open();
		}

		static #getTitle(title)
		{
			return title || Loc.getMessage('MOBILE_LAYOUT_UI_USER_LIST_DEFAULT_TITLE');
		}
	}

	module.exports = { UserListManager, ListItem };
});
