/**
 * @module im/messenger/controller/sidebar/channel/profile-info
 */
jn.define('im/messenger/controller/sidebar/channel/profile-info', (require, exports, module) => {
	const { ChatTitle } = require('im/messenger/lib/element');

	const { SidebarProfileInfo } = require('im/messenger/controller/sidebar/chat/sidebar-profile-info');
	const { ChannelProfileUserCounter } = require('im/messenger/controller/sidebar/channel/profile-user-counter-view');

	/**
	 * @class ChannelProfileInfo
	 * @typedef {LayoutComponent<SidebarProfileInfoProps, SidebarProfileInfoState>} ChannelProfileInfo
	 */
	class ChannelProfileInfo extends SidebarProfileInfo
	{
		/**
		 * @constructor
		 * @param {SidebarProfileInfoProps} props
		 */
		constructor(props)
		{
			super(props);

			this.state = {
				...this.state,
				dialogType: props?.dialogType ?? null,
			};
		}

		renderStatusImage()
		{
			return null;
		}

		renderShevronImage()
		{
			return null;
		}

		renderDepartment()
		{
			return null;
		}

		renderCountersOrLastActivity()
		{
			return new ChannelProfileUserCounter({ dialogId: this.props.dialogId });
		}

		/**
		 * @param {MutationPayload<DialoguesUpdateData, DialoguesUpdateActions>} payload
		 */
		onUpdateDialogState({ payload })
		{
			if (payload.data?.dialogId !== this.props.dialogId)
			{
				return;
			}

			const newState = Object.create(null);
			if (payload.data.fields.name && payload.data.fields.name !== this.state.title)
			{
				newState.title = payload.data?.fields.name;
			}

			if (payload.data.fields.avatar && payload.data.fields.avatar !== this.state.imageUrl)
			{
				newState.imageUrl = payload.data?.fields.avatar;
			}

			if (payload.data.fields.type && payload.data.fields.type !== this.state.dialogType)
			{
				newState.dialogType = payload.data?.fields.type;
				newState.desc = ChatTitle.getChatDescriptionByDialogType(newState.dialogType);
			}

			if (Object.keys(newState).length > 0)
			{
				this.setState(newState);
			}
		}
	}

	module.exports = { ChannelProfileInfo };
});
