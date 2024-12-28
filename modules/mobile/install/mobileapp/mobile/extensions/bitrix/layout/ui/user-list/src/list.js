/**
 * @module layout/ui/user-list/src/list
 */
jn.define('layout/ui/user-list/src/list', (require, exports, module) => {
	const { Indent, Color } = require('tokens');
	const { ProfileView } = require('user/profile');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { UserName } = require('layout/ui/user/user-name');
	const { Text2, Text5 } = require('ui-system/typography/text');

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
			const { id, name, avatar, workPosition } = user;
			const userTestId = `${this.testId}_USER_${id}`;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginLeft: Indent.XL3.toNumber(),
					},
					testId: userTestId,
					onClick: () => this.openUserProfile(id),
				},
				Avatar({
					id,
					name,
					size: 40,
					testId: `${userTestId}_AVATAR`,
					image: avatar,
					withRedux: true,
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
					UserName({
						id,
						testId: `${userTestId}_NAME`,
						text: name,
						numberOfLines: 1,
						ellipsize: 'end',
						textElement: Text2,
					}),
					(workPosition && Text5({
						style: {
							color: Color.base3.toHex(),
						},
						numberOfLines: 1,
						ellipsize: 'end',
						text: workPosition,
					})),
				),
			);
		}

		openUserProfile(userId)
		{
			this.layoutWidget.openWidget(
				'list',
				{
					groupStyle: true,
					backdrop: {
						bounceEnable: false,
						swipeAllowed: true,
						showOnTop: true,
						hideNavigationBar: false,
						horizontalSwipeAllowed: false,
					},
				},
			).then((list) => {
				ProfileView.open({ userId, isBackdrop: true }, list);
			}).catch(console.error);
		}
	}

	module.exports = { UserList };
});
