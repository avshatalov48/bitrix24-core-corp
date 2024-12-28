/*
* @module call/calls/layout/participants-list
*/
jn.define('call/calls/layout/participants-list', (require, exports, module) => {
	const { Color, Corner } = require('tokens');

	const Events = {
		onRequestFloor: 'onRequestFloor',
	};

	const Icons = {
		emptyAvatar: '<svg width="55" height="54" viewBox="0 0 55 54" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="27.5" cy="27" r="26.7" fill="#C4C4C4"/><path fill-rule="evenodd" clip-rule="evenodd" d="M24.8275 14.6978C23.8798 13.203 31.7392 11.9567 32.4892 16.3612L32.5183 16.5653C32.7304 17.966 32.7304 19.3895 32.5183 20.7902L32.5867 20.7894C32.8227 20.8016 33.5583 20.9762 32.9875 22.7497L32.8546 23.1586C32.7064 23.592 32.3202 24.513 31.7915 24.2264L31.7946 24.4308C31.7902 24.965 31.6952 26.3813 30.8251 26.667L30.9021 27.8537L31.8025 27.9876L31.8015 28.1998C31.8052 28.4797 31.8304 28.9453 31.9548 29.0148C32.7762 29.5429 33.6766 29.9433 34.6238 30.2014C37.3262 30.8843 38.7429 32.0399 38.8344 33.0749L38.8391 33.1815L39.5901 36.9888C36.3543 38.3389 32.5989 39.1464 28.5826 39.2309H27.1789C23.1717 39.1466 19.4242 38.3425 16.1934 36.998L16.2873 36.3541C16.4186 35.4854 16.573 34.5999 16.7316 33.9845C17.1569 32.3336 19.5496 31.1074 21.7512 30.1646C22.8907 29.6764 23.1375 29.3835 24.2842 28.884C24.3325 28.656 24.3591 28.4244 24.3638 28.1919L24.3612 27.9593L25.3364 27.8441L25.3489 27.8578C25.3754 27.8715 25.4218 27.7921 25.2589 26.7124L25.2019 26.692C24.9764 26.5984 24.156 26.13 24.1122 24.2579L24.0509 24.272C23.8687 24.303 23.3369 24.3106 23.2487 23.3711L23.2386 23.2148C23.2051 22.3624 22.5612 21.617 23.3893 20.9935L23.5116 20.9092L22.9972 19.5438L22.9709 19.2023C22.8993 18.0413 22.825 14.3374 24.8275 14.6978Z" fill="white"/></svg>',
		hand: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" fill="none"><path fill="#FAA72C" fill-rule="evenodd" d="M11.488 4.524c0-.05.031-.154.162-.264a.76.76 0 0 1 .475-.175c.357 0 .584.274.584.495v6.626c0 .35.24.66.596.717a.678.678 0 0 0 .786-.678V6.531c0-.032.024-.127.16-.237a.73.73 0 0 1 .433-.167.73.73 0 0 1 .44.155c.114.092.16.193.16.294v5.431c0 .412.325.684.658.705.17.01.364-.042.518-.19a.709.709 0 0 0 .214-.515l-.002-.343c-.003-.242-.005-.595-.008-1.02l-.01-1.486v-.006c0-.233.078-.35.151-.414a.607.607 0 0 1 .405-.132c.162 0 .325.059.44.165.106.097.206.263.206.558 0 .305.002.606.004.907a84.35 84.35 0 0 1-.004 1.85v.01c-.015.98-.023 1.497-.16 2.446-.075.522-.476 1.807-1.006 2.977-.263.58-.542 1.097-.807 1.459a1.975 1.975 0 0 1-.334.373.428.428 0 0 1-.076.051H9.845a1.243 1.243 0 0 1-.114-.083 5.545 5.545 0 0 1-.541-.523c-.436-.468-.968-1.119-1.518-1.831a80.861 80.861 0 0 1-2.76-3.824.625.625 0 0 1-.087-.292c-.004-.11.026-.17.074-.212.24-.214.415-.224.57-.193.207.041.427.171.703.357.415.279.71.575.919.82.106.123.19.233.263.329l.015.02c.054.071.144.19.232.267l.023.02.022.02h.001v.001c.015.013.053.047.098.079l.002.001a.624.624 0 0 0 .918-.211.75.75 0 0 0 .08-.248c.008-.053.012-.107.014-.155.004-.095.005-.221.004-.374-.005-1.352-.003-3.503-.002-5.092l.001-1.475c0-.537.163-.773.279-.88a.6.6 0 0 1 .384-.157c.112 0 .305.041.452.142.12.082.211.198.211.428v4.357c0 .218.1.412.26.538a.716.716 0 0 0 .862.015.673.673 0 0 0 .278-.545V4.524Zm5.722 2.882a1.92 1.92 0 0 0-.727.14v-.97a1.57 1.57 0 0 0-.608-1.23 1.93 1.93 0 0 0-1.191-.419c-.27 0-.536.066-.775.177V4.58c0-.974-.858-1.695-1.784-1.695-.47 0-.914.178-1.244.454-.327.273-.593.689-.593 1.185v.242a2.086 2.086 0 0 0-.863-.2c-.333 0-.803.112-1.196.474-.41.377-.667.96-.667 1.763v1.47c-.002 1.24-.003 2.824-.001 4.104a5.67 5.67 0 0 0-.72-.572c-.262-.176-.665-.443-1.137-.538-.525-.105-1.084.01-1.605.475-.378.338-.486.78-.473 1.149.012.354.134.689.29.92.536.799 1.688 2.441 2.806 3.888.558.721 1.116 1.406 1.59 1.915.235.252.463.476.667.64.103.082.213.16.326.221a.983.983 0 0 0 .459.127h5.734c.328 0 .6-.154.799-.312.204-.163.39-.377.556-.605.334-.457.652-1.058.93-1.671.55-1.216 1.002-2.618 1.1-3.301.148-1.024.157-1.605.173-2.586v-.022c.01-.657.007-1.284.004-1.893a154.92 154.92 0 0 1-.004-.883c0-.606-.224-1.101-.593-1.44a1.857 1.857 0 0 0-1.253-.483Z" clip-rule="evenodd"/></svg>',
		camera: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" fill="none"><path fill="#1F86FF" fill-rule="evenodd" d="m16.25 9.94 2.2-1.54h-.01c1.13-.79 2.68.01 2.68 1.39v5.4c0 1.39-1.55 2.19-2.68 1.39l-2.19-1.55v1.45c0 1.1-.9 2-2 2h-8.3c-1.1 0-2-.9-2-2V8.51c0-1.1.9-2 2-2.01h8.3c1.1 0 2 .9 2 2v1.44Zm-2 7.34c.44 0 .8-.36.8-.8V8.51c0-.44-.36-.8-.8-.8h-8.3c-.44 0-.8.36-.8.8v7.97c0 .44.36.8.8.8h8.3Zm4.89-1.68c.33.23.79 0 .79-.41V9.8c0-.41-.46-.64-.79-.41l-2.88 2.02v2.16l2.88 2.03Z" clip-rule="evenodd"/></svg>',
		mic: '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="29" fill="none"><path fill="#fff" fill-opacity=".01" d="M0 6.5a6 6 0 0 1 6-6h16a6 6 0 0 1 6 6v16a6 6 0 0 1-6 6H6a6 6 0 0 1-6-6v-16Z"/><path fill="#1F86FF" fill-rule="evenodd" d="M13.998 5.326a3.37 3.37 0 0 0-3.37 3.37v5.407a3.37 3.37 0 1 0 6.741 0V8.697a3.37 3.37 0 0 0-3.37-3.37Zm-2.17 3.37a2.17 2.17 0 0 1 4.341 0v5.407a2.17 2.17 0 1 1-4.341 0V8.697ZM8 13.606a.6.6 0 0 0-.6.6 6.6 6.6 0 0 0 6 6.573v1.688h-1.8a.6.6 0 0 0 0 1.2h4.8a.6.6 0 1 0 0-1.2h-1.8v-1.688a6.6 6.6 0 0 0 6-6.573.6.6 0 0 0-1.2 0 5.4 5.4 0 1 1-10.8 0 .6.6 0 0 0-.6-.6Z" clip-rule="evenodd"/></svg>',
		dots: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" fill="none"><path fill="#A7A7A7" fill-rule="evenodd" d="M7.95 12.5a2.45 2.45 0 1 1-4.9 0 2.45 2.45 0 0 1 4.9 0ZM5.5 13.75a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5Zm8.95-1.25a2.45 2.45 0 1 1-4.9 0 2.45 2.45 0 0 1 4.9 0ZM12 13.75a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5Zm8.95-1.25a2.45 2.45 0 1 1-4.9 0 2.45 2.45 0 0 1 4.9 0Zm-2.45 1.25a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5Z" clip-rule="evenodd"/></svg>',
	};

	const Styles = {
		body: {
			paddingTop: 10,
			flex: 1,
			backgroundColor: Color.bgContentPrimary.toHex(),
		},
		title: {
			textAlign: 'center',
			fontSize: 17,
			fontWeight: 500,
			lineHeight: 21,
		},
		separator: {
			width: '100%',
			height: 1,
			backgroundColor: Color.bgSeparatorSecondary.toHex(),
		},
		usersContainer: {
			marginTop: 11,
			paddingLeft: 18,
			width: '100%',
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
		bottom: {
			marginLeft: 24,
			marginRight: 24,
			marginBottom: device.screen.safeArea.bottom + 12,
			height: 42,
			flexDirection: 'row',
			borderRadius: Corner.M.toNumber(),
			backgroundColor: Color.accentMainWarning.toHex(),
			alignContent: 'center',
			alignItems: 'center',
			justifyContent: 'center',
		},
		button: {
			marginLeft: 4,
			fontSize: 17,
			fontWeight: 500,
			lineHeight: 23,
			color: Color.baseWhiteFixed.toHex(),
		},

	};

	class ParticipantsList extends LayoutComponent
	{
		constructor(props = {})
		{
			super(props);
			this.state = {
				avatarPath: props.avatarPath,
				title: props.title,
				subtitle: props.subtitle,
				userList: props.userList,
			};

			if (typeof props[Events.onRequestFloor] === 'function')
			{
				this.on(Events.onRequestFloor, props.onRequestFloor);
			}
		}

		render()
		{
			const users = this.state.userList.map((userModel) => this.renderUser(userModel));

			const avatar = this.state.avatarPath === ''
				? { svg: { content: Icons.emptyAvatar } }
				: { uri: encodeURI(this.state.avatarPath) }
			;

			return View(
				{
					style: Styles.body,
				},
				View(
					{
						style: { paddingTop: 12, paddingBottom: 12, paddingLeft: 18, flexDirection: 'row' },
					},
					Image({
						style: { marginLeft: 12, width: 72, height: 72 },
						...avatar,
					}),
					View(
						{
							style: {
								marginLeft: 24,
								marginRight: 30,
								flex: 1,
								flexDirection: 'column',
								justifyContent: 'space-around',
							},
						},
						Text({
							style: {
								height: 21,
								fontWeight: 500,
								fontSize: 17,
								lineHeight: 21,
								color: Color.base1.toHex(),
								width: '100%',
							},
							text: this.state.title,
							numberOfLines: 1,
							ellipsize: 'end',
						}),
						Text({
							style: {
								height: 18,
								fontWeight: 400,
								fontSize: 15,
								lineHeight: 18,
								color: Color.base1.toHex(),
							},
							numberOfLines: 1,
							ellipsize: 'end',
							text: this.state.subtitle,
						}),
						Text({
							style: {
								height: 16,
								fontWeight: 400,
								fontSize: 13,
								lineHeight: 16,
								color: Color.base4.toHex(),
							},
							numberOfLines: 1,
							ellipsize: 'end',
							text: BX.message('MOBILE_CALL_COUNT_PARTICIPANTS').replace('#COUNT#', this.state.userList.length),
						}),
					),
				),

				TabView({
					style: {
						height: 51,
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
					params: {
						styles: {
							tabTitle: {
								underlineColor: Color.accentMainPrimary.toHex(),
							},
						},
						items: [{ id: 'participants', title: BX.message('MOBILE_CALL_PARTICIPANTS') }],
					},
				}),

				ScrollView(
					{ style: { flex: 1 } },
					View(
						{ style: Styles.usersContainer },
						...users,
					),
				),
			);
		}

		renderUser(userModel)
		{
			const avatar = userModel.avatar === ''
				? { svg: { content: Icons.emptyAvatar } }
				: { uri: encodeURI(userModel.avatar) }
			;

			return View(
				{
					style: Styles.userRow,
				},
				View(
					{
						style: {
							marginRight: 12,
							alignSelf: 'center',
						},
					},
					Image(
						{
							style: {
								width: 40,
								height: 40,
								borderRadius: 20,
							},
							resizeMode: 'cover',
							...avatar,
						},
					),
				),
				View(
					{
						style: { flex: 1, width: '100%', flexDirection: 'column' },
					},

					View(
						{
							style: Styles.userRowInner,
						},
						View(
							{
								style: { flex: 1, flexDirection: 'column', justifyContent: 'center' },
							},
							Text({
								style: Styles.userName,
								numberOfLines: 1,
								ellipsize: 'end',
								text: userModel.name,
							}),
							Text({
								style: Styles.userPosition,
								numberOfLines: 1,
								ellipsize: 'end',
								text: userModel.getDescription(),
							}),
						),
						userModel.cameraState && Image({
							style: { width: 24, height: 25 },
							svg: { content: Icons.camera },
						}),
						userModel.microphoneState && Image({
							style: { width: 28, height: 29 },
							svg: { content: Icons.mic },
						}),
						View(
							{
								onClick: () => this.emit('onUserMenuClick', [userModel.id]),
							},
							Image({
								style: { width: 24, height: 25 },
								svg: { content: Icons.dots },
							}),
						),
					),
					View({ style: Styles.separator }),
				),
			);
		}
	}

	module.exports = {
		ParticipantsList,
	};
});
