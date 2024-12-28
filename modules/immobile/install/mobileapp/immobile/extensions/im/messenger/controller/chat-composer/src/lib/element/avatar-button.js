/**
 * @module im/messenger/controller/chat-composer/lib/element/avatar-button
 */
jn.define('im/messenger/controller/chat-composer/lib/element/avatar-button', (require, exports, module) => {
	/* globals media, include */
	include('media');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Corner, Color } = require('tokens');
	const { getFile } = require('files/entry');
	const { FileConverter } = require('files/converter');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { Avatar, AvatarShape } = require('ui-system/blocks/avatar');

	const { UuidManager } = require('im/messenger/lib/uuid-manager');

	const { ComposerDialogType } = require('im/messenger/controller/chat-composer/lib/const');

	/**
	 * @class AvatarButton
	 * @typedef {LayoutComponent<ElementAvatarButtonProps, ElementAvatarButtonState>} AvatarButton
	 */
	class AvatarButton extends LayoutComponent
	{
		/**
		 * @param {ElementAvatarButtonProps} props
		 */
		constructor(props)
		{
			super(props);
			this.state = {
				preview: Type.isStringFilled(props.preview) ? props.preview : null,
				canClick: Type.isBoolean(this.props.canClick) ? this.props.canClick : true,
			};

			this.showImagePicker = this.showImagePicker.bind(this);
		}

		get testId()
		{
			return 'avatar-button';
		}

		get type()
		{
			switch (this.props.type)
			{
				case ComposerDialogType.channel:
				{
					return AvatarShape.SQUARE;
				}

				case ComposerDialogType.groupChat:
				{
					return AvatarShape.CIRCLE;
				}

				default:
				{
					return AvatarShape.CIRCLE;
				}
			}
		}

		render()
		{
			return View(
				{},
				Avatar({
					testId: this.testId,
					onClick: this.state.canClick ? this.showImagePicker : null,
					shape: this.type,
					radius: Corner.L.toNumber(),
					size: 84,
					accent: !this.state.preview,
					backgroundColor: Color.accentSoftBlue3,
					backBorderWidth: 0,
					...this.getContent(),
					id: 1,
				}),
			);
		}

		getContent()
		{
			if (this.state.preview)
			{
				return {
					uri: this.state.preview,
				};
			}

			return {
				icon: IconView({
					size: 48,
					color: Color.accentMainPrimary,
					icon: Icon.CAMERA,
				}),
			};
		}

		showImagePicker()
		{
			const items = [
				{
					id: 'camera',
					name: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_CAMERA'),
				},
				{
					id: 'mediateka',
					name: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_MEDIATEKA'),
				},
			];

			dialogs.showImagePicker(
				{
					settings: {
						resize: {
							targetWidth: -1,
							targetHeight: -1,
							sourceType: 1,
							encodingType: 0,
							mediaType: 0,
							allowsEdit: true,
							saveToPhotoAlbum: true,
							cameraDirection: 0,
						},
						editingMediaFiles: false,
						maxAttachedFilesCount: 1,
						attachButton: { items },
					},
				},
				(data) => this.editorFile(data),
			);
		}

		editorFile(data)
		{
			if (!Type.isArrayFilled(data))
			{
				return;
			}

			const url = data[0].url;
			media.showImageEditor(url)
				.then((targetFilePath) => this.addFile(targetFilePath))
				.catch((error) => console.error(error))
			;
		}

		addFile(filePath)
		{
			const converter = new FileConverter();
			const uuid = UuidManager.getInstance().getActionUuid();

			converter.resize(`im-resize-avatar-${uuid}`, {
				url: filePath,
				width: 1000,
				height: 1000,
			})
				.then((path) => getFile(path))
				.then((file) => {
					// eslint-disable-next-line no-param-reassign
					file.readMode = 'readAsDataURL';

					return file.readNext();
				})
				.then((fileData) => {
					if (fileData.content)
					{
						const { content } = fileData;
						this.setState({ preview: filePath });
						this.props.onChange({
							preview: filePath,
							avatar: content.slice(content.indexOf('base64,') + 7),
						});
					}
				})
				.catch((e) => console.error(e))
				.finally(() => {
					UuidManager.getInstance().removeActionUuid(uuid);
				})
			;
		}
	}

	module.exports = {
		AvatarButton,
	};
});
