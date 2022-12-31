/**
 * * @bxjs_lang_path component.php
 * @let BaseList list
 */

(() =>
{
	const { ProfileView } = jn.require("user/profile");
	const storageId = "user.component.result";
	let componentResult = {
		/**
		 * @returns {{nameFormat:String}}
		 */
		get: function ()
		{
			if (!this.result)
			{
				return result;
			}
			else
			{
				return this.result;
			}
		},
		update: function ()
		{
			this.result = Application.storage.getObject(storageId);
			BX.ajax({url: component.resultUrl, dataType: "json"})
				.then(result =>
				{
					this.result = result;
					Application.storage.setObject(storageId, result);
				})
				.catch(e => console.error(e));
		}
	};

	componentResult.update();

	if (BX.componentParameters.get("canInvite", false))
	{
		let action = () =>
		{
			if (Application.getApiVersion() >= 34)
			{
				IntranetInvite.openRegisterSlider({
					originator: 'users',
					registerUrl: BX.componentParameters.get('registerUrl', ''),
					adminConfirm: BX.componentParameters.get('registerAdminConfirm', false),
					disableAdminConfirm: BX.componentParameters.get('disableRegisterAdminConfirm', false),
					sharingMessage: BX.componentParameters.get('sharingMessage', ''),
					rootStructureSectionId: BX.componentParameters.get('rootStructureSectionId', 0),
				});
			}
			else if (Application.getApiVersion() >= 29)
			{
				dialogs.showContactList().then(
					users =>
					{
						let fields = {
							PHONE:[],
							PHONE_COUNTRY:[],
							MESSAGE_TEXT:"",
							DEPARTMENT_ID:1,
							CONTEXT: 'mobile'
						};
						users.forEach(
							user =>
							{
								fields.PHONE.push(user.phone);
								fields.PHONE_COUNTRY.push(user.countryCode);
							});

						if(fields.PHONE.length > 0)
						{
							Notify.showIndicatorLoading();
							BX.rest.callMethod("intranet.invite.register", {"fields":fields})
								.then(res=>
								{
									let errors = res.answer.result.errors;
									if(errors && errors.length > 0)
									{
										let errorText = errors.reduce((fullMessage, errorMessage)=>{
											errorMessage = errorMessage.replace("<br/>:","\n").replace("<br/>","\n");
											fullMessage +=`\n${errorMessage}`;
											return fullMessage;
										},"");

										Notify.showIndicatorError({hideAfter: 30000, onTap:()=>Notify.hideCurrentIndicator(), text: errorText});
									}
									else
									{
										Notify.showIndicatorSuccess({hideAfter: 2000});
									}

								})
								.catch(res=>
								{
									Notify.showIndicatorError({hideAfter: 2000, text: res.answer.error_description});
								})
						}
					}
				);
			}
			else
			{
				PageManager.openPage({
					url: "/mobile/users/invite.php?",
					cache: false,
					modal: true,
					title: BX.message("INVITE_USERS")
				});
			}

		};

		let addUserButton = {
			type: "plus",
			callback: action,
			icon: "plus",//for floating button
			animation: "hide_on_scroll", //for floating button
			color: "#47AADE"
		};

		BX.onViewLoaded(()=>{
			if (Application.getPlatform() === "ios")
			{
				list.setRightButtons([{type:"search", callback:()=> list.showSearchBar()}])
				//button in navigation bar for iOS
				if(Application.getApiVersion()>=33)
					list.setFloatingButton(addUserButton);
				else
					list.setRightButtons([addUserButton]);
			}
			else
			{
				//floating button for Android
				if (Application.getApiVersion() >= 24)
				{
					list.setFloatingButton(addUserButton);
				}
			}
		})
	}


	class ListDelegate
	{
		filterUserList(items)
		{
			return items;
		}

		onSearchResult(items, sections, list, state)
		{
			list.setSearchResultItems(items, sections);
		}

		onUserSelected(user)
		{
			ProfileView.open(
				{
					userId: user.params.id,
					imageUrl: encodeURI(user.imageUrl),
					title: BX.message("PROFILE_INFO"),
					workPosition: user.subtitle,
					name: user.title,
					url: user.params.profileUrl,
				}
			);
		};
	}


	this.userList = new UserList(list, new ListDelegate(), componentResult.get().nameFormat);
	this.userList.init();

})();

