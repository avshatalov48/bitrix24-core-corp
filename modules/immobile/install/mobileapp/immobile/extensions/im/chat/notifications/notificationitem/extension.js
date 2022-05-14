(() =>
{
	var styles = {
		container: {
			flexDirection: 'row',
			borderBottomWidth: 1,
			borderBottomColor: '#e8e7e8',
		},
		contentWrapper: {
			flexDirection: 'row',
			flex: 1,
			padding: 11,
			backgroundColor: '#fff',
		},
		avatar: {
			width: 35,
			height: 35,
			marginRight: 10,
			borderRadius: 17
		},
		content: {
			flex: 1,
			paddingBottom: 30,
		},
		author: {
			color: '#333333',
			fontWeight: 'bold',
			fontSize: 15,
			marginRight: 5
		},
		text: {
			color: '#000000',
			fontSize: 16,
			marginBottom: 10,
		},
		bottomShadow: {
			position: 'absolute',
			left: 0,
			right: 0,
			bottom: 0,
		},
		bottomPanel: {
			flexDirection: 'row',
			justifyContent: 'space-between',
			paddingLeft: 55,
			paddingBottom: 10,
			paddingRight: 10,
			alignItems: 'center',
			backgroundColor: '#ffffff'
		},
		time: {
			color: '#acb2b9',
			fontSize: 14,
		},
		expandButton: {
			color: '#acb2b9',
			fontSize: 14,
			height: 30,
			textAlign: 'right',
			width: '40%',
			marginRight: 7
		},
		closeButton: {
			width: 35,
			height: 20,
		},
		moreUsersLink: {
			color: '#1d54a2',
			fontWeight: 'bold',
			fontSize: 15,
			maxHeight: 18,
			paddingLeft: 5
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

	const MAX_HEIGHT = 200;
	const ANIMATION_DURATION = 200;

	this.NotificationItem = class NotificationItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				height: null,
				fullHeight: null,
				expanded: false
			}
		}

		getItemType()
		{
			return this.props.notification.commonType;
		}

		confirmButtonsHandler()
		{
			this.props.itemClickHandler(this.props.notification.messageId, 'delete');
		}

		renderAvatar() {
			const avatarProps = {
				resizeMode: 'cover',
				style: {}
			};

			if (this.props.notification.author === '')
			{
				// bell icon
				avatarProps.svg = {
					content: '<svg xmlns="http://www.w3.org/2000/svg" width="19" height="22" viewBox="0 0 19 22" >\n' +
						'<path fill="#FFFFFF" d="M 18.808594 18.273438 C 18.851562 17.359375 18.613281 16.457031 18.128906 15.675781 C 17.714844 14.929688 16.394531 14.019531 15.992188 13.195312 C 14.710938 10.601562 15.804688 5.246094 9.585938 5.246094 C 2.980469 5.246094 4.363281 10.429688 3.019531 13.058594 C 2.5 14.070312 1.21875 14.800781 0.753906 15.695312 C 0.351562 16.476562 0.207031 17.363281 0.332031 18.230469 C 5.664062 18.242188 9.664062 18.253906 12.328125 18.257812 C 13.769531 18.261719 15.929688 18.269531 18.808594 18.273438 Z M 10.875 0.113281 L 10.902344 3.617188 L 8.257812 3.617188 L 8.25 0.113281 Z M 10.902344 18.660156 L 10.902344 21.953125 L 8.257812 21.953125 L 8.257812 18.660156 Z M 10.902344 18.660156 "/>\n' +
						'</svg>'
				};
				avatarProps.style = { backgroundColor: '#77828e'};
				avatarProps.resizeMode = 'center';
			}
			else if (this.props.notification.avatarUrl !== '')
			{
				avatarProps.uri = encodeURI(this.props.notification.avatarUrl);
			}
			else
			{
				// default avatar icon
				avatarProps.svg = {
					content: '<svg width="55" height="54" viewBox="0 0 55 54" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M24.8275 14.6978C23.8798 13.203 31.7392 11.9567 32.4892 16.3612L32.5183 16.5653C32.7304 17.966 32.7304 19.3895 32.5183 20.7902L32.5867 20.7894C32.8227 20.8016 33.5583 20.9762 32.9875 22.7497L32.8546 23.1586C32.7064 23.592 32.3202 24.513 31.7915 24.2264L31.7946 24.4308C31.7902 24.965 31.6952 26.3813 30.8251 26.667L30.9021 27.8537L31.8025 27.9876L31.8015 28.1998C31.8052 28.4797 31.8304 28.9453 31.9548 29.0148C32.7762 29.5429 33.6766 29.9433 34.6238 30.2014C37.3262 30.8843 38.7429 32.0399 38.8344 33.0749L38.8391 33.1815L39.5901 36.9888C36.3543 38.3389 32.5989 39.1464 28.5826 39.2309H27.1789C23.1717 39.1466 19.4242 38.3425 16.1934 36.998L16.2873 36.3541C16.4186 35.4854 16.573 34.5999 16.7316 33.9845C17.1569 32.3336 19.5496 31.1074 21.7512 30.1646C22.8907 29.6764 23.1375 29.3835 24.2842 28.884C24.3325 28.656 24.3591 28.4244 24.3638 28.1919L24.3612 27.9593L25.3364 27.8441L25.3489 27.8578C25.3754 27.8715 25.4218 27.7921 25.2589 26.7124L25.2019 26.692C24.9764 26.5984 24.156 26.13 24.1122 24.2579L24.0509 24.272C23.8687 24.303 23.3369 24.3106 23.2487 23.3711L23.2386 23.2148C23.2051 22.3624 22.5612 21.617 23.3893 20.9935L23.5116 20.9092L22.9972 19.5438L22.9709 19.2023C22.8993 18.0413 22.825 14.3374 24.8275 14.6978Z" fill="white"/></svg>',
				};
				avatarProps.style = { backgroundColor: this.props.notification.avatarColor };
			}

			avatarProps.style = Object.assign({}, styles.avatar, avatarProps.style);

			return Image(avatarProps);
		}

		onClose()
		{
			this.props.onRemove && this.props.onRemove(this.props.notification);
		}

		renderCloseButton()
		{
			const closeIcon = `<svg width="11px" height="11px" viewBox="0 0 11 11" version="1.1" xmlns="http://www.w3.org/2000/svg">
			    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
			        <g transform="translate(-350.000000, -81.000000)" fill="#D8D8D8">
			            <g id="01" transform="translate(11.000000, 76.000000)">
			                <g id="ui-/-popup-/-close" transform="translate(336.000000, 2.000000)">
			                    <path d="M9.48528137,7.48528137 L14.4852814,7.48528137 L14.4852814,9.48528137 L9.48528137,9.48528137 L9.48528137,14.4852814 L7.48528137,14.4852814 L7.48528137,9.48528137 L2.48528137,9.48528137 L2.48528137,7.48528137 L7.48528137,7.48528137 L7.48528137,2.48528137 L9.48528137,2.48528137 L9.48528137,7.48528137 Z" id="Combined-Shape" transform="translate(8.485281, 8.485281) rotate(-315.000000) translate(-8.485281, -8.485281) "></path>
			                </g>
			            </g>
			        </g>
			    </g>
			</svg>`;

			return ImageButton({
				style: styles.closeButton,
				svg: { content: closeIcon },
				onClick: this.onClose.bind(this),
			});
		}

		onLayout({ width, height })
		{
			if (this.state.fullHeight == null)
			{
				const isConfirmType = this.props.notification.commonType === Const.NotificationTypes.confirm;

				this.setState({
					fullHeight: height,
					height: ((height > MAX_HEIGHT) && !isConfirmType) ? MAX_HEIGHT : height
				});
			}
		}

		needShadow()
		{
			return this.shouldExpanded() && !this.state.expanded;
		}

		shouldExpanded()
		{
			if (this.props.notification.commonType !== Const.NotificationTypes.simple)
			{
				return false;
			}

			return Math.floor(this.state.fullHeight) > MAX_HEIGHT;
		}

		onExpandClick()
		{
			if (!this.shouldExpanded())
			{
				return;
			}
			if (this.state.expanded)
			{
				this.animate(false, MAX_HEIGHT);
			}
			else
			{
				this.animate(true, this.state.fullHeight);
			}
		}

		fireOnHeightWillChange(fromHeight, toHeight) {
			if (!this.props.onHeightWillChange) return;

			this.props.onHeightWillChange(fromHeight, toHeight);
		}

		animate(expanded, height)
		{
			const fromHeight = this.state.height;
			this.fireOnHeightWillChange(fromHeight, height);
			this.setState({
				expanded: expanded
			}, () => {
				this.view.animate({ maxHeight: height, duration: ANIMATION_DURATION }, () => {
					this.setState({
						height: height
					});
				});
			});
		}

		wasRead()
		{
			return this.props.notification.notifyRead !== 'N';
		}

		getBackgroundColor()
		{
			return this.wasRead() ? '#fff' : '#fcf9eb';
		}

		getContainerStyle() {
			const { height } = this.state;

			return !!height ? Object.assign({},
				styles.container,
				{ maxHeight: height }
			) : styles.container;
		}

		getContentWrapperStyle()
		{
			const backgroundColor = this.getBackgroundColor();
			return Object.assign({},
				styles.contentWrapper,
				{ backgroundColor: backgroundColor }
			);
		}

		getBottomPanelStyles()
		{
			const backgroundColor = this.getBackgroundColor();
			return Object.assign({},
				styles.bottomPanel,
				{ backgroundColor: backgroundColor }
			);
		}

		renderPlaceholder()
		{
			return View(
				{
					style: this.getContentWrapperStyle(),
				},
				View({
					style: styles.placeholderAvatar
				}),
				View(
					{
						style: styles.content
					},
					View(
						{
							style: { flexDirection: 'row' }
						},
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

		renderNonPlaceholder()
		{
			const isConfirm = this.getItemType() === Const.NotificationTypes.confirm;
			const showAttach = (this.props.notification.params !== null &&
				typeof this.props.notification.params === "object" &&
				this.props.notification.params.hasOwnProperty('ATTACH')
			);
			const isTextExists = typeof this.props.notification.text === 'string';
			const users = this.getItemUsers(this.props.notification);
			const author = this.props.notification.author !== '' ? this.props.notification.author : BX.message['MOBILE_EXT_NOTIFICATION_ITEM_AUTHOR_SYSTEM'];

			return View(
				{
					ref: ref => this.view = ref,
					style: this.getContainerStyle(),
					onLayout: this.onLayout.bind(this),
					onClick: () => {
						Utils.openLinkFromTag(this.props.notification.notifyTag);
					}
				},
				View(
					{
						style: this.getContentWrapperStyle()
					},
					this.renderAvatar(),
					View(
						{
							style: styles.content
						},
						View(
							{ style: { flexDirection: 'row', flexWrap: 'wrap', marginBottom: 5 } },
							author && Text({
								text: author,
								style: styles.author
							}),
							users && Text({
								style: { fontWeight: 'bold', fontSize: 15 },
								text: this.getMoreUsersText(users.length).start,
							}),
							users && Button({
								style: styles.moreUsersLink,
								text: this.getMoreUsersText(users.length).end,
								onClick: () => {
									this.openUserList({
										title: BX.message['MOBILE_EXT_NOTIFICATION_ITEM_USERS_LIST'],
										backdrop: true,
										users
									});
								}
							}),
						),
						isTextExists && BBCodeText({
							value: Utils.decodeCustomBbCode(this.props.notification.text),
							style: styles.text,
							linksUnderline: false,
							onLinkClick: ({url}) => {
								Utils.openUrl(url);
							},
						}),
						showAttach && Attach({
							params: this.props.notification.params,
						}),
						isConfirm && new ConfirmItemButtons({
							buttons: JSON.parse(this.props.notification.buttons),
							messageId: this.props.notification.messageId,
							confirmButtonsHandler: this.confirmButtonsHandler.bind(this),
						})
					),
					!isConfirm && this.renderCloseButton(),
				),
				Shadow(
					{
						style: styles.bottomShadow,
						radius: this.needShadow() ? 10 : 0,
						color: this.getBackgroundColor(),
						offset: {
							x: 0,
							y: this.needShadow() ? -10 : 0
						},
						inset: {
							left: 10,
							right: 10,
							bottom: 10,
						},
					},
					View(
						{
							style: this.getBottomPanelStyles()
						},
						Text({
							text: Utils.getFormattedDateTime(this.props.notification.time),
							style: styles.time
						}),
						this.shouldExpanded() && Button({
							text: this.state.expanded ? BX.message['MOBILE_EXT_NOTIFICATION_ITEM_FOLD'] : BX.message['MOBILE_EXT_NOTIFICATION_ITEM_UNFOLD'],
							numberOfLines: 1,
							style: styles.expandButton,
							onClick: this.onExpandClick.bind(this)
						})
					)
				)
			);
		}

		render()
		{
			const itemType = this.getItemType();
			const isPlaceholder = itemType === Const.NotificationTypes.placeholder;

			if (isPlaceholder)
			{
				return this.renderPlaceholder();
			}
			else
			{
				return this.renderNonPlaceholder();
			}
		}

		getItemUsers(notification)
		{
			if (
				notification.params !== null
				&& typeof notification.params === 'object'
				&& notification.params.hasOwnProperty('USERS')
				&& notification.params.USERS.length > 0
			)
			{
				return this.props.notification.params.USERS
			}

			return null;
		}

		openUserList(params = {})
		{
			const {users = false, title = '', listType = 'LIST', backdrop = true} = params;

			const settings = {
				title,
				objectName: 'ChatUserListInterface',
			};

			if (backdrop)
			{
				settings.backdrop = {};
			}

			const imChatUserListVersion = BX.componentParameters.get('WIDGET_CHAT_USERS_VERSION', '1.0.0');
			PageManager.openComponent('JSStackComponent', {
				scriptPath: `/mobile/mobile_component/im:im.chat.user.list/?version=${imChatUserListVersion}`,
				params: {
					'DIALOG_ID': this.props.notification.chatId,
					'LIST_TYPE': listType,
					'USERS': users,
					'IS_BACKDROP': true
				},
				rootWidget: {
					name: 'list',
					settings
				}
			});
		}

		getMoreUsersText(length)
		{
			const phrase = BX.message['MOBILE_EXT_NOTIFICATION_ITEM_MORE_USERS'].split('#COUNT#');

			return {
				start: phrase[0],
				end: length + phrase[1]
			}
		}
	}

})();