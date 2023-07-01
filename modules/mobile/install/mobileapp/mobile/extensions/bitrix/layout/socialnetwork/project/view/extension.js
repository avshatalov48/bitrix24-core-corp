(() => {
	const pathToImages = `${currentDomain}/bitrix/mobileapp/mobile/extensions/bitrix/layout/socialnetwork/project/images/`;

	const { ButtonsToolbar } = jn.require('layout/ui/buttons-toolbar');

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

			BX.addCustomEvent('ProjectEdit:close', data => this.onProjectEditClose(data));
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
					.then(response => this.setState({ userUploadedFilesFolder: response.result }))
				;
			}
			if (!this.state.subjects.length)
			{
				(new RequestExecutor('sonet_group_subject.get'))
					.call()
					.then(response => this.setState({ subjects: response.result }))
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
							dateStart: (Date.parse(props.PROJECT_DATE_START) / 1000),
							dateFinish: (Date.parse(props.PROJECT_DATE_FINISH) / 1000),
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
							moderatorsData: response.result.relations.map(item => ({
								id: item.userId,
								title: item.formattedUserName,
								imageUrl: item.image,
							})),
						});
					})
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
			else
			{
				const themeImageStyle = {
					backgroundResizeMode: 'cover',
				};

				if (this.state.themeData)
				{
					const imageUrl = this.state.themeData.prefetchImages[0];

					if (imageUrl && imageUrl.split('.').pop())
					{
						const extension = imageUrl.split('.').pop().toLowerCase();
						if (extension !== 'svg')
						{
							themeImageStyle.backgroundImage = `${currentDomain}${imageUrl}`;
						}
						else
						{
							themeImageStyle.backgroundImageSvgUrl = `${currentDomain}${this.state.themeData.previewImage}`;
							themeImageStyle.backgroundColor = this.state.themeData.previewColor;
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
								backgroundColor: '#eef2f4',
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
						backgroundColor: '#000',
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
			return ImageButton({
				style: {
					width: 30,
					height: 30,
					alignSelf: 'flex-end',
				},
				uri: `${pathToImages}mobile-layout-project-more.png`,
				onClick: () => this.showMoreContextMenu(),
			});
		}

		showMoreContextMenu()
		{
			const actions = [
				{
					id: 'members',
					title: BX.message('MOBILE_LAYOUT_PROJECT_VIEW_MENU_MEMBERS'),
					data: {
						svgIcon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.5099 10.7668C15.5099 10.7668 15.4924 11.1101 15.4753 11.156C15.4363 11.2153 15.4035 11.2784 15.3773 11.3444C15.3994 11.3444 15.6281 11.453 15.6281 11.453L16.5137 11.7369L16.5009 12.1661C16.3326 12.2348 15.9776 12.4953 15.9165 12.5798C16.7234 12.9144 17.4645 13.5835 17.4836 14.1315C17.4967 14.2291 17.9852 16.0252 18.0143 16.4295H21.4729C21.4759 16.4127 21.3605 14.0894 21.3593 14.0718C21.3593 14.0718 21.1453 13.5122 20.8062 13.4553C20.4694 13.4116 20.1444 13.3028 19.8491 13.135C19.6541 13.0194 19.4483 12.9231 19.2346 12.8473C19.1251 12.7488 19.0403 12.626 18.9871 12.4886C18.8747 12.3457 18.7271 12.2345 18.5587 12.1661L18.5461 11.7369L19.432 11.454C19.432 11.454 19.6605 11.3454 19.6828 11.3454C19.6474 11.2698 19.6066 11.1967 19.5607 11.1269C19.5439 11.0813 19.4604 10.7683 19.4604 10.7683C19.5895 10.9371 19.7414 11.0872 19.9118 11.2142C19.7609 10.9261 19.6318 10.627 19.5256 10.3196C19.4545 10.0349 19.4051 9.74532 19.3778 9.45323C19.3169 8.91043 19.2222 8.37196 19.0942 7.84097C19.0031 7.57713 18.841 7.34356 18.6256 7.16602C18.3099 6.94195 17.9394 6.8076 17.5534 6.77725C17.5461 6.77725 17.5391 6.77725 17.5316 6.77725C17.524 6.77725 17.517 6.77725 17.509 6.77725C17.123 6.80749 16.7524 6.94185 16.4367 7.16602C16.2217 7.34378 16.0597 7.57728 15.9685 7.84097C15.8401 8.37191 15.7453 8.91039 15.6845 9.45323C15.6611 9.75187 15.6141 10.0482 15.5438 10.3394C15.4375 10.6414 15.306 10.9339 15.1508 11.214C15.3202 11.0863 15.5099 10.7668 15.5099 10.7668ZM17.4506 18.4674C17.4506 17.7016 16.609 14.4619 16.609 14.4619C16.609 14.4619 16.0029 13.4474 14.8088 13.1323C14.4032 13.0166 14.0184 12.8378 13.6684 12.6024C13.6123 12.4565 13.5901 12.2998 13.6035 12.1441L13.2207 12.0842C13.2207 12.0506 13.188 11.5546 13.188 11.5546C13.6479 11.3968 13.6007 10.4656 13.6007 10.4656C13.8928 10.631 14.083 9.89513 14.083 9.89513C14.4286 8.87193 13.9109 8.93349 13.9109 8.93349C14.0015 8.30843 14.0015 7.67359 13.9109 7.04854C13.681 4.97557 10.216 5.53767 10.627 6.21532C9.61448 6.02394 9.84558 8.37754 9.84558 8.37754L10.0652 8.98609C9.63406 9.2712 9.93372 9.61702 9.94827 10.0149C9.96953 10.6024 10.3207 10.4805 10.3207 10.4805C10.3425 11.4494 10.81 11.5767 10.81 11.5767C10.8979 12.1852 10.8433 12.0803 10.8433 12.0803L10.427 12.1318C10.4327 12.2701 10.4217 12.4085 10.3943 12.5442C9.9035 12.768 9.79914 12.8978 9.31258 13.1152C8.37248 13.5349 7.35069 14.083 7.16938 14.8194C6.98808 15.5559 6.44696 18.4674 6.44696 18.4674H17.4506ZM8.43657 11.1534C8.47583 11.2131 8.5089 11.2766 8.53528 11.343C8.51307 11.343 8.28282 11.4523 8.28282 11.4523L7.39111 11.7378L7.40398 12.1698C7.57342 12.239 7.722 12.3509 7.8352 12.4947C7.87809 12.5905 7.93071 12.6816 7.99223 12.7666C7.18005 13.1034 6.43405 13.5965 6.41512 14.1481C6.40174 14.2463 5.91018 16.0542 5.8809 16.4612H2.39935C2.39632 16.4443 2.51246 14.1057 2.51346 14.088C2.51346 14.088 2.72882 13.5248 3.07015 13.4675C3.40916 13.4235 3.73637 13.314 4.03356 13.1451C4.22989 13.0288 4.43704 12.9318 4.6521 12.8555C4.76233 12.7564 4.8477 12.6327 4.90129 12.4945C5.01435 12.3506 5.16298 12.2387 5.3325 12.1698L5.34538 11.7378L4.45367 11.4531C4.45367 11.4531 4.22367 11.3437 4.2012 11.3437C4.2368 11.2676 4.2778 11.1941 4.3239 11.1238C4.34081 11.0779 4.42488 10.7628 4.42488 10.7628C4.29489 10.9327 4.14195 11.0838 3.97045 11.2117C4.12233 10.9216 4.25229 10.6206 4.35924 10.3112C4.43076 10.0247 4.48047 9.73314 4.50795 9.43913C4.56923 8.89276 4.66459 8.35076 4.79349 7.81628C4.88513 7.55071 5.04833 7.31561 5.26509 7.1369C5.5829 6.91135 5.95588 6.77612 6.34438 6.74557C6.35171 6.74557 6.35877 6.74557 6.36635 6.74557C6.37392 6.74557 6.38099 6.74557 6.38907 6.74557C6.7776 6.77601 7.15061 6.91125 7.46836 7.1369C7.68484 7.31582 7.84791 7.55086 7.93972 7.81628C8.0689 8.35071 8.16436 8.89273 8.22551 9.43913C8.24905 9.73973 8.29641 10.038 8.36714 10.3311C8.47419 10.6351 8.60652 10.9296 8.76276 11.2114C8.59229 11.0829 8.44039 10.9314 8.31135 10.7613C8.31135 10.7613 8.4194 11.1072 8.43657 11.1534Z" fill="#828B95"/></svg>',
					},
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
						resolve({closeMenu: false});
					}),
				},
			];
			if (this.state.actions.EDIT)
			{
				actions.push({
					id: 'edit',
					title: BX.message('MOBILE_LAYOUT_PROJECT_VIEW_MENU_EDIT'),
					data: {
						svgIcon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.417 4.79822L19.8679 8.28543L9.86399 18.253L6.4131 14.7658L16.417 4.79822ZM4.81048 19.4185C4.77785 19.542 4.81281 19.6725 4.90139 19.7633C4.99229 19.8542 5.12282 19.8892 5.24636 19.8542L9.10398 18.815L5.85006 15.562L4.81048 19.4185Z" fill="#828b95"/></svg>',
					},
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
				});
				actions.push({
					id: 'perms',
					title: BX.message('MOBILE_LAYOUT_PROJECT_VIEW_MENU_PERMISSIONS'),
					data: {
						svgIcon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.1457 15.0262V16.6184H10.8846V15.0262C10.6577 14.8412 10.5124 14.5586 10.5124 14.2418C10.5124 13.6846 10.9614 13.2329 11.5152 13.2329C12.069 13.2329 12.518 13.6846 12.518 14.2418C12.518 14.5586 12.3727 14.8412 12.1457 15.0262ZM8.76969 8.55751C8.76969 7.0319 9.99888 5.79516 11.5151 5.79516C13.0314 5.79516 14.2606 7.0319 14.2606 8.55751V10.8926H8.76969V8.55751ZM15.7115 10.8926V8.55751C15.7115 6.22563 13.8327 4.33533 11.5151 4.33533C9.19758 4.33533 7.31877 6.22563 7.31877 8.55751V10.8926H6.0448V18.8889H16.9855V10.8926H15.7115Z" fill="#828B95"/></svg>',
					},
					onClickCallback: () => new Promise((resolve) => {
						contextMenu.close(() => {
							QRCodeAuthComponent.open(this.layoutWidget, {
								redirectUrl: `/workgroups/group/${this.state.id}/features/`,
								showHint: true,
								title: BX.message('MOBILE_LAYOUT_PROJECT_VIEW_MENU_PERMISSIONS'),
							});
						});
						resolve({ closeMenu: false });
					}),
				});
			}
			if (this.state.actions.LEAVE)
			{
				actions.push({
					id: 'leave',
					title: BX.message('MOBILE_LAYOUT_PROJECT_VIEW_MENU_LEAVE'),
					data: {
						svgIcon: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.96074 3.13705V16.8257H16.1225V3.13705H4.96074ZM4.47545 1.83337C4.02875 1.83337 3.66663 2.19817 3.66663 2.64817V17.3145C3.66663 17.7645 4.02875 18.1293 4.47545 18.1293H16.6078C17.0545 18.1293 17.4166 17.7645 17.4166 17.3145V2.64817C17.4166 2.19817 17.0545 1.83337 16.6078 1.83337H4.47545Z" fill="#828B95"/><path d="M7.71074 6.45056C7.71074 6.12163 7.90708 5.82495 8.20848 5.69844L16.2967 2.30345C16.8295 2.07983 17.4166 2.47415 17.4166 3.05557V17.5861C17.4166 17.9151 17.2203 18.2117 16.9189 18.3383L8.2707 21.9683C8.00432 22.0801 7.71074 21.8829 7.71074 21.5922V6.45056Z" fill="#828B95"/></svg>',
					},
					onClickCallback: () => new Promise((resolve) => {
						contextMenu.close();
						resolve({ closeMenu: false });
						Action.leave(this.state.id).then(
							response => this.updateProjectData(),
							response => console.log(response),
						);
					}),
				});
			}
			if (this.state.actions.DELETE)
			{
				actions.push({
					id: 'delete',
					title: BX.message('MOBILE_LAYOUT_PROJECT_VIEW_MENU_DELETE'),
					data: {
						svgIcon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12.4916 4.69727H10.3104V5.7943H7.44812C6.61969 5.7943 5.94812 6.46587 5.94812 7.2943V7.98837H16.8539V7.2943C16.8539 6.46587 16.1824 5.7943 15.3539 5.7943H12.4916V4.69727Z" fill="#828B95"/><path d="M7.0387 9.0854H15.7633L14.9715 18.0467C14.9259 18.5629 14.4935 18.9587 13.9754 18.9587H8.8267C8.30852 18.9587 7.87619 18.5629 7.83058 18.0467L7.0387 9.0854Z" fill="#828B95"/></svg>',
					},
					onClickCallback: () => new Promise((resolve) => {
						contextMenu.close(() => {
							const deleteContextMenu = new ContextMenu({
								params: {
									title: BX.message('MOBILE_LAYOUT_PROJECT_VIEW_MENU_DELETE_TITLE'),
									showCancelButton: false,
								},
								actions: [
									{
										id: 'deleteYes',
										title: BX.message('MOBILE_LAYOUT_PROJECT_VIEW_MENU_DELETE_YES'),
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
										title: BX.message('MOBILE_LAYOUT_PROJECT_VIEW_MENU_DELETE_NO'),
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
		}

		renderAvatar()
		{
			let uri = `${pathToImages}mobile-layout-project-default-avatar.png`;
			if (this.state.avatar)
			{
				uri = this.state.avatar;
				uri = (uri.indexOf('http') !== 0 ? `${currentDomain}${uri}` : uri);
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
					color: '#fff',
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
					color: '#fff',
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
						color: '#fff',
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
				Image({
					style: {
						width: 24,
						height: 24,
					},
					uri: `${pathToImages}mobile-layout-project-members.png`,
				}),
				Text({
					style: {
						fontSize: 14,
						color: '#fff',
						marginLeft: 3,
					},
					numberOfLines: 1,
					text: BX.message(`MOBILE_LAYOUT_PROJECT_VIEW_MEMBERS_COUNT_${this.state.membersCountPlural}`)
						.replace('#COUNT#', this.state.membersCount)
					,
				}),
				Image({
					style: {
						width: 24,
						height: 24,
						marginLeft: 3,
					},
					uri: `${pathToImages}mobile-layout-project-edit-arrow.png`,
				}),
			);
		}

		renderProjectFields()
		{
			return View(
				{
					style: {
						backgroundColor: '#fff',
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
						text: BX.message('MOBILE_LAYOUT_PROJECT_VIEW_JOIN_BUTTON_JOINED'),
						icon: '<svg xmlns="http://www.w3.org/2000/svg" width="29" height="28" viewBox="0 0 29 28" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M24.0173 14.091C24.0173 19.297 19.7971 23.5172 14.5912 23.5172C9.38526 23.5172 5.16504 19.297 5.16504 14.091C5.16504 8.88514 9.38526 4.66492 14.5912 4.66492C19.7971 4.66492 24.0173 8.88514 24.0173 14.091ZM13.104 15.1206L10.9245 12.8632L9.21202 15.0428L13.104 18.9348L20.4631 11.5757L18.5528 9.59395L13.104 15.1206Z" fill="white"/></svg>',
						style: {},
						onClick: () => {
						},
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
						text: BX.message('MOBILE_LAYOUT_PROJECT_VIEW_JOIN_BUTTON_REQUEST_SENT'),
						icon: '<svg xmlns="http://www.w3.org/2000/svg" width="29" height="28" viewBox="0 0 29 28" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M24.0173 14.091C24.0173 19.297 19.7971 23.5172 14.5912 23.5172C9.38526 23.5172 5.16504 19.297 5.16504 14.091C5.16504 8.88514 9.38526 4.66492 14.5912 4.66492C19.7971 4.66492 24.0173 8.88514 24.0173 14.091ZM13.104 15.1206L10.9245 12.8632L9.21202 15.0428L13.104 18.9348L20.4631 11.5757L18.5528 9.59395L13.104 15.1206Z" fill="#525C69"/></svg>',
						style: {},
						onClick: () => {
						},
					}),
				);
			}
			else if (this.state.actions.JOIN)
			{
				buttons.push(
					new PrimaryButton({
						text: BX.message('MOBILE_LAYOUT_PROJECT_VIEW_JOIN_BUTTON_JOIN'),
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
						response => resolve(response),
						response => reject(response),
					)
				;
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
						response => resolve(response),
						response => reject(response),
					)
				;
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
						response => resolve(response),
						response => reject(response),
					)
				;
			});
		}
	}

	class ProjectViewManager
	{
		static open(userId, projectId, parentWidget = PageManager)
		{
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
				},
				onError: error => console.log(error),
			}).then((layoutWidget) => {
				projectView.layoutWidget = layoutWidget;
				layoutWidget.showComponent(projectView);
			});
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
				title: BX.message('MOBILE_LAYOUT_PROJECT_VIEW_MEMBERS_LIST_TITLE'),
				onReady: (list) => {
					new ProjectMemberList(list, userId, projectId, {
						isOwner: params.isOwner,
						canInvite: params.canInvite,
						minSearchSize: 3,
					});
				},
				onError: error => console.log(error),
			});
		}
	}

	this.ProjectView = ProjectView;
	this.ProjectViewManager = ProjectViewManager;
})();