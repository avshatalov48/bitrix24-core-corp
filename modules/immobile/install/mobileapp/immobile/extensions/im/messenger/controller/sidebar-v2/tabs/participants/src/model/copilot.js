/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants/src/model/copilot
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants/src/model/copilot', (require, exports, module) => {

	const { ParticipantBaseModelData } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/model/base');

	/**
	 * @class ParticipantCopilotItem
	 */
	class ParticipantCopilotModelData extends ParticipantBaseModelData
	{
		createData()
		{
			this.data = {
				userId: this.getUserId(),
				copilotRole: this.getCopilotRole(),
			};
		}

		getCopilotRole()
		{
			return this.store.getters['dialoguesModel/copilotModel/getMainRoleByDialogId'](this.getDialogId());
		}
	}

	module.exports = {
		ParticipantCopilotModelData,
	};
});
