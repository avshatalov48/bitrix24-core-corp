/**
 * @module layout/ui/user/avatar/src/redux-avatar
 */
jn.define('layout/ui/user/avatar/src/redux-avatar', (require, exports, module) => {
	const { Avatar } = require('ui-system/blocks/avatar');

	module.exports = {
		/**
		 * @deprecated
		 * @see ui-system/blocks/avatar
		 */
		ReduxAvatar: (props) => Avatar({
			...props,
			withRedux: true,
		}),
	};
});
