/**
 * @module layout/ui/reaction-list/tab-panel
 */
jn.define('layout/ui/reaction-list/tab-panel', (require, exports, module) => {
	const { ReduxReactionPanel } = require('layout/ui/reaction-list/tab-panel/src/redux-panel');
	const { ReactionTabPanel } = require('layout/ui/reaction-list/tab-panel/src/tab-panel');

	module.exports = {
		ReactionPanel: (props) => new ReactionTabPanel(props),
		ReduxReactionPanel,
	};
});
