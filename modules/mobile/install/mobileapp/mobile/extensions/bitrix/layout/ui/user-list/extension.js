/**
 * @module layout/ui/user-list
 */
jn.define('layout/ui/user-list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Indent, Color } = require('tokens');
	const { Text2, Text5 } = require('ui-system/typography/text');
	const { ProfileView } = require('user/profile');
	const { Avatar } = require('layout/ui/user/avatar');

	class UserList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = null;
			this.testId = (props.testId || '');
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
					safeArea: {
						bottom: true,
					},
					testId: `${this.testId}_USER_LIST`,
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
					View(
						{},
						...this.props.users.map((user, index) => this.renderUser(user, (index > 0))),
					),
				),
			);
		}

		renderUser(user, isWithTopBorder = true)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginLeft: Indent.XL3.toNumber(),
					},
					testId: `${this.testId}_USER_${user.id}`,
					onClick: () => this.openUserProfile(user.id),
				},
				Avatar({
					id: user.id,
					name: user.name,
					size: 40,
					testId: `${this.testId}_USER_${user.id}_AVATAR`,
					image: user.avatar,
				}),
				View(
					{
						style: {
							height: 70,
							justifyContent: 'center',
							flex: 1,
							flexDirection: 'column',
							marginHorizontal: Indent.XL.toNumber(),
							borderTopWidth: isWithTopBorder ? 1 : 0,
							borderTopColor: Color.bgSeparatorPrimary.toHex(),
							paddingVertical: Indent.XL2.toNumber(),
						},
					},
					Text2({
						text: user.name,
						style: {
							color: Color.base1.toHex(),
						},
						numberOfLines: 1,
						ellipsize: 'end',
					}),
					(user.workPosition && Text5({
						style: {
							color: Color.base3.toHex(),
						},
						numberOfLines: 1,
						ellipsize: 'end',
						text: user.workPosition,
					})),
				),
			);
		}

		openUserProfile(userId)
		{
			this.layoutWidget.openWidget('list', {
				groupStyle: true,
				backdrop: {
					bounceEnable: false,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
				},
			}).then((list) => ProfileView.open({ userId, isBackdrop: true }, list));
		}
	}

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
			});
		}
	}

	module.exports = { UserListManager };
});
