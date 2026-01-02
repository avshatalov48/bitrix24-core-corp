/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants/src/factories/model-factory
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants/src/factories/model-factory', (require, exports, module) => {
	const { ParticipantType } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/const');
	const { resolveParticipantsType } = require(
		'im/messenger/controller/sidebar-v2/tabs/participants/src/type-resolver',
	);

	const { ParticipantCopilotModelData } = require(
		'im/messenger/controller/sidebar-v2/tabs/participants/src/model/copilot',
	);
	const { ParticipantUserModelData } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/model/user');

	const ParticipantModelImplementation = {
		[ParticipantType.user]: ParticipantUserModelData,
		[ParticipantType.copilot]: ParticipantCopilotModelData,
	};

	function modelFactory(props)
	{
		const resolveParticipantType = resolveParticipantsType(props);

		const implementationType = ParticipantModelImplementation[resolveParticipantType]
			? resolveParticipantType
			: ParticipantType.user;

		return new ParticipantModelImplementation[implementationType](props);
	}

	module.exports = {
		modelFactory,
	};
});
