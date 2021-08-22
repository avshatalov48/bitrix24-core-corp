import {Loc, Type} from 'main.core';
import {MobileUtils} from 'mobile.utils';

import {Instance} from './feed';

class PostFormOldManager
{
	constructor()
	{
		this.postFormParams = {};
		this.postFormExtraData = {};
	}

	setExtraDataArray(extraData)
	{
		let ob = null;

		for (const [key, value] of Object.entries(extraData))
		{
			if (extraData.hasOwnProperty(key))
			{
				ob = {};
				ob[key] = value;
				this.setExtraData(ob);
			}
		}
	}

	setExtraData(params)
	{
		if (!Type.isPlainObject(params))
		{
			return;
		}

		for (const [key, value] of Object.entries(params))
		{
			if (
				(
					key == 'hiddenRecipients'
					|| key == 'logId'
					|| key == 'postId'
					|| key == 'postAuthorId'
					|| key == 'messageUFCode'
					|| key == 'commentId'
					|| key == 'commentType'
					|| key == 'nodeId'
					|| key == 'pinnedContext'
				)
			)
			{
				this.postFormExtraData[key] = value;
			}
		}
	}

	getExtraData()
	{
		return this.postFormExtraData;
	}

	setParams(params)
	{
		if (!Type.isPlainObject(params))
		{
			return;
		}

		for (const [key, value] of Object.entries(params))
		{
			if ([ 'selectedRecipients', 'messageText', 'messageFiles' ].indexOf(key) !== -1)
			{
				this.postFormParams[key] = value;
			}
		}
	}

	addDestination(selectedDestinations, params)
	{
		if (
			!Type.isPlainObject(params)
			|| !Type.isStringFilled(params.type)
		)
		{
			return;
		}

		let searchRes = null;

		if (params.type === 'UA')
		{
			searchRes = selectedDestinations.a_users.some(this.findDestinationCallBack, { value: 0 });
			if (!searchRes)
			{
				selectedDestinations.a_users.push({
					id: 0,
					name: Loc.getMessage('MSLPostDestUA'),
					bubble_background_color: '#A7F264',
					bubble_text_color: '#54901E',
				});
			}
		}
		else if (params.type === 'U')
		{
			searchRes = selectedDestinations.a_users.some(this.findDestinationCallBack, { value: params.id });
			if (!searchRes)
			{
				selectedDestinations.a_users.push({
					id: params.id,
					name: params.name,
					bubble_background_color: '#BCEDFC',
					bubble_text_color: '#1F6AB5',
				});
			}
		}
		else if (params.type === 'SG')
		{
			searchRes = selectedDestinations.b_groups.some(this.findDestinationCallBack, { value: params.id });
			if (!searchRes)
			{
				selectedDestinations.b_groups.push({
					id: params.id,
					name: params.name,
					bubble_background_color: '#FFD5D5',
					bubble_text_color: '#B54827',
				});
			}
		}
	}

	findDestinationCallBack(element, index, array)
	{
		return (element.id == this.value);
	}

	show(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		const entityType = (
			Type.isStringFilled(params.entityType)
				? params.entityType
				: 'post'
		);

		const extraData = this.getExtraData();

		const postFormParams = {
			attachButton: this.getAttachButton(),
			mentionButton: this.getMentionButton(),
			attachFileSettings: this.getAttachFileSettings(),
			extraData: (extraData ? extraData : {}),
			smileButton: {},
			supportLocalFilesInText: (entityType === 'post'),
			okButton: {
				callback: (data) => {
					if (!Type.isStringFilled(data.text))
					{
						return;
					}

					const postData = this.buildRequestStub({
						type: entityType,
						extraData: data.extraData,
						text: this.parseMentions(data.text),
						pinnedContext: (
							Type.isStringFilled(data.extraData.pinnedContext)
							&&  data.extraData.pinnedContext === 'YES'
						)
					});

					const ufCode = data.extraData.messageUFCode;

					this.buildFiles(
						postData,
						data.attachedFiles,
						{
							ufCode: ufCode,
						}
					).then(() => {
						if (entityType !== 'post')
						{
							return;
						}

						this.buildDestinations(
							postData,
							data.selectedRecipients,
							(
								Type.isPlainObject(data.extraData)
								&& !Type.isUndefined(data.extraData.hiddenRecipients)
									? data.extraData.hiddenRecipients
									: []
							),
							{}
						);

						if (!postData.postVirtualId)
						{
							return;
						}

						postData.ufCode = ufCode;
						postData.contentType = 'post';

						oMSL.initPostForm({
							groupId: (params.groupId ? params.groupId : null),
						});

						BXMobileApp.onCustomEvent('Livefeed.PublicationQueue::setItem', {
							key: postData.postVirtualId,
							pinnedContext: !!postData.pinnedContext,
							item: postData,
							pageId: Instance.getPageId(),
							groupId: (params.groupId ? params.groupId : null)
						}, true);

					}, () => {});
				},
				name: Loc.getMessage('MSLPostFormSend'),
			},
			cancelButton: {
				callback: () => {
					oMSL.initPostForm({
						groupId: (params.groupId ? params.groupId : null),
					});
				},
				name: Loc.getMessage('MSLPostFormCancel'),
			}
		};

		if (!Type.isUndefined(this.postFormParams.messageText))
		{
			postFormParams.message = {
				text: this.postFormParams.messageText,
			};
		}

		if (!Type.isUndefined(this.postFormParams.messageFiles))
		{
			postFormParams.attachedFiles = this.postFormParams.messageFiles;
		}

		if (entityType === 'post')
		{
			postFormParams.recipients = {
				dataSource: this.getRecipientsDataSource(),
			};

			if (!Type.isUndefined(this.postFormParams.selectedRecipients))
			{
				postFormParams.recipients.selectedRecipients = this.postFormParams.selectedRecipients;
			}

			if (!Type.isUndefined(this.postFormParams.backgroundCode))
			{
				postFormParams.backgroundCode = this.postFormParams.backgroundCode;
			}
		}

		return postFormParams;
	}

