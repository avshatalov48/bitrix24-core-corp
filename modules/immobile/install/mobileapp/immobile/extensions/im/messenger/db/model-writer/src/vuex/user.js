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

			if (actionName === 'addShort')
			{
				this.repository.user.saveShortFromModel(userList);

				return;
			}

			this.repository.user.saveFromModel(userList);
		}

		deleteRouter(mutation)
		{}
	}

	module.exports = {
		UserWriter,
	};
});
