(() => {
	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { TrialFeatureActivation } = require('layout/socialnetwork/project/create/trial-feature-activation');

	class ProjectCreate extends LayoutComponent
	{
		static get projectTypes()
		{
			return {
				public: 'public',
				private: 'private',
				secret: 'secret',
			};
		}

		static get initiatePerms()
		{
			return {
				owner: 'A',
				headers: 'E',
				members: 'K',
			};
		}

		constructor(props)
		{
			super(props);

			this.layoutWidget = null;
			this.state = {
				showLoading: true,
				userId: props.userId,
				ownerData: {
					id: 0,
					title: '',
					imageUrl: '',
				},
				moderatorsData: [],
				userUploadedFilesFolder: false,
				guid: this.getGuid(),
				name: '',
				description: '',
				avatarFileId: null,
				avatarPreview: null,
				avatarSelected: 'folder',
				avatarIsLoading: false,
				avatarDefaultTypes: {},
				type: ProjectCreate.projectTypes.public,
				dateStart: 0,
				dateFinish: 0,
				subject: 0,
				subjects: [],
				tags: [],
				initiatePerms: ProjectCreate.initiatePerms.members,
			};

			BX.addCustomEvent('onFileUploadStatusChanged', this.onFileUploadStatusChanged.bind(this));
		}

		getGuid()
		{
			const s4 = function() {
				return Math.floor((1 + Math.random()) * 0x10000).toString(16).slice(1);
			};

			return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
		}

		componentDidMount()
		{
			Promise.allSettled([
				this.getAvatarDefaultTypes(),
				this.getUploadedFilesFolder(),
				this.getSubjects(),
				this.getOwnerData(),
			]).then(() => this.setState({ showLoading: false })).catch(console.error);
		}

		getAvatarDefaultTypes()
		{
			return new Promise((resolve) => {
				if (Object.keys(this.state.avatarDefaultTypes).length > 0)
				{
					return resolve();
				}
				(new RequestExecutor('socialnetwork.api.workgroup.getAvatarTypes'))
					.call()
					.then((response) => {
						this.setState({ avatarDefaultTypes: response.result });
						resolve();
					}).catch(console.error);
			});
		}

		getUploadedFilesFolder()
		{
			return new Promise((resolve) => {
				if (this.state.userUploadedFilesFolder)
				{
					return resolve();
				}
				(new RequestExecutor('mobile.disk.getUploadedFilesFolder'))
					.call()
					.then((response) => {
						this.setState({ userUploadedFilesFolder: response.result });
						resolve();
					}).catch(console.error);
			});
		}

		getSubjects()
		{
			return new Promise((resolve) => {
				if (this.state.subjects.length > 0)
				{
					return resolve();
				}
				(new RequestExecutor('sonet_group_subject.get'))
					.call()
					.then((response) => {
						this.setState({ subjects: response.result });
						resolve();
					}).catch(console.error);
			});
		}

		getOwnerData()
		{
			return new Promise((resolve) => {
				if (this.state.ownerData.id)
				{
					return resolve();
				}
				(new RequestExecutor('mobile.user.get', { filter: { ID: this.state.userId } }))
					.call()
					.then((response) => {
						const user = response.result[0];
						this.setState({
							ownerData: {
								id: this.state.userId,
								title: user.NAME_FORMATTED,
								imageUrl: (user.PERSONAL_PHOTO_ORIGINAL || null),
							},
						});
						resolve();
					}).catch(console.error);
			});
		}

		render()
		{
			if (this.state.showLoading)
			{
				return View({}, new LoadingScreenComponent());
			}

			return View(
				{
					resizableByKeyboard: true,
					style: {
						flex: 1,
						backgroundColor: AppTheme.colors.bgSecondary,
					},
				},
				ScrollView(
					{
						style: {
							flex: 1,
							borderRadius: 12,
							marginBottom: 15,
						},
						bounces: false,
						showsVerticalScrollIndicator: true,
					},
					View(
						{},
						View(
							{
								style: {
									backgroundColor: AppTheme.colors.bgContentPrimary,
									borderRadius: 12,
									paddingVertical: 5,
									paddingHorizontal: 15,
								},
							},
							FieldsWrapper({
								fields: [
									new ProjectNameField({
										value: this.state.name,
										focus: true,
										onChange: (text) => this.setState({ name: text }),
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
									new ProjectTypeField({
										value: this.state.type,
										parentWidget: this.layoutWidget,
										onChange: (id, title) => this.setState({ type: id }),
									}),
								],
							}),
						),
						this.renderAdvancedSettingsButton(),
						this.renderAdvancedSettingsFilledFields(),
					),
				),
			);
		}

		renderAdvancedSettingsButton()
		{
			return View(
				{
					style: {
						paddingTop: 12,
						paddingLeft: 15,
					},
					onClick: () => {
						Keyboard.dismiss();
						AdvancedSettingsManager.open(
							{
								description: this.state.description,
								dateStart: this.state.dateStart,
								dateFinish: this.state.dateFinish,
								subjects: this.state.subjects,
								subject: this.state.subject,
								initiatePerms: this.state.initiatePerms,
								ownerData: this.state.ownerData,
								moderatorsData: this.state.moderatorsData,
								tags: this.state.tags,
								onFieldsSave: (fields) => this.setState(fields),
							},
							this.layoutWidget,
						);
					},
				},
				BBCodeText({
					style: {
						fontSize: 13,
						color: AppTheme.colors.base5,
					},
					value: `[d type=dot color=#bdc1c6]${BX.message(
						'MOBILE_LAYOUT_PROJECT_CREATE_ADVANCED_SETTINGS_BUTTON',
					)}[/d]`,
				}),
			);
		}

		renderAdvancedSettingsFilledFields()
		{
			const fields = {
				description: {
					isEmpty: (this.state.description === ''),
					message: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_ADVANCED_SETTINGS_FILLED_FIELDS_DESCRIPTION'),
				},
				subject: {
					isEmpty: (this.state.subjects.length <= 1),
					message: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_ADVANCED_SETTINGS_FILLED_FIELDS_SUBJECT'),
				},
				ownerData: {
					isEmpty: false,
					message: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_ADVANCED_SETTINGS_FILLED_FIELDS_OWNER'),
				},
				moderatorsData: {
					isEmpty: (this.state.moderatorsData.length === 0),
					message: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_ADVANCED_SETTINGS_FILLED_FIELDS_MODERATORS'),
				},
				dateStart: {
					isEmpty: !this.state.dateStart,
					message: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_ADVANCED_SETTINGS_FILLED_FIELDS_DATE_START'),
				},
				dateFinish: {
					isEmpty: !this.state.dateFinish,
					message: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_ADVANCED_SETTINGS_FILLED_FIELDS_DATE_FINISH'),
				},
				tags: {
					isEmpty: (this.state.tags.length === 0),
					message: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_ADVANCED_SETTINGS_FILLED_FIELDS_TAGS'),
				},
				initiatePerms: {
					isEmpty: false,
					message: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_ADVANCED_SETTINGS_FILLED_FIELDS_INITIATE_PERMS'),
				},
			};
			const filledFields = Object.values(fields).reduce((result, field) => {
				if (!field.isEmpty)
				{
					result.push(field.message);
				}

				return result;
			}, []).join(', ');

			return View(
				{
					style: {
						paddingTop: 7,
						paddingLeft: 15,
					},
				},
				Text({
					style: {
						fontSize: 13,
						color: AppTheme.colors.base4,
					},
					text: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_ADVANCED_SETTINGS_FILLED_FIELDS').replace(
						'#FIELDS#',
						filledFields,
					),
				}),
			);
		}

		close(callback = () => {
		})
		{
			if (this.layoutWidget)
			{
				this.layoutWidget.close(callback);
			}
		}

		getNextButton()
		{
			return {
				name: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_HEADER_BUTTON_NEXT'),
				callback: this.onNextButtonClick.bind(this),
				color: AppTheme.colors.accentMainLinks,
			};
		}

		onNextButtonClick()
		{
			if (this.state.showLoading || this.isCreating)
			{
				return;
			}

			if (!this.state.name || this.state.name.trim() === '')
			{
				Notify.showIndicatorError({
					text: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_ERROR_NO_TITLE'),
					hideAfter: 3000,
				});

				return;
			}

			if (this.state.avatarSelected === 'loaded' && this.state.avatarIsLoading)
			{
				Notify.showIndicatorError({
					text: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_ERROR_AVATAR_IS_UPLOADING'),
					hideAfter: 3000,
				});

				return;
			}

			void this.layoutWidget.openWidget('selector', {
				title: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_HEADER_TITLE_ADD_MEMBERS'),
			}).then((selector) => {
				let items = [];
				selector.on('onSelectedChanged', (data) => {
					items = data.items;
				});
				selector.setRightButtons([
					{
						name: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_HEADER_BUTTON_CREATE'),
						color: AppTheme.colors.accentMainLinks,
						callback: () => {
							if (this.isCreating)
							{
								return;
							}
							this.isCreating = true;
							Notify.showIndicatorLoading();

							Action.create(this.state).then(
								(response) => {
									const projectId = response.result;
									Action.inviteMembers(this.state.moderatorsData, items, projectId).then(() => {
										void Action.setOwner(this.state, projectId);
									});
									Action.turnOnTrial().then((isTrialTurnedOn) => {
										this.close(isTrialTurnedOn ? TrialFeatureActivation.open : undefined);
									});
								},
								(response) => {
									Notify.showIndicatorError({
										text: response.error.description,
										hideAfter: 3000,
									});
									setTimeout(() => {
										this.isCreating = false;
										this.layoutWidget.back();
									}, 3000);
								},
							);
						},
					},
				]);
				void new RecipientSelector('GROUP_INVITE', ['user', 'department'], selector)
					.setEntitiesOptions({
						user: {
							options: {
								intranetUsersOnly: true,
							},
							searchable: true,
							dynamicLoad: true,
							dynamicSearch: true,
						},
						department: {
							options: {
								selectMode: 'departmentsOnly',
								allowFlatDepartments: true,
							},
							searchable: true,
							dynamicLoad: true,
							dynamicSearch: true,
						},
					})
					.open()
				;
			});
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
		static create(fields)
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('sonet_group.create', {

					NAME: fields.name,
					DESCRIPTION: fields.description,
					IMAGE_FILE_ID: (fields.avatarSelected === 'loaded' ? fields.avatarFileId : null),
					AVATAR_TYPE: (fields.avatarSelected === 'loaded' ? null : fields.avatarSelected),
					PROJECT: 'Y',
					GROUP_THEME_ID: '',
					SUBJECT_ID: (fields.subject || null),
					PROJECT_DATE_START: (fields.dateStart ? new Date(fields.dateStart * 1000).toISOString() : null),
					PROJECT_DATE_FINISH: (fields.dateFinish ? new Date(fields.dateFinish * 1000).toISOString() : null),
					INITIATE_PERMS: fields.initiatePerms,
					KEYWORDS: fields.tags.join(','),
					...Action.typeToFields(fields.type),
				}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => reject(response),
					)
				;
			});
		}

		static inviteMembers(moderators, members, projectId)
		{
			const moderatorsIds = moderators.map((item) => item.id);
			const users = members.filter((item) => item.params.type === 'user').map((item) => item.params.id);
			const departments = members.filter((item) => item.params.type === 'department').map((item) => item.params.id);

			return Promise.allSettled([
				Action.inviteModerators(moderatorsIds, projectId),
				Action.inviteUsers(users, projectId),
				Action.inviteDepartments(departments, projectId),
			]);
		}

		static inviteModerators(moderators, projectId)
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.usertogroup.setModerators', {
					groupId: projectId,
					userIds: moderators,
				}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => reject(response),
					).catch(console.error);
			});
		}

		static inviteUsers(users, projectId)
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('sonet_group.user.invite', {
					GROUP_ID: projectId,
					USER_ID: users,
					MESSAGE: '',
				}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => reject(response),
					)
				;
			});
		}

		static inviteDepartments(departments, projectId)
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('sonet_group.update', {
					GROUP_ID: projectId,
					UF_SG_DEPT: departments,
				}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => reject(response),
					).catch(console.error);
			});
		}

		static setOwner(fields, projectId)
		{
			return new Promise((resolve, reject) => {
				if (Number(fields.userId) === Number(fields.ownerData.id))
				{
					return resolve();
				}

				(new RequestExecutor('socialnetwork.api.usertogroup.setowner', {
					userId: fields.ownerData.id,
					groupId: projectId,
				}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => reject(response),
					).catch(console.error);
			});
		}

		static typeToFields(type)
		{
			if (!Object.keys(ProjectCreate.projectTypes).includes(type))
			{
				type = ProjectCreate.projectTypes.public;
			}

			let isOpened = 'N';
			let isVisible = 'N';

			if (type === ProjectCreate.projectTypes.private)
			{
				isVisible = 'Y';
			}
			else if (type === ProjectCreate.projectTypes.public)
			{
				isOpened = 'Y';
				isVisible = 'Y';
			}

			return {
				OPENED: isOpened,
				VISIBLE: isVisible,
			};
		}

		static turnOnTrial()
		{
			return new Promise((resolve) => {
				new RunActionExecutor('socialnetwork.api.workgroup.turnOnTrial')
					.setHandler((response) => resolve(response.data))
					.call(false)
				;
			});
		}
	}

	class ProjectCreateManager
	{
		static open(userId)
		{
			const projectCreate = new ProjectCreate({ userId });

			PageManager.openWidget('layout', {
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					bounceEnable: true,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
					navigationBarColor: AppTheme.colors.bgSecondary,
				},
				onReady: (layoutWidget) => {
					layoutWidget.setTitle({ text: BX.message('MOBILE_LAYOUT_PROJECT_CREATE_HEADER_TITLE_CREATE') });
					layoutWidget.setRightButtons([projectCreate.getNextButton()]);
					layoutWidget.enableNavigationBarBorder(false);
					layoutWidget.showComponent(projectCreate);

					projectCreate.layoutWidget = layoutWidget;
				},
				onError: console.error,
			});
		}
	}

	this.ProjectCreate = ProjectCreate;
	this.ProjectCreateManager = ProjectCreateManager;
})();
