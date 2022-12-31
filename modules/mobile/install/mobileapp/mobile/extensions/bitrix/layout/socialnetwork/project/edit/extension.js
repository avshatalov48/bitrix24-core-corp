(() => {

	const { ButtonsToolbar } = jn.require('layout/ui/buttons-toolbar');

	class ProjectEdit extends LayoutComponent
	{
		static get projectTypes()
		{
			return {
				public: 'public',
				private: 'private',
				secret: 'secret',
			};
		}

		constructor(props)
		{
			super(props);

			let avatarPreview = null;
			if (props.avatar)
			{
				avatarPreview = (props.avatar.indexOf('http') !== 0 ? `${currentDomain}${props.avatar}` : props.avatar);
			}

			this.layoutWidget = null;
			this.state = {
				userId: props.userId,
				userUploadedFilesFolder: props.userUploadedFilesFolder,
				guid: this.getGuid(),
				id: props.id,
				name: props.name,
				description: props.description,
				avatarId: props.avatarId,
				avatarFileId: null,
				avatarPreview: avatarPreview,
				avatarSelected: (props.avatar ? 'loaded' : (props.avatarType || 'folder')),
				avatarIsLoading: false,
				avatarDefaultTypes: props.avatarTypes,
				isProject: props.isProject,
				isOpened: props.isOpened,
				isVisible: props.isVisible,
				ownerData: props.ownerData,
				moderatorsData: props.moderatorsData,
				type: props.type,
				dateStart: props.dateStart,
				dateFinish: props.dateFinish,
				subject: props.subject,
				subjects: props.subjects,
				tags: props.tags,
				initiatePerms: props.initiatePerms,
			};
			this.ownerId = props.ownerData.id;

			BX.addCustomEvent('onFileUploadStatusChanged', this.onFileUploadStatusChanged.bind(this));
		}

		getGuid()
		{
			const s4 = function() {
				return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
			};

			return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
		}

		render()
		{
			return View(
				{
					resizableByKeyboard: true,
					style: {
						flex: 1,
						backgroundColor: '#eef2f4',
					},
				},
				Text({
					style: {
						color: '#333333',
						fontSize: 18,
						fontWeight: '500',
						marginLeft: 17,
						marginVertical: 15,
					},
					text: BX.message('MOBILE_LAYOUT_PROJECT_EDIT_HEADER_TITLE'),
				}),
				ScrollView(
					{
						style: {
							flex: 1,
							borderRadius: 12,
							marginBottom: 100,
						},
						bounces: false,
						showsVerticalScrollIndicator: true,
					},
					View(
						{
							style: {
								backgroundColor: '#ffffff',
								borderRadius: 12,
								paddingVertical: 5,
								paddingHorizontal: 15,
								paddingBottom: 15,
							},
						},
						FieldsWrapper({
							fields: [
								new ProjectNameField({
									value: this.state.name,
									onChange: text => this.setState({name: text}),
								}),
								new ProjectAvatarField({
									userId: this.state.userId,
									guid: this.state.guid,
									value: this.state.avatarSelected,
									loaded: this.state.avatarPreview,
									isLoading: this.state.avatarIsLoading,
									defaultImages: this.state.avatarDefaultTypes,
									userUploadedFilesFolder: this.state.userUploadedFilesFolder,
									onChange: (selected, loaded, isLoading, diskFileId) => {
										this.setState({
											avatarPreview: (loaded || this.state.avatarPreview),
											avatarSelected: selected,
											avatarIsLoading: (typeof isLoading === 'boolean' ? isLoading : this.state.avatarIsLoading),
											avatarFileId: diskFileId,
										});
									},
								}),
								new ProjectDescriptionField({
									value: this.state.description,
									onChange: text => this.setState({description: text}),
								}),
								(this.state.subjects.length > 1 && new ProjectSubjectField({
									readOnly: false,
									value: this.state.subject,
									parentWidget: this.layoutWidget,
									subjects: this.state.subjects,
									onChange: (id, title) => this.setState({subject: id}),
								})),
								new ProjectOwnerField({
									readOnly: false,
									value: this.state.ownerData.id,
									ownerData: this.state.ownerData,
									parentWidget: this.layoutWidget,
									onChange: (ownerId, ownerData) => {
										if (ownerId)
										{
											this.setState({
												ownerData: {
													id: ownerId,
													title: ownerData.title,
													imageUrl: ownerData.imageUrl,
												},
											});
										}
									},
								}),
								new ProjectModeratorsField({
									readOnly: false,
									value: this.state.moderatorsData.map(item => item.id),
									moderatorsData: this.state.moderatorsData,
									parentWidget: this.layoutWidget,
									onChange: (moderatorsIds, moderatorsData) => {
										if (moderatorsIds)
										{
											this.setState({moderatorsData});
										}
									},
								}),
								new ProjectTypeField({
									value: this.state.type,
									parentWidget: this.layoutWidget,
									onChange: (id, title) => this.setState({type: id}),
								}),
								new ProjectDateStartField({
									readOnly: false,
									value: this.state.dateStart,
									onChange: date => this.setState({dateStart: date}),
								}),
								new ProjectDateFinishField({
									readOnly: false,
									value: this.state.dateFinish,
									onChange: date => this.setState({dateFinish: date}),
								}),
								new ProjectTagsField({
									readOnly: false,
									value: this.state.tags,
									projectId: this.state.id,
									parentWidget: this.layoutWidget,
									onChange: tags => this.setState({tags}),
								}),
								new ProjectInitiatePermsField({
									value: this.state.initiatePerms,
									parentWidget: this.layoutWidget,
									onChange: (id, title) => this.setState({initiatePerms: id}),
								}),
							],
						}),
					),
				),
				ButtonsToolbar({
					buttons: [
						new CancelButton({
							text: BX.message('MOBILE_LAYOUT_PROJECT_EDIT_CANCEL'),
							onClick: () => this.onCancelClick(),
							style: {},
						}),
						new PrimaryButton({
							text: BX.message('MOBILE_LAYOUT_PROJECT_EDIT_SAVE'),
							onClick: () => this.onSaveClick(),
							style: {},
						}),
					],
				}),
			);
		}

		onSaveClick()
		{
			if (!this.state.name || this.state.name.trim() === '')
			{
				Notify.showIndicatorError({
					text: BX.message('MOBILE_LAYOUT_PROJECT_EDIT_ERROR_NO_TITLE'),
					hideAfter: 3000,
				});
				return;
			}
			if (this.state.avatarSelected === 'loaded' && this.state.avatarIsLoading)
			{
				Notify.showIndicatorError({
					text: BX.message('MOBILE_LAYOUT_PROJECT_EDIT_ERROR_AVATAR_IS_UPLOADING'),
					hideAfter: 3000,
				});
				return;
			}

			Notify.showIndicatorLoading();
			Action.save(this.state, this.ownerId)
				.then(
					(response) => this.close(),
					(response) => {
						Notify.showIndicatorError({
							text: response.error.description,
							hideAfter: 3000,
						});
					}
				)
				.catch((response) => {
					console.log(response);

					Notify.showIndicatorError({
						text: 'Something goes wrong',
						hideAfter: 3000,
					});
				})
			;
		}

		onCancelClick()
		{
			this.close();
		}

		close(callback = () => {})
		{
			const resultCallback = () => {
				callback();
				BX.onCustomEvent('ProjectEdit:close', [{id: this.state.id}]);
			}

			if (this.layoutWidget)
			{
				this.layoutWidget.close(resultCallback);
			}
		}

		onFileUploadStatusChanged(eventName, eventData, taskId)
		{
			if (taskId.indexOf('projectAvatar-') !== 0)
			{
				return false;
			}

			switch (eventName)
			{
				case BX.FileUploadEvents.FILE_CREATED:
				{
					if (eventData.file.params.guid !== this.state.guid)
					{
						break;
					}
					this.setState({
						avatarFileId: eventData.result.data.file.id,
						avatarIsLoading: false,
					});
					break;
				}

				case BX.FileUploadEvents.FILE_UPLOAD_START:
				case BX.FileUploadEvents.FILE_UPLOAD_PROGRESS:
				case BX.FileUploadEvents.ALL_TASK_COMPLETED:
				case BX.FileUploadEvents.TASK_TOKEN_DEFINED:
				case BX.FileUploadEvents.TASK_CREATED:
				default:
					// do nothing
					break;

				case BX.FileUploadEvents.TASK_STARTED_FAILED:
				case BX.FileUploadEvents.FILE_CREATED_FAILED:
				case BX.FileUploadEvents.FILE_UPLOAD_FAILED:
				case BX.FileUploadEvents.TASK_CANCELLED:
				case BX.FileUploadEvents.TASK_NOT_FOUND:
				case BX.FileUploadEvents.FILE_READ_ERROR:
					break;
			}

			return true;
		}
	}

	class Action
	{
		static save(fields, ownerId)
		{
			return new Promise((resolve, reject) => {
				Action.saveProjectFields(fields).then(
					(response) => {
						Action.saveModerators(fields).then(
							(response) => {
								Action.saveOwner(fields, ownerId).then(
									response => resolve(),
									response => reject(response)
								);
							},
							response => reject(response)
						);
					},
					response => reject(response)
				);
			});
		}

		static saveProjectFields(fields)
		{
			return new Promise((resolve, reject) => {
				const isCustomAvatarSelected = (fields.avatarSelected === 'loaded');
				const isCustomAvatarLoaded = !!fields.avatarFileId;
				const isCustomAvatarExisted = !!fields.avatarId;
				let imageFileId = null;

				if (isCustomAvatarSelected)
				{
					if (isCustomAvatarLoaded)
					{
						imageFileId = fields.avatarFileId;
					}
				}
				else if (isCustomAvatarExisted)
				{
					imageFileId = false;
				}

				(new RequestExecutor('sonet_group.update', {
					...{
						GROUP_ID: fields.id,
						NAME: fields.name,
						DESCRIPTION: fields.description,
						IMAGE_FILE_ID: imageFileId,
						AVATAR_TYPE: (isCustomAvatarSelected ? null : fields.avatarSelected),
						SUBJECT_ID: fields.subject,
						PROJECT_DATE_START: (fields.dateStart ? new Date(fields.dateStart * 1000).toISOString() : null),
						PROJECT_DATE_FINISH: (fields.dateFinish ? new Date(fields.dateFinish * 1000).toISOString(): null),
						INITIATE_PERMS: fields.initiatePerms,
						KEYWORDS: fields.tags.join(','),
					},
					...Action.typeToFields(fields.type),
				}))
					.call()
					.then(
						response => resolve(response),
						response => reject(response)
					)
				;
			});
		}

		static saveModerators(fields)
		{
			return new Promise((resolve, reject) => {
				const moderatorsIds = fields.moderatorsData.map(item => item.id);

				(new RequestExecutor('socialnetwork.api.usertogroup.setModerators', {
					groupId: fields.id,
					userIds: (moderatorsIds.length > 0 ? moderatorsIds : [0]),
				}))
					.call()
					.then(
						response => resolve(response),
						response => reject(response)
					)
				;
			});
		}

		static saveOwner(fields, ownerId)
		{
			return new Promise((resolve, reject) => {
				if (ownerId === fields.ownerData.id)
				{
					resolve();
					return;
				}

				(new RequestExecutor('socialnetwork.api.usertogroup.setowner', {
					userId: fields.ownerData.id,
					groupId: fields.id,
				}))
					.call()
					.then(
						response => resolve(response),
						response => reject(response)
					)
				;
			});
		}

		static typeToFields(type)
		{
			if (!Object.keys(ProjectEdit.projectTypes).includes(type))
			{
				type = ProjectEdit.projectTypes.public;
			}

			let isOpened = 'N';
			let isVisible = 'N';

			if (type === ProjectEdit.projectTypes.private)
			{
				isVisible = 'Y';
			}
			else if (type === ProjectEdit.projectTypes.public)
			{
				isOpened = 'Y';
				isVisible = 'Y';
			}

			return {
				OPENED: isOpened,
				VISIBLE: isVisible,
			};
		}
	}

	class ProjectEditManager
	{
		static open(data, parentWidget = null)
		{
			const projectEdit = new ProjectEdit(data);
			const widgetParams = {
				backdrop: {
					bounceEnable: true,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: true,
					horizontalSwipeAllowed: false,
				},
				onReady: (layoutWidget) => {
					projectEdit.layoutWidget = layoutWidget;
					layoutWidget.showComponent(projectEdit);
				},
				onError: error => console.log(error),
			};

			if (parentWidget)
			{
				parentWidget.openWidget('layout', widgetParams);
			}
			else
			{
				PageManager.openWidget('layout', widgetParams);
			}
		}
	}

	this.ProjectEdit = ProjectEdit;
	this.ProjectEditManager = ProjectEditManager;
})();