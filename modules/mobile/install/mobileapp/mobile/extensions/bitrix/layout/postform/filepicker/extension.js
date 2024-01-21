(() => {

	this.FilePicker = {

		show: ({
			callback,
			moduleWebdavInstalled,
			moduleDiskInstalled,
			fileAttachPath
		}) => {
			dialogs.showImagePicker({
					settings: {
						resize: {
							targetWidth: -1,
							targetHeight: -1,
							sourceType: 1,
							encodingType: 0,
							mediaType: 2,
							allowsEdit: true,
							saveToPhotoAlbum: true,
							cameraDirection: 0
						},
						editingMediaFiles: false,
						maxAttachedFilesCount: 100,
						previewMaxWidth: 200,
						previewMaxHeight: 200,
						attachButton:{
							items: [
								(
									moduleDiskInstalled
									|| moduleWebdavInstalled
										? {
											id: 'disk',
											name: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_FILEPICKER_ATTACH_BUTTON_DISK_MSGVER_1'),
											dataSource: {
												multiple: false,
												url: fileAttachPath,
												table_settings: {
													searchField: 'YES',
													showtitle: 'YES',
													modal: 'YES',
													name: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_FILEPICKER_ATTACH_DISK_TITLE')
												}
											},

										}
										: null
								),
								{
									id: 'mediateka',
									name: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_FILEPICKER_ATTACH_BUTTON_GALLERY')
								},
								{
									id: 'camera',
									name: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_FILEPICKER_ATTACH_BUTTON_CAMERA')
								}
							]
						}
					}
				},
				callback
			);
		}

	}

})();