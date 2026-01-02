/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants/src/model/user
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants/src/model/user', (require, exports, module) => {
	const { Type } = require('type');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ParticipantBaseModelData } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/model/base');

	/**
	 * @class ParticipantUserModelData
	 */
	class ParticipantUserModelData extends ParticipantBaseModelData
	{
		createData()
		{
			this.data = {
				userId: this.getUserId(),
				isYou: this.isYou(),
				isAdmin: this.isAdmin(),
				isManager: this.isManager(),
			};
		}

		isAdmin()
		{
			const isGroupDialog = DialogHelper.isDialogId(this.getDialogId());
			const ownerId = Type.isNumber(this.getDialogModel()?.owner)
				? this.getDialogModel().owner
				: this.getCurrentUserId();

			return isGroupDialog ? ownerId === this.getUserId() : false;
		}

		isManager()
		{
			const { managerList } = this.getDialogModel();

			if (!Array.isArray(managerList))
			{
				return false;
			}

			return managerList.includes(Number(this.getUserId()));
		}

		isYou()
		{
			return this.getUserId() === this.getCurrentUserId();
		}

		getCurrentUserId()
		{
			return MessengerParams.getUserId();
		}
	}

	module.exports = {
		ParticipantUserModelData,
	};
});
