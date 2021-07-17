(() =>
{
	var styles = {
		contentWrapper: {
			flexDirection: 'row',
			padding: 11,
			paddingBottom: 22,
			borderBottomWidth: 1,
			borderBottomColor: '#e8e7e8'
		},
		avatar: {
			width: 35,
			height: 35,
			marginRight: 10,
			borderRadius: 17
		},
		content: {
			width: 150,
			flexGrow: 1
		},
		author: {
			color: '#333333',
			fontWeight: 'bold',
			fontSize: 16,
			marginBottom: 5,
			marginRight: 5
		},
		text: {
			color: '#000000',
			fontSize: 16,
			marginBottom: 10,
		},
		time: {
			color: '#acb2b9',
			fontSize: 12,
			marginTop: 10
		},
		closeButton: {
			color: '#acb2b9',
			width: 30,
			height: 30
		},
		moreUsersLink: {
			color: '#1d54a2',
			fontWeight: 'bold',
			fontSize: 14,
			marginBottom: 6,
		},
		placeholderAvatar: {
			width: 35,
			height: 35,
			marginRight: 10,
			borderRadius: 17,
			backgroundColor: '#eee'
		},
		placeholderTitle: {
			width: 100,
			height: 10,
			backgroundColor: '#eee',
			marginBottom: 6,
		},
		placeholderText: {
			width: 200,
			height: 20,
			backgroundColor: '#eee'
		},
	}

	this.NotificationItem = class NotificationItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.currentDomain = currentDomain.replace('https', 'http');
		}

		getItemType()
		{
			return this.props.notification.commonType;
		}

		confirmButtonsHandler()
		{
			this.props.itemClickHandler(this.props.notification.messageId, 'delete');
		}

		getAvatarParams()
		{
			const avatarParams = {
				resizeMode: 'cover',
				style: styles.avatar
			};

			if (this.props.notification.author === '')
			{
				avatarParams.uri = this.currentDomain + '/bitrix/templates/mobile_app/images/notification-block/avatar.png';
			}
			else if (this.props.notification.avatarUrl !== '')
			{
				avatarParams.uri = this.props.notification.avatarUrl;
			}
			else
			{
				avatarParams.svg = {
					uri: this.currentDomain +'/bitrix/js/mobile/images/postform/avatar/user.svg'
				};
			}

			return avatarParams;
		}

		render()
		{
			const itemType = this.getItemType();
			const isConfirm = itemType === 'confirm';
			const isPlaceholder = itemType === 'placeholder';
			const showAttach = (this.props.notification.params !== null &&
				typeof this.props.notification.params === "object" &&
				this.props.notification.params.hasOwnProperty('ATTACH')
			);
			const users = this.getItemUsers(this.props.notification);

			styles.contentWrapper.backgroundColor = this.props.notification.notifyRead === 'N' ? '#fcf9eb' : '#fff';

			if (isPlaceholder)
			{
				return View({
						style: styles.contentWrapper,
					},
					View({
						style: styles.placeholderAvatar
					}),
					View(
						{ style: styles.content },
						View(
							{ style: { flexDirection: 'row' } },
							View({
								style: styles.placeholderTitle
							}),
						),
						View({
							style: styles.placeholderText
						}),
					),
				);
			}
			else
			{
				return View(
					{
						style: styles.contentWrapper,
						onClick: () => {
							console.log(this.props.notification);
							Utils.openLinkFromTag(this.props.notification.notifyTag);

							// todo: we don't need to change read status by click yet
							// if (this.getItemType() !== 1)
							// {
							// 	this.props.itemClickHandler(this.props.notification.messageId, 'changeReadStatus');
							// }
						}
					},
					Image(this.getAvatarParams()),
					View(
						{ style: styles.content },
						View(
							{ style: { flexDirection: 'row' } },
							Text({
								text: this.props.notification.author !== '' ? this.props.notification.author : BX.message['MOBILE_EXT_NOTIFICATION_ITEM_AUTHOR_SYSTEM'],
								style: styles.author
							}),
							users && Button({
								style: styles.moreUsersLink,
								text: BX.message['MOBILE_EXT_NOTIFICATION_ITEM_MORE_USERS'].replace('#COUNT#', users.length),
								onClick: () => {
									this.openUserList({
										title: BX.message['MOBILE_EXT_NOTIFICATION_ITEM_USERS_LIST'],
										backdrop: true,
										users
									});
								}
							}),
						),
						Text({
							html: Utils.decodeBbCode({ text: this.props.notification.text }),
							style: styles.text,
							onLinkClick: () => {},
							onRewriteUrl: () => {}
						}),
						showAttach && new Attach({
							params: this.props.notification.params,
						}),
						isConfirm && new ConfirmItemButtons({
							buttons: JSON.parse(this.props.notification.buttons),
							chatId: this.props.notification.chatId,
							messageId: this.props.notification.messageId,
							confirmButtonsHandler: this.confirmButtonsHandler.bind(this),
						}),
						Text({
							text: this.props.notification.time,
							style: styles.time
						})
					),
					!isConfirm && ImageButton({
						svg: {
							uri: `${this.currentDomain}/bitrix/templates/mobile_app/images/notification-block/close.svg`
						},
						style: styles.closeButton,
						onClick: () => {
							this.props.itemClickHandler(this.props.notification.messageId, 'delete');

							BX.rest.callMethod('im.notify.delete', { 'ID': this.props.notification.messageId })
								.then(res => console.log('im.notify.delete res', res))
								.catch(error => console.log(error));
						}
					}),
				);
			}
		}

		getItemUsers(notification)
		{
			if (notification.params !== null &&
				typeof notification.params === "object" &&
				notification.params.hasOwnProperty('USERS')
			)
			{
				return this.props.notification.params.USERS
			}
		}

		openUserList(params = {})
		{
			const {users = false, title = '', listType = 'LIST', backdrop = true} = params;

			const settings = {
				title,
				objectName: "ChatUserListInterface",
			};

			if (backdrop)
			{
				settings.backdrop = {};
			}

			const imChatUserListVersion = BX.componentParameters.get('WIDGET_CHAT_USERS_VERSION', '1.0.0');
			PageManager.openComponent("JSStackComponent", {
				scriptPath: "/mobile/mobile_component/im.chat.user.list/?version=" + imChatUserListVersion,
				params: {
					"DIALOG_ID": this.props.notification.chatId,
					"LIST_TYPE": listType,
					"USERS": users,
					"IS_BACKDROP": true
				},
				rootWidget: {
					name: "list",
					settings
				}
			});
		}
	}

})();