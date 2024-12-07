/**
 * @module im/messenger/controller/sidebar/chat/sidebar-rest-service
 */
jn.define('im/messenger/controller/sidebar/chat/sidebar-rest-service', (require, exports, module) => {
	const { RestMethod } = require('im/messenger/const/rest');
	const { CopilotRest } = require('im/messenger/provider/rest');
	const { runAction } = require('im/messenger/lib/rest');
	const { Type } = require('type');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--sidebar-rest-service');
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
							logger.error('getParticipantList.error', result.error(), result.ex);
							reject(result.error());

							return;
						}

						const data = result.data();
						logger.info('SidebarServices.getParticipantList:', result);

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
			return runAction(RestMethod.imV2ChatDeleteUser, {
				data: {
					dialogId: this.dialogId,
					userId,
				},
			});
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
						logger.error(result.error());
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
						logger.error(result.error());
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
						logger.error(result.error());
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
						logger.error(result.error());
					}

					return result.data();
				},
			);
		}

		/**
		 * @desc Rest add manager
		 * @return {Promise<string>}
		 */
		addManager(userId)
		{
			return runAction(RestMethod.imV2ChatAddManagers, {
				data: {
					dialogId: this.dialogId,
					userIds: [userId],
				},
			})
				.then(
					(result) => {
						if (result.error())
						{
							logger.error(`${this.constructor.name}.addManager.error:`, result.error());
						}
						logger.log(`${this.constructor.name}.addManager.result:`, result.data());

						return result.data();
					},
				);
		}

		/**
		 * @desc Rest add manager
		 * @return {Promise<string>}
		 */
		removeManager(userId)
		{
			return runAction(RestMethod.imV2ChatDeleteManagers, {
				data: {
					dialogId: this.dialogId,
					userIds: [userId],
				},
			})
				.then(
					(result) => {
						if (result.error())
						{
							logger.error(`${this.constructor.name}.removeManager.error:`, result.error());
						}
						logger.log(`${this.constructor.name}.removeManager.result:`, result.data());

						return result.data();
					},
				);
		}

		/**
		 * @desc changeCopilotRole
		 * @param {string||null} roleCode
		 */
		changeCopilotRole(roleCode)
		{
			CopilotRest.changeRole({ dialogId: this.dialogId, roleCode })
				.then((result) => logger.log(`${this.constructor.name}.changeCopilotRole.result:`, result))
				.catch((error) => logger.error(`${this.constructor.name}.changeCopilotRole.catch:`, error));
		}

		async deleteChat(dialogId)
		{
			return runAction(RestMethod.imV2ChatDelete, {
				data: {
					dialogId,
				},
			});
		}
	}

	module.exports = {
		SidebarRestService,
	};
});
