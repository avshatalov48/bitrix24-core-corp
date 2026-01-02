/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants/src/menu-factory
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants/src/menu-factory', (require, exports, module) => {
	const { SidebarType, resolveSidebarType } = require('im/messenger/controller/sidebar-v2/factory');
	const { ParticipantsGroupMenu } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/menu/group');
	const { ParticipantsDirectMenu } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/menu/direct');

	const ParticipantMenuImplementation = {
		[SidebarType.groupChat]: ParticipantsGroupMenu,
		[SidebarType.directChat]: ParticipantsDirectMenu,
	};

	function menuFactory(props)
	{
		const sidebarType = resolveSidebarType(props.dialogId);

		const implementationType = ParticipantMenuImplementation[sidebarType]
			? sidebarType
			: SidebarType.groupChat;

		return new ParticipantMenuImplementation[implementationType]({ ...props, sidebarType });
	}

	module.exports = {
		menuFactory,
	};
});
