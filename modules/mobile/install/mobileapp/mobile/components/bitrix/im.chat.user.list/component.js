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

	this.items = BX.componentParameters.get('ITEMS', []);
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

	this.event.init();

	return true;
};

ChatUserList.openUserProfile = function(userId, userData = {})
{
	console.log('ChatUserList.openUserProfile', userId, userData);

	ProfileView.open({
		userId,
		imageUrl: ChatUtils.getAvatar(userData.avatar),
		title: userData.name,
		workPosition: userData.work_position,
		name: userData.name,
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

	BX.onViewLoaded(() => ChatUserListInterface.setItems(this.base.items, this.base.sections));
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

/* Initialization */
ChatUserList.init();