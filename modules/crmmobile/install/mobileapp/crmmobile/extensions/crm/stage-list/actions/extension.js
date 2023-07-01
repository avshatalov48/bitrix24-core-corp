/**
 * @module crm/stage-list/actions
 */
jn.define('crm/stage-list/actions', (require, exports, module) => {
	const StageSelectActions = {
		SelectTunnelDestination: 'selectTunnelDestination',
		CreateTunnel: 'createTunnel',
		ChangeEntityStage: 'changeEntityStage',
	};

	module.exports = { StageSelectActions };
});
