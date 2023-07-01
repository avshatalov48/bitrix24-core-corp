/**
 * @module im/messenger/controller/dialog/header/menu
 */
jn.define('im/messenger/controller/dialog/header/menu', (require, exports, module) => {

	const { core } = require('im/messenger/core');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { getHeaderIcon, headerIconType } = require('im/messenger/controller/dialog/header/icon');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { ParticipantsList } = require('im/messenger/controller/participants-list');
	const { UserAdd } = require('im/messenger/controller/user-add');
	const { Loc } = require('loc');
	class HeaderMenu
	{

		static showWithDialogId(dialogId)
		{
			console.log(dialogId)
			const menu = new HeaderMenu(dialogId);
			menu.show();
		}
		constructor(dialogId)
		{
			this.dialogId = dialogId;
			this.store = core.getStore();
			this.isUser = DialogHelper.isChatId(dialogId);
			this.dialogData = this.isUser
				? ChatUtils.objectClone(this.store.getters['usersModel/getUserById'](dialogId))
				: ChatUtils.objectClone(this.store.getters['dialoguesModel/getById'](dialogId))
			;
		}

		show()
		{
			const buttonList = this.getMenuItems().filter(button => button !== null);

			const self = this;
			const popup = dialogs.createPopupMenu();
			popup.setPosition('top');
			popup.setData(buttonList, [{id: '0'}], function(event, button){
				self.eventHandler(event, button);
			})

			popup.show();
		}

		getMenuItems()
		{
			return [
				this.getMuteItem(),
				this.getProfileItem(),
				this.getUserListItem(),
				this.getAddUserItem(),
				this.getLeaveItem(),
				this.getReloadItem(),
			];
		}

		eventHandler(event, button)
		{
			this.profileEventHandler(event, button);
			this.userListEventHandler(event, button);
			this.userAddEventHandler(event, button);
			this.muteEventHandler(event, button);
			this.leaveEventHandler(event, button);
		}

		getMuteItem()
		{
			if(this.isUser || !this.dialogData.restrictions.mute)
			{
				return null;
			}

			let isMuted = this.dialogData.muteList.find(userId => MessengerParams.getUserId() === userId);
			if (typeof isMuted === 'undefined')
			{
				isMuted = false
			}

			return {
				id: 'mute',
				title: isMuted ? Loc.getMessage('IMMOBILE_MESSENGER_HEADER_UNMUTE') : Loc.getMessage('IMMOBILE_MESSENGER_HEADER_MUTE'),
				iconUrl: getHeaderIcon(isMuted ? headerIconType.notifyOff : headerIconType.notify),
				sectionCode: "0",
			}
		}

		muteEventHandler(event, button)
		{
			if (event !== 'onItemSelected' || button.id !== 'mute')
			{
				return null;
			}

			let isMuted = this.dialogData.muteList.find(userId => MessengerParams.getUserId() === userId);
			BX.rest.callMethod(
				'im.chat.mute',
				{
					CHAT_ID: this.dialogId.replace('chat',''),
					MUTE: isMuted ? 'N' : 'Y'
				}
			)
		}

		getProfileItem()
		{
			if (this.isUser && !this.dialogData.bot)
			{
				return {
					id: 'profile',
					title: Loc.getMessage('IMMOBILE_MESSENGER_HEADER_PROFILE'),
					iconUrl: getHeaderIcon(headerIconType.user),
					sectionCode: "0",
				};
			}

			return null;
		}

		profileEventHandler(event, button)
		{
			if (event !== 'onItemSelected' || button.id !== 'profile')
			{
				return;
			}

			UserProfile.show(this.dialogId, {backdrop: true});
		}

		getUserListItem()
		{
			if (!this.isUser && this.dialogData.restrictions.userList)
			{
				return {
					id: 'userList',
					title: Loc.getMessage('IMMOBILE_MESSENGER_HEADER_USER_LIST'),
					iconUrl: getHeaderIcon(headerIconType.users),
					sectionCode: "0",
				};
			}

			return null;
		}

		userListEventHandler(event, button)
		{
			if (event !== 'onItemSelected' || button.id !== 'userList')
			{
				return;
			}

			ParticipantsList.open({ dialogId: this.dialogId });
		}

		getAddUserItem()
		{
			if (this.isUser || this.dialogData.restrictions.extend)
			{
				return {
					id: 'addUser',
					title: Loc.getMessage('IMMOBILE_MESSENGER_HEADER_ADD_USER'),
					iconUrl: getHeaderIcon(headerIconType.userPlus),
					sectionCode: "0",
				};
			}

			return null;
		}

		userAddEventHandler(event, button)
		{
			if (event !== 'onItemSelected' || button.id !== 'addUser')
			{
				return;
			}

			UserAdd.open({ dialogId: this.dialogId });
		}

		getLeaveItem()
		{
			if (this.isUser || !this.dialogData.restrictions.leave)
			{
				return null;
			}

			return {
				id: 'leave',
				title: Loc.getMessage('IMMOBILE_MESSENGER_HEADER_LEAVE'),
				iconUrl: getHeaderIcon(headerIconType.cross),
				sectionCode: "0",
			};
		}

		leaveEventHandler(event, button)
		{
			if (event !== 'onItemSelected' || button.id !== 'leave')
			{
				return;
			}

			BX.rest.callMethod('im.chat.leave', {CHAT_ID: this.dialogId.replace('chat','')});
		}

		getReloadItem()
		{
			return {
				id: 'reload',
				title: Loc.getMessage('IMMOBILE_MESSENGER_HEADER_RELOAD'),
				iconUrl: getHeaderIcon(headerIconType.reload),
				sectionCode: "0",
			};
		}
	}

	module.exports = { HeaderMenu };
});