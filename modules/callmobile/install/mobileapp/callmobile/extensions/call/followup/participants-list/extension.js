jn.define('call:followup/participants-list', (require, exports, module) => {

	const { Color } = require('tokens');
	const { Avatar } = require('ui-system/blocks/avatar');

	const styles = {
		separator: {
			width: '100%',
			height: 1,
			backgroundColor: Color.bgSeparatorSecondary.toHex(),
		},
		userRow: {
			flexDirection: 'row',
			height: 70,
			width: '100%',
		},
		userRowInner: {
			paddingRight: 18,
			flexDirection: 'row',
			flex: 1,
			alignItems: 'center',
		},
		userName: {
			fontSize: 17,
			fontWeight: 400,
			lineHeight: 23,
			color: Color.base1.toHex(),
		},
		userPosition: {
			fontSize: 13,
			fontWeight: 400,
			lineHeight: 16,
			color: Color.base3.toHex(),
		},
	};
	class ParticipantsList extends LayoutComponent
	{
		constructor(props = {})
		{
			super(props);
			this.users = props.users;
		}

		renderUser(user)
		{
			const avatar = Avatar({
				testId: `avatar-participants-list-item-${user.id}`,
				uri: user.avatar,
				name: user.name,
				size: 40,
				backgroundColor: new Color('avatarColor', user.color),
			});
			return View(
				{
					style: styles.userRow,
					onClick: () => BX.postComponentEvent('ImMobile.Messenger.Dialog:open', [{ dialogId: user.id }], 'im.messenger'),
				},
				View(
					{
						style: {
							marginRight: 12,
							alignSelf: 'center',
						},
					},
					avatar,
				),
				View(
					{
						style: { flex: 1, width: '100%', flexDirection: 'column' },
					},
					View(
						{
							style: styles.userRowInner,
						},
						View(
							{
								style: { flex: 1, flexDirection: 'column', justifyContent: 'center' },
							},
							Text({
								style: styles.userName,
								numberOfLines: 1,
								ellipsize: 'end',
								text: user.name,
							}),
							Text({
								style: styles.userPosition,
								numberOfLines: 1,
								ellipsize: 'end',
								text: user.work_position,
							}),
						),
					),
					View({ style: styles.separator })
				)
			)
		}

		render()
		{
			const users = Object.values(this.users).map((user) => this.renderUser(user));

			return View(
				{},
				View(
					{ style: { width: '100%', marginTop: 12 } },
					Text({
						style: { fontSize: 17, color: Color.base1.toHex(), fontWeight: '500', textAlign: 'center' },
						text: BX.message('MOBILE_CALL_PARTICIPANTS'),
					}),
				),
				ScrollView(
					{ style: { flex: 1, height: '100%', marginTop: 15 } },
					View(
						{ style: { paddingLeft: 19, paddingRight: 19 } },
						...users,
					),
				),
			);
		}
	}

	module.exports = {
		ParticipantsList,
	};
});
