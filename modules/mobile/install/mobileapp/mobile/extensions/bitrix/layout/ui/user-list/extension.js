/**
 * @module layout/ui/user-list
 */
jn.define('layout/ui/user-list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { UserList } = require('layout/ui/user-list/src/list');

	class UserListManager
	{
		static open(data)
		{
			const userList = new UserList({
				users: data.users,
				testId: (data.testId || ''),
			});
			const parentWidget = (data.layoutWidget || PageManager);

			parentWidget.openWidget('layout', {
				backdrop: {
					bounceEnable: true,
					swipeAllowed: true,
					showOnTop: false,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
					shouldResizeContent: true,
					mediumPositionPercent: 70,
				},
			}).then((layoutWidget) => {
				layoutWidget.setTitle({
					text: (data.title || Loc.getMessage('MOBILE_LAYOUT_UI_USER_LIST_DEFAULT_TITLE')),
					type: 'dialog',
				});
				layoutWidget.showComponent(userList);

				userList.layoutWidget = layoutWidget;
			}).catch(console.error);
		}
	}

	module.exports = { UserListManager };
});
