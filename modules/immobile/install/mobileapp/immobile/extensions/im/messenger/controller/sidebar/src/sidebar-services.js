/**
 * @module im/messenger/controller/sidebar/sidebar-services
 */
jn.define('im/messenger/controller/sidebar/sidebar-services', (require, exports, module) => {
	const { MapCache } = require('im/messenger/cache');
	const { MuteService } = require('im/messenger/provider/service/classes/chat/mute');
	const { restManager, RestManager } = require('im/messenger/lib/rest-manager');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { RestMethod } = require('im/messenger/const/rest');
	const { Type } = require('type');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class SidebarServices
	 * @desc The class API provides getting the controller sidebar services.
	 * * rest manager created in two variants:
	 * * 1 - sidebarRestManager ( this new manager with only actions sidebar )
	 * * 2 - generalRestManager ( this general/global manager, used by messenger )
	 */
	class SidebarServices
	{
		constructor(store, dialogId)
		{
			this.store = store;
			this.dialogId = dialogId;
			this.sidebarRestManager = new RestManager();
			this.generalRestManager = restManager;
			this.muteService = new MuteService(this.store, this.sidebarRestManager);
			this.mapCache = new MapCache(35000);
		}

		/**
		 * @desc Set data in current store
		 * @param {object} [data]
		 * @param {string} [data.dialogId]
		 * @param {boolean} [data.isMute]
		 * @void
		 */
		setStore(data)
		{
			const dataStore = data || { dialogId: this.dialogId, isMute: this.isMuteDialog() };
			this.store.dispatch('sidebarModel/set', dataStore);
		}

		/**
		 * @desc Check is mute chat ( dialog )
		 * @return {boolean}
		 */
		isMuteDialog()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (dialogData)
			{
				const user = MessengerParams.getUserId();

				return dialogData.muteList.includes(user);
			}

			return false;
		}

		/**
		 * @desc Rest call all participants ( by fulfilled state are updating store user model )
		 * @return {Promise<object>}
		 */
		getParticipantList()
		{
			return new Promise((resolve, reject) => {
				BX.rest.callMethod(
					RestMethod.imDialogUsersList,
					{
						DIALOG_ID: this.dialogId,
						LIMIT: 200,
					},
					(result) => {
						if (result.error())
						{
							reject(result.error());
							Logger.error(result.error());
						}
						const data = result.data();

						this.store.dispatch('usersModel/update', data);

						this.store.dispatch('dialoguesModel/update', {
							actionName: 'updateParticipants',
							dialogId: this.dialogId,
							fields: {
								userCounter: data.length,
								participants: data.map((user) => user.id),
							},
						});
						resolve(data);
					},
				);
			});
		}

		/**
		 * @desc Rest call delete participant by id
		 * @param {number} userId
		 * @return {Promise<boolean>}
		 */
		deleteParticipant(userId)
		{
			const chatId = Type.isString(this.dialogId) ? Number(this.dialogId.slice(4)) : this.dialogId;

			return BX.rest.callMethod(
				RestMethod.imChatUserDelete,
				{
					user_id: userId,
					chat_id: chatId,
				},
			).then(
				(result) => {
					return result.data();
				},
			).catch(
				(err) => {
					Logger.error(err);
				},
			);
		}

		/**
		 * @desc Rest call user by id
		 * @param {number} [userId=this.dialogId]
		 * @return {Promise<object>}
		 */
		getUserById(userId = this.dialogId)
		{
			return BX.rest.callMethod(
				RestMethod.imUserGet,
				{
					ID: userId,
				},
			).then(
				(result) => {
					if (result.error())
					{
						Logger.error(result.error());
					}

					const data = result.data();
					this.store.dispatch('usersModel/update', [data]);

					return data;
				},
			);
		}

		/**
		 * @desc Rest call dialog by id
		 * @param {number} [dialogId=this.dialogId]
		 * @return {Promise<object>}
		 */
		getDialogById(dialogId = this.dialogId)
		{
			return BX.rest.callMethod(
				RestMethod.imDialogGet,
				{
					DIALOG_ID: this.dialogId,
				},
			).then(
				(result) => {
					if (result.error())
					{
						Logger.error(result.error());
					}

					const data = result.data();
					this.store.dispatch('dialoguesModel/set', data);

					return data;
				},
			);
		}

		/**
		 * @desc Rest call user department
		 * @param {number} [userId=this.dialogId]
		 * @return {Promise<string>}
		 */
		getUserDepartment(userId = this.dialogId)
		{
			return BX.rest.callMethod(
				RestMethod.imUserGetDepartment,
				{
					id: userId,
				},
			).then(
				(result) => {
					if (result.error())
					{
						Logger.error(result.error());
					}

					const data = result.data();
					this.store.dispatch('usersModel/update', [{ id: userId, departmentName: data.name }]);

					return data.name;
				},
			);
		}
	}

	module.exports = {
		SidebarServices,
	};
});
