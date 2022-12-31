"use strict";
/**
 * @bxjs_lang_path component.php
 */

/* Clean session variables after page restart */
if (typeof clearInterval == 'undefined')
{
	clearInterval = (id) => clearTimeout(id);
}
if (typeof ChatUserList != 'undefined' && typeof ChatUserList.cleaner != 'undefined')
{
	ChatUserList.cleaner();
}

/* Chat user selector API */
var ChatUserList = {};

ChatUserList.init = function()
{
	this.type = BX.componentParameters.get('LIST_TYPE', 'LIST');

	this.userId = parseInt(BX.componentParameters.get('USER_ID', 0));
	this.dialogId = BX.componentParameters.get('DIALOG_ID', 'LIST');
	this.dialogOwnerId = parseInt(BX.componentParameters.get('DIALOG_OWNER_ID', 0));

	this.users = BX.componentParameters.get('USERS', false);
	this.items = BX.componentParameters.get('ITEMS', []);
	this.isBackdrop = BX.componentParameters.get('IS_BACKDROP', false);

	this.sections = BX.componentParameters.get('SECTIONS', []);

	if (!this.dialogId)
	{
		this.close();
		return false;
	}

	/* set cross-links in class */
	let links = ['base', 'event', 'rest'];
	links.forEach((subClass) => {
		if (typeof this[subClass] != 'undefined')
		{
			links.forEach((element) => {
				if (element == 'base')
				{
					this[subClass]['base'] = this;
				}
				else if (subClass != element)
				{
					this[subClass][element] = this[element];
				}
			});
		}
	});

	ChatUserListInterface.setRefreshingEnabled(false);

	this.event.init();

	return true;
};

ChatUserList.openUserProfile = function(userId, userData = {})
{
	console.log('ChatUserList.openUserProfile', userId, userData);
	const { ProfileView } = jn.require("user/profile");
	ProfileView.open({
		userId,
		imageUrl: ChatUtils.getAvatar(userData.avatar),
		title: userData.name,
		workPosition: userData.work_position,
		name: userData.name,
		isBackdrop: this.isBackdrop,
		url: currentDomain+'/mobile/users/?user_id='+userId+'&FROM_DIALOG=Y',
	});

	return true;
};

ChatUserList.alert = function(text)
{
	ChatUserListInterface.showAlert(text);
	return true;
};

ChatUserList.close = function()
{
	ChatUserListInterface.close();
	return true;
};

ChatUserList.cleaner = function()
{
	BX.listeners = {};

	console.warn('ChatUserList.cleaner: OK');
};


/* Event API */
ChatUserList.event = {};

ChatUserList.event.init = function ()
{
	this.debug = false;

	this.handlersList = {
		onItemAction : this.onItemAction,
		onItemSelected : this.onItemSelected
	};

	ChatUserListInterface.setListener(this.router.bind(this));

	if (this.base.items.length <= 0)
	{
		if (this.base.users.length <= 0)
		{
			this.base.items.push({
				title : BX.message("IM_USER_LIST_EMPTY"),
				type:"button",
				unselectable: true,
				params: { action: 'empty'}
			});
		}
		else
		{
			ChatUserList.rest.userListGet(this.base.users);
		}
	}

	if (this.base.items.length > 0)
	{
		BX.onViewLoaded(() => ChatUserListInterface.setItems(this.base.items, this.base.sections));
	}
};

ChatUserList.event.router = function(eventName, eventResult)
{
	if (this.handlersList[eventName])
	{
		this.handlersList[eventName].apply(this, [eventResult])
	}
	else if (this.debug)
	{
		console.info('ChatUserList.event.router: skipped event - '+eventName+' '+JSON.stringify(eventResult));
	}
};

ChatUserList.event.onItemAction = function(event)
{
	console.info('ChatUserList.event.onItemAction', event);

	if (this.base.type == 'USERS')
	{
		if (event.action.identifier == 'kick')
		{
			this.rest.userDelete(event.item.params.id);
		}
		else if (event.action.identifier == 'owner')
		{
			this.rest.setOwner(event.item.params.id);
		}
	}
	else
	{
		console.warn(`Action skipped: ${event.action.identifier} - id: ${event.item.params.id}`);
	}

	return true;
};

ChatUserList.event.onItemSelected = function(event)
{
	console.info('ChatUserList.event.onItemSelected', event);

	if (
		event.params.external_auth_id === 'imconnector'
		|| event.params.external_auth_id === 'call'
	)
	{
		return false;
	}


	this.base.openUserProfile(event.params.id, {
		avatar: event.imageUrl,
		name: event.title,
		work_position: event.subtitle,
	});

	return true;
};


/* Rest API */
ChatUserList.rest = {};

