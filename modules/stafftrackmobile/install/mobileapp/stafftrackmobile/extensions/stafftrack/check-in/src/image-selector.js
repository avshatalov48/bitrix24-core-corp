/**
 * @module stafftrack/check-in/image-selector
 */
jn.define('stafftrack/check-in/image-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { NotifyManager } = require('notify-manager');
	const { confirmDestructiveAction } = require('alert');
	const { showToast } = require('toast');
	const { outline: { alert } } = require('assets/icons');
	const { PureComponent } = require('layout/pure-component');
	const { Haptics } = require('haptics');
	const { Color, Indent, Component } = require('tokens');
	const { Uuid } = require('utils/uuid');
	const { getMimeType, getExtension } = require('utils/file');
	const { withCurrentDomain } = require('utils/url');
	const { UploaderClient } = require('uploader/client');

	const { Button, ButtonSize, ButtonDesign, Icon } = require('ui-system/form/buttons/button');
	const { BadgeButton, BadgeButtonDesign, BadgeButtonSize } = require('ui-system/blocks/badges/button');

	const { Analytics, SetupPhotoEnum } = require('stafftrack/analytics');

	/**
	 * @class ImageSelector
	 */
	class ImageSelector extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				isImageSelected: false,
				imagePreviewUri: null,
			};

			this.localFile = null;
			this.diskFile = null;
			this.imageUuid = null;
			this.isImageInProgress = false;

			this.uploader = null;

			this.showImagePicker = this.showImagePicker.bind(this);
			this.onUploadImageDone = this.onUploadImageDone.bind(this);
			this.onUploadImageError = this.onUploadImageError.bind(this);
			this.showCancelUploadAlert = this.showCancelUploadAlert.bind(this);
		}

		get diskFolderId()
		{
			return this.props.diskFolderId;
		}

		render()
		{
			return View(
				{},
				!this.state.imagePreviewUri && this.renderButton(),
				this.state.imagePreviewUri && this.renderImage(),
			);
		}

		renderButton()
		{
			return Button({
				size: ButtonSize.M,
				design: this.state.isImageSelected
					? ButtonDesign.FILLED
					: ButtonDesign.OUTLINE_NO_ACCENT,
				leftIcon: Icon.CAMERA,
				leftIconColor: this.state.isImageSelected
					? Color.baseWhiteFixed
					: Color.base4,
				color: this.state.isImageSelected
					? Color.accentMainPrimary
					: Color.base4,
				style: {
					marginLeft: Indent.M.toNumber(),
					paddingRight: Component.paddingLr.toNumber(),
					marginTop: Indent.XL2.toNumber(),
				},
				onClick: this.showImagePicker,
				testId: 'stafftrack-message-image-selector',
			});
		}

		renderImage()
		{
			return View(
				{
					style: {
						height: 36 + Indent.XL2.toNumber(),
						width: 36 + Indent.XL2.toNumber(),
						marginRight: Indent.XL.toNumber(),
					},
					onClick: this.showCancelUploadAlert,
				},
				Image({
					uri: withCurrentDomain(this.state.imagePreviewUri),
					style: {
						marginTop: Indent.XL2.toNumber(),
						width: 36,
						height: 36,
						borderRadius: 6,
						marginLeft: Indent.M.toNumber(),
					},
					resizeMode: 'cover',
				}),
				View(
					{
						style: {
							position: 'absolute',
							top: Indent.XL2.toNumber() - Indent.M.toNumber(),
							right: 0,
						},
					},
					BadgeButton({
						design: BadgeButtonDesign.GREY,
						size: BadgeButtonSize.SMALL,
						icon: Icon.CROSS,
					}),
				),
			);
		}

		showImagePicker()
		{
			if (this.props.readOnly)
			{
				this.props.showAlreadyCheckInToast();

				return;
			}

			if (this.isLoading() || !this.props.sendMessage)
			{
				return;
			}

			if (this.state.isImageSelected)
			{
				this.showCancelUploadAlert();

				return;
			}

			const items = [
				{
					id: 'mediateka',
					name: Loc.getMessage('M_STAFFTRACK_CHECK_IN_MEDIATEKA'),
				},
				{
					id: 'camera',
					name: Loc.getMessage('M_STAFFTRACK_CHECK_IN_CAMERA'),
				},
			];

			dialogs.showImagePicker(
				{
					settings: {
						resize: {
							previewMaxWidth: 640,
							previewMaxHeight: 640,
							targetWidth: -1,
							targetHeight: -1,
							sourceType: 1,
							encodingType: 0,
							mediaType: 0,
							allowsEdit: false,
							saveToPhotoAlbum: true,
							cameraDirection: 0,
						},
						editingMediaFiles: false,
						maxAttachedFilesCount: 1,
						attachButton: { items },
					},
				},
				(data) => this.handleImage(data),
			);
		}

		handleImage(data)
		{
			const image = data[0];

			if (!image)
			{
				return;
			}

			Analytics.sendSetupPhoto(SetupPhotoEnum.FROM_GALLERY);

			this.startLoading();

			if (image.dataAttributes)
			{
				this.diskFile = image;

				return;
			}

			const { type, url, previewUrl } = image;
			this.imageUuid = `stafftrack-shift-${Uuid.getV4()}`;
			const extension = getExtension(image.name);
			const name = extension === 'heic'
				? `${image.name.slice(0, Math.max(0, image.name.length - (extension.length)))}jpg`
				: image.name
			;
			image.uuid = this.imageUuid;

			this.localFile = {
				taskId: this.imageUuid,
				id: this.imageUuid,
				params: image,
				name,
				type,
				mimeType: getMimeType(type),
				url,
				previewUrl,
				folderId: this.diskFolderId,
				resize: {
					height: 1080,
					width: 1920,
					quality: 80,
				},
				onDestroyEventName: BX.FileUploadEvents.FILE_CREATED,
			};

			this.getUploader().addTask(this.localFile);
		}

		getFileId()
		{
			return this.localFile?.fileId;
		}

		getUploader()
		{
			if (!this.uploader)
			{
				this.uploader = new UploaderClient('stafftrack-shift');
				this.uploader.on('done', this.onUploadImageDone);
				this.uploader.on('error', this.onUploadImageError);
			}

			return this.uploader;
		}

		onUploadImageDone(id, eventData)
		{
			const { file: currentFile, result } = eventData;

			if (!currentFile || !result)
			{
				return;
			}

			if (currentFile.params.uuid !== this.imageUuid)
			{
				return;
			}

			if (result.status !== 'success')
			{
				this.handleErrorFileUpload();

				return;
			}

			const fileInfo = result.data?.file;
			if (fileInfo)
			{
				this.localFile.fileId = fileInfo.id;
				const { imagePreviewUri } = fileInfo.extra;

				this.stopLoading();
				this.setState({
					isImageSelected: true,
					imagePreviewUri,
				});
			}
			else
			{
				this.handleErrorFileUpload();
			}
		}

		onUploadImageError(id, eventData)
		{
			const { file: currentFile, error: result } = eventData;

			if (!currentFile || !result)
			{
				return;
			}

			if (currentFile.params.uuid !== this.imageUuid)
			{
				return;
			}

			this.handleErrorFileUpload();
		}

		handleErrorFileUpload()
		{
			this.stopLoading(false);

			showToast({
				message: Loc.getMessage('M_STAFFTRACK_CHECK_IN_IMAGE_SELECTOR_ERROR_TOAST'),
				svg: {
					content: alert(),
				},
				backgroundColor: Color.accentMainAlert.toHex(),
			});

			Haptics.notifyFailure();
		}

		showCancelUploadAlert()
		{
			if (!this.props.sendMessage)
			{
				return;
			}

			confirmDestructiveAction({
				title: Loc.getMessage('M_STAFFTRACK_CHECK_IN_IMAGE_SELECTOR_DELETE_PHOTO_TITLE'),
				description: '',
				destructionText: Loc.getMessage('M_STAFFTRACK_CHECK_IN_IMAGE_SELECTOR_DELETE_PHOTO_CONFIRM'),
				cancelText: Loc.getMessage('M_STAFFTRACK_CHECK_IN_IMAGE_SELECTOR_DELETE_PHOTO_CLOSE'),
				onDestruct: () => this.cancelUploading(),
			});

			Haptics.notifyWarning();
		}

		cancelUploading()
		{
			if (this.imageUuid)
			{
				this.getUploader().cancelTask(this.imageUuid);

				this.imageUuid = null;
				this.localFile = null;

				this.isImageInProgress = false;
				this.setState({
					isImageSelected: false,
					imagePreviewUri: null,
				});
			}
		}

		isLoading()
		{
			return this.isImageInProgress;
		}

		startLoading()
		{
			this.isImageInProgress = true;
			void NotifyManager.showLoadingIndicator();
		}

		stopLoading(success = true)
		{
			this.isImageInProgress = false;
			NotifyManager.hideLoadingIndicator(success);
		}
	}

	module.exports = { ImageSelector };
});
