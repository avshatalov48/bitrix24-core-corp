/**
 * @module im/messenger/controller/participants-list
 */
jn.define('im/messenger/controller/participants-list', (require, exports, module) => {

	const { Loc } = require('loc');
	const { core } = require('im/messenger/core');
	const { RestMethod } = require('im/messenger/const/rest');
	const { DialogHelper } = require('im/messenger/lib/helper/dialog');
	const { ChatTitle } = require('im/messenger/lib/element');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { ParticipantsListView } = require('im/messenger/controller/participants-list/view');

	class ParticipantsList
	{
		static open(options = {}, parentWidget = null)
		{
			const { dialogId } = options;
			if (!(new DialogHelper()).isDialogId(dialogId))
			{
				return;
			}

			const widget = new ParticipantsList(dialogId, parentWidget);
			widget.getUserList()
				.then(userList => userList.map(user => widget.prepareUserData(user)))
				.then(userList => widget.show(userList))
		}

		constructor(dialogId, parentWidget = null)
		{
			this.dialogId = dialogId;
			this.parentWidget = parentWidget;
			this.store = core.getStore();
			this.dialogData = ChatUtils.objectClone(this.store.getters['dialoguesModel/getById'](dialogId));
		}

		getUserList()
		{
			return new Promise((resolve, reject) => {
				BX.rest.callMethod(
					RestMethod.imDialogUsersList,
					{
						DIALOG_ID: this.dialogId,
					},
					(result) => {
						if (result.error())
						{
							reject(result.error());
						}

						const data = result.data();
						this.store.dispatch('usersModel/set', data);

						resolve(data);
					}
				);
			});
		}

		prepareUserData(user)
		{
			const chatTitle = ChatTitle.createFromDialogId(user.id)
			return {
				data: {
					id: user.id,
					title: chatTitle.getTitle(),
					subtitle: chatTitle.getDescription(),
					avatarColor: user.color,
					avatarUri: user.avatar,
					status: this.dialogData.ownerId === user.id ? 'owner' : null,
				},
				nextTo: true
			};
		}

		show(itemList)
		{
			const widgetConfig = {
				title: Loc.getMessage('IMMOBILE_MESSENGER_WIDGET_PARTICIPANT_LIST_TITLE'),
				backdrop: {
					mediumPositionPercent: 50,
					horizontalSwipeAllowed: false,
				},

				onReady: layoutWidget =>
				{
					layoutWidget.showComponent(new ParticipantsListView({
						itemList: itemList,
						onItemSelected: (itemData) => {
							UserProfile.show(itemData.id, { backdrop: true });
						}
					}));
				},
				onError: error => reject(error),
			};

			if (this.parentWidget)
			{
				this.parentWidget.openWidget('layout', widgetConfig);
				return;
			}

			PageManager.openWidget('layout', widgetConfig);
		}
	}

	module.exports = { ParticipantsList };
});