ChatUserList.rest.userDelete = function (userId)
{
	BX.rest.callMethod('im.chat.user.delete', {'DIALOG_ID': this.base.dialogId, 'USER_ID': userId})
		.then((result) =>
		{
			if (result.data())
			{
				this.base.items = this.base.items.filter(element => element.id != userId);
				console.info(`ChatUserList.rest.userDelete: user ${userId} deleted`);
			}
			else
			{
				console.error("ChatUserList.rest.userDelete: we have some problems on server\n", result.answer);
				this.base.alert(BX.message('IM_USER_API_ERROR'));
			}
		})
		.catch((result) =>
		{
			let error = result.error();
			if (error.ex.error == 'NO_INTERNET_CONNECTION')
			{
				console.error("ChatUserList.rest.userDelete - error: connection error", error.ex);
				this.base.alert(BX.message('IM_USER_CONNECTION_ERROR'));
			}
			else
			{
				console.error("ChatUserList.rest.userDelete - error: we have some problems on server\n", result.answer);
				this.base.alert(BX.message('IM_USER_API_ERROR'));
			}
		});
};

ChatUserList.rest.setOwner = function (userId)
{
	BX.rest.callMethod('im.chat.setOwner', {'DIALOG_ID': this.base.dialogId, 'USER_ID': userId})
		.then((result) =>
		{
			if (result.data())
			{
				this.base.items = this.base.items.map(item => {
					item.actions = [];

					if (item.id == userId)
					{
						item.styles.title.image = {name: 'name_status_owner'};
					}
					else if (
						item.styles
						&& item.styles.title
						&& item.styles.title.image
						&& item.styles.title.image.name == 'name_status_owner'
					)
					{
						item.styles.title.image = {};
					}

					return item;
				});
				ChatUserListInterface.setItems(this.base.items, this.base.sections);
				console.info(`ChatUserList.rest.setOwner: new owner is ${userId}`);
			}
			else
			{
				console.error("ChatUserList.rest.setOwner: we have some problems on server\n", result.answer);
				this.base.alert(BX.message('IM_USER_API_ERROR'));
			}
		})
		.catch((result) =>
		{
			let error = result.error();
			if (error.ex.error == 'NO_INTERNET_CONNECTION')
			{
				console.error("ChatUserList.rest.setOwner - error: connection error", error.ex);
				this.base.alert(BX.message('IM_USER_CONNECTION_ERROR'));
			}
			else
			{
				console.error("ChatUserList.rest.setOwner - error: we have some problems on server\n", result.answer);
				this.base.alert(BX.message('IM_USER_API_ERROR'));
			}
		});
};

ChatUserList.rest.userListGet = function (users)
{
	let restMethod = 'im.user.list.get';
	let restParams = {ID: users, RESULT_TYPE: 'array'};

	if (!users)
	{
		restMethod = 'im.dialog.users.get';
		restParams = {DIALOG_ID: this.base.dialogId};
	}

	BX.rest.callMethod(restMethod, restParams).then((result) =>
	{
		if (result.data())
		{
			const items = [];

			result.data().forEach(element =>
			{
				let item = ChatDataConverter.getSearchElementFormat(element);
				item.actions = [];

				if (this.base.type === 'USERS')
				{
					if (this.base.dialogOwnerId === this.base.userId)
					{
						if (false && this.base.isLines) // TODO lines
						{
							if (
								this.base.userId !== item.id
								&& linesUsers.indexOf(item.id) < 0
							)
							{
								item.actions.push({
									title : BX.message("IM_USER_LIST_KICK"),
									identifier : "kick",
									iconName : "action_delete",
									destruct : true,
									color : "#df532d"
								});
							}
						}
						else if (this.base.userId !== item.id)
						{
							if (element.extranet && (
								element.external_auth_id === 'imconnector'
								|| element.external_auth_id === 'call'
							))
							{
								item.unselectable = true;
								item.type = 'info';
							}

							if (this.base.userId === this.base.dialogOwnerId)
							{
								if (!element.extranet)
								{
									item.actions.push({
										title : BX.message("IM_USER_LIST_OWNER"),
										identifier : "owner",
										color : "#aac337"
									});
								}

								item.actions.push({
									title : BX.message("IM_USER_LIST_KICK"),
									identifier : "kick",
									destruct : true,
									color : "#df532d"
								});
							}
						}
					}

					if (item.id === this.base.dialogOwnerId)
					{
						item.styles.title.image = {name: 'name_status_owner'};
					}
				}

				items.push(item);

				return true;
			});

			this.base.items = items;
			ChatUserListInterface.setItems(items);
		}
		else
		{
			console.error("ChatUserList.rest.userListGet: we have some problems on server\n", result.answer);
			this.base.alert(BX.message('IM_USER_API_ERROR'));
		}
	})
	.catch((result) =>
	{
		let error = result.error();
		if (error.ex.error === 'NO_INTERNET_CONNECTION')
		{
			console.error("ChatUserList.rest.userListGet - error: connection error", error.ex);
			this.base.alert(BX.message('IM_USER_CONNECTION_ERROR'));
		}
		else
		{
			console.error("ChatUserList.rest.userListGet - error: we have some problems on server\n", result.answer);
			this.base.alert(BX.message('IM_USER_API_ERROR'));
		}
	});
};

/* Initialization */
ChatUserList.init();
