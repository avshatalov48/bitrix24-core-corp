(() =>
{
	this.Utils = {
		getFileMimeType: (fileType) => {
			fileType = fileType.toString().toLowerCase();

			if (fileType.indexOf('/') !== -1) // iOS old form
			{
				return fileType;
			}

			const mimeTypeMap = {
				'png': 'image/png',
				'gif': 'image/gif',
				'jpg': 'image/jpeg',
				'jpeg': 'image/jpeg',
				'heic': 'image/heic',
				'mp3': 'audio/mpeg',
				'mp4': 'video/mp4',
				'mpeg': 'video/mpeg',
				'ogg': 'video/ogg',
				'mov': 'video/quicktime',
				'zip': 'application/zip',
				'php': 'text/php',
			}

			return mimeTypeMap[fileType]? mimeTypeMap[fileType] : '';
		},

		getResizeOptions: (type) => {
			const mimeType = this.Utils.getFileMimeType(type);
			const fileType = this.Utils.getType(mimeType);
			const shouldBeConverted = (fileType === 'image' && mimeType !== 'image/gif') || fileType === 'video';
			const resizeOptions = {
				quality: 80,
				width: 1920,
				height: 1080
			};

			return shouldBeConverted ?  resizeOptions : null;
		},

		getType: (mimeType) => {
			let result = mimeType.substring(0, mimeType.indexOf('/'));
			if (!['image', 'video', 'audio'].includes(result))
			{
				result = 'file';
			}

			return result;
		},

		formatSelectedRecipients: (recipients) => {
			return Object.keys(recipients)
				.filter(type => Array.isArray(recipients[type]))
				.reduce((result, type)=> {
					result[type] = recipients[type].map(item => {
						const colors = {
							'users': RecipientUtils.getColor('user'),
							'groups': RecipientUtils.getColor('group'),
							'departments':RecipientUtils.getColor('department')
						}

						if (item.id === 'A')
						{
							item.color = RecipientUtils.getColor('userAll');
						}
						else
						{
							item.color = colors[type] ? colors[type] : ''
						}

						return item;
					})

					return result;
				}, {});
		},

		sanitizeText: (text) =>
		{
			return text
				.replace(/\[USER=(\d+)\]|\[\/USER\]/gi, '')
				.replace(/\[PROJECT=(\d+)\]|\[\/PROJECT\]/gi, '')
				.replace(/\[DEPARTMENT=(\d+)\]|\[\/DEPARTMENT\]/gi, '');
		},

		getForAllValue: ({
			postData,
			forAllAllowed,
			forAllDefault,
		}) =>
		{
			if (
				typeof postData.recipients !== 'undefined'
				&& typeof postData.recipients.users !== 'undefined'
				&& Array.isArray(postData.recipients.users)
			)
			{
				return (postData.recipients.users.findIndex((item) => {
					return item.id === 'A';
				}) !== -1);
			}
			else
			{
				return (forAllAllowed && forAllDefault);
			}
		},

		getRecipientsCountValue: ({recipients}) =>
		{
			let entityList = ['users', 'groups', 'departments']
			return entityList
				.filter(item => Array.isArray(recipients[item]))
				.reduce((result, item) => result + recipients[item].length, 0);
		},

		getRecipientsValue: ({
			postData,
			forAllAllowed,
			forAllDefault,
		}) => {

			if (postData.recipients)
			{
				if (Array.isArray(postData.recipients.users))
				{
					postData.recipients.users = postData.recipients.users.map((item) => {
						if (item.id === 'A')
						{
							item.title = BX.message('MOBILE_EXT_LAYOUT_POSTFORM_UTIL_VALUE_ALL_SELECTED');
						}

						item.imageUrl = '';
						if (item.avatar)
						{
							item.imageUrl = item.avatar;
							if (item.imageUrl.indexOf('http') === -1)
							{
								item.imageUrl = currentDomain + encodeURI(item.imageUrl);
							}
							delete item.avatar;
						}

						return item;
					});
				}

				if (Array.isArray(postData.recipients.groups))
				{
					postData.recipients.groups = postData.recipients.groups.map((item) => {

						item.imageUrl = '';
						if (item.avatar)
						{
							item.imageUrl = item.avatar;
							if (item.imageUrl.indexOf('http') === -1)
							{
								item.imageUrl = currentDomain + encodeURI(item.imageUrl);
							}
							delete item.avatar;
						}

						return item;
					});
				}

				return postData.recipients;
			}

			return (
				(forAllAllowed && forAllDefault)
					? {
						users: [{
							id: 'A',
							title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_UTIL_VALUE_ALL_SELECTED')
						}]
					}
					: {
						users: []
					}
			);
		},

		getHiddenRecipientsValue: ({
			postData,
		}) => {
			let result = (Array.isArray(postData.hiddenRecipients) ? postData.hiddenRecipients : []);

			result = result.filter(item => {
				return (item !== 'U' + postData.post.post_user_id);
			});

			return result;
		},

		getAttachmentsValue: ({
			postData,
		}) => {
			let result = [];

			if (postData.post && Array.isArray(postData.post.PostFiles))
			{
				result = postData.post.PostFiles.map((item) => {

					const fileData = {
						ID: item.id,
						NAME: item.name,
						URL: {
							EXTERNAL: 'YES',
							URL: item.url,
						},
						VALUE: item.objectId
					};

					if (typeof item.previewImageUrl !== 'undefined')
					{
						fileData.IMAGE = item.previewImageUrl;
					}

					return {
						dataAttributes: fileData,
						name: item.name,
						type: item.type,
						url: item.url,
						previewUrl: (typeof item.previewImageUrl !== 'undefined' ? item.previewImageUrl : null)
					};
				});
			}

			return result;
		},

		getBackgroundImageCodeValue: ({
			postData,
		}) => {
			let result = null;

			if (
				postData.post
				&& typeof postData.post.PostBackgroundCode !== 'undefined'
			)
			{
				result = postData.post.PostBackgroundCode;
			}

			return result;
		},

		getIsImportantValue: ({
			postData,
		}) => {
			let result = false;

			if (
				postData.post
				&& typeof postData.post.PostImportantData !== 'undefined'
			)
			{
				result = (
					typeof postData.post.PostImportantData.value !== 'undefined'
					&& postData.post.PostImportantData.value === 'Y'
				);
			}

			return result;
		},

		getImportantUntilValue: ({
			postData,
		}) => {
			let result = null;

			if (
				postData.post
				&& typeof postData.post.PostImportantData !== 'undefined'
				&& typeof postData.post.PostImportantData.value !== 'undefined'
				&& postData.post.PostImportantData.value === 'Y'
				&& typeof postData.post.PostImportantData.endDate !== 'undefined'
			)
			{
				result = parseInt(postData.post.PostImportantData.endDate) * 1000;
			}

			return result;
		},

		getMedalValue: ({
			postData
		}) => {
			let result = null;

			if (
				postData.post
				&& typeof postData.post.PostGratitudeData !== 'undefined'
			)
			{
				result = (
					typeof postData.post.PostGratitudeData.gratitude !== 'undefined'
						? postData.post.PostGratitudeData.gratitude
						: null
				);
			}

			return result;
		},

		getGratitudeEmployeesValue: ({
			postData
		}) => {
			let result = [];

			if (
				postData.post
				&& typeof postData.post.PostGratitudeData !== 'undefined'
			)
			{
				result = (
					typeof postData.post.PostGratitudeData.employees !== 'undefined'
					&& Array.isArray(postData.post.PostGratitudeData.employees)
						? postData.post.PostGratitudeData.employees
						: null
				);
			}

			return result;
		},

		getVoteValue: ({
			postData
		}) => {
			let result = {
				questions: [],
			};

			if (
				postData.post
				&& typeof postData.post.PostVoteData === 'object'
				&& postData.post.PostVoteData !== null
			)
			{
				result = postData.post.PostVoteData;
				if (Array.isArray(result.questions))
				{
					result.questions.map((question) => {
						question.allowMultiSelect = (question.allowMultiSelect === 'Y');
						return question;
					})
				}
			}

			return result;
		},

		showError: ({
			errorText,
			callback,
		}) => {
			include("InAppNotifier");

			InAppNotifier.showNotification({
				title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_UTIL_PUBLICATION_ERROR'),
				backgroundColor: "#affb0000",
				message: errorText,
			});
		},

		drawFile: ({
			url,
			imageUri,
			type,
			name,
			attachmentCloseIcon,
			attachmentFileIconFolder,
			onDeleteAttachmentItem,
		}) => {

			if (
				url
				&& url.indexOf('file://') !== 0
			)
			{
				url = currentDomain + url;
			}

			let result = null;
			const fileType = this.Utils.getType(this.Utils.getFileMimeType(type));

			if (
				(fileType === 'image' || fileType === 'video')
				&& imageUri.length > 0
			)
			{
				result = View({
						testId: 'pinnedFileContainer',
						style: {
							width: 70,
							height: 70,
						},
						onClick: () => {
							if (
								url
								// && url.indexOf('file://') !== 0
							)
							{
								this.Utils.openViewer({
									fileType,
									url,
									name,
								});
							}
						},
					},
					Image({
						testId: 'pinnedFileImage',
						style: {
							position: 'absolute',
							width: 52,
							height: 52,
							left: 0,
							top: 8,
						},
						uri: imageUri,
						resizeMode: 'cover'
					}),
					Image({
						testId: 'pinnedFileDetach',
						uri: attachmentCloseIcon,
						resizeMode: 'cover',
						style: {
							borderColor: '#000000',
							position: 'absolute',
							width: 25,
							height: 25,
							left: 37,
							top: 0,
							paddingRight: 10,
							paddingTop: 10,
							backgroundColor: '#00000000',
						},
						onClick: onDeleteAttachmentItem
					})
				);
			}
			else
			{
				const extension = this.Utils.getExtension({
					uri: (name ? name : url),
				});

				let icon = this.Utils.getFileType({
					extension,
				});
				icon = (icon ? icon : 'empty');

				result = View({
						testId: 'pinnedFileContainer',
						style: {
							width: 70,
							height: 70,
						},
						onClick: () => {
							if (
								url
								// && url.indexOf('file://') !== 0
							)
							{
								this.Utils.openViewer({
									fileType,
									url,
									name,
								});
							}
						},
					},
					View({
						style: {
							position: 'absolute',
							width: 52,
							height: 52,
							left: 0,
							top: 8,
							borderWidth: 1,
							borderColor: '#e6e7e9',
							backgroundColor: '#ffffff',
						},
					}),
					View({
						testId: 'pinnedFileIcon',
						style: {
							position: 'absolute',
							width: 25,
							height: 30,
							left: 14,
							top: 16,
							backgroundColor: '#00000000',
							backgroundImageSvgUrl: attachmentFileIconFolder + icon + '.svg',
						},
					}),
					Text({
						testId: 'pinnedFileName',
						style: {
							position: 'absolute',
							color: '#a8adb4',
							fontWeight: 'normal',
							fontSize: 10,
							textAlign: 'center',
							width: 45,
							left: 4,
							top: 46,
							backgroundColor: '#00000000',
						},
						text: name.substring(0, 6) + (name.length > 6 ? '...' : ''),
					}),
					Image({
						testId: 'pinnedFileDetach',
						uri: attachmentCloseIcon,
						resizeMode: 'cover',
						style: {
							borderColor: '#000000',
							position: 'absolute',
							width: 25,
							height: 25,
							left: 37,
							top: 0,
							paddingRight: 10,
							paddingTop: 10,
							backgroundColor: '#00000000',
						},
						onClick: onDeleteAttachmentItem,
					})
				);
			}

			return result;
		},

		getExtension({ uri }) {
			return (uri && uri.indexOf('.') >= 0 ? uri.split('.').pop().toLowerCase() : '');
		},

		getFileType({
			extension,
		})
		{
			let result = null;

			switch (extension)
			{
				case 'xls':
				case 'xlsx':
					result = 'xls';
					break;
				case 'doc':
				case 'docx':
					result = 'doc';
					break;
				case 'ppt':
				case 'pptx':
					result = 'ppt';
					break;
				case 'txt':
					result = 'txt';
					break;
				case 'pdf':
					result = 'pdf';
					break;
				case 'php':
					result = 'php';
					break;
				case 'rar':
					result = 'rar';
					break;
				case 'zip':
					result = 'zip';
					break;
				case 'mp4':
				case 'mpeg':
				case 'ogg':
				case 'mov':
				case '3gp':
					result = 'video';
					break;
				case 'png':
				case 'gif':
				case 'jpg':
				case 'jpeg':
				case 'heic':
					result = 'image';
					break;
				default:
					result = null;
			}

			return result;
		},

		openViewer({
			fileType,
			url,
			name,
		})
		{
			if (fileType === 'video')
			{
				viewer.openVideo(url);
			}
			else if (fileType === 'image')
			{
				viewer.openImage(url, name);
			}
			else
			{
				viewer.openDocument(url, name);
			}
		},

		getNowDate()
		{
			let result = new Date();
			result.setHours(0, 0, 0, 0);
			result = result.getTime();

			return result;
		},

		getImportantDatePeriods()
		{
			const nowDate = this.getNowDate();
			const aDay = 60 * 60 * 24 * 1000;

			return {
				always: nowDate + aDay * 365 * 10,
				oneDay: nowDate + aDay,
				twoDays: nowDate + aDay * 2,
				oneWeek: nowDate + aDay * 7,
				oneMonth: nowDate + aDay * 30,
			};
		}
	}

})();