	getAttachButton()
	{
		const attachButtonItems = [];

		if (
			Loc.getMessage('MOBILE_EXT_LIVEFEED_DISK_INSTALLED') === 'Y'
			|| Loc.getMessage('MOBILE_EXT_LIVEFEED_WEBDAV_INSTALLED') === 'Y'
		)
		{
			const diskAttachParams = {
				id: 'disk',
				name: Loc.getMessage('MSLPostFormDisk'),
				dataSource: {
					multiple: 'NO',
					url: (
						Loc.getMessage('MOBILE_EXT_LIVEFEED_DISK_INSTALLED') === 'Y'
							? `${Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR')}mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${Loc.getMessage('USER_ID')}`
							: `${Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR')}mobile/webdav/user/${Loc.getMessage('USER_ID')}/`
					),
				}
			};

			const tableSettings = {
				searchField: 'YES',
				showtitle: 'YES',
				modal: 'YES',
				name: Loc.getMessage('MSLPostFormDiskTitle'),
			};

			//FIXME temporary workaround
			if (window.platform === 'ios')
			{
				diskAttachParams.dataSource.table_settings = tableSettings;
			}
			else
			{
				diskAttachParams.dataSource.TABLE_SETTINGS = tableSettings;
			}

			attachButtonItems.push(diskAttachParams);
		}

		attachButtonItems.push({
			id: 'mediateka',
			name: Loc.getMessage('MSLPostFormPhotoGallery'),
		});

		attachButtonItems.push({
			id: 'camera',
			name: Loc.getMessage('MSLPostFormPhotoCamera'),
		});

		return {
			items: attachButtonItems,
		};
	}

	getMentionButton()
	{
		return {
			dataSource: {
				return_full_mode: 'YES',
				outsection: 'NO',
				okname: Loc.getMessage('MSLPostFormTableOk'),
				cancelname: Loc.getMessage('MSLPostFormTableCancel'),
				multiple: 'NO',
				alphabet_index: 'YES',
				url: `${Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR')}mobile/index.php?mobile_action=get_user_list&use_name_format=Y`,
			}
		};
	}

	getAttachFileSettings()
	{
		return {
			resize: [
				40,
				1,
				1,
				1000,
				1000,
				0,
				2,
				false,
				true,
				false,
				null,
				0
			],
			saveToPhotoAlbum: true,
		};
	}

