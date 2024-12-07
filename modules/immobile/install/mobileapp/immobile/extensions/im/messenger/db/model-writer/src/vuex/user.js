/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/user
 */
jn.define('im/messenger/db/model-writer/vuex/user', (require, exports, module) => {
	const { Type } = require('type');

	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class UserWriter extends Writer
	{
		subscribeEvents()
		{
			this.storeManager
				.on('usersModel/set', this.addRouter)
				.on('usersModel/delete', this.deleteRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('usersModel/set', this.addRouter)
				.off('usersModel/delete', this.deleteRouter)
			;
		}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		addRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const saveActions = [
				'set',
				'update',
				'merge',
				'addShort',
			];
			if (!saveActions.includes(actionName))
			{
				return;
			}

			if (!Type.isArrayFilled(data.userList))
			{
				return;
			}

			const userList = [];
			data.userList.forEach((user) => {
				const modelUser = this.store.getters['usersModel/getById'](user.id);
				if (modelUser)
				{
					userList.push(modelUser);
				}
			});

			if (!Type.isArrayFilled(userList))
			{
				return;
			}

			const uniqueUserList = this.getUniqueUsers(userList);
			if (actionName === 'addShort')
			{
				this.repository.user.saveShortFromModel(uniqueUserList);

				return;
			}

			this.repository.user.saveFromModel(uniqueUserList);
		}

		deleteRouter(mutation)
		{}

		/**
		 * @param {Array<UsersModelState>} users
		 * @return {Array<UsersModelState>}
		 */
		getUniqueUsers(users)
		{
			return [...new Map(users.map((user) => [user.id, user])).values()];
		}
	}

	module.exports = {
		UserWriter,
	};
});
