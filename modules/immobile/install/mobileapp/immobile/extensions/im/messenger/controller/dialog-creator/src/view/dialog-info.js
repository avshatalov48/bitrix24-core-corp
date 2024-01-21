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
	const AppTheme = require('apptheme');

	const previewIcon = `<svg width="76" height="76" viewBox="0 0 76 76" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="76" height="76" rx="38" fill="${AppTheme.colors.bgContentTertiary}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M48.5509 30.6862H43.2754C42.8986 29.2028 42.5218 27.7194 41.0145 27.7194H34.9855C33.4782 27.7194 33.1014 29.2028 32.7246 30.6862H27.4491C26.6202 30.6862 25.9419 31.3538 25.9419 32.1696V45.5202C25.9419 46.3361 26.6202 47.0036 27.4491 47.0036H48.5509C49.3799 47.0036 50.0582 46.3361 50.0582 45.5202V32.1696C50.0581 31.3538 49.3799 30.6862 48.5509 30.6862ZM34.1328 38.6111C34.1328 40.7131 35.8642 42.4171 38 42.4171C40.1358 42.4171 41.8673 40.7131 41.8673 38.6111C41.8673 36.5091 40.1359 34.8051 38 34.8051C35.8642 34.8051 34.1328 36.5091 34.1328 38.6111ZM38 43.8078C35.0838 43.8078 32.7197 41.4812 32.7197 38.6111C32.7197 35.741 35.0838 33.4144 38 33.4144C40.9163 33.4144 43.2803 35.741 43.2803 38.6111C43.2803 41.4812 40.9163 43.8078 38 43.8078ZM47.4512 34.3456H45.4987V33.0441H47.4512V34.3456Z" fill="${AppTheme.colors.base5}"/></svg>`;

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
							paddingTop: 16,
							paddingLeft: 18,
							paddingBottom: 16,
							paddingRight: 18,
							flexDirection: 'row',
							alignItems: 'center',
							flexWrap: 2,
							justifyContent: 'space-between',
							backgroundColor: AppTheme.colors.bgContentPrimary,
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
						borderBottomColor: AppTheme.colors.bgSeparatorSecondary,
					},
				},
				TextField({
					placeholder: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_CHAT_TITLE_PLACEHOLDER'),
					placeholderTextColor: AppTheme.colors.base5,
					value: this.dialogDTO.getTitle(),
					multiline: false,
					style: {
						flexGrow: 2,
						color: AppTheme.colors.base1,
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
								content: cross({ color: AppTheme.colors.base4, strokeWight: 0 }),
							},
						}
					),
				),
			);
		}

	}


	module.exports = { DialogInfoView };
});
