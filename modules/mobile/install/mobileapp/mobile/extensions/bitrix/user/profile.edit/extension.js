/**
 * @module user/profile.edit
 */
jn.define('user/profile.edit', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Profile } = require('user/profile');
	const { getFile } = require('files/entry');
	const { FileConverter } = require('files/converter');
	const { openDeleteDialog } = require('user/account-delete');
	const { AvaMenu } = require('ava-menu');
	const store = require('statemanager/redux/store');
	const { updateUserThunk } = require('statemanager/redux/slices/users/thunk');

	class ProfileEdit extends Profile
	{
		init()
		{
			this.changed = false;
			this.changedFields = [];
			BX.onViewLoaded(() => {
				if (this.form.setTitle)
				{
					this.form.setTitle({
						text: BX.message('PROFILE_INFO_MSGVER_1'),
						type: 'section',
					});
				}
			});

			super.init();
		}

		onItemSelected(item)
		{
			if (item.id === 'delete_account')
			{
				openDeleteDialog();
			}
		}

		onItemChanged(data)
		{
			this.changed = true;
			if (data.id === 'delete_account')
			{
				this.onItemSelected(data);

				return;
			}

			if (data.type === 'userpic')
			{
				if (data.value === '')
				{
					this.updateAvatar(false);
				}
				else
				{
					const converter = new FileConverter();
					converter.resize('avatarResize', {
						url: data.value, width: 1000, height: 1000,
					}).then((path) => {
						getFile(path)
							.then((file) => {
								file.readMode = BX.FileConst.READ_MODE.DATA_URL;
								file.readNext()
									.then((fileData) => {
										if (fileData.content)
										{
											const content = fileData.content;
											this.updateAvatar(
												[
													'avatar.png', content.substr(
														content.indexOf('base64,') + 7,
														content.length,
													),
												],
											);
										}
									})
									.catch(console.error);
							})
							.catch(console.error);
					}).catch(console.error);
				}
			}
			else
			{
				this.changedFields.push(data.id);
			}
		}

		showNotification(message)
		{
			include('InAppNotifier');
			if (typeof InAppNotifier !== 'undefined')
			{
				InAppNotifier.showNotification({
					title: BX.message('PROFILE_EDIT'),
					backgroundColor: AppTheme.colors.accentSoftElementBlue1,
					time: 1,
					blur: true,
					message,
				});
			}
		}

		updateAvatar(avatar)
		{
			const data = { PERSONAL_PHOTO: avatar, id: this.userId };
			BX.rest.callMethod('user.update', data)
				.then(this.#syncWithAvaMenu)
				.then((e) => {
					this.showNotification(BX.message('AVATAR_CHANGED_SUCCESS'));
					BX.postComponentEvent('shouldReloadMenu', null, 'settings');
				})
				.catch((response) => {
					if (response.answer && response.answer.error_description)
					{
						this.error(response.answer.error_description.replaceAll('<br>', '').trim());
					}

					console.error(response);
				});
		}

		render()
		{
			this.formSections = this.formSections.map((section) => {
				section.styles = {
					title: {
						font: {
							color: AppTheme.colors.base3,
							fontStyle: 'medium',
						},
					},
				};

				return section;
			});

			Object.keys(this.formFields).forEach((fieldName) => {
				if (this.fieldsValues[fieldName])
				{
					this.formFields[fieldName].value = this.fieldsValues[fieldName];
				}

				if (this.formFields[fieldName].asterix)
				{
					this.formFields[fieldName].title = `${this.formFields[fieldName].title}*`;
					const sectionIndex = this.formSections.findIndex(
						(section) => section.id === this.formFields[fieldName].sectionCode,
					);
					if (this.formSections[sectionIndex].footer)
					{
						this.formSections[sectionIndex].footer += `\n${this.formFields[fieldName].asterix}`;
					}
					else
					{
						this.formSections[sectionIndex].footer = this.formFields[fieldName].asterix;
					}
				}

				this.formFields[fieldName].styles = {
					title: {
						font: {
							color: AppTheme.colors.base3,
							fontStyle: 'semibold',
							size: 14,
						},
					},
				};

				if (!this.formFields[fieldName].type)
				{
					delete this.formFields[fieldName];
				}
			});

			const imageUrl = this.fieldsValues.PERSONAL_PHOTO ? encodeURI(this.fieldsValues.PERSONAL_PHOTO) : '';
			this.formFields.PERSONAL_PHOTO = {
				imageUrl,
				value: imageUrl,
				title: `${this.fieldsValues.NAME} ${this.fieldsValues.LAST_NAME}`,
				subtitle: this.fieldsValues.WORK_POSITION,
				sectionCode: 'top',
				type: 'userpic',
				height: 160,
				imageHeight: 120,
				useLetterImage: true,
				color: AppTheme.colors.base2,
			};

			const items = Object.values(this.formFields);
			if (Number(this.userId) === Number(env.userId))
			{
				const { isCloudAccount } = require('user/account-delete');
				if (isCloudAccount())
				{
					items.push({
						sectionCode: 'account',
						type: 'default',
						title: BX.message('DELETE_ACCOUNT'),
						id: 'delete_account',
						styles: {
							title: {
								font: {
									color: AppTheme.colors.accentSoftElementRed1,
								},
							},
						},
					});

					this.formSections.push({ id: 'account', title: '' });
				}
			}

			this.form.setItems(items, this.formSections);

			this.form.setRightButtons([
				{
					name: BX.message('SAVE_FORM'),
					callback: () => {
						if (!this.isBeingUpdated)
						{
							this.isBeingUpdated = true;
							const data = {};
							delete data.PERSONAL_PHOTO;
							this.form.getItems()
								.filter((item) => {
									if (item.type === 'userpic' || item.type === 'default')
									{
										return false;
									}
									let oldValue = this.formFields[item.id].value;
									if (typeof oldValue === 'undefined')
									{
										oldValue = '';
									}

									return oldValue !== item.value;
								})
								.forEach((item) => {
									data[item.id] = item.value;
								});

							if (Object.values(data).length === 0)
							{
								this.isBeingUpdated = false;
								this.form.back();

								return;
							}

							data.ID = this.userId;
							dialogs.showLoadingIndicator();
							store.dispatch(updateUserThunk({ data }))
								.unwrap()
								.then(this.#syncWithAvaMenu)
								.then(() => {
									this.isBeingUpdated = false;
									this.changed = false;
									this.showNotification(BX.message('PROFILE_CHANGED_SUCCESS'));
									BX.postComponentEvent('shouldReloadMenu', null, 'settings');
									dialogs.hideLoadingIndicator();
									this.form.back();
								})
								.catch((response) => {
									this.isBeingUpdated = false;
									dialogs.hideLoadingIndicator();
									if (response.error && response.error_description)
									{
										this.error(response.error_description.replace('<br>', ''));

										return;
									}

									this.error();
								});
						}
					},
				},
			]);
		}

		async #syncWithAvaMenu()
		{
			return BX.ajax.runAction('mobile.AvaMenu.getUserInfo', { data: { reloadFromDb: true } })
				.then(({ data }) => {
					AvaMenu.setUserInfo(data);
				})
				.catch(console.error);
		}
	}

	module.exports = ProfileEdit;
});
