(() => {
	const MEDIA_TYPE = {
		IMAGE: 'image',
		VIDEO: 'video',
		FILE: 'file'
	}

	/**
	 * @class Fields.FileField
	 */
	class FileField extends Fields.BaseField
	{
		constructor(props)
		{
			super(props);

			/** @type {UI.FileAttachment} */
			this.fileAttachmentRef = null;
			this.fileAttachmentWidget = null;
		}

		showEditMenu()
		{
			return BX.prop.getBoolean(this.props, 'showEditMenu', true);
		}

		renderMenu()
		{
			if (this.isReadOnly() || this.isEmpty())
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row'
					}
				},
				View({
					style: {
						width: 1,
						backgroundColor: '#DBDDE0',
						marginRight: 11.5
					}
				}),
				fileMenu(this.openMenu.bind(this))
			)
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);
			this.isInitialValueEmpty = !CommonUtils.isNotEmptyObject(this.getConfig().fileInfo);
			if (this.fileAttachmentRef)
			{
				this.fileAttachmentRef.onChangeAttachments(this.getFilesInfo(newProps.value));
			}
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				fileInfo: BX.prop.getObject(config, 'fileInfo', {}),
				mediaType: BX.prop.getString(config, 'mediaType', MEDIA_TYPE.FILE)
			};
		}

		canFocusTitle()
		{
			return BX.prop.getBoolean(this.props, 'canFocusTitle', false);
		}

		getContentClickHandler()
		{
			return null;
		}

		// workaround to focus if already focused (because there is no events for file picker close)
		setFocus(callback = null)
		{
			Fields.FocusManager.blurFocusedFieldIfHas(this, () => {
				this.setState({focus: true}, () => {
					Fields.FocusManager.setFocusedField(this);

					callback && callback();
					this.props.onFocusIn && this.props.onFocusIn();
				});
			});
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			return View(
				{
					style: this.styles.fieldWrapper
				},
				this.getFilesView()
			)
		}

		renderEditableContent()
		{
			return View(
				{
					style: this.styles.fieldWrapper
				},
				this.isEmpty() ? this.renderAddButton() : this.getFilesView()
			);
		}

		renderAddButton()
		{
			return View(
				{
					style: this.styles.addFileButtonContainer,
					onClick: () => this.setFocus(this.openFilePicker.bind(this))
				},
				this.getMediaTypeId(this.getConfig().mediaType) === 2 ? Image(
					{
						style: this.styles.addFileButtonIcon,
						resizeMode: 'center',
						svg: {
							content: svgImages.file.content.replace(/%color%/g, this.getButtonStyles().iconColor)
						}
					}
				) : null,
				Text(
					{
						style: this.styles.addFileButtonText,
						text: this.getAddButtonText(this.getConfig().mediaType)
					}
				)
			)
		}

		getFilesInfo(values)
		{
			const fileInfo = this.getConfig().fileInfo;

			return (
				values
					.map((value) => {
						if (BX.type.isNumber(Number(value)))
						{
							return fileInfo[value];
						}

						return value;
					})
					.filter((file) => file)
			);
		}

		getFilePath(url)
		{
			if (url && url.indexOf('file://') !== 0)
			{
				url = currentDomain + url;
			}

			return url;
		}

		getFilesView()
		{
			const files = this.getFilesInfo(this.props.value);

			const fieldWidth = device.screen.width - 20 * 2 - 40 * 2 - 25;
			const visibleFilesCount = Math.floor(fieldWidth / 50);

			const hiddenFilesCount = Math.max(files.length - visibleFilesCount, 0);
			const visibleFiles = files.slice(0, files.length - hiddenFilesCount);

			return View(
				{
					style: {
						flexWrap: 'no-wrap',
						flexDirection: 'row',
						borderWidth: 0
					}
				},
				View(
					{
						style: this.styles.filesListWrapper
					},
					...visibleFiles.map((file, index) => filePreview(file, index, files, this.isInitialValueEmpty, this.onDeleteFile.bind(this))),
					hiddenFilesCount > 0 ? this.renderHiddenFilesCounter(hiddenFilesCount) : null,
				),
				this.renderMenu()
			)
		}

		openMenu()
		{
			let actions = [];
			if (this.isReadOnly())
			{
				actions = [
					{
						id: 'open',
						title: BX.message('FIELDS_FILE_OPEN_GALLERY'),
						data: {
							svgIcon: `<svg width="18" height="22" viewBox="0 0 18 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.7509 19.4851C15.7509 19.6597 15.6065 19.7998 15.4301 19.7998H2.57123C2.39342 19.7998 2.25034 19.6597 2.25034 19.4851V2.51483C2.25034 2.34168 2.39342 2.20014 2.57123 2.20014H8.67908C8.85688 2.20014 8.99997 2.34168 8.99997 2.51483V8.48721C8.99997 8.66036 9.14443 8.80053 9.32224 8.80053H15.4301C15.6065 8.80053 15.7509 8.94209 15.7509 9.11524V19.4851ZM11.2503 3.39708C11.2503 3.33387 11.3045 3.28028 11.3711 3.28028C11.4031 3.28028 11.4337 3.29264 11.4559 3.31463L14.6119 6.39977C14.6592 6.44511 14.6592 6.51932 14.6119 6.56604C14.5883 6.58803 14.5591 6.60041 14.5258 6.60041H11.3711C11.3045 6.60041 11.2503 6.54681 11.2503 6.48222V3.39708ZM17.529 6.14004L11.6031 0.34493C11.3781 0.125054 11.0711 0 10.7502 0H1.20574C0.53897 0 0 0.527702 0 1.17908V20.8223C0 21.4723 0.53897 22 1.20574 22H16.7955C17.4596 22 18 21.4723 18 20.8223V7.25179C18 6.83403 17.8305 6.43412 17.529 6.14004ZM13.0978 11.0007H4.90073C4.67988 11.0007 4.49929 11.1766 4.49929 11.3937V12.8078C4.49929 13.0235 4.67988 13.2008 4.90073 13.2008H13.0978C13.3201 13.2008 13.5006 13.0235 13.5006 12.8078V11.3937C13.5006 11.1766 13.3201 11.0007 13.0978 11.0007ZM4.9827 8.80053H6.26761C6.53431 8.80053 6.74963 8.5889 6.74963 8.3278V7.07176C6.74963 6.81066 6.53431 6.60041 6.26761 6.60041H4.9827C4.71599 6.60041 4.49929 6.81066 4.49929 7.07176V8.3278C4.49929 8.5889 4.71599 8.80053 4.9827 8.80053ZM13.0978 15.4009H4.90073C4.67988 15.4009 4.49929 15.5755 4.49929 15.7926V17.2081C4.49929 17.4238 4.67988 17.6011 4.90073 17.6011H13.0978C13.3201 17.6011 13.5006 17.4238 13.5006 17.2081V15.7926C13.5006 15.5755 13.3201 15.4009 13.0978 15.4009Z" fill="#525C69"/></svg>`
						},
						onClickCallback: () => new Promise((resolve) => {
							menu.close(() => {
								this.onOpenAttachmentList();
							});
							resolve();
						}),
					}
				];
			}
			else
			{
				actions = [
					{
						id: 'add',
						title: this.getAddButtonText(this.getConfig().mediaType),
						data: {
							svgIcon: `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M21.5239 12.712C20.7847 12.712 20.0694 12.8141 19.3913 13.0048H14.6384C14.4784 13.0048 14.3484 12.8773 14.3484 12.7198V7.28735C14.3484 7.12985 14.2197 7.0011 14.0597 7.0011H8.56344C8.40344 7.0011 8.27469 7.12985 8.27469 7.28735V22.7236C8.27469 22.8823 8.40344 23.0098 8.56344 23.0098H14.0528C14.2872 23.728 14.6223 24.4006 15.0414 25.0111H7.33469C6.73469 25.0111 6.24969 24.5311 6.24969 23.9398V6.07235C6.24969 5.47985 6.73469 4.99985 7.33469 4.99985H15.9234C16.2122 4.99985 16.4884 5.1136 16.6909 5.3136L22.0234 10.5848C22.2947 10.8523 22.4472 11.2161 22.4472 11.5961V12.7657C22.1443 12.7302 21.8362 12.712 21.5239 12.712ZM10.6597 15.0061H15.9751C15.3903 15.5894 14.897 16.2642 14.5184 17.0073H10.6597C10.4609 17.0073 10.2984 16.8461 10.2984 16.6498V15.3636C10.2984 15.1661 10.4609 15.0061 10.6597 15.0061ZM10.6597 19.0086H13.8215C13.7198 19.5131 13.6664 20.035 13.6664 20.5694C13.6664 20.7172 13.6705 20.8641 13.6786 21.0098H10.6597C10.4609 21.0098 10.2984 20.8486 10.2984 20.6523V19.3648C10.2984 19.1673 10.4609 19.0086 10.6597 19.0086ZM16.3734 8.08985C16.3734 8.03235 16.4222 7.9836 16.4822 7.9836C16.5109 7.9836 16.5384 7.99485 16.5584 8.01485L19.3984 10.8211C19.4409 10.8623 19.4409 10.9298 19.3984 10.9723C19.3772 10.9923 19.3509 11.0036 19.3209 11.0036H16.4822C16.4222 11.0036 16.3734 10.9548 16.3734 10.8961V8.08985ZM10.7334 13.0048H11.8897C12.1297 13.0048 12.3234 12.8123 12.3234 12.5748V11.4323C12.3234 11.1948 12.1297 11.0036 11.8897 11.0036H10.7334C10.4934 11.0036 10.2984 11.1948 10.2984 11.4323V12.5748C10.2984 12.8123 10.4934 13.0048 10.7334 13.0048ZM21.5238 26.3742C18.3179 26.3742 15.719 23.7753 15.719 20.5694C15.719 17.3635 18.3179 14.7646 21.5238 14.7646C24.7297 14.7646 27.3286 17.3635 27.3286 20.5694C27.3286 23.7753 24.7297 26.3742 21.5238 26.3742ZM22.346 17.3471H20.7016V19.7472H18.3015V21.3916H20.7016V23.7916H22.346V21.3916H24.7461V19.7472H22.346V17.3471Z" fill="#525C69"/></svg>`
						},
						onClickCallback: () => new Promise((resolve) => {
							menu.close(() => {
								this.openFilePicker();
							});
							resolve();
						}),
					},
					{
						id: 'edit',
						title: BX.message('FIELDS_FILE_EDIT'),
						data: {
							svgIcon: `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M19.7425 6.25L23.75 10.2997L12.1325 21.875L8.125 17.8253L19.7425 6.25ZM6.26396 23.2285C6.22606 23.3719 6.26667 23.5234 6.36953 23.629C6.47509 23.7345 6.62668 23.7751 6.77014 23.7345L11.25 22.5276L7.47122 18.75L6.26396 23.2285Z" fill="#525C69"/></svg>`
						},
						onClickCallback: () => new Promise((resolve) => {
							menu.close(() => {
								this.onOpenAttachmentList();
							});
							resolve();
						}),
					}
				];
			}

			const menu = new ContextMenu({
				params: {
					showCancelButton: true
				},
				actions,
			});
			menu.show();
		}

		openFilePicker()
		{
			if (!this.isReadOnly())
			{
				const items = [
					{
						id: 'mediateka',
						name: BX.message('FIELDS_FILE_MEDIATEKA')
					},
					{
						id: 'camera',
						name: BX.message('FIELDS_FILE_CAMERA')
					}
				];

				if (
					BX.componentParameters.get('MODULE_WEBDAV_INSTALLED', 'N') === 'Y'
					&& BX.componentParameters.get('MODULE_DISK_INSTALLED', 'N') === 'Y'
				)
				{
					items.push({
						id: 'disk',
						name: BX.message('FIELDS_FILE_B24_DISK'),
						dataSource: {
							multiple: false,
							url: this.getConfig().fileAttachPath
						}
					});
				}

				dialogs.showImagePicker({
						settings: {
							resize: {
								targetWidth: -1,
								targetHeight: -1,
								sourceType: 1,
								encodingType: 0,
								mediaType: this.getMediaTypeId(this.getConfig().mediaType),
								allowsEdit: true,
								saveToPhotoAlbum: true,
								cameraDirection: 0
							},
							maxAttachedFilesCount: this.isMultiple() ? '100' : '1',
							previewMaxWidth: 40,
							previewMaxHeight: 40,
							attachButton: {items}
						}
					},
					data => this.onAddFile(data)
				);
			}
		}

		onAddFile(addedFiles)
		{
			const filteredFiles = this.filterFilesByValidMediaType(this.getConfig().mediaType, addedFiles)

			if(addedFiles.length > filteredFiles.length)
			{
				navigator.notification.alert(BX.message('FIELDS_FILE_MEDIA_TYPE_ALERT_TEXT'), null, '');
			}

			if(filteredFiles.length === 0)
			{
				return;
			}

			let files = this.props.value;

			if (!Array.isArray(files))
			{
				files = [];
			}

			if (this.isMultiple())
			{
				files = [...files, ...filteredFiles];
			}
			else
			{
				files = [...filteredFiles];
			}

			this.handleChange(files);
		}

		renderHiddenFilesCounter(hiddenFilesCount)
		{
			return View(
				{
					style: this.styles.hiddenFilesCounterWrapper,
					onClick: () => {
						this.onOpenAttachmentList();
					}
				},
				Text(
					{
						style: this.styles.hiddenFilesCounterText,
						text: '+' + String(hiddenFilesCount)
					}
				)
			)
		}

		onOpenAttachmentList()
		{
			PageManager.openWidget(
				'layout',
				{
					title: BX.message('FIELDS_FILE_ATTACHMENTS_DIALOG_TITLE').replace('#NUM#', this.props.value.length),
					useLargeTitleMode: true,
					modal: false,
					backdrop: {
						mediumPositionPercent: 75
					},
					onReady: (layoutWidget) => {
						this.fileAttachmentWidget = layoutWidget;

						layoutWidget.showComponent(
							new UI.FileAttachment({
								ref: (ref) => this.fileAttachmentRef = ref,
								attachments: this.getFilesInfo(this.props.value),
								layoutWidget,
								onDeleteAttachmentItem: !this.isReadOnly() && this.onDeleteFile.bind(this),
								styles: {
									wrapper: {
										marginBottom: 10
									},
									imagePreview: {
										width: 70,
										height: 70
									},
									deleteButtonWrapper: this.isReadOnly() ? null : {
										width: 25,
										height: 25
									}
								},
							})
						);
					},
					onError: error => reject(error)
				}
			);
		}

		onDeleteFile(deletedFileIndex)
		{
			const attachments = this.props.value.filter((file, currentIndex) => currentIndex !== deletedFileIndex);

			this.handleChange(attachments);

			if (this.fileAttachmentWidget)
			{
				this.fileAttachmentWidget.setTitle({
					text: BX.message('FIELDS_FILE_ATTACHMENTS_DIALOG_TITLE').replace('#NUM#', attachments.length),
					largeMode: true
				});
			}
		}

		getDefaultStyles()
		{
			const buttonStyles = this.getButtonStyles();

			return {
				...super.getDefaultStyles(),
				fieldWrapper: {
					flex: 1
				},
				wrapper: {
					paddingTop: 7,
					paddingBottom: this.hasErrorMessage() ? 5 : 10
				},
				readOnlyWrapper: {
					paddingTop: 7,
					paddingBottom: this.hasErrorMessage() ? 5 : 9
				},
				addFileButtonContainer: {
					height: 40,
					width: '100%',
					flexDirection: 'row',
					justifyContent: 'center',
					alignItems: 'center',
					borderRadius: 6,
					borderWidth: 1,
					...buttonStyles.container,
				},
				addFileButtonIcon: {
					width: 18,
					height: 17,
					marginRight: 7
				},
				addFileButtonText: {
					fontSize: 16,
					...buttonStyles.text,
				},
				filesListWrapper: {
					flexDirection: 'row',
					alignItems: 'center',
					borderColor: '#ffffff',
					flexGrow: 2
				},
				hiddenFilesCounterWrapper: {
					borderColor: '#525C69',
					borderWidth: 0.5,
					borderRadius: 20,
					width: 40,
					height: 40,
					alignItems: 'center',
					alignSelf: 'center',
					justifyContent: 'center'
				},
				hiddenFilesCounterText: {
					fontSize: 17,
					color: '#828B95'
				}
			}
		}

		getButtonStyles()
		{
			switch (this.getButtonType())
			{
				case 'primary':
					return {
						iconColor: '#ffffff',
						container: {
							backgroundColor: '#00a2e8',
							borderColor: '#00a2e8',
						},
						text: {
							color: '#ffffff',
						},
					};
				default:
					return {
						iconColor: '#b9c0ca',
						container: {
							backgroundColor: '#ffffff',
							borderColor: '#82888f',
						},
						text: {
							color: '#525c69',
						},
					}
			}
		}

		getButtonType()
		{
			return BX.prop.getString(this.getConfig(), 'buttonType', 'default');
		}

		getAddButtonText(mediaType)
		{
			switch (mediaType)
			{
				case MEDIA_TYPE.IMAGE:
					return BX.message('FIELDS_FILE_ADD_IMAGE')
				case MEDIA_TYPE.VIDEO:
					return BX.message('FIELDS_FILE_ADD_VIDEO')
				default:
					return BX.message('FIELDS_FILE_ADD_FILE')
			}
		}

		getMediaTypeId(mediaType)
		{
			switch (mediaType) {
				case MEDIA_TYPE.IMAGE:
					return 0
				case MEDIA_TYPE.VIDEO:
					return 1
				default:
					return 2
			}
		}

		filterFilesByValidMediaType(mediaType, files)
		{
			if(mediaType === MEDIA_TYPE.FILE)
			{
				return files;
			}

			return files.filter(file => UI.File.getType(UI.File.getFileMimeType(file.type)) === mediaType);
		}
	}

	const svgImages = {
		file: {
			content: `<svg width="19" height="18" viewBox="0 0 19 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.659 0.783569H0.15918V3.1169H12.9925L10.659 0.783569Z" fill="%color%"/><path d="M18.8258 4.28357H0.15918V17.1169H18.8258V4.28357Z" fill="%color%"/></svg>`
		},
	}

	this.Fields = this.Fields || {};
	this.Fields.FileField = FileField;
})();
