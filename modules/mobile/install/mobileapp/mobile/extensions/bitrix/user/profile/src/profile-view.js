/**
 * @module user/profile/src/profile-view
 */
jn.define('user/profile/src/profile-view', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Profile } = require('user/profile/src/profile');

	const COMMUNICATION_PATH = '/bitrix/mobileapp/mobile/extensions/bitrix/user/profile/images/communication';

	const statusColor = (status) => {
		const colors = {
			admin: AppTheme.colors.accentBrandBlue,
			extranet: AppTheme.colors.accentMainWarning,
			integrator: AppTheme.colors.accentExtraAqua,
			fired: AppTheme.colors.base4,
			owner: AppTheme.colors.accentExtraPink,
		};

		if (colors[status])
		{
			return colors[status];
		}

		return colors.fired;
	};

	const DialogOpener = () => {
		try
		{
			const { DialogOpener } = require('im/messenger/api/dialog-opener');

			return DialogOpener;
		}
		catch (e)
		{
			console.log(e, 'DialogOpener not found');

			return null;
		}
	};

	class ProfileView extends Profile
	{
		init()
		{
			super.init();
			this.loaded = false;
			this.name = '';
			this.imageUrl = '';
			BX.onViewLoaded(() => this.form.setTitle({ text: BX.message('PROFILE_INFO_MSGVER_1'), type: 'entity' }));
		}

		loadPlaceholder()
		{
			const action = {
				title: BX.message('COMMUNICATE_MESSAGE'),
				imageUrl: this.getCommunicationImage(),
				sectionCode: 'actions',
				styles: {
					title: {
						font: {
							size: 17,
							color: AppTheme.colors.base0,
						},
					},
					image: {
						image: {
							height: 32,
							borderRadius: 0,
						},
					},
				},
				type: 'info',
				params: { code: 'communicate' },
			};

			this.formSections.push({ id: 'actions' });
			this.formFields.push(action);

			BX.onViewLoaded(() => {
				this.buildPopupMenu();
				this.form.setItems(this.formFields, this.formSections);
			});
		}

		getCommunicationImage()
		{
			return `${COMMUNICATION_PATH}-${AppTheme.id}.png`;
		}

		prepare()
		{
			this.formSections = this.formSections
				.filter((section) => section.id !== 'account')
				.map((section) => {
					section.styles = {
						title: {
							font: {
								color: AppTheme.colors.base3,
								fontStyle: 'semibold',
								size: 16,
							},
						},
					};
					section.height = 40;

					return section;
				});
			const excludedFields = new Set([
				'PERSONAL_PHOTO_ORIGINAL',
				'PERSONAL_PHOTO',
				'NAME',
				'LAST_NAME',
				'SECOND_NAME',
				'WORK_POSITION',
				'NAME_FORMATTED',
			]);

			Object.keys(this.fieldsValues).forEach((fieldName) => {
				if (!excludedFields.has(fieldName))
				{
					if (this.formFields[fieldName])
					{
						if (this.fieldsValues[fieldName])
						{
							if (this.formFields[fieldName].asterix)
							{
								this.formFields[fieldName].title = `${this.formFields[fieldName].title}*`;
								const sectionIndex = this.formSections.findIndex((section) => (
									section.id === this.formFields[fieldName].sectionCode
								));
								if (this.formSections[sectionIndex].footer)
								{
									this.formSections[sectionIndex].footer += `\n${this.formFields[fieldName].asterix}`;
								}
								else
								{
									this.formSections[sectionIndex].footer = this.formFields[fieldName].asterix;
								}
							}

							if (fieldName === 'PERSONAL_GENDER')
							{
								this.formFields[fieldName].subtitle = (this.fieldsValues[fieldName] === 'F')
									? BX.message('FEMALE')
									: BX.message('MALE');
							}
							else
							{
								this.formFields[fieldName].subtitle = this.fieldsValues[fieldName];
							}
						}
					}
					else if (fieldName === 'COMPANY_STRUCTURE_RELATIONS')
					{
						const data = this.fieldsValues[fieldName];
						if (data.HEAD_DATA && data.HEAD_DATA.id)
						{
							this.formFields.HEAD = {
								title: BX.message('STRUCTURE_HEAD_MSGVER_1'),
								subtitle: data.HEAD,
								params: {
									openScheme: 'user',
									data: {
										userId: data.HEAD_DATA.id,
										name: data.HEAD_DATA.name,
										title: data.HEAD_DATA.name,
										workPosition: data.HEAD_DATA.position,
										imageUrl: data.HEAD_DATA.personal_photo,
									},
								},
								sectionCode: 'extra',
								id: 'HEAD',
							};
						}

						if (data.DEPARTMENTS)
						{
							this.formFields.DEPARTMENTS = {
								title: BX.message('DEPARTMENTS'),
								subtitle: data.DEPARTMENTS,
								sectionCode: 'extra',
								useEstimatedHeight: true,
								id: 'DEPARTMENTS',
							};
						}

						if (data.EMPLOYEES_LIST)
						{
							this.formFields.EMPLOYEES_LIST = {
								title: BX.message('STRUCTURE_EMPLOYEES'),
								subtitle: data.EMPLOYEES_LIST,
								sectionCode: 'extra',
								useEstimatedHeight: true,
								id: 'EMPLOYEES',
							};
						}
					}
				}
			});
		}

		getUserData()
		{
			return {
				[this.user.id]: {
					avatar: this.userPhotoUrl,
					name: this.user.name_formatted,
				},
			};
		}

		openCommunicateMenu()
		{
			let buttons = [{ title: BX.message('COMMUNICATE_MESSAGE'), code: 'message' }];
			if (!['bot', 'email', 'network', 'imconnector'].includes(this.externalAuthId) && BX.componentParameters.get(
				'userId',
				0,
			) !== this.userId)
			{
				buttons = [
					...buttons,
					{ title: BX.message('COMMUNICATE_VIDEO'), code: 'video' },
					{ title: BX.message('COMMUNICATE_AUDIO'), code: 'audio' },
				];
			}

			dialogs.showActionSheet({
				callback: (item) => {
					if (item.code === 'message')
					{
						this.openChat(this.userId, {
							name: this.name,
							description: this.position,
							avatar: this.imageUrl,
						});
					}
					else if (item.code === 'video' || item.code === 'audio')
					{
						BX.postComponentEvent(
							'onCallInvite',
							[
								{
									userId: this.userId,
									video: (item.code === 'video'),
									userData: { [this.userId]: { avatar: this.imageUrl, name: this.name } },
								},
							],
						);
					}
				},
				items: buttons,
			});
		}

		onItemSelected(item)
		{
			let valueForOpening = item.subtitle;
			const params = {
				NAME: this.fieldsValues.NAME_FORMATTED,
			};

			if (item.params && item.params.code === 'communicate')
			{
				this.openChat(this.userId, {
					name: this.name,
					description: this.position,
					avatar: this.imageUrl,
				});

				return;
			}

			if (item.id === 'userinfo')
			{
				if (this.fieldsValues.PERSONAL_PHOTO_ORIGINAL && viewer)
				{
					viewer.openImage(this.fieldsValues.PERSONAL_PHOTO_ORIGINAL, item.title);
				}

				return;
			}

			if (item.params && Object.keys(item.params).length > 0)
			{
				if (item.params.openScheme)
				{
					const scheme = item.params.openScheme;
					if (scheme === 'tel' || scheme === 'tel-inner')
					{
						const items = [];
						if (scheme === 'tel')
						{
							items.push({ title: BX.message('PHONE_CALL'), code: 'call' });
						}

						if (this.canUseTelephony)
						{
							items.push({ title: BX.message('PHONE_CALL_B24'), code: 'callbx24' });
						}
						items.push({ title: BX.message('PHONE_COPY'), code: 'copy' });

						valueForOpening = valueForOpening.replaceAll(/\s/g, '');
						dialogs.showActionSheet({
							callback: (item) => {
								if (item.code === 'copy')
								{
									Application.copyToClipboard(valueForOpening);
								}
								else if (item.code === 'callbx24')
								{
									BX.postComponentEvent('onPhoneTo', [{ number: valueForOpening, params }], 'calls');
								}
								else
								{
									Application.openUrl(`${scheme}:${valueForOpening}`);
								}
							},
							items,
						});
					}
					else if (scheme === 'user')
					{
						console.warn(item);
						ProfileView.open(item.params.data);
					}
					else
					{
						Application.openUrl(`${scheme}:${valueForOpening}`);
					}
				}
			}
			else if (item.subtitle.startsWith('http'))
			{
				Application.openUrl(String(valueForOpening));
			}
		}

		openChat(dialogId, dialogTitleParams)
		{
			dialogTitleParams = dialogTitleParams || false;
			if (!dialogId)
			{
				return false;
			}

			console.info('BX.MobileTools.openChat: open chat in JSNative component');

			const dialogParams = {
				dialogId,
				dialogTitleParams: dialogTitleParams ? {
					name: dialogTitleParams.name || '',
					avatar: dialogTitleParams.avatar || '',
					color: dialogTitleParams.color || '',
					description: dialogTitleParams.description || '',
				} : false,
			};

			let openDialog = () => {
				BX.postComponentEvent('onOpenDialog', [dialogParams], 'im.recent');
				BX.postComponentEvent('ImMobile.Messenger.Dialog:open', [dialogParams], 'im.messenger');
			};

			const imOpener = DialogOpener();
			if (typeof imOpener === 'function')
			{
				openDialog = () => imOpener.open(dialogParams);
			}

			if (!this.isBackdrop)
			{
				return openDialog();
			}

			this.form.close(openDialog);

			return true;
		}

		/**
		 *
		 * @returns {JSPopoverMenu}
		 */
		get popupMenu()
		{
			if (!this._popupMenu)
			{
				if (typeof dialogs.createPopupMenu === 'function')
				{
					this._popupMenu = dialogs.createPopupMenu();
				}
				else
				{
					this._popupMenu = dialogs.popupMenu;
				}
			}

			return this._popupMenu;
		}

		availableStatus(status)
		{
			return [
				'admin',
				'extranet',
				'integrator',
				'fired',
				'owner',
			].includes(status);
		}

		render()
		{
			this.prepare();
			if (this.form.stopRefreshing)
			{
				this.form.stopRefreshing();
			}
			this.name = this.fieldsValues.NAME_FORMATTED;
			this.imageUrl = this.fieldsValues.PERSONAL_PHOTO;
			this.position = this.fieldsValues.WORK_POSITION;
			this.externalAuthId = this.fieldsValues.EXTERNAL_AUTH_ID;
			const items = Object.values(this.formFields)
				.filter((item) => (item.subtitle && item.subtitle.length > 0))
				.map((item) => {
					item.type = 'info';

					if (!item.height)
					{
						item.height = 80;
					}

					item.styles = {
						title: {
							font: {
								color: AppTheme.colors.base3,
								fontStyle: 'normal',
								size: 14,
							},
						},
						subtitle: {
							font: {
								color: AppTheme.colors.base0,
								fontStyle: 'normal',
								size: 16,
							},
						},
					};

					return item;
				});

			BX.onViewLoaded(() => {
				const topItem = {
					imageUrl: encodeURI(this.fieldsValues.PERSONAL_PHOTO),
					title: this.fieldsValues.NAME_FORMATTED,
					styles: {
						tag: {
							color: AppTheme.colors.baseWhiteFixed,
							backgroundColor: statusColor(this.fieldsValues.STATUS),
							padding: { top: 5, bottom: 5, left: 10, right: 10 },
							cornerRadius: 14,
						},
						title: {
							font: {
								size: 18,
								fontStyle: 'medium',
							},
						},
					},
					// tag: this.availableStatus(this.fieldsValues["STATUS"])? this.fieldsValues["STATUS_NAME"] : undefined,
					subtitle: this.fieldsValues.WORK_POSITION,
					sectionCode: 'top',
					type: 'userinfo',
					id: 'userinfo',
					// height: this.availableStatus(this.fieldsValues["STATUS"])? 130 : 100,
					height: 100,
					imageHeight: 80,
					useLetterImage: true,
					color: AppTheme.colors.base2,
				};

				Application.sharedStorage().set(`user_head_${this.userId}`, JSON.stringify({
					imageUrl: encodeURI(this.fieldsValues.PERSONAL_PHOTO),
					title: this.fieldsValues.NAME_FORMATTED,
					name: this.fieldsValues.NAME_FORMATTED,
					workPosition: this.fieldsValues.WORK_POSITION,
				}));

				if (this.loaded)
				{
					items.push(topItem, {
						title: BX.message('COMMUNICATE_MESSAGE'),
						imageUrl: this.getCommunicationImage(),
						sectionCode: 'actions',
						styles: {
							title: {
								font: {
									size: 17,
									color: AppTheme.colors.base0,
								},
							},
							image: {
								image: {
									height: 32,
									borderRadius: 0,
								},
							},
						},
						type: 'info',
						params: { code: 'communicate' },
					});

					this.form.setItems(items, this.formSections);
				}
				else
				{
					this.form.setSections(this.formSections);
					this.form.addItems(items, false);
					this.form.updateItems([
						{ filter: { type: 'userinfo' }, element: topItem },
					]);
					this.loaded = true;
				}
			});
		}

		buildPopupMenu()
		{
			this.popupMenu.setData(
				[
					this.isTasksMobileInstalled() && { title: BX.message('PROFILE_USER_TASKS_MSGVER_1'), sectionCode: 'usermenu', id: 'tasks' },
					{ title: BX.message('PROFILE_USER_FILES_MSGVER_1'), sectionCode: 'usermenu', id: 'files' },
					{ title: BX.message('PROFILE_USER_MESSAGES_MSGVER_1'), sectionCode: 'usermenu', id: 'messages' },
				].filter(Boolean),
				[{ id: 'usermenu', title: '' }],
				(event, item) => {
					if (event === 'onItemSelected')
					{
						switch (item.id)
						{
							case 'files':
							{
								this.form.openWidget(
									'list',
									{
										useSearch: true,
										onReady: (list) => {
											UserDisk.open({
												userId: env.userId,
												ownerId: this.userId,
												title: item.title,
												list,

											});
										},
										title: BX.message('PROFILE_INFO_MSGVER_1'),
									},
								);

								break;
							}

							case 'tasks':
							{
								BX.postComponentEvent(
									'taskbackground::taskList::open',
									[{ ownerId: this.userId }],
									'background',
								);

								break;
							}

							case 'messages':
							{
								PageManager.openPage({ url: `${env.siteDir}mobile/index.php?blog=Y&created_by_id=${this.userId}` });

								break;
							}
							// No default
						}
					}
				},
			);
			this.form.setRightButtons([
				{
					type: 'more',
					callback: () => this.popupMenu.show(),
				},
			]);
		}

		isTasksMobileInstalled()
		{
			return BX.prop.getBoolean(jnExtensionData.get('user/profile'), 'isTasksMobileInstalled', false);
		}

		error(e)
		{
			this.form.stopRefreshing();
			super.error(e);
		}

		onRefresh()
		{
			this.load();
		}

		static open(userData = {}, formObject = false)
		{
			let params = {
				userId: null,
				url: null,
				imageUrl: '',
				title: '',
				workPosition: '',
				name: '',
				isBackdrop: false,
			};

			let cachedData = Application.sharedStorage().get(`user_head_${userData.userId}`);
			if (cachedData)
			{
				cachedData = JSON.parse(cachedData);
				params = Object.assign(params, cachedData);
			}

			params = Object.assign(params, userData);
			if (params.userId !== null)
			{
				const top = {
					imageUrl: params.imageUrl,
					title: params.name,
					subtitle: params.workPosition,
					sectionCode: 'top',
					type: 'userinfo',
					styles: {
						tag: {
							// backgroundColor:AppTheme.colors.base7,
							padding: { top: 5, bottom: 5, left: 10, right: 10 },
							cornerRadius: 14,
						},
						title: {
							font: {
								size: 18,
								fontStyle: 'medium',
							},
						},
					},
					height: 100,
					useLetterImage: true,
					color: AppTheme.colors.base2,
				};

				const openProfile = (form) => {
					const profile = new ProfileView(params.userId, form, [
						top,
						{ type: 'loading', sectionCode: '1', title: '' },
					], [
						{ id: 'top', backgroundColor: AppTheme.colors.base7 },
						{ id: 'actions', backgroundColor: AppTheme.colors.base7 },
						{ id: '1', backgroundColor: AppTheme.colors.base7 },
					]);

					profile.isBackdrop = params.isBackdrop;
					profile.init();
				};

				if (formObject == false)
				{
					PageManager.openWidget(
						'list',
						{
							titleParams: { text: params.title, type: 'entity' },
							groupStyle: true,
							onReady: openProfile,
							onError: (error) => console.error(error),
						},
					);
				}
				else
				{
					if (formObject.setTitle)
					{
						formObject.setTitle({ text: BX.message('PROFILE_INFO_MSGVER_1'), type: 'entity' });
					}

					openProfile(formObject);
				}
			}
			else if (url)
			{
				PageManager.openPage({ url: params.url, titleParams: { text: params.title } });
			}
		}

		static openComponent(userData = {})
		{
			PageManager.openComponent(
				'JSStackComponent',
				{
					scriptPath: availableComponents?.['user.profile']?.publicUrl ?? '',
					params: { userId: userData.userId },
					canOpenInDefault: true,
					rootWidget: {
						name: 'list',
						title: userData.title,
						description: true,
						settings: { objectName: 'form', description: true },
					},
				},
			);
		}

		/**
		 * @param {number} userId
		 * @param {PageManager} [parentWidget=PageManager]
		 * @param {object} [userData={}]
		 */
		static openInBottomSheet(userId, parentWidget = PageManager, userData = {})
		{
			parentWidget.openWidget(
				'list',
				{
					groupStyle: true,
					backdrop: {
						bounceEnable: false,
						swipeAllowed: true,
						showOnTop: true,
						hideNavigationBar: false,
						horizontalSwipeAllowed: false,
					},
				},
			)
				.then((list) => ProfileView.open({ ...userData, userId, isBackdrop: true }, list))
				.catch(console.error)
			;
		}
	}

	module.exports = { ProfileView };
});
