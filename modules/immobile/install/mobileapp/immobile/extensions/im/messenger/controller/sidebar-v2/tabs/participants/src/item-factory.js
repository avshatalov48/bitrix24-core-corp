/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants/src/item-factory
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants/src/item-factory', (require, exports, module) => {
	const { ParticipantUserItem } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/items/user');
	const { ParticipantButtonItem } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/items/button');
	const { ParticipantCopilotItem } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/items/copilot');

	const { UserHelper } = require('im/messenger/lib/helper');

	const ParticipantItemType = {
		notes: 'notes',
		copilot: 'copilot',
		button: 'button',
		user: 'user',
	};

	const ParticipantItemImplementation = {
		[ParticipantItemType.user]: ParticipantUserItem,
		[ParticipantItemType.copilot]: ParticipantCopilotItem,
		[ParticipantItemType.button]: ParticipantButtonItem,
	};

	class ItemFactory
	{
		constructor(props)
		{
			this.props = props;
		}

		static make(props)
		{
			const factory = new ItemFactory(props);
			const type = factory.resolveType();

			return new ParticipantItemImplementation[type](props);
		}

		resolveType()
		{
			const { type, userId } = this.props;

			if (ParticipantItemType[type])
			{
				return type;
			}

			if (userId)
			{
				const userHelper = UserHelper.createByUserId(userId);

				if (userHelper.isCopilotBot)
				{
					return ParticipantItemType.copilot;
				}
			}

			return ParticipantItemType.user;
		}
	}

	module.exports = {
		ItemFactory,
		ParticipantItemType,
	};
});
