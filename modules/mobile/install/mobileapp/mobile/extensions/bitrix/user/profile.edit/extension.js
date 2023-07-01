/**
 * @bxjs_lang_path extension.php
 */

jn.define("user/profile.edit", (require, exports, module) => {
	const { Profile } = jn.require("user/profile")
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
						text: BX.message("PROFILE_INFO")
					});
				}
			});

			super.init();
		}

		onItemSelected(item)
		{
			if (item.id === "delete_account")
			{
				let {openDeleteDialog} = jn.require("user/account-delete")
				openDeleteDialog()
			}
		}

		onItemChanged(data)
		{
			this.changed = true;
			if (data.id === "delete_account")
			{
				this.onItemSelected(data);
				return;
			}
			if (data.type === "userpic")
			{
				if (data.value === "")
				{
					this.updateAvatar(false)
				}
				else
				{
					let {FileConverter} = jn.require("files/converter")
					let {getFile} = jn.require("files/entry")
					let converter = new FileConverter()
					converter.resize("avatarResize", {
						url: data.value, width: 1000, height: 1000,
					}).then(path => {
						getFile(path)
							.then(file => {
								file.readMode = BX.FileConst.READ_MODE.DATA_URL;
								file.readNext()
									.then(fileData => {
										if (fileData.content)
										{
											let content = fileData.content;
											this.updateAvatar(
												["avatar.png", content.substr(content.indexOf("base64,") + 7,
													content.length),]);
										}
									})
									.catch(e => console.error(e));
							})
							.catch(e => console.error(e));
					});
				}
			}
			else
			{
				this.changedFields.push(data.id);
			}
		}

		showNotification(message)
		{
			include("InAppNotifier");
			if (typeof InAppNotifier != "undefined")
			{

				InAppNotifier.showNotification({
					title: BX.message("PROFILE_EDIT"), backgroundColor: "#004f69", time: 1, blur: true, message: message
				})
			}
		}

		updateAvatar(avatar)
		{
			let data = {PERSONAL_PHOTO: avatar, id: this.userId};
			BX.rest.callMethod("user.update", data)
				.then(e => {
					this.showNotification(BX.message("AVATAR_CHANGED_SUCCESS"));
					BX.postComponentEvent("shouldReloadMenu", null, "settings");
				})
				.catch(response => {
					if (response.answer && response.answer.error_description)
					{
						this.error(response.answer.error_description.replace(/<br>/g, "").trim());
					}

					console.error(response);
				});
		}

		render()
		{
			this.formSections = this.formSections.map((section) => {
				section.styles = {
					'title': {
						'font': {
							'color': '#777777', 'fontStyle': 'medium',
						},
					},
				};

				return section;
			});

			Object.keys(this.formFields).forEach(fieldName => {
				if (this.fieldsValues[fieldName])
				{
					this.formFields[fieldName]["value"] = this.fieldsValues[fieldName];
				}

				if (this.formFields[fieldName]["asterix"])
				{
					this.formFields[fieldName]['title'] = this.formFields[fieldName]['title'] + "*"
					let sectionIndex = this.formSections.findIndex(
						section => section.id === this.formFields[fieldName]['sectionCode'])
					if (!this.formSections[sectionIndex]["footer"])
					{
						this.formSections[sectionIndex]["footer"] = this.formFields[fieldName]["asterix"];
					}
					else
					{
						this.formSections[sectionIndex]["footer"] += "\n" + this.formFields[fieldName]["asterix"];
					}
				}

				this.formFields[fieldName].styles = {
					'title': {
						'font': {
							'color': '#777777', 'fontStyle': 'semibold', 'size': 14
						},
					},
				};

				if (!this.formFields[fieldName]["type"])
				{
					delete this.formFields[fieldName]
				}
			});

			let imageUrl = this.fieldsValues["PERSONAL_PHOTO"] ? encodeURI(this.fieldsValues["PERSONAL_PHOTO"]) : "";
			this.formFields["PERSONAL_PHOTO"] = {
				imageUrl: imageUrl,
				value: imageUrl,
				title: `${this.fieldsValues["NAME"]} ${this.fieldsValues["LAST_NAME"]}`,
				subtitle: this.fieldsValues["WORK_POSITION"],
				sectionCode: "top",
				type: "userpic",
				height: 160,
				imageHeight: 120,
				useLetterImage: true,
				color: "#2e455a"
			};

			let items = Object.values(this.formFields);
			if (this.userId === Number(env.userId) && Application.getPlatform() === "ios")
			{
				let {isCloudAccount} = jn.require("user/account-delete")
				if (isCloudAccount())
				{
					items.push({
						sectionCode: 'account',
						type: "default",
						title: BX.message('DELETE_ACCOUNT'),
						id: "delete_account",
						styles: {
							title: {
								font: {color: "#fb0000"}
							},
						}
					})

					this.formSections.push({id: "account", title: ""})
				}
			}

			this.form.setItems(items, this.formSections);

			console.log(items, this.formSections);
			this.form.setRightButtons([{
				name: BX.message("SAVE_FORM"), callback: () => {
					if (!this.isBeingUpdated)
					{
						this.isBeingUpdated = true;
						let data = {};
						delete data["PERSONAL_PHOTO"];
						this.form.getItems()
							.filter(item => {
								if (item.type === "userpic" || item.type === "default")
								{
									return false;
								}
								let oldValue = this.formFields[item.id].value
								if (typeof oldValue === "undefined")
								{
									oldValue = ""
								}

								return oldValue !== item.value;
							})
							.forEach(item => data[item["id"]] = item["value"]);

						if (Object.values(data).length === 0)
						{
							this.isBeingUpdated = false;
							this.form.back();
							return;
						}

						data["ID"] = this.userId;
						dialogs.showLoadingIndicator();
						BX.rest.callMethod("mobile.user.update", data)
							.then(e => {
								this.isBeingUpdated = false;
								this.changed = false;
								this.showNotification(BX.message("PROFILE_CHANGED_SUCCESS"));
								BX.postComponentEvent("shouldReloadMenu", null, "settings");
								dialogs.hideLoadingIndicator();
								this.form.back();
							})
							.catch(response => {

								this.isBeingUpdated = false;
								dialogs.hideLoadingIndicator();
								if (response.answer && response.answer.error)
								{
									if (response.answer.error_description)
									{
										this.error(response.answer.error_description.replace("<br>", ""));
										return;
									}
								}

								this.error();

							});
					}

				}
			}]);
		}
	}

	module.exports = ProfileEdit
});
