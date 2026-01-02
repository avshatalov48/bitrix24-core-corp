(() => {
	const pathToImages = `${currentDomain}/bitrix/mobileapp/mobile/extensions/bitrix/layout/socialnetwork/project/images/`;

	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { Alert } = require('alert');
	const { Color } = require('tokens');
	const { Notify } = require('notify');
	const { RequestExecutor } = require('rest');
	const { QRCodeAuthComponent } = require('qrauth');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { ButtonsToolbar } = require('layout/ui/buttons-toolbar');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const { getFeatureRestriction, tariffPlanRestrictionsReady } = require('tariff-plan-restriction');
	const { CollabAccessService } = require('collab/service/access');
	const { ProjectTagsField } = require('layout/socialnetwork/project/fields/tags');
	const { ProjectMemberList } = require('project/member-list');

	class ProjectView extends LayoutComponent
	{
		static get projectTypes()
		{
			return {
				public: 'public',
				private: 'private',
				secret: 'secret',
			};
		}

		static get roles()
		{
			return {
				owner: 'A',
				moderator: 'E',
				member: 'K',
				request: 'Z',
			};
		}

		static get initiatorTypes()
		{
			return {
				group: 'G',
				user: 'U',
			};
		}

		constructor(props)
		{
			super(props);

			this.layoutWidget = null;
			this.state = {
				showLoading: (props.showLoading || false),
				showJoinedButton: false,
				userId: props.userId,
				userUploadedFilesFolder: false,
				id: props.projectId,
				subjects: [],
			};

			BX.addCustomEvent('ProjectEdit:close', (data) => this.onProjectEditClose(data));
		}

		onProjectEditClose(data)
		{
			if (Number(data.id) === Number(this.state.id))
			{
				this.updateProjectData({ updateModerators: true });
			}
		}

		componentDidMount()
		{
			if (!this.state.userUploadedFilesFolder)
			{
				(new RequestExecutor('mobile.disk.getUploadedFilesFolder'))
					.call()
					.then((response) => this.setState({ userUploadedFilesFolder: response.result }))
				;
			}

			if (this.state.subjects.length === 0)
			{
				(new RequestExecutor('sonet_group_subject.get'))
					.call()
					.then((response) => this.setState({ subjects: response.result }))
				;
			}
			this.updateProjectData({ updateModerators: true });
		}

		updateProjectData(params = {})
		{
			const promises = [
				this.updateProjectFields(),
			];
			if (params.updateModerators)
			{
				promises.push(this.updateProjectModerators());
			}

			Promise.allSettled(promises).then(() => this.setState({ showLoading: false }));
		}

		updateProjectFields()
		{
			return new Promise((resolve) => {
				(new RequestExecutor('socialnetwork.api.workgroup.get', {
					params: {
						select: [
							'AVATAR',
							'AVATAR_TYPES',
							'OWNER_DATA',
							'SUBJECT_DATA',
							'TAGS',
							'THEME_DATA',
							'ACTIONS',
							'USER_DATA',
						],
						groupId: this.state.id,
					},
				}))
					.call()
					.then((response) => {
						const props = response.result;
						this.setState({
							showJoinedButton: false,
							userRole: props.USER_DATA.ROLE,
							userInitiatedByType: props.USER_DATA.INITIATED_BY_TYPE,
							id: props.ID,
							name: props.NAME,
							description: props.DESCRIPTION,
							avatar: props.AVATAR,
							avatarId: props.IMAGE_ID,
							avatarType: props.AVATAR_TYPE,
							avatarTypes: props.AVATAR_TYPES,
							isProject: (props.PROJECT === 'Y'),
							isOpened: (props.OPENED === 'Y'),
							isVisible: (props.VISIBLE === 'Y'),
							membersCount: Number(props.NUMBER_OF_MEMBERS),
							membersCountPlural: Number(props.NUMBER_OF_MEMBERS_PLURAL),
							dateStart: props.PROJECT_DATE_START && (Date.parse(props.PROJECT_DATE_START) / 1000),
							dateFinish: props.PROJECT_DATE_FINISH && (Date.parse(props.PROJECT_DATE_FINISH) / 1000),
							dateStartFormatted: props.FORMATTED_PROJECT_DATE_START,
							dateFinishFormatted: props.FORMATTED_PROJECT_DATE_FINISH,
							ownerData: {
								id: props.OWNER_DATA.ID,
								title: props.OWNER_DATA.FORMATTED_NAME,
								imageUrl: props.OWNER_DATA.PHOTO,
							},
							subjectData: props.SUBJECT_DATA,
							tags: props.TAGS,
							themeData: props.THEME_DATA,
							actions: props.ACTIONS,
							type: this.getType(props),
							initiatePerms: props.INITIATE_PERMS,
						});
						resolve();
					})
					.catch((errorResponse) => {
						if (errorResponse?.error?.code === 'SONET_CONTROLLER_WORKGROUP_NOT_FOUND')
						{
							Alert.alert(
								Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_ACCESS_DENIED_TITLE'),
								Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_ACCESS_DENIED_DESC'),
								() => {
									this.layoutWidget.close();
								},
							);
						}
						else
						{
							console.error(errorResponse);
						}
					})
				;
			});
		}

		updateProjectModerators()
		{
			return new Promise((resolve) => {
				(new RequestExecutor('socialnetwork.api.usertogroup.list', {
					select: [
						'ID',
						'USER_ID',
						'USER_NAME',
						'USER_LAST_NAME',
						'USER_SECOND_NAME',
						'USER_LOGIN',
						'USER_WORK_POSITION',
						'USER_PERSONAL_PHOTO',
					],
					filter: {
						GROUP_ID: this.state.id,
						ROLE: 'E',
					},
				}))
					.call()
					.then((response) => {
						this.setState({
							moderatorsData: response.result.relations.map((item) => ({
								id: item.userId,
								title: item.formattedUserName,
								imageUrl: item.image,
							})),
						});
					})
					.catch(console.error)
				;
				resolve();
			});
		}

		getType(props)
		{
			const isOpened = (props.OPENED === 'Y');
			const isVisible = (props.VISIBLE === 'Y');

			if (isVisible)
			{
				if (isOpened)
				{
					return ProjectView.projectTypes.public;
				}

				return ProjectView.projectTypes.private;
			}

			return ProjectView.projectTypes.secret;
		}

		render()
		{
			if (this.state.showLoading)
			{
				return View({}, new LoadingScreenComponent());
			}

			const themeImageStyle = {
				backgroundResizeMode: 'cover',
			};

			if (this.state.themeData)
			{
				const imageUrl = this.state.themeData.prefetchImages[0];

				if (imageUrl && imageUrl.split('.').pop())
				{
					const extension = imageUrl.split('.').pop().toLowerCase();
					if (extension === 'svg')
					{
						themeImageStyle.backgroundImageSvgUrl = `${currentDomain}${this.state.themeData.previewImage}`;
						themeImageStyle.backgroundColor = this.state.themeData.previewColor;
					}
					else
					{
						themeImageStyle.backgroundImage = `${currentDomain}${imageUrl}`;
					}
				}
				else
				{
					themeImageStyle.backgroundImageSvgUrl = `${currentDomain}${this.state.themeData.previewImage}`;
					themeImageStyle.backgroundColor = this.state.themeData.previewColor;
				}
			}

			return View(
				{},
				ScrollView(
					{
						style: {
							flex: 1,
							backgroundColor: AppTheme.colors.bgPrimary,
						},
					},
					View(
						{},
						View(
							{
								style: themeImageStyle,
							},
							this.renderFog(),
							this.renderProjectInfo(),
						),
						View(
							{
								style: {
									top: -12,
									borderRadius: 12,
								},
							},
							this.renderProjectFields(this.state),
						),
					),
				),
				this.renderButtonsToolbar(),
			);
		}

		renderFog()
		{
			return View(
				{
					style: {
						position: 'absolute',
						left: 0,
						top: 0,
						width: '100%',
						height: '100%',
						backgroundColor: AppTheme.colors.base0,
						opacity: 0.5,
					},
				},
			);
		}

		renderProjectInfo()
		{
			return View(
				{
					style: {
						marginLeft: 18,
						marginRight: 13,
						marginTop: 12,
					},
				},
				View(
					{},
					this.renderMore(),
					this.renderAvatar(),
					this.renderTitle(),
					this.renderDescription(),
				),
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
							top: -20,
							marginTop: 55,
						},
					},
					this.renderType(),
					this.renderMembers(),
				),
			);
		}

		renderMore()
		{
			return IconView({
				size: 30,
				style: {
					alignSelf: 'flex-end',
				},
				icon: Icon.MORE,
				color: Color.baseWhiteFixed,
				testId: 'project_view_more',
				onClick: this.showMoreContextMenu,
			});
		}

		showMoreContextMenu = () => {
			const actions = [
				{
					id: 'members',
					title: Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_MENU_MEMBERS'),
					icon: Icon.THREE_PERSONS,
					onClickCallback: () => new Promise((resolve) => {
						contextMenu.close(() => {
							ProjectViewManager.openProjectMemberList(
								this.state.userId,
								this.state.id,
								{
									isOwner: (this.state.userRole === ProjectView.roles.owner),
									canInvite: this.state.actions.INVITE,
								},
								this.layoutWidget,
							);
						});
						resolve({ closeMenu: false });
					}),
				},
			];
			if (this.state.actions.EDIT)
			{
				actions.push(
					{
						id: 'edit',
						title: Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_MENU_EDIT'),
						icon: Icon.EDIT,
						onClickCallback: () => new Promise((resolve) => {
							contextMenu.close(() => {
								ProjectEditManager.open(
									{
										userId: this.state.userId,
										userUploadedFilesFolder: this.state.userUploadedFilesFolder,
										id: this.state.id,
										name: this.state.name,
										description: this.state.description,
										avatar: this.state.avatar,
										avatarId: this.state.avatarId,
										avatarType: this.state.avatarType,
										avatarTypes: this.state.avatarTypes,
										isProject: this.state.isProject,
										isOpened: this.state.isOpened,
										isVisible: this.state.isVisible,
										type: this.state.type,
										ownerData: this.state.ownerData,
										moderatorsData: this.state.moderatorsData,
										dateStart: this.state.dateStart,
										dateFinish: this.state.dateFinish,
										subject: this.state.subjectData.ID,
										subjects: this.state.subjects,
										tags: this.state.tags,
										initiatePerms: this.state.initiatePerms,
									},
									this.layoutWidget,
								);
							});
							resolve({ closeMenu: false });
						}),
					},
					{
						id: 'perms',
						title: Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_MENU_PERMISSIONS'),
						icon: Icon.LOCK,
						onClickCallback: () => new Promise((resolve) => {
							contextMenu.close(() => {
								QRCodeAuthComponent.open(this.layoutWidget, {
									redirectUrl: `/workgroups/group/${this.state.id}/features/`,
									showHint: true,
									title: Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_MENU_PERMISSIONS'),
								});
							});
							resolve({ closeMenu: false });
						}),
					},
				);
			}

			if (this.state.actions.LEAVE)
			{
				actions.push({
					id: 'leave',
					title: Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_MENU_LEAVE'),
					icon: Icon.LOG_OUT,
					onClickCallback: () => new Promise((resolve) => {
						contextMenu.close();
						resolve({ closeMenu: false });
						Action.leave(this.state.id).then(
							(response) => this.updateProjectData(),
							(response) => console.log(response),
						);
					}),
				});
			}

			if (this.state.actions.DELETE)
			{
				actions.push({
					id: 'delete',
					title: Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_MENU_DELETE'),
					icon: Icon.TRASHCAN,
					onClickCallback: () => new Promise((resolve) => {
						contextMenu.close(() => {
							const deleteContextMenu = new ContextMenu({
								params: {
									title: Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_MENU_DELETE_TITLE'),
									showCancelButton: false,
								},
								actions: [
									{
										id: 'deleteYes',
										title: Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_MENU_DELETE_YES'),
										onClickCallback: () => new Promise((resolve) => {
											setTimeout(() => Notify.showIndicatorLoading(), 500);
											deleteContextMenu.close();
											resolve({ closeMenu: false });
											Action.delete(this.state.id).then(
												(response) => {
													const { result } = response;
													if (typeof result === 'string')
													{
														setTimeout(() => {
															Notify.showIndicatorError({
																text: result,
																hideAfter: 3000,
															});
														}, 500);
													}
													else if (result === true)
													{
														this.layoutWidget.close();
													}
												},
												(response) => Notify.hideCurrentIndicator(),
											);
										}),
									},
									{
										id: 'deleteNo',
										title: Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_MENU_DELETE_NO'),
										onClickCallback: () => new Promise((resolve) => {
											deleteContextMenu.close();
											resolve({ closeMenu: false });
										}),
									},
								],
							});
							deleteContextMenu.show(this.layoutWidget);
						});
						resolve({ closeMenu: false });
					}),
				});
			}

			const contextMenu = new ContextMenu({
				params: {
					showCancelButton: false,
				},
				actions,
			});
			contextMenu.show(this.layoutWidget);
		};

		renderAvatar()
		{
			let uri = `${pathToImages}mobile-layout-project-default-avatar.png`;
			if (this.state.avatar)
			{
				uri = this.state.avatar;
				uri = (uri.indexOf('http') === 0 ? uri : `${currentDomain}${uri}`);
			}
			else if (this.state.avatarType)
			{
				uri = this.state.avatarTypes[this.state.avatarType].mobileUrl;
				uri = `${currentDomain}${uri}`;
			}

			return Image({
				style: {
					width: 77,
					height: 77,
					borderRadius: 38,
				},
				uri: encodeURI(uri),
			});
		}

		renderTitle()
		{
			return Text({
				style: {
					fontSize: 20,
					fontWeight: 'bold',
					color: AppTheme.colors.bgContentPrimary,
					marginTop: 18,
				},
				ellipsize: 'end',
				numberOfLines: 1,
				text: this.state.name,
			});
		}

		renderDescription()
		{
			return Text({
				style: {
					fontSize: 14,
					color: AppTheme.colors.bgContentPrimary,
					marginTop: 9,
					minHeight: 50,
				},
				ellipsize: 'end',
				numberOfLines: 3,
				text: this.state.description,
			});
		}

		renderType()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Image({
					style: {
						width: 24,
						height: 24,
					},
					uri: `${pathToImages}mobile-layout-project-type-public.png`,
				}),
				Text({
					style: {
						fontSize: 14,
						color: AppTheme.colors.baseWhiteFixed,
						marginLeft: 3,
					},
					numberOfLines: 1,
					text: ProjectTypeField.types[this.state.type],
				}),
			);
		}

		renderMembers()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
					onClick: () => {
						ProjectViewManager.openProjectMemberList(
							this.state.userId,
							this.state.id,
							{
								isOwner: (this.state.userRole === ProjectView.roles.owner),
								canInvite: this.state.actions.INVITE,
							},
							this.layoutWidget,
						);
					},
				},
				IconView({
					size: 24,
					icon: Icon.THREE_PERSONS,
					color: Color.baseWhiteFixed,
				}),
				Text({
					style: {
						fontSize: 14,
						color: AppTheme.colors.baseWhiteFixed,
						marginLeft: 3,
					},
					numberOfLines: 1,
					text: Loc.getMessage(`MOBILE_LAYOUT_PROJECT_VIEW_MEMBERS_COUNT_${this.state.membersCountPlural}`)
						.replace('#COUNT#', this.state.membersCount)
					,
				}),
				IconView({
					size: 24,
					style: {
						marginLeft: 3,
					},
					icon: Icon.CHEVRON_TO_THE_RIGHT_SIZE_S,
					color: Color.baseWhiteFixed,
				}),
			);
		}

		renderProjectFields()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						paddingVertical: 10,
						paddingHorizontal: 16,
					},
				},
				new ProjectOwnerField({
					readOnly: true,
					value: this.state.ownerData.id,
					ownerData: this.state.ownerData,
					parentWidget: this.layoutWidget,
				}),
				new ProjectSubjectField({
					readOnly: true,
					value: this.state.subjectData.NAME,
				}),
				(this.state.isProject && new ProjectDateStartField({
					readOnly: true,
					value: this.state.dateStart,
				})),
				(this.state.isProject && new ProjectDateFinishField({
					readOnly: true,
					value: this.state.dateFinish,
				})),
				(this.state.tags.length > 0 && new ProjectTagsField({
					readOnly: true,
					value: this.state.tags,
				})),
			);
		}

		renderButtonsToolbar()
		{
			const buttons = [];

			if (this.state.showJoinedButton)
			{
				buttons.push(
					new SuccessButton({
						text: Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_JOIN_BUTTON_JOINED'),
						icon: '<svg xmlns="http://www.w3.org/2000/svg" width="29" height="28" viewBox="0 0 29 28" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M24.0173 14.091C24.0173 19.297 19.7971 23.5172 14.5912 23.5172C9.38526 23.5172 5.16504 19.297 5.16504 14.091C5.16504 8.88514 9.38526 4.66492 14.5912 4.66492C19.7971 4.66492 24.0173 8.88514 24.0173 14.091ZM13.104 15.1206L10.9245 12.8632L9.21202 15.0428L13.104 18.9348L20.4631 11.5757L18.5528 9.59395L13.104 15.1206Z" fill="white"/></svg>',
						style: {},
						onClick: () => {},
					}),
				);
			}
			else if (
				this.state.userRole === ProjectView.roles.request
				&& this.state.userInitiatedByType === ProjectView.initiatorTypes.user
			)
			{
				buttons.push(
					new CancelButton({
						text: Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_JOIN_BUTTON_REQUEST_SENT'),
						icon: '<svg xmlns="http://www.w3.org/2000/svg" width="29" height="28" viewBox="0 0 29 28" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M24.0173 14.091C24.0173 19.297 19.7971 23.5172 14.5912 23.5172C9.38526 23.5172 5.16504 19.297 5.16504 14.091C5.16504 8.88514 9.38526 4.66492 14.5912 4.66492C19.7971 4.66492 24.0173 8.88514 24.0173 14.091ZM13.104 15.1206L10.9245 12.8632L9.21202 15.0428L13.104 18.9348L20.4631 11.5757L18.5528 9.59395L13.104 15.1206Z" fill="#525C69"/></svg>',
						style: {},
						onClick: () => {},
					}),
				);
			}
			else if (this.state.actions.JOIN)
			{
				buttons.push(
					new PrimaryButton({
						text: Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_JOIN_BUTTON_JOIN'),
						icon: '<svg xmlns="http://www.w3.org/2000/svg" width="29" height="28" viewBox="0 0 29 28" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M24.0173 14.091C24.0173 19.297 19.7971 23.5172 14.5912 23.5172C9.38526 23.5172 5.16504 19.297 5.16504 14.091C5.16504 8.88514 9.38526 4.66492 14.5912 4.66492C19.7971 4.66492 24.0173 8.88514 24.0173 14.091ZM13.104 15.1206L10.9245 12.8632L9.21202 15.0428L13.104 18.9348L20.4631 11.5757L18.5528 9.59395L13.104 15.1206Z" fill="white"/></svg>',
						style: {},
						onClick: () => {
							if (this.state.isOpened)
							{
								this.setState({ showJoinedButton: true });
							}
							else
							{
								this.setState({
									userRole: ProjectView.roles.request,
									userInitiatedByType: ProjectView.initiatorTypes.user,
								});
							}
							Action.join(this.state.id).then(() => this.updateProjectData());
						},
					}),
				);
			}

			if (buttons.length > 0)
			{
				return ButtonsToolbar({ buttons });
			}

			return null;
		}
	}

	class Action
	{
		static join(projectId)
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.usertogroup.join', {
					params: {
						groupId: projectId,
					},
				}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => reject(response),
					)
					.catch(console.error);
			});
		}

		static leave(projectId)
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.usertogroup.leave', {
					params: {
						groupId: projectId,
					},
				}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => reject(response),
					)
					.catch(console.error);
			});
		}

		static delete(projectId)
		{
			return new Promise((resolve, reject) => {
				(new RequestExecutor('socialnetwork.api.workgroup.delete', {
					groupId: projectId,
				}))
					.call()
					.then(
						(response) => resolve(response),
						(response) => reject(response),
					)
					.catch(console.error);
			});
		}
	}

	class ProjectViewManager
	{
		static async open(userId, projectId, parentWidget = PageManager, isCollab = false, dialogId = '')
		{
			await tariffPlanRestrictionsReady();
			const { isRestricted, showRestriction } = getFeatureRestriction('socialnetwork_projects_groups');
			if (isRestricted())
			{
				showRestriction({ parentWidget });

				return;
			}

			if (isCollab)
			{
				const isCollabToolEnabled = await CollabAccessService.checkAccess();

				if (isCollabToolEnabled)
				{
					if (dialogId)
					{
						void requireLazy('im:messenger/api/dialog-opener')
							.then(({ DialogOpener }) => DialogOpener.open({ dialogId }));
					}
				}
				else
				{
					CollabAccessService.openAccessDeniedBox();
				}

				return;
			}

			const projectView = new ProjectView({
				showLoading: true,
				userId,
				projectId,
			});

			parentWidget.openWidget('layout', {
				backdrop: {
					bounceEnable: false,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: true,
					horizontalSwipeAllowed: false,
					disableTopInset: true,
				},
				onError: console.error,
			}).then((layoutWidget) => {
				projectView.layoutWidget = layoutWidget;
				layoutWidget.showComponent(projectView);
			}).catch(console.error);
		}

		static openProjectMemberList(userId, projectId, params, parentWidget)
		{
			parentWidget.openWidget('list', {
				backdrop: {
					bounceEnable: false,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
				},
				useSearch: true,
				useLargeTitleMode: true,
				title: Loc.getMessage('MOBILE_LAYOUT_PROJECT_VIEW_MEMBERS_LIST_TITLE'),
				onReady: (list) => {
					new ProjectMemberList(list, userId, projectId, {
						isOwner: params.isOwner,
						canInvite: params.canInvite,
						minSearchSize: 3,
					});
				},
				onError: console.error,
			});
		}
	}

	this.ProjectView = ProjectView;
	this.ProjectViewManager = ProjectViewManager;
})();
