(() => {
	class ChecklistFilesQueue
	{
		constructor()
		{
			this.queue = new Map();
		}

		getQueue()
		{
			return this.queue;
		}

		addChecklistQueue(checklistItemId)
		{
			if (!this.queue.has(checklistItemId))
			{
				this.queue.set(checklistItemId, new Set());
			}
		}

		removeChecklistQueue(checklistItemId)
		{
			if (this.queue.has(checklistItemId))
			{
				this.queue.delete(checklistItemId);
			}
		}

		getChecklistQueue(checklistItemId)
		{
			this.addChecklistQueue(checklistItemId);

			return this.queue.get(checklistItemId);
		}

		getArrayChecklistQueue(checklistItemId)
		{
			return [...this.getChecklistQueue(checklistItemId)];
		}

		addFile(checklistItemId, file)
		{
			this.getChecklistQueue(checklistItemId).add(file);
		}

		removeFile(file)
		{
			this.queue.forEach((checklistQueue) => checklistQueue.delete(file));
		}
	}

	class ChecklistFilesList extends BaseList
	{
		static id()
		{
			return 'checklistFiles';
		}

		static method()
		{
			return 'mobile.disk.getattachmentsdata';
		}

		static getTypeByFileName(name)
		{
			let extension = name.split('.').pop();
			if (extension)
			{
				extension = extension.toLowerCase();
			}

			return ChecklistFilesList.getTypeByFileExtension(extension);
		}

		static getTypeByFileExtension(extension = '')
		{
			const types = {
				jpg: 'image',
				jpeg: 'image',
				png: 'image',
				gif: 'image',
				tiff: 'image',
				bmp: 'image',
				avi: 'video',
				mov: 'video',
				mpeg: 'video',
				mp4: 'video',
			};

			return types[extension.toLowerCase()] || 'document';
		}

		static getIconByFileName(name)
		{
			let extension = name.split('.').pop();
			if (extension)
			{
				extension = extension.toLowerCase();
			}

			return ChecklistFilesList.getIconByFileExtension(extension);
		}

		static getIconByFileExtension(extension = '')
		{
			const icons = {
				pdf: 'pdf.png',
				jpg: 'img.png',
				png: 'img.png',
				doc: 'doc.png',
				docx: 'doc.png',
				ppt: 'ppt.png',
				pptx: 'ppt.png',
				rar: 'rar.png',
				xls: 'xls.png',
				csv: 'xls.png',
				xlsx: 'xls.png',
				zip: 'zip.png',
				txt: 'txt.png',
				avi: 'movie.png',
				mov: 'movie.png',
				mpeg: 'movie.png',
				mp4: 'movie.png',
			};
			let fileExtension = extension;

			if (fileExtension)
			{
				fileExtension = fileExtension.toLowerCase();
			}

			return (icons[fileExtension] ? `${icons[fileExtension]}?2` : 'blank.png?21');
		}

		static getFileSize(size)
		{
			let fileSize = size / 1024;

			if (fileSize < 1024)
			{
				fileSize = `${Math.ceil(fileSize)} ${BX.message('TASKS_TASK_DETAIL_CHECKLIST_FILES_LIST_ITEM_SIZE_KB')}`;
			}
			else
			{
				fileSize = `${(fileSize / 1024).toFixed(1)} ${BX.message('TASKS_TASK_DETAIL_CHECKLIST_FILES_LIST_ITEM_SIZE_MB')}`;
			}

			return fileSize;
		}

		prepareItem(file)
		{
			const fileSize = file.SIZE || ChecklistFilesList.getFileSize(file.size);
			const fileType = (file.EXTENSION
				? ChecklistFilesList.getTypeByFileExtension(file.EXTENSION)
				: ChecklistFilesList.getTypeByFileName(file.NAME || file.name)
			);
			const preparedItem = {
				id: String(file.ID || file.id),
				title: file.NAME || file.name,
				subtitle: `${fileSize} ${(new Date(file.UPDATE_TIME || file.updateTime)).toLocaleString()}`,
				sectionCode: 'checklistFiles',
				styles: {
					image: {
						image: {
							borderRadius: 0,
						},
					},
				},
				params: {
					previewUrl: file.URL || file.links.download,
					type: fileType,
				},
				type: 'info',
			};

			if (this.canUpdate)
			{
				preparedItem.actions = [{
					title: BX.message('TASKS_TASK_DETAIL_CHECKLIST_FILES_LIST_REMOVE'),
					color: AppTheme.colors.accentMainAlert,
					identifier: 'remove',
				}];
			}

			if (fileType === 'image')
			{
				preparedItem.imageUrl = file.URL || file.links.download;
				preparedItem.styles.image.image.borderRadius = 10;
			}
			else
			{
				const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/disk/';
				const iconName = (file.EXTENSION
					? ChecklistFilesList.getIconByFileExtension(file.EXTENSION)
					: ChecklistFilesList.getIconByFileName(file.NAME || file.name)
				);

				preparedItem.imageUrl = `${pathToExtension}/images/${iconName}`;
			}

			return preparedItem;
		}

		static prepareLoadingItem(file)
		{
			const preparedItem = {
				id: file.id,
				title: file.name,
				subtitle: BX.message('TASKS_TASK_DETAIL_CHECKLIST_FILES_LIST_ITEM_LOADING'),
				sectionCode: 'checklistFiles',
				unselectable: true,
				styles: {
					image: {
						image: {
							borderRadius: 0,
						},
					},
				},
				params: {
					previewUrl: file.previewUrl,
					type: ChecklistFilesList.getTypeByFileName(file.name),
				},
				type: 'info',
			};

			if (preparedItem.params.type === 'image')
			{
				preparedItem.imageUrl = file.previewUrl;
				preparedItem.styles.image.image.borderRadius = 10;
			}
			else
			{
				const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/disk/';
				const iconName = ChecklistFilesList.getIconByFileName(file.name);

				preparedItem.imageUrl = `${pathToExtension}/images/${iconName}`;
			}

			return preparedItem;
		}

		static prepareDiskItem(file)
		{
			const fileType = ChecklistFilesList.getTypeByFileName(file.NAME);
			const preparedItem = {
				id: file.ID,
				title: file.NAME,
				subtitle: file.TAGS,
				sectionCode: 'checklistFiles',
				styles: {
					image: {
						image: {
							borderRadius: 0,
						},
					},
				},
				params: {
					previewUrl: file.URL.URL,
					type: fileType,
				},
				actions: [{
					title: BX.message('TASKS_TASK_DETAIL_CHECKLIST_FILES_LIST_REMOVE'),
					color: AppTheme.colors.accentMainAlert,
					identifier: 'remove',
				}],
				type: 'info',
			};

			if (fileType === 'image')
			{
				preparedItem.imageUrl = file.URL.URL;
				preparedItem.styles.image.image.borderRadius = 10;
			}
			else
			{
				const pathToExtension = '/bitrix/mobileapp/mobile/extensions/bitrix/disk/';
				const iconName = ChecklistFilesList.getIconByFileName(file.NAME);

				preparedItem.imageUrl = `${pathToExtension}/images/${iconName}`;
			}

			return preparedItem;
		}

		static openFile(url, previewUrl = '', type, name = '')
		{
			if (type === 'video')
			{
				viewer.openVideo(url);
			}
			else if (type === 'image')
			{
				viewer.openImageCollection([{ url, previewUrl, name }]);
			}
			else
			{
				viewer.openDocument(url, name);
			}
		}

		params()
		{
			return {
				attachmentsIds: this.attachmentsIds,
			};
		}

		prepareItems(items)
		{
			const preparedItems = [];

			this.files.forEach((file) => {
				if (
					file.id.indexOf('taskChecklist-') !== 0
					&& !items.find((item) => item.ID === file.id)
					&& !this.filesStorage.getArrayFiles().find((item) => item.id === file.id)
					&& !this.filesToShow.has(file.id)
				)
				{
					preparedItems.push(file);
				}
			});
			this.files = new Map();

			items.forEach((item) => {
				const preparedItem = this.prepareItem(item);
				this.files.set(preparedItem.id, preparedItem);
				preparedItems.push(preparedItem);
			});

			if (this.isEditMode())
			{
				this.filesToShow.forEach((file) => {
					if (file.dataAttributes)
					{
						if (file.checkListItemId === this.checkListItemId)
						{
							const preparedDiskItem = ChecklistFilesList.prepareDiskItem(file.dataAttributes);
							this.files.set(preparedDiskItem.id, preparedDiskItem);
							preparedItems.push(preparedDiskItem);
						}
					}
					else if (file.extra.params.ajaxData.checkListItemId === this.checkListItemId)
					{
						const preparedItem = this.prepareItem(file);
						this.files.set(preparedItem.id, preparedItem);
						preparedItems.push(preparedItem);
					}
				});
			}

			this.filesStorage.getArrayFiles().forEach((file) => {
				if (file.params.ajaxData.checkListItemId === this.checkListItemId)
				{
					const preparedLoadingItem = ChecklistFilesList.prepareLoadingItem(file);
					this.files.set(preparedLoadingItem.id, preparedLoadingItem);
					preparedItems.push(preparedLoadingItem);
				}
			});

			return preparedItems;
		}

		get list()
		{
			return this._list;
		}

		constructor(listObject, userId, checklistData, checklistController)
		{
			super(listObject);

			this.userId = userId;
			this.checklistData = checklistData;
			this.checklistController = checklistController;

			this.canUpdate = checklistData.canUpdate;
			this.attachmentsIds = checklistData.attachmentsIds;
			this.checkListItemId = checklistData.ajaxData.checkListItemId;
			this.filesToRemoveQueue = checklistController.filesToRemoveQueue;
			this.filesToAddQueue = checklistController.filesToAddQueue;
			this.filesToShow = checklistController.filesToShow;
			this.filesStorage = checklistController.filesStorage;
			this.mode = checklistController.mode;

			this.files = new Map();

			this.setListeners();
			this.setTopButtons();
		}

		setTopButtons()
		{
			this.list.setLeftButtons([{
				name: BX.message('TASKS_TASK_DETAIL_CHECKLIST_FILES_LIST_BACK'),
				callback: () => {
					this.list.close();
				},
			}]);

			if (this.canUpdate)
			{
				this.list.setRightButtons([{
					name: BX.message('TASKS_TASK_DETAIL_CHECKLIST_FILES_LIST_ADD'),
					callback: () => {
						const { nodeId } = this.checklistData;
						this.checklistController.addFile(this.checklistData, { nodeId });
					},
				}]);
			}
		}

		setListeners()
		{
			const listeners = {
				onViewRemoved: this.onViewRemoved,
				onItemSelected: this.onItemSelected,
				onItemAction: this.onItemAction,
			};

			this.list.setListener((eventName, data) => {
				if (listeners[eventName])
				{
					listeners[eventName].apply(this, [data]);
				}
			});
		}

		isEditMode()
		{
			return (this.mode === 'edit');
		}

		addDiskFile(file)
		{
			const preparedDiskItem = ChecklistFilesList.prepareDiskItem(file);

			this.list.addItems([preparedDiskItem]);
			this.files.set(preparedDiskItem.id, preparedDiskItem);
		}

		addLoadingFile(taskId, file)
		{
			file.id = taskId;

			const preparedLoadingItem = ChecklistFilesList.prepareLoadingItem(file);

			this.list.addItems([preparedLoadingItem]);
			this.files.set(preparedLoadingItem.id, preparedLoadingItem);
		}

		addRealFile(taskId, file)
		{
			const preparedItem = Object.assign(this.prepareItem(file), { unselectable: false });

			this.list.findItem({ id: taskId }, (item) => {
				if (item)
				{
					this.list.updateItem({ id: taskId }, preparedItem);
				}
				else
				{
					this.list.addItems([preparedItem]);
				}
			});
			this.files.delete(taskId);
			this.files.set(preparedItem.id, preparedItem);
		}

		onViewRemoved()
		{
			this.checklistController.filesList = null;
		}

		onItemSelected(item)
		{
			if (item.unselectable)
			{
				return;
			}

			const { previewUrl, type } = item.params;
			ChecklistFilesList.openFile(previewUrl, previewUrl, type, item.title);
		}

		onItemAction(eventData)
		{
			const fileId = eventData.item.id;

			if (eventData.action.identifier === 'remove')
			{
				this.onItemActionDelete(fileId);
			}
		}

		onItemActionDelete(fileId)
		{
			if (!this.canUpdate)
			{
				return;
			}

			const { ajaxData } = this.checklistData;
			const { checkListItemId } = ajaxData;

			this.list.removeItem({ id: fileId });
			this.filesToRemoveQueue.addFile(checkListItemId, fileId);
			this.filesToShow.delete(fileId);
			this.files.delete(fileId);

			BX.postWebEvent('tasks.view.native::checklist.fakeRemoveFiles', {
				nodeId: this.checklistData.nodeId,
				filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
				filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
			});

			if (this.isEditMode())
			{
				this.removeFileInEditMode(ajaxData, fileId);
			}
			else
			{
				this.removeFileInViewMode(ajaxData, fileId);
			}

			if (this.files.size === 0)
			{
				this.list.close();
			}
		}

		removeFileInViewMode(ajaxData, fileId)
		{
			const { entityId, entityTypeId, checkListItemId } = ajaxData;

			BX.ajax.runAction('tasks.task.checklist.removeAttachments', {
				data: {
					checkListItemId,
					[entityTypeId]: entityId,
					attachmentsIds: [fileId],
				},
			}).then((response) => {
				if (response.status === 'success')
				{
					const { attachments } = response.data.checkListItem;

					this.attachmentsIds = this.attachmentsIds.filter((id) => id !== fileId);
					this.filesToRemoveQueue.removeFile(fileId);

					BX.postWebEvent('tasks.view.native::checklist.removeFiles', {
						attachments,
						nodeId: this.checklistData.nodeId,
						filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
						filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
					});
				}
				else
				{
					this.filesToRemoveQueue.removeFile(fileId);
					this.reload();

					BX.postWebEvent('tasks.view.native::checklist.fakeRemoveFiles', {
						nodeId: this.checklistData.nodeId,
						filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
						filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
					});
				}
			});
		}

		removeFileInEditMode(ajaxData, fileId)
		{
			const { checkListItemId } = ajaxData;

			this.filesToRemoveQueue.removeFile(fileId);

			BX.postWebEvent('tasks.view.native::checklist.removeAttachment', {
				nodeId: checkListItemId,
				attachmentId: fileId,
			});
			BX.postWebEvent('tasks.view.native::checklist.fakeRemoveFiles', {
				nodeId: this.checklistData.nodeId,
				filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
				filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
			});
		}
	}

	/**
	 * @class ChecklistController
	 */
	class ChecklistController
	{
		static getGuid()
		{
			function s4()
			{
				return Math.floor((1 + Math.random()) * 0x10000).toString(16).slice(1);
			}

			return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
		}

		constructor(taskId, userId, taskGuid, mode)
		{
			this.taskId = taskId;
			this.userId = userId;
			this.taskGuid = taskGuid;
			this.mode = mode;

			this.popupMenu = dialogs.createPopupMenu();
			this.popupMenu.setPosition('center');

			this.filesList = null;
			this.filesToAddQueue = new ChecklistFilesQueue();
			this.filesToRemoveQueue = new ChecklistFilesQueue();
			this.filesToShow = new Map();

			this.filesStorage = new TaskChecklistUploadFilesStorage();
			this.filesStorage.getArrayFiles().forEach((file) => {
				if (this.checkEvent(file.params.taskId))
				{
					const { checkListItemId } = file.params.ajaxData;
					this.filesToAddQueue.addFile(checkListItemId, file.id);
				}
			});

			this.setListeners();
		}

		setListeners()
		{
			BX.addCustomEvent('onChecklistInit', this.onChecklistInit.bind(this));
			BX.addCustomEvent('onChecklistAjaxError', this.onChecklistAjaxError.bind(this));
			BX.addCustomEvent('onChecklistAttachmentsClick', this.onChecklistAttachmentsClick.bind(this));
			BX.addCustomEvent('onChecklistSettingsClick', this.onChecklistSettingsClick.bind(this));
			BX.addCustomEvent('onChecklistInputMemberSelectorCall', this.onChecklistInputMemberSelectorCall.bind(this));
			BX.addCustomEvent('onFileUploadStatusChanged', this.onFileUploadStatusChange.bind(this));
		}

		checkEvent(taskId = null, taskGuid = null)
		{
			let idCheck = true;
			let guidCheck = true;

			if (taskId !== null)
			{
				idCheck = (Number(this.taskId) === Number(taskId));
			}

			if (taskGuid !== null)
			{
				guidCheck = (this.taskGuid === taskGuid);
			}

			return idCheck && guidCheck;
		}

		sendOnChecklistInitQueueData()
		{
			this.filesToAddQueue.getQueue().forEach((queue, checkListItemId) => {
				BX.postWebEvent('tasks.view.native::checklist.fakeAttachFiles', {
					checkListItemId,
					nodeId: null,
					filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
					filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
				});
			});
		}

		onChecklistInit(eventData)
		{
			const { taskId, taskGuid } = eventData;

			if (this.checkEvent(taskId, taskGuid))
			{
				this.sendOnChecklistInitQueueData();
			}
		}

		onChecklistAjaxError(eventData)
		{
			if (this.checkEvent(eventData.taskId, eventData.taskGuid))
			{
				InAppNotifier.showNotification({
					message: BX.message('TASKS_TASK_DETAIL_CHECKLIST_NOTIFICATION_AJAX_ERROR'),
					backgroundColor: AppTheme.colors.base1,
					time: 5,
				});
			}
		}

		onChecklistAttachmentsClick(checklistData)
		{
			const { taskId, taskGuid, ajaxData } = checklistData;
			const { checkListItemId } = ajaxData;

			if (!this.checkEvent(taskId, taskGuid))
			{
				return;
			}

			this.filesToAddQueue.addChecklistQueue(checkListItemId);
			PageManager.openWidget('list', {
				backdrop: {
					bounceEnable: true,
					swipeAllowed: true,
					showOnTop: false,
				},
				title: BX.message('TASKS_TASK_DETAIL_CHECKLIST_FILES_LIST_TITLE'),
				onReady: (list) => {
					this.filesList = new ChecklistFilesList(list, this.userId, checklistData, this);
					this.filesList.init(false);
				},
				onError: (error) => console.log(error),
			});
		}

		onChecklistSettingsClick(checklistData)
		{
			const { taskId, taskGuid, ajaxData, popupMenuItems, popupMenuSections } = checklistData;
			const { checkListItemId } = ajaxData;

			if (!this.checkEvent(taskId, taskGuid))
			{
				return;
			}

			this.filesToAddQueue.addChecklistQueue(checkListItemId);
			this.popupMenu.setData(popupMenuItems, popupMenuSections, (eventName, item) => {
				if (eventName === 'onItemSelected')
				{
					this.onPopupMenuItemSelected(checklistData, item.id);
				}
			});
			this.popupMenu.show();
		}

		onChecklistInputMemberSelectorCall(webEventData)
		{
			this.addMember('', webEventData, '');
		}

		onPopupMenuItemSelected(checklistData, itemId)
		{
			const webEventData = { nodeId: checklistData.nodeId };

			switch (itemId)
			{
				default:
					BX.postWebEvent(`tasks.view.native::checklist.${itemId}`, webEventData);
					break;

				case 'addAuditor':
				case 'addAccomplice':
					this.addMember(itemId, webEventData, itemId.replace('add', '').toLowerCase());
					break;

				case 'toAnotherChecklist':
					this.moveToAnotherChecklist(checklistData, webEventData);
					break;

				case 'addFile':
					this.addFile(checklistData, webEventData);
					break;
			}
		}

		addMember(webEventName, webEventData, memberType)
		{
			UserList.openPicker({
				allowMultipleSelection: false,
				listOptions: {
					users: {
						hideUnnamed: true,
						useRecentSelected: true,
					},
				},
			}).then((data) => {
				if (data.length > 0)
				{
					const user = data[0];

					webEventData.member = {
						id: user.id,
						nameFormatted: user.title,
						type: memberType,
					};

					if (memberType)
					{
						BX.postWebEvent(`tasks.view.native::checklist.${webEventName}`, webEventData);
						BX.postWebEvent('tasks.view.native::onItemAction', {
							taskId: this.taskId,
							taskGuid: this.taskGuid,
							name: memberType,
							values: {
								user,
							},
						});
					}
					else
					{
						dialogs.showActionSheet({
							title: BX.message('TASKS_TASK_DETAIL_CHECKLIST_ROLE_POPUP_TEXT').replace('#USER_NAME#', user.title),
							callback: (item) => {
								webEventData.member.type = item.id.toLowerCase();
								webEventData.focusInput = true;

								BX.postWebEvent(`tasks.view.native::checklist.add${item.id}`, webEventData);
								BX.postWebEvent('tasks.view.native::onItemAction', {
									taskId: this.taskId,
									taskGuid: this.taskGuid,
									name: item.id.toLowerCase(),
									values: {
										user,
									},
								});
							},
							items: [
								{
									id: 'Auditor',
									title: BX.message('TASKS_TASK_DETAIL_CHECKLIST_ROLE_POPUP_AUDITOR'),
									code: 'answer',
								},
								{
									id: 'Accomplice',
									title: BX.message('TASKS_TASK_DETAIL_CHECKLIST_ROLE_POPUP_ACCOMPLICE'),
									code: 'answer',
								},
							],
						});
					}
				}
			});
		}

		moveToAnotherChecklist(checklistData, webEventData)
		{
			const { popupChecklists } = checklistData;

			if (popupChecklists.length > 1)
			{
				const checklistChooser = dialogs.createPopupMenu();

				checklistChooser.setPosition('center');
				checklistChooser.setData(popupChecklists, [{ id: '0' }], (eventName, checklist) => {
					if (eventName === 'onItemSelected')
					{
						webEventData.checklistId = checklist.id;
						BX.postWebEvent('tasks.view.native::checklist.toAnotherChecklist', webEventData);
					}
				});
				checklistChooser.show();
			}
			else
			{
				webEventData.checklistId = popupChecklists[0].id;
				BX.postWebEvent('tasks.view.native::checklist.toAnotherChecklist', webEventData);
			}
		}

		addFile(checklistData, webEventData)
		{
			dialogs.showImagePicker(
				{
					settings: {
						resize: {
							targetWidth: -1,
							targetHeight: -1,
							sourceType: 1,
							encodingType: 0,
							mediaType: 2,
							allowsEdit: true,
							saveToPhotoAlbum: true,
							cameraDirection: 0,
						},
						maxAttachedFilesCount: 3,
						previewMaxWidth: 640,
						previewMaxHeight: 640,
						attachButton: {
							items: [
								{
									id: 'disk',
									name: BX.message('TASKS_TASK_DETAIL_IMAGE_PICKER_BITRIX24_DISK_MSGVER_1'),
									dataSource: {
										multiple: true,
										url: `/mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${this.userId}`,
									},
								},
								{
									id: 'mediateka',
									name: BX.message('TASKS_TASK_DETAIL_IMAGE_PICKER_GALLERY'),
								},
							],
						},
					},
				},
				(filesMetaArray) => {
					this.onImagePickerFileChoose(checklistData, webEventData, filesMetaArray);
				},
			);
		}

		onImagePickerFileChoose(checklistData, webEventData, files)
		{
			const { disk, ajaxData } = checklistData;
			const diskAttachments = [];
			const diskAttachmentsIds = [];
			const localAttachments = [];
			let filesFrom = 'mediateka';

			files.forEach((file) => {
				if (file.dataAttributes)
				{
					filesFrom = 'disk';
					diskAttachments.push(file);
					diskAttachmentsIds.push(file.dataAttributes.ID);
				}
				else
				{
					const taskId = `taskChecklist-${ChecklistController.getGuid()}`;

					file.ajaxData = ajaxData;
					file.taskId = this.taskId;

					localAttachments.push({
						taskId,
						id: taskId,
						params: file,
						name: file.name,
						type: file.type,
						url: file.url,
						previewUrl: file.previewUrl,
						folderId: disk.folderId,
						onDestroyEventName: TaskChecklistUploaderEvents.FILE_SUCCESS_UPLOAD,
					});
				}
			});

			if (filesFrom === 'disk')
			{
				ajaxData.attachmentsIds = diskAttachmentsIds;
				this.attachDiskFiles(ajaxData, diskAttachments, webEventData);
			}
			else
			{
				this.filesStorage.addFiles(localAttachments);
				BX.postComponentEvent('onFileUploadTaskReceived', [{ files: localAttachments }], 'background');
			}
		}

		attachDiskFiles(ajaxData, diskAttachments, webEventData)
		{
			this.sendFakeAttachFilesEvent(ajaxData, diskAttachments, webEventData);

			if (this.mode === 'edit')
			{
				this.attachDiskFilesInEditMode(ajaxData, diskAttachments, webEventData);
			}
			else
			{
				this.runAjaxAttachingFilesFromDisk(ajaxData, diskAttachments, webEventData);
			}
		}

		attachDiskFilesInEditMode(ajaxData, diskAttachments, webEventData)
		{
			const { checkListItemId } = ajaxData;

			diskAttachments.forEach((file) => {
				const fileId = file.dataAttributes.ID;

				file.checkListItemId = checkListItemId;

				this.filesToShow.set(fileId, file);
				this.filesToAddQueue.removeFile(fileId);

				if (this.filesList && this.filesList.checkListItemId === checkListItemId)
				{
					this.filesList.addDiskFile(file.dataAttributes);
				}

				const params = {
					nodeId: webEventData.nodeId,
					filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
					filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
					attachment: {
						[fileId]: fileId,
					},
				};
				BX.postWebEvent('tasks.view.native::checklist.addAttachment', params);
				BX.postWebEvent('tasks.view.native::checklist.fakeAttachFiles', params);
			});
		}

		sendFakeAttachFilesEvent(ajaxData, diskAttachments, webEventData)
		{
			const { checkListItemId } = ajaxData;

			diskAttachments.forEach((file) => this.filesToAddQueue.addFile(checkListItemId, file.dataAttributes.ID));

			BX.postWebEvent('tasks.view.native::checklist.fakeAttachFiles', {
				nodeId: webEventData.nodeId,
				filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
				filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
			});
		}

		runAjaxAttachingFilesFromDisk(ajaxData, diskAttachments, webEventData)
		{
			const { entityTypeId, entityId, checkListItemId, attachmentsIds } = ajaxData;

			BX.ajax.runAction('tasks.task.checklist.addAttachmentsFromDisk', {
				data: {
					checkListItemId,
					[entityTypeId]: entityId,
					filesIds: attachmentsIds,
				},
			}).then((response) => {
				if (response.status === 'success')
				{
					const { attachments } = response.data.checkListItem;

					diskAttachments.forEach((file) => {
						const fileId = file.dataAttributes.ID;

						if (Object.values(attachments).includes(`n${fileId}`))
						{
							this.filesToAddQueue.removeFile(fileId);
							if (this.filesList && this.filesList.checkListItemId === checkListItemId)
							{
								file.dataAttributes.ID = Object.keys(attachments).find((id) => attachments[id] === `n${fileId}`);
								this.filesList.addDiskFile(file.dataAttributes);
							}
						}
					});

					BX.postWebEvent('tasks.view.native::checklist.attachFiles', {
						attachments,
						nodeId: webEventData.nodeId,
						filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
						filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
					});
				}
			});
		}

		onFileUploadStatusChange(eventName, eventData, taskId)
		{
			if (taskId.indexOf('taskChecklist-') !== 0)
			{
				return false;
			}

			switch (eventName)
			{
				default:
					console.log('onFileUploadStatusChange::default event warning!');
					break;

				case BX.FileUploadEvents.FILE_CREATED:
				case BX.FileUploadEvents.FILE_UPLOAD_PROGRESS:
				case BX.FileUploadEvents.ALL_TASK_COMPLETED:
				case BX.FileUploadEvents.TASK_TOKEN_DEFINED:
				case BX.FileUploadEvents.TASK_CREATED:
					// do nothing
					break;

				case BX.FileUploadEvents.FILE_UPLOAD_START:
					this.onFileUploadStart(eventData, taskId);
					break;

				case TaskChecklistUploaderEvents.FILE_SUCCESS_UPLOAD:
					this.onFileUploadSuccess(eventData, taskId);
					break;

				case BX.FileUploadEvents.TASK_STARTED_FAILED:
				case BX.FileUploadEvents.FILE_CREATED_FAILED:
				case BX.FileUploadEvents.FILE_UPLOAD_FAILED:
				case BX.FileUploadEvents.TASK_CANCELLED:
				case BX.FileUploadEvents.TASK_NOT_FOUND:
				case BX.FileUploadEvents.FILE_READ_ERROR:
				case TaskChecklistUploaderEvents.FILE_FAIL_UPLOAD:
					this.onFileUploadError(eventData, taskId);
					break;
			}

			return true;
		}

		onFileUploadStart(eventData, taskId)
		{
			const file = eventData.file.params;
			const { checkListItemId, mode } = file.ajaxData;

			if (!this.checkEvent(file.taskId) || mode !== this.mode)
			{
				return;
			}

			this.filesToAddQueue.addFile(checkListItemId, taskId);
			if (this.filesList && this.filesList.checkListItemId === checkListItemId)
			{
				this.filesList.addLoadingFile(taskId, file);
			}

			const params = {
				checkListItemId,
				nodeId: checkListItemId,
				filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
				filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
			};
			delete params[(this.mode === 'edit' ? 'checkListItemId' : 'nodeId')];
			BX.postWebEvent('tasks.view.native::checklist.fakeAttachFiles', params);
		}

		handleSuccessUploadInViewMode(eventData, taskId)
		{
			const { file } = eventData;
			const { checkListItem } = eventData.result;
			const { attachments } = checkListItem;
			const checkListItemId = checkListItem.id;

			if (this.filesList && this.filesList.checkListItemId === checkListItemId)
			{
				const fileId = Object.keys(attachments).find((id) => attachments[id] === `n${file.id}`);
				if (fileId)
				{
					file.id = fileId;
					this.filesList.addRealFile(taskId, file);
				}
			}

			this.filesToAddQueue.removeFile(taskId);
			this.filesStorage.removeFiles([taskId]);

			BX.postWebEvent('tasks.view.native::checklist.attachFiles', {
				checkListItemId,
				nodeId: null,
				attachments: checkListItem.attachments,
				filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
				filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
			});
		}

		handleSuccessUploadInEditMode(eventData, taskId)
		{
			const { file } = eventData;
			const { checkListItemId } = eventData.result;

			if (this.filesList && this.filesList.checkListItemId === checkListItemId)
			{
				this.filesList.addRealFile(taskId, file);
			}

			file.id = String(file.id);

			this.filesToShow.set(file.id, file);
			this.filesToAddQueue.removeFile(taskId);
			this.filesStorage.removeFiles([taskId]);

			const params = {
				nodeId: checkListItemId,
				filesToRemove: this.filesToRemoveQueue.getArrayChecklistQueue(checkListItemId),
				filesToAdd: this.filesToAddQueue.getArrayChecklistQueue(checkListItemId),
				attachment: {
					[file.id]: file.id,
				},
			};

			BX.postWebEvent('tasks.view.native::checklist.addAttachment', params);
			BX.postWebEvent('tasks.view.native::checklist.fakeAttachFiles', params);
		}

		onFileUploadSuccess(eventData, taskId)
		{
			const fileParams = eventData.file.extra.params;
			const { mode } = fileParams.ajaxData;

			if (!this.checkEvent(fileParams.taskId) || mode !== this.mode)
			{
				return;
			}

			if (mode === 'edit')
			{
				this.handleSuccessUploadInEditMode(eventData, taskId);
			}
			else
			{
				this.handleSuccessUploadInViewMode(eventData, taskId);
			}
		}

		onFileUploadError(eventData, taskId)
		{
			this.filesToAddQueue.removeFile(taskId);
			this.filesStorage.removeFiles([taskId]);
		}
	}

	jnexport([ChecklistController, 'ChecklistController']);
})();
