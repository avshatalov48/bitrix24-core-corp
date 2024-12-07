/**
 * @module im/messenger/controller/dialog-creator/dialog-info/view
 */
jn.define('im/messenger/controller/dialog-creator/dialog-info/view', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Theme } = require('im/lib/theme');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { List } = require('im/messenger/lib/ui/base/list');
	const { cross } = require('im/messenger/assets/common');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ChatTitle, ChatAvatar } = require('im/messenger/lib/element');

	const previewIconLight = `<svg width="84" height="84" viewBox="0 0 84 84" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="42" cy="42" r="40.5" fill="white" stroke="url(#paint0_linear_17702_428925)" stroke-width="3"/><g clip-path="url(#clip0_17702_428925)"><path d="M6.19489 42.0004C6.19489 22.2258 22.2254 6.19531 42 6.19531C61.7746 6.19531 77.8051 22.2258 77.8051 42.0004C77.8051 61.775 61.7746 77.8055 42 77.8055C22.2254 77.8055 6.19489 61.775 6.19489 42.0004Z" fill="#EDF7FF"/><path d="M6.19489 42.0004C6.19489 22.2258 22.2254 6.19531 42 6.19531C61.7746 6.19531 77.8051 22.2258 77.8051 42.0004C77.8051 61.775 61.7746 77.8055 42 77.8055C22.2254 77.8055 6.19489 61.775 6.19489 42.0004Z" stroke="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M34.8876 43.9335C34.8876 40.0086 38.0754 36.8208 42.0003 36.8208C45.9253 36.8208 49.113 40.0086 49.113 43.9335C49.113 47.8585 45.9253 51.0462 42.0003 51.0462C38.0754 51.0462 34.8876 47.8585 34.8876 43.9335ZM37.2785 43.9335C37.2785 46.5435 39.3903 48.6554 42.0003 48.6554C44.6103 48.6554 46.7222 46.5435 46.7222 43.9335C46.7222 41.3236 44.6103 39.2117 42.0003 39.2117C39.3903 39.2117 37.2785 41.3236 37.2785 43.9335Z" fill="#0075FF"/><path fill-rule="evenodd" clip-rule="evenodd" d="M50.2085 32.8553H53.9541C56.1457 32.8553 57.9388 34.6484 57.9388 36.84V51.9819C57.9388 54.1734 56.1457 55.9666 53.9541 55.9666H30.0459C27.8543 55.9666 26.0612 54.1734 26.0612 51.9819V36.84C26.0612 34.6484 27.8543 32.8553 30.0459 32.8553H33.6719L35.7041 29.8269C36.4413 28.7112 37.6766 28.0537 39.0114 28.0537H44.7893C46.1042 28.0537 47.3395 28.6913 48.0766 29.7671L50.2085 32.8553ZM53.9541 53.5558C54.8307 53.5558 55.548 52.8386 55.548 51.9619V36.8201C55.548 35.9434 54.8307 35.2262 53.9541 35.2262H50.2085C49.4115 35.2262 48.6744 34.8277 48.236 34.1902L46.1042 31.102C45.8054 30.6836 45.3073 30.4246 44.7893 30.4246H39.0114C38.4934 30.4246 37.9953 30.7035 37.6965 31.1419L35.6643 34.1702C35.2061 34.8277 34.4689 35.2262 33.6719 35.2262H30.0459C29.1692 35.2262 28.452 35.9434 28.452 36.8201V51.9619C28.452 52.8386 29.1692 53.5558 30.0459 53.5558H53.9541Z" fill="#0075FF"/></g><defs><linearGradient id="paint0_linear_17702_428925" x1="11.0339" y1="1.77966" x2="44.1356" y2="49.4746" gradientUnits="userSpaceOnUse"><stop stop-color="#86FFC7"/><stop offset="1" stop-color="#0075FF"/></linearGradient><clipPath id="clip0_17702_428925"><path d="M5.69489 42.0004C5.69489 21.9497 21.9492 5.69531 42 5.69531C62.0507 5.69531 78.3051 21.9497 78.3051 42.0004C78.3051 62.0511 62.0507 78.3055 42 78.3055C21.9492 78.3055 5.69489 62.0511 5.69489 42.0004Z" fill="white"/></clipPath></defs></svg>`;
	const previewIconDark = `<svg width="84" height="84" viewBox="0 0 84 84" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="42" cy="42" r="40.5" fill="#0F0F0F" stroke="url(#paint0_linear_17800_1227308)" stroke-width="3"/><g clip-path="url(#clip0_17800_1227308)"><path d="M5.69492 42.0004C5.69492 21.9497 21.9493 5.69531 42 5.69531C62.0507 5.69531 78.3051 21.9497 78.3051 42.0004C78.3051 62.0511 62.0507 78.3055 42 78.3055C21.9493 78.3055 5.69492 62.0511 5.69492 42.0004Z" fill="#1A2A33"/><path fill-rule="evenodd" clip-rule="evenodd" d="M34.8877 43.9335C34.8877 40.0086 38.0754 36.8208 42.0004 36.8208C45.9253 36.8208 49.1131 40.0086 49.1131 43.9335C49.1131 47.8585 45.9253 51.0462 42.0004 51.0462C38.0754 51.0462 34.8877 47.8585 34.8877 43.9335ZM37.2785 43.9335C37.2785 46.5435 39.3904 48.6554 42.0004 48.6554C44.6103 48.6554 46.7222 46.5435 46.7222 43.9335C46.7222 41.3236 44.6103 39.2117 42.0004 39.2117C39.3904 39.2117 37.2785 41.3236 37.2785 43.9335Z" fill="#1587FA"/><path fill-rule="evenodd" clip-rule="evenodd" d="M50.2085 32.8553H53.9541C56.1457 32.8553 57.9388 34.6484 57.9388 36.84V51.9819C57.9388 54.1734 56.1457 55.9666 53.9541 55.9666H30.0459C27.8543 55.9666 26.0612 54.1734 26.0612 51.9819V36.84C26.0612 34.6484 27.8543 32.8553 30.0459 32.8553H33.672L35.7042 29.8269C36.4413 28.7112 37.6766 28.0537 39.0115 28.0537H44.7893C46.1042 28.0537 47.3395 28.6913 48.0767 29.7671L50.2085 32.8553ZM53.9541 53.5558C54.8308 53.5558 55.548 52.8386 55.548 51.9619V36.8201C55.548 35.9434 54.8308 35.2262 53.9541 35.2262H50.2085C49.4116 35.2262 48.6744 34.8277 48.2361 34.1902L46.1043 31.102C45.8054 30.6836 45.3073 30.4246 44.7893 30.4246H39.0115C38.4935 30.4246 37.9954 30.7035 37.6965 31.1419L35.6643 34.1702C35.2061 34.8277 34.4689 35.2262 33.672 35.2262H30.0459C29.1693 35.2262 28.452 35.9434 28.452 36.8201V51.9619C28.452 52.8386 29.1693 53.5558 30.0459 53.5558H53.9541Z" fill="#1587FA"/></g><defs><linearGradient id="paint0_linear_17800_1227308" x1="11.0339" y1="1.77966" x2="44.1356" y2="49.4746" gradientUnits="userSpaceOnUse"><stop stop-color="#0A4C2E"/><stop offset="1" stop-color="#1B79E6"/></linearGradient><clipPath id="clip0_17800_1227308"><path d="M5.69492 42.0004C5.69492 21.9497 21.9493 5.69531 42 5.69531C62.0507 5.69531 78.3051 21.9497 78.3051 42.0004C78.3051 62.0511 62.0507 78.3055 42 78.3055C21.9493 78.3055 5.69492 62.0511 5.69492 42.0004Z" fill="white"/></clipPath></defs></svg>`;

	class DialogInfoView extends LayoutComponent
	{
		/**
		 *
		 * @param { Object } props
		 * @param { dialogDTO } props.dialogDTO
		 * @param { Function } props.onAvatarSetClick
		 */
		constructor(props)
		{
			super(props);

			/** @type DialogDTO */
			this.dialogDTO = props.dialogDTO;
			this.store = serviceLocator.get('core').getStore();
		}

		render()
		{
			const recipientList = this.prepareRecipientListToRender();

			return View(
				{},
				View(
					{
						style: {
							paddingTop: 16,
							paddingLeft: 18,
							paddingBottom: 16,
							paddingRight: 18,
							flexDirection: 'row',
							alignItems: 'center',
							flexWrap: 2,
							justifyContent: 'space-between',
							backgroundColor: Theme.colors.bgContentPrimary,
							borderRadius: 12,
							marginBottom: 20,
						},
					},
					this.getAvatarButton(),

					View(
						{
							style: {
								flexDirection: 'column',
								flexGrow: 3
							}
						},
						new ChatTitleInput({dialogDTO: this.dialogDTO})
					)
				),
				View(
					{
						style: {
							flex: 1
						}
					},
					new List({
						recentText: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_RECIPIENT_COUNT') + recipientList.length,
						itemList: recipientList,
						isScrollable: () => recipientList.length > 6,
					}),
				),
			);
		}

		prepareRecipientListToRender()
		{
			const recipientList = [];
			recipientList.push(this.getCreatorItem());

			return recipientList.concat(
				this.dialogDTO
					.getRecipientList()
					.filter(item => item.id !== MessengerParams.getUserId())
					.map(item => {
						return {
							data: item
						};
					})
			);
		}

		setAvatar(avatarUri)
		{
			this.previewImageRef.setAvatar(avatarUri);
		}

		getAvatarButton()
		{
			return new PreviewImage({
				uri: this.dialogDTO.getAvatarPreview(),
				callback: () => {
					this.props.onAvatarSetClick();
				},
				ref: ref => this.previewImageRef = ref
			});
		}

		getCreatorItem()
		{
			const creatorData = this.store.getters['usersModel/getById'](MessengerParams.getUserId());

			const chatTitle = ChatTitle.createFromDialogId(MessengerParams.getUserId());
			const chatAvatar = ChatAvatar.createFromDialogId(MessengerParams.getUserId());

			return {
				data: {
					id: MessengerParams.getUserId(),
					title: chatTitle.getTitle(),
					subtitle: chatTitle.getDescription(),
					avatarUri: chatAvatar.getAvatarUrl(),
					avatarColor: creatorData.color,
					isYou: true,
					isYouTitle: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_IS_YOU'),
				},
				type: 'chats',
				selected: false,
				disable: false,
			};
		}
	}

	class PreviewImage extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state.avatar = props.uri || null;
			this.previewIcon = Theme.getInstance().getId() === 'light'
				? previewIconLight
				: previewIconDark
			;

			props.ref(this);
		}

		render()
		{
			return View(
				{
					testId: 'btn_add_avatar',
					onClick: () => this.props.callback(),
				},
				Image(
					{
						style: {
							height: 76,
							width: 76,
							borderRadius: 38,
						},
						resizeMode: 'cover',
						uri: this.state.avatar,
						svg: {
							content: this.state.avatar === null ? this.previewIcon : null,
						},
					}
				),
			);
		}

		setAvatar(avatarUri)
		{
			this.setState({avatar: avatarUri});
		}
	}



	class ChatTitleInput extends LayoutComponent
	{

		constructor(props)
		{
			super(props);
			/** @type DialogDTO */
			this.dialogDTO = props.dialogDTO;
			this.state.isTextEmpty = true;
			this.state.isFocused = false;
		}

		render()
		{
			return View(
				{
					style: {
						marginLeft: 10,
						flexDirection: 'row',
						flexGrow: 2,
						borderBottomWidth: 1,
						borderBottomColor: this.state.isFocused
							? Theme.colors.accentMainPrimary
							: Theme.colors.bgSeparatorSecondary,
					},
				},
				TextField({
					placeholder: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_CHAT_TITLE_PLACEHOLDER'),
					placeholderTextColor: Theme.colors.base5,
					value: this.dialogDTO.getTitle(),
					multiline: false,
					style: {
						flexGrow: 2,
						color: Theme.colors.base1,
						fontSize: 18,
						marginRight: 5,
					},
					onChangeText: (text) => {
						if (text !== '' && this.state.isTextEmpty)
						{
							this.setState({isTextEmpty: false});
						}
						if (text === '' && !this.state.isTextEmpty)
						{
							this.setState({isTextEmpty: true});
						}
						this.dialogDTO.setTitle(text);
					},
					onFocus: () => {
						this.setState({ isFocused: true });
					},
					onBlur: () => {
						this.setState({ isFocused: false });
					},
					onSubmitEditing: () => this.textRef.blur(),
					ref: ref => this.textRef = ref,
				}),
				View(
					{
						style: {
							justifyContent: 'flex-end',
							marginBottom: 4,
						},
						clickable: true,
						onClick: () => {
							this.textRef.clear();
						},
					},
					Image(
						{
							style: {
								height: 24,
								width: 24,
								opacity: this.state.isTextEmpty ? 0 : 1,
							},
							resizeMode: 'contain',
							svg: {
								content: cross({ color: Theme.colors.base4, strokeWight: 0 }),
							},
						}
					),
				),
			);
		}

	}


	module.exports = { DialogInfoView };
});
