/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/renderer
 */
jn.define('im/messenger/lib/renderer', (require, exports, module) => {

	const { RecentRenderer } = jn.require('im/messenger/lib/renderer/recent');

	module.exports = {
		RecentRenderer,
	};
});
