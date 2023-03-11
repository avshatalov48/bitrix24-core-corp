/**
 * @module layout/ui/user-list
 */
jn.define('layout/ui/user-list', (require, exports, module) => {
	const {Loc} = require('loc');
	const {ProfileView} = require('user/profile');

	const DEFAULT_AVATAR = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/user-list/images/default-avatar.png';

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
						backgroundColor: '#ffffff',
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
						height: 70,
						alignItems: 'center',
						marginLeft: 16,
						borderTopWidth: (isWithTopBorder ? 1 : 0),
						borderTopColor: '#1A000000',
					},
					testId: `${this.testId}_USER_${user.id}`,
					onClick: () => this.openUserProfile(user.id),
				},
				Image({
					style: {
						width: 40,
						height: 40,
						borderRadius: 20,
					},
					uri: this.getImageUrl(user.avatar || DEFAULT_AVATAR),
				}),
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'column',
							marginHorizontal: 12,
						},
					},
					Text({
						style: {
							fontSize: 18,
							fontWeight: '400',
							color: '#333333',
						},
						numberOfLines: 1,
						ellipsize: 'end',
						text: user.name,
					}),
					(user.workPosition && Text({
						style: {
							fontSize: 15,
							fontWeight: '400',
							color: '#828b95',
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
			}).then(list => ProfileView.open({userId, isBackdrop: true}, list));
		}

		getImageUrl(imageUrl)
		{
			if (imageUrl.indexOf(currentDomain) !== 0)
			{
				imageUrl = imageUrl.replace(`${currentDomain}`, '');
				imageUrl = (imageUrl.indexOf('http') !== 0 ? `${currentDomain}${imageUrl}` : imageUrl);
			}

			return encodeURI(imageUrl);
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
				layoutWidget.setTitle({text: (data.title || Loc.getMessage('MOBILE_LAYOUT_UI_USER_LIST_DEFAULT_TITLE'))});
				layoutWidget.showComponent(userList);

				userList.layoutWidget = layoutWidget;
			});
		}
	}

	module.exports = {UserListManager};
});