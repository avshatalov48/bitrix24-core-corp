/**
 * @module im/messenger/controller/dialog-creator/dialog-info
 */
jn.define('im/messenger/controller/dialog-creator/dialog-info', (require, exports, module) => {

	include("media");
	include("InAppNotifier");

	const { Loc } = require('loc');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType } = require('im/messenger/const');
	const { getFile } = require('files/entry');
	const { FileConverter } = require('files/converter')
	const { DialogInfoView } = require('im/messenger/controller/dialog-creator/dialog-info/view');


	class DialogInfo
	{
		static open({ userList, dialogDTO}, parentLayout = null)
		{
			const widget = new DialogInfo(userList, dialogDTO, parentLayout);
			widget.show();
		}


		constructor(userList, dialogDTO, parentLayout)
		{
			this.dialogDTO = dialogDTO;
			this.layout = parentLayout;
			this.view = new DialogInfoView({
				dialogDTO: dialogDTO,
				onAvatarSetClick: () => {
					this.showImagePicker();
				}
			});
		}

		show()
		{
			const config = {
				onReady: layoutWidget =>
				{
					this.layout = layoutWidget;
					layoutWidget.showComponent(this.view);
				},
				onError: error => reject(error),
			};

			if (this.layout !== null)
			{
				this.layout.openWidget(
					'layout',
					config,
				).then(layoutWidget => {
					this.configureWidget(layoutWidget);
				});

				return;
			}

			PageManager.openWidget(
				'layout',
				config,
			).then(layoutWidget => {
				this.configureWidget(layoutWidget);
			});
		}


		configureWidget(layoutWidget)
		{
			layoutWidget.setRightButtons([
				{
					id: "create",
					name: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_BUTTON_CREATE'),
					callback: () => {
						this.createChat();
					}
				},
			]);
		}



		showImagePicker()
		{
			const items = [
				{
					id: 'mediateka',
					name: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_CAMERA'),
				},
				{
					id: 'camera',
					name: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_MEDIATEKA'),
				},
			];

			dialogs.showImagePicker({
					settings: {
						resize: {
							targetWidth: -1,
							targetHeight: -1,
							sourceType: 1,
							encodingType: 0,
							mediaType: 0,
							allowsEdit: true,
							saveToPhotoAlbum: true,
							cameraDirection: 0
						},
						editingMediaFiles: false,
						maxAttachedFilesCount: 1,
						attachButton: { items }
					}
				},
				data => this.editorFile(data)
			);
		}

		editorFile(data)
		{
			const url = data[0].url;
			media.showImageEditor(url)
				.then(targetFilePath => this.addFile(targetFilePath))
				.catch(error => console.error(error))
			;
		}


		addFile(filePath)
		{

			const converter = new FileConverter()

			converter.resize("avatarResize", {
				url: filePath,
				width: 1000,
				height: 1000,
			}).then(path => {
				getFile(path)
					.then(file => {
						file.readMode = 'readAsDataURL';
						file.readNext()
							.then(fileData => {
								if (fileData.content)
								{
									let {content} = fileData;
									this.dialogDTO.setAvatar(content.substr(content.indexOf("base64,") + 7));

									this.dialogDTO.setAvatarPreview(filePath);
									this.view.setAvatar(filePath);
								}
							})
							.catch(e => console.error(e));
					})
					.catch(e => console.error(e));
			});
		}

		createChat()
		{
			let users = [];
			if (this.dialogDTO.getRecipientList())
			{
				this.dialogDTO.getRecipientList().forEach((recipient) => {
					users.push(recipient.id);
				});
			}

			let config = {
				'TYPE': this.dialogDTO.getType(),
				'TITLE': this.dialogDTO.getTitle(),
			};
			if (users.length > 0)
			{
				config.USERS = users;
			}
			if (this.dialogDTO.getAvatar())
			{
				config.AVATAR = this.dialogDTO.getAvatar();
			}

			BX.rest.callMethod('im.chat.add', config)
				.then((result) =>
				{
					let chatId = parseInt(result.data());
					if (chatId > 0)
					{
						this.openDialog('chat' + chatId);

						this.layout.close();
					}
				})
				.catch((result) =>
				{
					let error = result.error();
					if (error.ex.error === 'NO_INTERNET_CONNECTION')
					{
						console.error("ChatCreate.event.onChatCreate - error: connection error", error.ex);
						this.alert(Loc.getMessage('IM_CREATE_CONNECTION_ERROR'));
					}
					else
					{
						console.error("ChatCreate.event.onChatCreate - error: we have some problems on server\n", result.answer);
						this.alert(Loc.getMessage('IMMOBILE_DIALOG_CREATOR_API_ERROR'));
					}
				});

			return true;
		}

		alert(message)
		{
			InAppNotifier.showNotification({
				backgroundColor: "#E6000000",
				message: message,
			});
		}

		openDialog(dialogId)
		{
			BX.rest.callMethod('im.dialog.get', {DIALOG_ID: dialogId})
				.then(result => {
					const chatData = result.data();
					MessengerEmitter.emit(EventType.messenger.openDialog, {
						dialogId: dialogId,
						dialogTitleParams: {
							name: chatData.name,
							description: this.dialogDTO.getType() === 'CHAT' ? Loc.getMessage('IMMOBILE_DIALOG_CREATOR_CHAT_NEW') : Loc.getMessage('IMMOBILE_DIALOG_CREATOR_CHANNEL_NEW'),
							avatar: chatData.avatar,
							color: chatData.color,
						}
					});
				})
		}
	}

	module.exports = { DialogInfo };
});