/**
 * @module layout/ui/user-list/src/list
 */
jn.define('layout/ui/user-list/src/list', (require, exports, module) => {
	const { Color } = require('tokens');
	const { ScrollView } = require('layout/ui/scroll-view');
	const { ListItem } = require('layout/ui/user-list/src/list-item');

	/**
	 * @typedef {Object} UserListParams
	 * @property {string} id
	 * @property {string} name
	 * @property {string} avatar
	 * @property {string} workPosition
	 *
	 * @typedef {Object} UserListProps
	 * @property {string} testId
	 * @property {PageManager} [parentWidget]
	 * @property {UserListParams[]} users
	 *
	 * @class UserList
	 */
	class UserList extends LayoutComponent
	{
		render()
		{
			const { users } = this.props;

			return View(
				{
					style: {
						flex: 1,
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
					safeArea: {
						bottom: true,
					},
					testId: this.getTestId('USER_LIST'),
				},
				ScrollView(
					{
						style: {
							flex: 1,
							borderRadius: 12,
						},
						bounces: true,
						showsVerticalScrollIndicator: true,
					},
					...users.map((user, index) => this.renderUser(user, (index > 0))),
				),
			);
		}

		renderUser(user, isWithTopBorder = true)
		{
			const testId = this.getTestId(`USER_${user.id}`);

			return new ListItem({
				user,
				showBorder: isWithTopBorder ? 1 : 0,
				testId,
				parentWidget: this.props.parentWidget,
				workPosition: this.props.workPosition,
				renderCustomDescription: this.props.renderCustomDescription,
			});
		}

		/**
		 * @param {string} suffix
		 */
		getTestId(suffix)
		{
			const { testId } = this.props;

			return [testId, suffix].filter(Boolean).join('_');
		}
	}

	module.exports = { UserList };
});
