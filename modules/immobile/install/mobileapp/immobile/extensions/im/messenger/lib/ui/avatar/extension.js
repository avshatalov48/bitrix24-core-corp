/**
 * @module im/messenger/lib/ui/avatar
 */
jn.define('im/messenger/lib/ui/avatar', (require, exports, module) => {
	const { Avatar } = require('ui-system/blocks/avatar');
	const { ChatAvatarAdapter } = require('im/messenger/lib/ui/avatar/src/chat-avatar-adapter');

	module.exports = {
		Avatar,
		/**
		 * @param {ChatAvatarAdapterProps} props
		 */
		ChatAvatar: (props) => ChatAvatarAdapter(props),
	};
});
