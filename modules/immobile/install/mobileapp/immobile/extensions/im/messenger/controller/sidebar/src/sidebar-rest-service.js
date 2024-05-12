/**
 * @module im/messenger/controller/sidebar/sidebar-rest-service
 */
jn.define('im/messenger/controller/sidebar/sidebar-rest-service', (require, exports, module) => {
	const { RestMethod } = require('im/messenger/const/rest');
	const { Type } = require('type');
	const { Logger } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class SidebarRestService
	 */
	class SidebarRestService
	{
		constructor(dialogId)
		{
			this.dialogId = dialogId;
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @desc Rest call all participants ( by fulfilled state are updating store user model )
		 * @return {Promise<object>}
		 */
		getParticipantList()
		{
			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialogModel)
			{
				return new Promise().resolve(false);
			}

			return new Promise((resolve, reject) => {
				BX.rest.callMethod(
					RestMethod.imDialogUsersList,
					{
						DIALOG_ID: this.dialogId,
						LIMIT: 50,
						LAST_ID: dialogModel.lastLoadParticipantId,
					},
					(result) => {
						if (result.error() || result.status !== 200)
						{
							Logger.error('getParticipantList.error', result.error(), result.ex);
							reject(result.error());

							return;
						}

						const data = result.data();
						Logger.info('SidebarServices.getParticipantList:', result);

						if (Array.isArray(data) && data.length > 0)
						{
							this.store.dispatch('usersModel/merge', data);

							this.store.dispatch('dialoguesModel/addParticipants', {
								dialogId: this.dialogId,
								participants: data.map((user) => user.id),
								lastLoadParticipantId: data[data.length - 1].id,
							});
						}

						if (Array.isArray(data) && data.length === 0)
						{
							this.store.dispatch('dialoguesModel/update', {
								dialogId: this.dialogId,
								fields: {
									participants: [],
									lastLoadParticipantId: 0,
								},
							});
						}

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
				RestMethod.imV2ChatDeleteUser,
				{
					id: chatId,
					userId,
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
					this.store.dispatch('usersModel/merge', [data]);

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

					if (Type.isNull(result.answer.result))
					{
						return '   ';
					}

					const data = result.data();
					this.store.dispatch('usersModel/update', [{ id: userId, departmentName: data.name }]);

					return data.name;
				},
			);
		}

		/**
		 * @desc Rest leave user from chat
		 * @return {Promise<string>}
		 */
		leaveChat()
		{
			return BX.rest.callMethod(
				RestMethod.imChatLeave,
				{
					DIALOG_ID: this.dialogId,
				},
			).then(
				(result) => {
					if (result.error())
					{
						Logger.error(result.error());
					}

					return result.data();
				},
			);
		}
	}

	module.exports = {
		SidebarRestService,
	};
});
