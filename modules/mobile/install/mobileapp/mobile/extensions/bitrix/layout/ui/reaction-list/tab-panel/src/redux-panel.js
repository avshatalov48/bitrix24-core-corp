/**
 * @module layout/ui/reaction-list/tab-panel/src/redux-panel
 */
jn.define('layout/ui/reaction-list/tab-panel/src/redux-panel', (require, exports, module) => {
	const { ReactionTabPanel } = require('layout/ui/reaction-list/tab-panel/src/tab-panel');
	const { selectReactionsByEntity } = require('statemanager/redux/slices/reactions/selector');
	const { connect } = require('statemanager/redux/connect');

	const mapStateToProps = (state, ownProps) => {
		const reactions = selectReactionsByEntity(state, ownProps.entityId, ownProps.entityType);

		return {
			reactions,
		};
	};

	module.exports = {
		ReduxReactionPanel: connect(mapStateToProps)(ReactionTabPanel),
	};
});
