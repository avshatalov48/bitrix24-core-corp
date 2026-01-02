/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants/src/type-resolver
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants/src/type-resolver', (require, exports, module) => {
	const { ParticipantType } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/const');
	const { UserHelper } = require('im/messenger/lib/helper');

	function resolveParticipantsType(props)
	{
		const { type, userId } = props;

		if (ParticipantType[type])
		{
			return type;
		}

		if (userId)
		{
			const userHelper = UserHelper.createByUserId(userId);

			if (userHelper.isCopilotBot)
			{
				return ParticipantType.copilot;
			}
		}

		return ParticipantType.user;
	}

	module.exports = {
		resolveParticipantsType,
	};
});
