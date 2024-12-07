/**
 * @module im/messenger/controller/channel-creator/components/avatar-button
 */
jn.define('im/messenger/controller/channel-creator/components/avatar-button', (require, exports, module) => {
	/* globals media */
	include('media');
	include('InAppNotifier');
	const { Loc } = require('loc');
	const { getFile } = require('files/entry');
	const { FileConverter } = require('files/converter');
	/**
	 * @class AvatarButton
	 * @typedef {LayoutComponent<AvatarButtonProps, AvatarButtonState>} AvatarButton
	 */
	class AvatarButton extends LayoutComponent
	{
		/**
		 * @param {AvatarButtonProps} props
		 */
		constructor(props)
		{
			super(props);
			this.state.previewAvatarPath = props.previewAvatarPath ?? null;
		}

		render()
		{
			return View(
				{
					testId: 'btn_add_avatar',
					onClick: () => this.showImagePicker(),
				},
				Image(
					{
						style: {
							height: 76,
							width: 76,
							borderRadius: this.props.cornerRadius,
						},
						resizeMode: 'cover',
						uri: this.state.previewAvatarPath,
						svg: {
							content: this.state.previewAvatarPath === null ? this.props.defaultIconSvg : null,
						},
					},
				),
			);
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
			const url = data[0].url;
			media.showImageEditor(url)
				.then((targetFilePath) => this.addFile(targetFilePath))
				.catch((error) => console.error(error))
			;
		}

		addFile(filePath)
		{
			const converter = new FileConverter();

			converter.resize('avatarResize', {
				url: filePath,
				width: 1000,
				height: 1000,
			})
				.then((path) => getFile(path))
				.then((file) => {
					file.readMode = 'readAsDataURL';

					return file.readNext();
				})
				.then((fileData) => {
					if (fileData.content)
					{
						const { content } = fileData;
						this.setState({ previewAvatarPath: filePath });
						this.props.onAvatarSelected({
							previewAvatarPath: filePath,
							avatarBase64: content.slice(content.indexOf('base64,') + 7),
						});
					}
				})
				.catch((e) => console.error(e))
			;
		}
	}

	module.exports = { AvatarButton };
});
