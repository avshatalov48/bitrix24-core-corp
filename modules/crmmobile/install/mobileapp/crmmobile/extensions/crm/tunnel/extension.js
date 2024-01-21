(() => {
	const require = (ext) => jn.require(ext);

	const { connect } = require('statemanager/redux/connect');
	const { PureComponent } = require('layout/pure-component');
	const { TunnelContent } = require('crm/tunnel/tunnel-content');
	const { selectById: selectTunnelById } = require('crm/statemanager/redux/slices/tunnels');

	/**
	 * @class Crm.Tunnel
	 */
	class Tunnel extends PureComponent
	{
		render()
		{
			const {
				tunnel,
				entityTypeId,
			} = this.props;

			if (!tunnel)
			{
				return null;
			}

			return TunnelContent({
				tunnel,
				entityTypeId,
			});
		}
	}

	const mapStateToProps = (state, { tunnelId }) => {
		const tunnel = selectTunnelById(state, tunnelId);

		return {
			tunnel,
		};
	};

	this.Crm = this.Crm || {};
	this.Crm.Tunnel = connect(mapStateToProps)(Tunnel);
})();
