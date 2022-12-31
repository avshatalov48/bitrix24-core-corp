(() => {
	const { ImageSelectField } = jn.require('layout/ui/fields/image-select');

	class ProjectAvatarField extends LayoutComponent
	{
		render()
		{
			return View(
				{},
				ImageSelectField({
					title: BX.message('MOBILE_LAYOUT_PROJECT_FIELDS_AVATAR_TITLE'),
					images: {
						default:
							Object.entries(this.props.defaultImages)
								.reduce((result, [key, value]) => {
									result.push([key, value.sort]);
									return result;
								}, [])
								.sort((a, b) => (a[1] - b[1]))
								.reduce((result, [id, sort]) => ({...result, [id]: `${currentDomain}${this.props.defaultImages[id].mobileUrl}`}), {})
						,
						loaded: this.props.loaded,
					},
					value: this.props.value,
					isLoading: this.props.isLoading,
					config: {
						fileAttachPath: `/mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${this.props.userId}`,
						hideDisk: !this.props.userUploadedFilesFolder,
					},
					onChange: (selected, loaded, file) => {
						if (file)
						{
							const files = this.prepareAttachedFiles([file], this.props.userUploadedFilesFolder);
							this.props.onChange(selected, loaded, this.uploadFiles(files, this.props.guid), files.disk[0]);
						}
						else
						{
							this.props.onChange(selected, loaded, null, null);
						}
					},
				})
			);
		}

		prepareAttachedFiles(attachedFiles, userUploadedFilesFolder)
		{
			const files = {
				disk: [],
				local: [],
			};

			if (!attachedFiles)
			{
				return files;
			}

			const getGuid = function() {
				const s4 = function() {
					return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
				};
				return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
			};

			attachedFiles.forEach((file) => {
				if (file.dataAttributes)
				{
					files.disk.push(file.dataAttributes.ID);
				}
				else
				{
					const taskId = `projectAvatar-${getGuid()}`;
					const extension = this.getExtension(file.name);

					let fileName = file.name;
					if (extension === 'heic')
					{
						fileName = fileName.substring(0, fileName.length - (extension.length)) + 'jpg';
					}

					files.local.push({
						taskId,
						id: taskId,
						params: file,
						name: fileName,
						type: file.type,
						url: file.url,
						previewUrl: file.previewUrl,
						folderId: userUploadedFilesFolder,
						resize: this.getResizeOptions(file.type),
						onDestroyEventName: BX.FileUploadEvents.FILE_CREATED,
					});
				}
			});

			return files;
		}

		uploadFiles(files, guid)
		{
			const {local} = files;

			if (local.length <= 0)
			{
				return false;
			}

			local.forEach(file => file.params.guid = guid);

			BX.postComponentEvent('onFileUploadTaskReceived', [{files: local}], 'background');

			return true;
		}

		getExtension(uri)
		{
			return (uri && uri.indexOf('.') >= 0 ? uri.split('.').pop().toLowerCase() : '');
		}

		getResizeOptions(type)
		{
			const mimeType = this.getFileMimeType(type);
			const fileType = this.getType(mimeType);
			const shouldBeConverted = ((fileType === 'image' && mimeType !== 'image/gif') || fileType === 'video');

			if (shouldBeConverted)
			{
				return {
					quality: 80,
					width: 1920,
					height: 1080,
				};
			}

			return null;
		}

		getFileMimeType(fileType)
		{
			fileType = fileType.toString().toLowerCase();

			if (fileType.indexOf('/') !== -1) // iOS old form
			{
				return fileType;
			}

			const mimeTypeMap = {
				png: 'image/png',
				gif: 'image/gif',
				jpg: 'image/jpeg',
				jpeg: 'image/jpeg',
				heic: 'image/heic',
				mp3: 'audio/mpeg',
				mp4: 'video/mp4',
				mpeg: 'video/mpeg',
				ogg: 'video/ogg',
				mov: 'video/quicktime',
				zip: 'application/zip',
				php: 'text/php',
			}

			return (mimeTypeMap[fileType] || '');
		}

		getType(mimeType)
		{
			const result = mimeType.substring(0, mimeType.indexOf('/'));
			const types = ['image', 'video', 'audio'];

			if (!types.includes(result))
			{
				return 'file';
			}

			return result;
		}
	}

	this.ProjectAvatarField = ProjectAvatarField;
})();