	buildRequestStub(params)
	{
		let request = null;

		if (params.type === 'post')
		{
			request = {
				ACTION: 'ADD_POST',
				AJAX_CALL: 'Y',
				PUBLISH_STATUS: 'P',
				is_sent: 'Y',
				apply: 'Y',
				sessid: Loc.getMessage('bitrix_sessid'),
				POST_MESSAGE: params.text,
				decode: 'Y',
				SPERM: {},
				SPERM_NAME: {},
				MOBILE: 'Y',
				PARSE_PREVIEW: 'Y',
			};

			if (
				!Type.isUndefined(params.extraData.postId)
				&& parseInt(params.extraData.postId) > 0
			)
			{
				request.post_id = parseInt(params.extraData.postId);
				request.post_user_id = parseInt(params.extraData.postAuthorId);
				request.pinnedContext = !!params.pinnedContext;

				request.ACTION = 'EDIT_POST';

				if (
					!Type.isUndefined(params.extraData.logId)
					&& parseInt(params.extraData.logId) > 0
				)
				{
					request.log_id = parseInt(params.extraData.logId);
				}
			}
		}
		else if (
			params.type === 'comment'
			&& !Type.isUndefined(params.extraData.commentId)
			&& parseInt(params.extraData.commentId) > 0
			&& Type.isStringFilled(params.extraData.commentType)
		)
		{
			request = {
				action: 'EDIT_COMMENT',
				text: this.parseMentions(params.text),
				commentId: parseInt(params.extraData.commentId),
				nodeId: params.extraData.nodeId,
				sessid: Loc.getMessage('bitrix_sessid'),
			};

			if (params.extraData.commentType === 'blog')
			{
				request.comment_post_id = null;
			}
		}

		return request;
	}

	parseMentions(text)
	{
		let parsedText = text;

		if (typeof oMSL.arMention != 'undefined')
		{
			for (const [key, value] of Object.entries(oMSL.arMention))
			{
				parsedText = parsedText.replace(new RegExp(key, 'g'), value);
			}

			oMSL.arMention = {};
			oMSL.commentTextCurrent = '';
		}

		return parsedText;
	}

	buildFiles(postData, attachedFiles, params)
	{
		const promise = new Promise((resolve, reject) => {
			const ufCode = params.ufCode;

			postData.postVirtualId = parseInt(Math.random() * 100000);
			postData.tasksList = [];

			if (
				Type.isArray(attachedFiles)
				&& attachedFiles.length > 0
			)
			{
				let readedFileCount = 0;
				const fileTotal = attachedFiles.length;
				const fileCountIncrement = () => {
					readedFileCount++;
					if(readedFileCount >= fileTotal)
					{
						this.postProgressingFiles(postData, attachedFiles, params);
						resolve();
					}
				};

				const uploadTasks = [];

				attachedFiles.forEach((fileData) => {
					const isFileFromBitrix24Disk = (
						!Type.isUndefined(fileData.VALUE) // Android
						|| (
							!Type.isUndefined(fileData.id)
							&& parseInt(fileData.id) > 0
						) // disk object
						|| (
							Type.isPlainObject(fileData.dataAttributes)
							&& !Type.isUndefined(fileData.dataAttributes.VALUE)
						) // iOS and modern Android too
						|| (
							Type.isStringFilled(fileData.ufCode)
							&& fileData.ufCode === ufCode
						)
					);

					const isNewFileOnDevice = (
						Type.isUndefined(fileData.url)
						|| !Type.isNumber(fileData.id)
					);

					if (
						Type.isStringFilled(fileData.url)
						&& isNewFileOnDevice
						&& !isFileFromBitrix24Disk
					)
					{
						const taskId = `postTask_${parseInt(Math.random() * 100000)}`;
						const mimeType = MobileUtils.getFileMimeType(fileData.type);

						uploadTasks.push({
							taskId: taskId,
							type: fileData.type,
							mimeType: mimeType,
							folderId: parseInt(Loc.getMessage('MOBILE_EXT_UTILS_USER_FOLDER_FOR_SAVED_FILES')),
//							chunk: parseInt(Loc.getMessage('MOBILE_EXT_UTILS_MAX_UPLOAD_CHUNK_SIZE')),
							params: {
								postVirtualId: postData.postVirtualId,
								pinnedContext: !!postData.pinnedContext
							},
							name: (MobileUtils.getUploadFilename(fileData.name, fileData.type)),
							url: fileData.url,
							previewUrl: (fileData.previewUrl ? fileData.previewUrl : null),
							resize: MobileUtils.getResizeOptions(fileData.type)
						});

						postData.tasksList.push(taskId);
					}
					else
					{
						if (isFileFromBitrix24Disk)
						{
							if (Type.isUndefined(postData[ufCode]))
							{
								postData[ufCode] = [];
							}

							if (!Type.isUndefined(fileData.VALUE))
							{
								postData[ufCode].push(fileData.VALUE);
							}
							else if (parseInt(fileData.id) > 0)
							{
								postData[ufCode].push(parseInt(fileData.id));
							}
							else
							{
								postData[ufCode].push(fileData.dataAttributes.VALUE);
							}
						}

						fileCountIncrement();
					}
				});

				if (uploadTasks.length > 0)
				{
					BXMobileApp.onCustomEvent('onFileUploadTaskReceived', {
						files: uploadTasks,
					}, true);
				}
				resolve();
			}
			else
			{
				this.postProgressingFiles(postData, attachedFiles, params);
				resolve();
			}
		});

		promise.catch((error) => {console.error(error)});

		return promise;
	}

