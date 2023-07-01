/**
 * @module im/messenger/controller/dialog-creator/dialog-info/view
 */
jn.define('im/messenger/controller/dialog-creator/dialog-info/view', (require, exports, module) => {

	const { Loc } = require('loc');
	const { core } = require('im/messenger/core');
	const { List } = require('im/messenger/lib/ui/base/list');
	const { cross } = require('im/messenger/assets/common');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ChatTitle, ChatAvatar } = require('im/messenger/lib/element');


	const previewIcon = `<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M40 80C62.0914 80 80 62.0914 80 40C80 17.9086 62.0914 0 40 0C17.9086 0 0 17.9086 0 40C0 62.0914 17.9086 80 40 80Z" fill="#D7D7D7"/><path fill-rule="evenodd" clip-rule="evenodd" d="M55.75 28.75H47.875C47.3125 26.5 46.75 24.25 44.5 24.25H35.5C33.25 24.25 32.6875 26.5 32.125 28.75H24.25C23.0125 28.75 22 29.7625 22 31V51.25C22 52.4875 23.0125 53.5 24.25 53.5H55.75C56.9874 53.5 58 52.4875 58 51.25V31C57.9999 29.7625 56.9874 28.75 55.75 28.75ZM33.9367 41.1974C33.9367 44.577 36.6764 47.3167 40.056 47.3167C43.4355 47.3167 46.1753 44.577 46.1753 41.1974C46.1753 37.8178 43.4356 35.0782 40.056 35.0782C36.6764 35.0782 33.9367 37.8178 33.9367 41.1974ZM40.056 49.5526C35.4416 49.5526 31.7008 45.8119 31.7008 41.1974C31.7008 36.583 35.4415 32.8422 40.056 32.8422C44.6705 32.8422 48.4112 36.5829 48.4112 41.1974C48.4111 45.8119 44.6705 49.5526 40.056 49.5526ZM54.9554 33.6663H51.8658V31.5738H54.9554V33.6663Z" fill="white"/></svg>`;

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
			this.store = core.getStore();
		}

		render()
		{
			const recipientList = this.prepareRecipientListToRender();

			return View(
				{},
				View(
					{
						style: {
							paddingTop: 15,
							paddingLeft: 20,
							flexDirection: 'row',
							alignItems: 'center',
							flexWrap: 2,
							justifyContent: 'space-between'
						}
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
					View(
						{
							style: {
								backgroundColor: '#f6f7f8',
								paddingLeft: 15,
								marginTop: 15,
								height: 30,
								alignContent: 'center',

							}
						},
						Text(
							{
								style: {
									marginTop: 5,
									color: '#666'
								},
								text: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_RECIPIENT_COUNT') + recipientList.length,
							}
						)
					),
					new List({
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

			console.log(this.dialogDTO.getRecipientList());
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
			const creatorData = this.store.getters['usersModel/getUserById'](MessengerParams.getUserId());

			const chatTitle = ChatTitle.createFromDialogId(MessengerParams.getUserId(), {showItsYou: true});
			const chatAvatar = ChatAvatar.createFromDialogId(MessengerParams.getUserId());

			return {
				data: {
					id: MessengerParams.getUserId(),
					title: chatTitle.getTitle(),
					subtitle: chatTitle.getDescription(),
					avatarUri: chatAvatar.getAvatarUrl(),
					avatarColor: creatorData.color,
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
							height: 80,
							width: 80,
							borderRadius: 40,
						},
						resizeMode: 'cover',
						uri: this.state.avatar,
						svg: {
							content: this.state.avatar === null ? previewIcon : null,
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
						borderBottomColor: '#e9e9e9',
					},
				},
				TextField({
					placeholder: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_CHAT_TITLE_PLACEHOLDER'),
					placeholderTextColor: '#cacacb',
					value: this.dialogDTO.getTitle(),
					multiline: false,
					style: {
						flexGrow: 2,
						color: '#333333',
						fontSize: 18,
						marginRight: 5,
					},
					onChangeText: (text) => {
						if (text !== '')
						{
							this.setState({isTextEmpty: false});
						}
						if (text === '')
						{
							this.setState({isTextEmpty: true});
						}
						this.dialogDTO.setTitle(text);
					},
					onSubmitEditing: () => this.textRef.blur(),
					ref: ref => this.textRef = ref,
				}),
				View(
					{
						style: {
							marginRight: 10,
							justifyContent: 'flex-end',
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
								content: cross({ color: '#cbcbcb', strokeWight: 0 }),
							},
						}
					),
				),
			);
		}

	}


	module.exports = { DialogInfoView };
});
