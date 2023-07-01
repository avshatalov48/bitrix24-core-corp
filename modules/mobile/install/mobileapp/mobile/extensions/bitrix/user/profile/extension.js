/**
* @bxjs_lang_path extension.php
 * @module user/profile
*/
jn.define("user/profile", (require, exports, module) => {
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

	const crmEntityDetailComponent = 'crm:crm.entity.details';

	const statusColor = status =>{
		let colors = {
			admin: "#2FC6F6",
			extranet: "#F7A700",
			integrator: "#55D0E0",
			fired: "#A8ADB4",
			owner: "#FF799C",
		};

		if(colors[status]) {
			return colors[status];
		}

		return colors.fired;
	}

	class Profile
	{
		constructor(userId = 0, form, items = [], sections = [])
		{
			this.form = form;
			this.isBackdrop = false;
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
			console.error(message);
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
							if (this.formFields[fieldName]["asterix"]) {
								this.formFields[fieldName]['title'] =  this.formFields[fieldName]['title']+"*"
								let sectionIndex = this.formSections.findIndex( section => section.id === this.formFields[fieldName]['sectionCode'])
								if (!this.formSections[sectionIndex]["footer"]) {
									this.formSections[sectionIndex]["footer"] = this.formFields[fieldName]["asterix"];
								}
								else
								{
									this.formSections[sectionIndex]["footer"]+="\n"+this.formFields[fieldName]["asterix"];
								}
							}

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
			if(["bot", "email", "network", "imconnector"].indexOf(this.externalAuthId) === -1)
			{
				if(BX.componentParameters.get("userId",0) !== this.userId)
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
			const params = {
				'NAME': this.fieldsValues["NAME_FORMATTED"],
			};

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

			if(item.params && Object.keys(item.params).length > 0)
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
									BX.postComponentEvent("onPhoneTo", [{number: valueForOpening, params}], "calls");
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

				const dialogParams = {
					dialogId: dialogId,
					dialogTitleParams: dialogTitleParams ? {
						name: dialogTitleParams.name || '',
						avatar: dialogTitleParams.avatar || '',
						color: dialogTitleParams.color || '',
						description: dialogTitleParams.description || '',
					} : false,
				}

				let openDialog = () => {
					BX.postComponentEvent('onOpenDialog', [dialogParams], 'im.recent');
					BX.postComponentEvent('ImMobile.Messenger.Dialog:open', [dialogParams], 'im.messenger');
				};

				if(Application.getApiVersion() >= 45 && this.isCrmEntityDetail())
				{
					const imOpener = DialogOpener();
					if(typeof imOpener === 'function') {
						openDialog = () => imOpener.open(dialogParams)
					}
				}

				if (!this.isBackdrop)
				{
					return openDialog();
				}

				this.form.close(openDialog);

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

		isCrmEntityDetail()
		{
			if (!PageManager.getNavigator())
			{
				return null;
			}

			const { type } = PageManager.getNavigator().getVisible();

			return crmEntityDetailComponent === type;
		}

		/**
		 *
		 * @returns {JSPopoverMenu}
		 */
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

		availableStatus(status) {
			return [
				"admin",
				"extranet",
				"integrator",
				"fired",
				"owner",
			].indexOf(status) >= 0;
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
						item.height = 80;
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
						tag: {
							color:"#ffffff",
							backgroundColor:statusColor(this.fieldsValues["STATUS"]),
							padding:{top:5,bottom:5, left:10, right:10},
							cornerRadius:14,
						},
						title:{
							font:{
								size:18,
								fontStyle:"medium"
							}
						},
					},
					// tag: this.availableStatus(this.fieldsValues["STATUS"])? this.fieldsValues["STATUS_NAME"] : undefined,
					subtitle: this.fieldsValues["WORK_POSITION"],
					sectionCode: "top",
					type:"userinfo",
					id: "userinfo",
					// height: this.availableStatus(this.fieldsValues["STATUS"])? 130 : 100,
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
								let opener = Application.getApiVersion() >= 41 ? this.form: PageManager
								opener.openWidget(
									"list",
									{
										useSearch: true,
										onReady: list =>
										{

											UserDisk.open({
												userId: env.userId,
												ownerId: this.userId,
												title: item.title,
												list: list

											})
										},
										title: BX.message("PROFILE_INFO")
									});
							}
							else if(item.id == "tasks")
							{
								BX.postComponentEvent('taskbackground::taskList::open', [{ownerId: this.userId}], 'background');
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

		error(e)
		{
			this.form.stopRefreshing();
			super.error(e);
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
				name : "",
				isBackdrop: false
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
						tag: {
							// backgroundColor:"#f0f0f0",
							padding:{top:5,bottom:5, left:10, right:10},
							cornerRadius:14,
						},
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

				let openProfile = form=> {
					let profile = new ProfileView(params.userId,form,[top,
						{ type:"loading", sectionCode:"1", title:""}
					], [
						{id: "top", backgroundColor:"#f0f0f0"},
						{id: "actions", backgroundColor:"#f0f0f0"},
						{id: "1", backgroundColor:"#f0f0f0"},
					]);


					profile.isBackdrop = params.isBackdrop;
					profile.init();
				};

				if(formObject == false)
				{
					PageManager.openWidget(
						"list",
						{
							title: params.title,
							groupStyle: true,
							onReady: openProfile,
							onError: error=> console.log(error),
						});
				}
				else
				{
					if(formObject.setTitle)
						formObject.setTitle({text: BX.message("PROFILE_INFO")});

					openProfile(formObject);
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

		static openComponent(userData = {})
		{
			if(Application.getApiVersion() >= 27)
			{
				let url = "";
				if(availableComponents && availableComponents["user.profile"])
				{
					url = availableComponents["user.profile"]["publicUrl"];
				}
				console.log({
					scriptPath: url,
					params: {"userId": userData.userId},
					canOpenInDefault:true,
					rootWidget:{
						name: "list",
						title: userData.title,
						description: true,
						settings:{objectName: "form", description: true,}
					}
				});

				PageManager.openComponent("JSStackComponent",
					{
						scriptPath: url,
						params: {"userId": userData.userId},
						canOpenInDefault:true,
						rootWidget:{
							name: "list",
							title: userData.title,
							description: true,
							settings:{objectName: "form", description: true,}
						}
					});
			}
			else
			{
				PageManager.openPage({url:"/mobile/users/?user_id="+userData.userId});
			}
		}
	}


	//export

	module.exports = {Profile, ProfileView}
});