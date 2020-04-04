/**
* @bxjs_lang_path extension.php
*/

(()=>{
	class Profile
	{
		constructor(userId = 0, form, items = [], sections = [])
		{
			this.form = form;
			this.userId = userId;
			this.formFields = items;
			this.formSections = sections;
			this.fieldsValues = [];
			this.canUseTelephony = false;
			this.loadPlaceholder();
		}

		init()
		{
			this.form.setListener((event, data) => this.listener(event, data));
			this.load();
		}

		loadPlaceholder()
		{
			BX.onViewLoaded(() => this.form.setItems(this.formFields, this.formSections));
		}

		load()
		{
			this.request().then(() => this.render()).catch(e => this.error(e))
		}

		request()
		{
			return new Promise((resolve, reject) =>
			{
				BX.rest.callBatch({
					formData: ['mobile.user.get', {filter:{id: this.userId}, image_resize:"small"}],
					formStructure: ['mobile.form.profile'],
					canUseTelephony: ['mobile.user.canUseTelephony'],
				}, response =>
				{
					if (response.formStructure.error() || response.formData.error())
					{
						reject(response);
					}
					else
					{
						this.formFields = response.formStructure.answer.result["fields"];
						this.formSections = response.formStructure.answer.result["sections"];
						this.fieldsValues = response.formData.answer.result[0];
						this.canUseTelephony = response.canUseTelephony.answer.result;
						resolve();
					}
				});
			});
		}

		render()
		{
			//should override
		}

		listener(event, data)
		{
			if(this[event] && typeof this[event] == "function")
			{
				this[event].apply(this, [data])
			}
		}

		error(message)
		{
			let errorMessage = BX.message("SOMETHING_WENT_WRONG");
			if(message && typeof message == "string")
				errorMessage = message;

			navigator.notification.alert(
				errorMessage,
				() => {/*form.back();*/},
				BX.message("ERROR"),
				'OK'
			);
		}
	}

	class ProfileView extends Profile
	{
		init()
		{
			super.init();
			this.loaded = false;
			this.name = "";
			this.imageUrl = "";
			BX.onViewLoaded(()=> this.form.setTitle({text: BX.message("PROFILE_INFO")}));

		}

		loadPlaceholder()
		{
			let action = {
				title: BX.message("COMMUNICATE_MESSAGE"),
				imageUrl:"/bitrix/mobileapp/mobile/extensions/bitrix/user/profile/images/communication.png?1",
				sectionCode: "actions",
				styles:{
					title:{
						font:{
							'size': 17,
							'color': '#000000',
						}
					},
					image:{
						image:{
							height:26,
							borderRadius:0,
						}
					},
				},
				type:"info",
				params:{code: "communicate"}
			};

			this.formSections.push({id:"actions"});
			this.formFields.push(action);

			BX.onViewLoaded(() =>
			{
				this.buildPopupMenu();
				this.form.setItems(this.formFields, this.formSections);
			});
		}

		prepare()
		{
			this.formSections = this.formSections
				.filter(section=> section.id !== "account")
				.map(section =>
				{
					section.styles = {
						'title': {
							'font': {
								'color': '#777777',
								'fontStyle': 'semibold',
								'size': 16,
							},
						},
					};
					section.height = 40;
					section.backgroundColor = "#f0f0f0";

					return section;
				});
			let excludedFields = ["PERSONAL_PHOTO_ORIGINAL","PERSONAL_PHOTO", "NAME", "LAST_NAME", "SECOND_NAME","WORK_POSITION", "NAME_FORMATTED"];
			Object.keys(this.fieldsValues).forEach(fieldName =>
			{
				if(excludedFields.indexOf(fieldName)<0)
				{
					if (this.formFields[fieldName])
					{
						if(this.fieldsValues[fieldName])
						{
							if(fieldName == "PERSONAL_GENDER")
							{
								this.formFields[fieldName]["subtitle"] = (this.fieldsValues[fieldName] == "F")
									? BX.message("FEMALE")
									: BX.message("MALE");
							}
							else
							{
								this.formFields[fieldName]["subtitle"] = this.fieldsValues[fieldName];
							}
						}

					}
					else
					{
						if(fieldName == "COMPANY_STRUCTURE_RELATIONS")
						{
							let data = this.fieldsValues[fieldName];
							if(data["HEAD_DATA"] && data["HEAD_DATA"]["id"])
							{
								this.formFields["HEAD"] = {
									title:BX.message("STRUCTURE_HEAD"),
									subtitle:data["HEAD"],
									params:{
										openScheme:"user",
										data:{
											userId:data["HEAD_DATA"]["id"],
											name:data["HEAD_DATA"]["name"],
											title:data["HEAD_DATA"]["name"],
											workPosition:data["HEAD_DATA"]["position"],
											imageUrl:data["HEAD_DATA"]["personal_photo"]
										},
									},
									sectionCode:"extra",
									id:"HEAD"
								};
							}

							if(data["DEPARTMENTS"])
							{
								this.formFields["DEPARTMENTS"] = {
									title:BX.message("DEPARTMENTS"),
									subtitle:data["DEPARTMENTS"],
									sectionCode:"extra",
									useEstimatedHeight:true,
									id:"DEPARTMENTS"
								};
							}

							if(data["EMPLOYEES_LIST"])
							{
								this.formFields["EMPLOYEES_LIST"] = {
									title:BX.message("STRUCTURE_EMPLOYEES"),
									subtitle:data["EMPLOYEES_LIST"],
									sectionCode:"extra",
									useEstimatedHeight:true,
									id:"EMPLOYEES"
								};
							}
						}
					}
				}

			});

		}


		getUserData()
		{
			return {
				[this.user.id] : {
					avatar: this.userPhotoUrl,
					name: this.user.name_formatted
				}
			}
		}

		openCommunicateMenu()
		{
			let buttons = [{title: BX.message("COMMUNICATE_MESSAGE"), code: "message"},];
			if(["bot", "email", "network", "imconnector"].indexOf(this.externalAuthId) == -1)
			{
				if(BX.componentParameters.get("userId",0) != this.userId)
				{
					buttons = buttons.concat([
						{title: BX.message("COMMUNICATE_VIDEO"), code: "video"},
						{title: BX.message("COMMUNICATE_AUDIO"), code: "audio"},
					]);
				}
			}

			dialogs.showActionSheet({
				callback: item =>
				{
					if (item.code == "message")
					{
						this.openChat(this.userId,  {
							name: this.name,
							description: this.position,
							avatar: this.imageUrl
						});
					}
					else if (item.code == "video" || item.code == "audio")
					{
						BX.postComponentEvent("onCallInvite",
							[{
								userId: this.userId,
								video: (item.code == "video"),
								userData: {[this.userId] : { avatar: this.imageUrl, name: this.name}}
							}]);
					}
				},
				items: buttons
			});
		}

		onItemSelected(item)
		{

			let valueForOpening = item.subtitle;
			if(item.params)
			{

				if(item.params.code == "communicate")
				{
					this.openChat(this.userId,  {
						name: this.name,
						description: this.position,
						avatar: this.imageUrl
					});
					return;
				}
			}

			if(item.id == "userinfo")
			{
				console.log(this.fieldsValues["PERSONAL_PHOTO_ORIGINAL"]);
				if(this.fieldsValues["PERSONAL_PHOTO_ORIGINAL"] && viewer)
				{
					viewer.openImage(this.fieldsValues["PERSONAL_PHOTO_ORIGINAL"], item["title"]);
				}
				return;
			}

			if(item.params)
			{
				if(item.params.openScheme)
				{
					let scheme = item.params.openScheme;
					if(scheme === "tel" || scheme === "tel-inner")
					{
						let items = [];
						if(scheme === "tel")
						{
							items.push({title: BX.message("PHONE_CALL"), code:"call"});
						}
						if(this.canUseTelephony)
						{
							items.push({title: BX.message("PHONE_CALL_B24"), code:"callbx24"});
						}
						items.push({title: BX.message("PHONE_COPY"), code:"copy"});

						valueForOpening = valueForOpening.replace(/\s/g,"");
						dialogs.showActionSheet({
							callback: item =>{
								if(item.code == "copy")
								{
									Application.copyToClipboard(valueForOpening);
								}
								else if(item.code == "callbx24")
								{
									BX.postComponentEvent("onPhoneTo", [{number: valueForOpening}], "calls");
								}
								else
								{
									Application.openUrl(`${scheme}:${valueForOpening}`);
								}
							},
							items:items
						});
					}
					else if(scheme == "user")
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
			else
			{
				if(item.subtitle.startsWith("http"))
				{
					Application.openUrl(`${valueForOpening}`);
				}
			}
		}

		openChat (dialogId, dialogTitleParams)
		{
			dialogTitleParams = dialogTitleParams || false;
			if (!dialogId)
			{
				return false;
			}

			if (Application.getApiVersion() >= 25)
			{
				console.info('BX.MobileTools.openChat: open chat in JSNative component');
				BX.postComponentEvent("onOpenDialog", [{
					dialogId: dialogId,
					dialogTitleParams: dialogTitleParams ? {
						name: dialogTitleParams.name || '',
						avatar: dialogTitleParams.avatar || '',
						color: dialogTitleParams.color || '',
						description: dialogTitleParams.description || ''
					} : false }], 'im.recent');
			}
			else
			{
				PageManager.openPage({
					url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/im/dialog.php?id=" + dialogId,
					bx24ModernStyle: true,
					data: {dialogId: dialogId}
				});
			}

			return true;
		}

		get popupMenu()
		{
			if(!this._popupMenu)
			{
				if(typeof dialogs.createPopupMenu == "function")
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

		render()
		{

			this.prepare();
			if(this.form.stopRefreshing)
				this.form.stopRefreshing();
			this.name = this.fieldsValues["NAME_FORMATTED"];
			this.imageUrl = this.fieldsValues["PERSONAL_PHOTO"];
			this.position = this.fieldsValues["WORK_POSITION"];
			this.externalAuthId = this.fieldsValues["EXTERNAL_AUTH_ID"];
			let items = Object.values(this.formFields)
				.filter(item => (item["subtitle"] && item["subtitle"].length > 0))
				.map(item =>
				{
					item.type = "info";
					if(!item.height)
						item.height = 60;
					item.styles = {
						'title': {
							'font': {
								'color': '#777777',
								'fontStyle': 'normal',
								'size': 14,
							},
						},
						'subtitle': {
							'font': {
								'color': '#000000',
								'fontStyle': 'normal',
								'size': 16,
							},
						},
					};

					return item;
				});

			BX.onViewLoaded(() =>
			{
				let topItem = {
					imageUrl: encodeURI(this.fieldsValues["PERSONAL_PHOTO"]),
					title: this.fieldsValues["NAME_FORMATTED"],
					styles:{
						title:{
							font:{
								size:18,
								fontStyle:"medium"
							}
						},
					},
					subtitle: this.fieldsValues["WORK_POSITION"],
					sectionCode: "top",
					type:"userinfo",
					id: "userinfo",
					height: 100,
					imageHeight: 80,
					useLetterImage:true,
					color:"#2e455a"
				};


				Application.sharedStorage().set("user_head_"+this.userId, JSON.stringify({
					imageUrl: encodeURI(this.fieldsValues["PERSONAL_PHOTO"]),
					title: this.fieldsValues["NAME_FORMATTED"],
					name: this.fieldsValues["NAME_FORMATTED"],
					workPosition:this.fieldsValues["WORK_POSITION"]
				}));

				if(!this.loaded)
				{
					this.form.setSections(this.formSections);
					this.form.addItems(items, false);
					this.form.updateItems([
						{ filter: {type: "userinfo"}, element: topItem}
					]);
					this.loaded = true;
				}
				else
				{
					items.push(topItem);
					items.push({
					title: BX.message("COMMUNICATE_MESSAGE"),
					imageUrl:"/bitrix/mobileapp/mobile/extensions/bitrix/user/profile/images/communication.png?1",
					sectionCode: "actions",
					styles:{
						title:{
							font:{
								'size': 17,
								'color': '#000000',
							}
						},
						image:{
							image:{
								height:26,
								borderRadius:0,
							}
						},
					},
					type:"info",
					params:{code: "communicate"}
				});

					this.form.setItems(items, this.formSections)
				}
			});
		}

		buildPopupMenu()
		{
			if(Application.getApiVersion() >= 28)
			{
				this.popupMenu.setData(
					[
						{title: BX.message("PROFILE_USER_TASKS"), sectionCode: "usermenu", id: "tasks"},
						{title: BX.message("PROFILE_USER_FILES"), sectionCode: "usermenu", id: "files"},
						{title: BX.message("PROFILE_USER_MESSAGES"), sectionCode: "usermenu", id: "messages"}
					],
					[{id: "usermenu", title: ""}],
					(event, item) =>
					{
						if(event == "onItemSelected")
						{
							if(item.id == "files")
							{
								PageManager.openWidget(
									"list",
									{
										onReady: list =>
										{

											UserDisk.open({
												userId: env.userId,
												ownerId: this.userId,
												title: item.title,
												list: list

											})
										},
										title: item.title
									});
							}
							else if(item.id == "tasks")
							{
								let data = {
									params: {
										"COMPONENT_CODE" :"tasks.list",
										"USER_ID" :this.userId,
										"SITE_ID" :env.siteId,
										"LANGUAGE_ID" :env.languageId,
										"SITE_DIR" :env.siteDir,
										"PATH_TO_TASK_ADD":env.siteDir+"mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#"
									},
									path: availableComponents["tasks.list"]["publicUrl"]
								};

								TaskView.open(data);
							}
							else if(item.id == "messages")
							{
								PageManager.openPage({url:`${env.siteDir}mobile/index.php?blog=Y&created_by_id=${this.userId}`})
							}
						}
					});
				this.form.setRightButtons([
					{
						type: "more",
						callback: () => this.popupMenu.show()

					}]);
			}
		}

		error()
		{
			this.form.stopRefreshing();
			super.error();
		}

		onRefresh()
		{
			this.load();
		}

		static open (userData = {}, formObject = false)
		{
			let params = {
				userId : null,
				url : null,
				imageUrl : "",
				title :"",
				workPosition : "",
				name : ""
			};

			let cachedData = Application.sharedStorage().get("user_head_"+userData.userId);
			if(cachedData)
			{
				cachedData = JSON.parse(cachedData);
				params = Object.assign(params, cachedData);
			}

			params = Object.assign(params,userData);
			if(Application.getApiVersion() >= 27 && params.userId != null)
			{
				let top = {
					imageUrl: params.imageUrl,
					title: params.name,
					subtitle: params.workPosition,
					sectionCode: "top",
					type:"userinfo",
					styles:{
						title:{
							font:{
								size:18,
								fontStyle:"medium"
							}
						},
					},
					height: 100,
					useLetterImage:true,
					color:"#2e455a"
				};


				if(formObject == false)
				{
					PageManager.openWidget(
						"list",
						{
							title: params.title,
							groupStyle: true,
							onReady: form=> {
								(new ProfileView(params.userId,form,[top,
									{ type:"loading", sectionCode:"1", title:""}
								], [
									{id: "top", backgroundColor:"#f0f0f0"},
									{id: "actions", backgroundColor:"#f0f0f0"},
									{id: "1", backgroundColor:"#f0f0f0"},
								])).init();
							},
							onError: error=> console.log(error),
						});
				}
				else
				{

					if(formObject.setTitle)
						formObject.setTitle({text: BX.message("PROFILE_INFO")});
					(new ProfileView(params.userId, formObject,[top,
						{ type:"loading", sectionCode:"1", title:""}
					], [
						{id: "top", backgroundColor:"#f0f0f0"},
						{id: "actions", backgroundColor:"#f0f0f0"},
						{id: "1", backgroundColor:"#f0f0f0"},
					])).init();
				}

			}
			else
			{
				if(url)
				{
					PageManager.openPage({url: params.url, titleParams:{text:params.title}});
				}
			}
		}
	}

	class ProfileEdit extends Profile
	{

		init()
		{
			BX.onViewLoaded(()=>
			{
				if(this.form.setTitle)
				{
					this.form.setTitle({
						text: BX.message("PROFILE_INFO")
					});
				}
			});

			super.init();
		}

		onItemChanged(data)
		{
			if(data.type == "userpic")
			{
				if(data.value == "")
				{
					this.updateAvatar(false)
				}
				else
				{
					FileProcessing.resize("avatarResize", {
						url:data.value,
						width:1000,
						height:1000,
					}).then(path => {
						BX.FileUtils.fileForReading(path)
							.then(file=>
							{
								file.readMode = BX.FileConst.READ_MODE.DATA_URL;
								file.readNext()
									.then(fileData=>
									{
										if(fileData.content)
										{
											let content = fileData.content;
											this.updateAvatar([
												"avatar.png",
												content.substr(content.indexOf("base64,")+7, content.length),
											]);
										}
									})
									.catch(e=>console.error(e));
							})
							.catch(e=>console.error(e));
					});
				}
			}
		}

		showNotification(message)
		{
			include("InAppNotifier");
			if(typeof InAppNotifier != "undefined")
			{

				InAppNotifier.showNotification({
					title:BX.message("PROFILE_EDIT"),
					backgroundColor:"#075776",
					time: 1,
					blur:true,
					message: message
				})
			}
		}

		updateAvatar(avatar)
		{
			let data = {PERSONAL_PHOTO: avatar, id: this.userId};
			BX.rest.callMethod("user.update", data)
				.then(e =>
				{
					this.showNotification(BX.message("AVATAR_CHANGED_SUCCESS"));
					BX.postComponentEvent("shouldReloadMenu", null, "settings");
				})
				.catch(response =>
				{
					if(response.answer && response.answer.error_description)
					{
						this.error(response.answer.error_description.replace(/<br>/g,"").trim());
					}

					console.error(response);
				});
		}

		render()
		{
			this.formSections = this.formSections.map((section) =>
			{
				section.styles = {
					'title': {
						'font': {
							'color': '#777777',
							'fontStyle': 'medium',
						},
					},
				};

				return section;
			});

			Object.keys(this.formFields).forEach(fieldName =>
			{
				if (this.fieldsValues[fieldName])
				{
					this.formFields[fieldName]["value"] = this.fieldsValues[fieldName];
				}

				this.formFields[fieldName].styles = {
					'title': {
						'font': {
							'color': '#777777',
							'fontStyle': 'semibold',
							'size': 14
						},
					},
				};

				if(!this.formFields[fieldName]["type"])
				{
					delete this.formFields[fieldName]
				}
			});


			let imageUrl = this.fieldsValues["PERSONAL_PHOTO"]
					?encodeURI(this.fieldsValues["PERSONAL_PHOTO"])
					:"";
			this.formFields["PERSONAL_PHOTO"] = {
				imageUrl: imageUrl,
				value: imageUrl,
				title: `${this.fieldsValues["NAME"]} ${this.fieldsValues["LAST_NAME"]}`,
				subtitle: this.fieldsValues["WORK_POSITION"],
				sectionCode: "top",
				type:"userpic",
				height: 160,
				imageHeight: 120,
				useLetterImage:true,
				color:"#2e455a"
			};

			let items = Object.values(this.formFields);
			this.form.setItems(items, this.formSections);
			this.form.setRightButtons([
				{
					name: BX.message("SAVE_FORM"),
					callback: () =>
					{
						if(!this.isBeingUpdated)
						{
							this.isBeingUpdated = true;
							let data = {id: this.userId};
							delete data["PERSONAL_PHOTO"];
							this.form.getItems().forEach(item => data[item["id"]] = item["value"]);
							dialogs.showLoadingIndicator();
							BX.rest.callMethod("user.update", data)
								.then(e =>
								{
									this.isBeingUpdated = false;
									this.showNotification(BX.message("PROFILE_CHANGED_SUCCESS"));
									BX.postComponentEvent("shouldReloadMenu", null, "settings");
									dialogs.hideLoadingIndicator();
									this.form.back();
								})
								.catch(response =>
								{
									this.isBeingUpdated = false;
									dialogs.hideLoadingIndicator();
									if(response.answer && response.answer.error)
									{
										if(response.answer.error_description)
										{
											this.error(response.answer.error_description);
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

	//export
	this.Profile = Profile;
	this.ProfileEdit = ProfileEdit;
	this.ProfileView = ProfileView;

})();