	buildDestinations(postData, selectedRecipients, hiddenRecipients, params)
	{
		postData['DEST'] = [];

		if (Type.isPlainObject(selectedRecipients.a_users))
		{
			for (const [key, userData] of Object.entries(selectedRecipients.a_users))
			{
				const prefix = 'U';

				if (Type.isUndefined(postData.SPERM[prefix]))
				{
					postData.SPERM[prefix] = [];
				}

				if (Type.isUndefined(postData.SPERM_NAME[prefix]))
				{
					postData.SPERM_NAME[prefix] = [];
				}

				const id = (
					!Type.isUndefined(userData.ID)
						? userData.ID
						: userData.id
				);

				const name = (
					!Type.isUndefined(userData.NAME)
						? userData.NAME
						: userData.name
				);

				const value = (
					parseInt(id) === 0
						? 'UA'
						: `U${id}`
				);

				postData.SPERM[prefix].push(value);
				postData.DEST.push(value);
				postData.SPERM_NAME[prefix].push(name);
			}
		}

		if (Type.isPlainObject(selectedRecipients.b_groups))
		{
			for (const [key, groupData] of Object.entries(selectedRecipients.b_groups))
			{
				const prefix = 'SG';

				if (Type.isUndefined(postData.SPERM[prefix]))
				{
					postData.SPERM[prefix] = [];
				}

				if (Type.isUndefined(postData.SPERM_NAME[prefix]))
				{
					postData.SPERM_NAME[prefix] = [];
				}

				const id = (
					!Type.isUndefined(groupData.ID)
						? groupData.ID
						: groupData.id
				);

				const name = (
					!Type.isUndefined(groupData.NAME)
						? groupData.NAME
						: groupData.name
				);

				const value = `SG${id}`;

				postData.SPERM[prefix].push(value);
				postData.DEST.push(value);
				postData.SPERM_NAME[prefix].push(name);
			}
		}

		for (const key in hiddenRecipients)
		{
			if (!hiddenRecipients.hasOwnProperty(key))
			{
				continue;
			}

			const prefix = hiddenRecipients[key].TYPE;
			if (Type.isUndefined(postData.SPERM[prefix]))
			{
				postData.SPERM[prefix] = [];
			}

			const value = `${hiddenRecipients[key].TYPE}${hiddenRecipients[key].ID}`;

			postData.SPERM[prefix].push(value);
			postData.DEST.push(value);
		}
	}

	getRecipientsDataSource()
	{
		return {
			return_full_mode: 'YES',
			outsection: (Loc.getMessage('MOBILE_EXT_LIVEFEED_DEST_TO_ALL_DENIED') !== 'Y' ? 'YES' : 'NO'),
			okname: Loc.getMessage('MSLPostFormTableOk'),
			cancelname: Loc.getMessage('MSLPostFormTableCancel'),
			multiple: 'YES',
			alphabet_index: 'YES',
			showtitle: 'YES',
			user_all: 'YES',
			url: `${Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR')}mobile/index.php?mobile_action=${(Loc.getmessage('MOBILE_EXT_LIVEFEED_CURRENT_EXTRANET_SITE') === 'Y' ? 'get_group_list' : 'get_usergroup_list')}&feature=blog`,
		};
	}

	postProgressingFiles(postData, attachedFiles, params)
	{
		const ufCode = params.ufCode;
		if (Type.isUndefined(postData[ufCode]))
		{
			postData[ufCode] = [];
		}

		if (Type.isUndefined(attachedFiles))
		{
			attachedFiles = [];
		}

		for (const keyOld in this.postFormParams.messageFiles) /* existing */
		{
			if (!this.postFormParams.messageFiles.hasOwnProperty(keyOld))
			{
				continue;
			}

			for (const keyNew in attachedFiles)
			{
				if (!attachedFiles.hasOwnProperty(keyNew))
				{
					continue;
				}

				if (
					this.postFormParams.messageFiles[keyOld].id == attachedFiles[keyNew].id
					|| this.postFormParams.messageFiles[keyOld].id == attachedFiles[keyNew].ID
				)
				{
					postData[ufCode].push(this.postFormParams.messageFiles[keyOld].id);
					break;
				}
			}
		}

		if (postData[ufCode].length <= 0)
		{
			postData[ufCode].push('empty');
		}
	}
}

export {
	PostFormOldManager,
}