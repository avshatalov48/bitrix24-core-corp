/**
 * @module layout/ui/stage-list/actions
 */
jn.define('layout/ui/stage-list/actions', (require, exports, module) => {
	const StageSelectActions = {
		SelectTunnelDestination: 'selectTunnelDestination',
		CreateTunnel: 'createTunnel',
		ChangeEntityStage: 'changeEntityStage',
	};

	module.exports = { StageSelectActions };
});
