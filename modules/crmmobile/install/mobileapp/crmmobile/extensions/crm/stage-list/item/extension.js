/**
 * @module crm/stage-list/item
 */
jn.define('crm/stage-list/item', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { StageListItem, MIN_STAGE_HEIGHT } = require('layout/ui/stage-list/item');
	const { connect } = require('statemanager/redux/connect');
	const {
		selectById: selectStageById,
	} = require('crm/statemanager/redux/slices/stage-settings');
	const {
		selectById: selectStageCounterById,
	} = require('crm/statemanager/redux/slices/stage-counters');
	const { selectItemsByIds } = require('crm/statemanager/redux/slices/tunnels');

	const FIRST_TUNNEL_ADDITIONAL_HEIGHT = 5;
	const TUNNEL_HEIGHT = 22;
	const TUNNEL_MARGIN_TOP = 9;

	/**
	 * @class CrmStageListItem
	 */
	class CrmStageListItem extends StageListItem
	{
		get isNewLead()
		{
			return BX.prop.getBoolean(this.props, 'isNewLead', false);
		}

		get disabledStageIds()
		{
			return BX.prop.getArray(this.props, 'disabledStageIds', []);
		}

		isUnsuitable()
		{
			if (super.isUnsuitable())
			{
				return true;
			}

			return this.isNewLead && this.stage.semantics === 'S';
		}

		renderAdditionalContent(stage)
		{
			if (!this.showTunnels)
			{
				return null;
			}

			return this.renderTunnels(stage);
		}

		hasTunnels(stage)
		{
			return this.showTunnels && stage.tunnels !== 0;
		}

		isStageEnabled()
		{
			if (this.props.tunnels && this.props.tunnels.length > 0 && this.disabledStageIds.length > 0)
			{
				const intersection = this.props.tunnels.filter((tunnel) => this.disabledStageIds.includes(tunnel.dstStageId));

				return intersection.length === 0;
			}

			return BX.prop.getBoolean(this.props, 'enabled', true);
		}

		renderTunnels(stage)
		{
			if (stage.tunnels === 0)
			{
				return null;
			}

			const tunnels = stage.tunnels.map((tunnelId, index) => this.renderTunnel(tunnelId, index));

			return View(
				{
					style: {
						marginLeft: 6,
						marginTop: -8,
					},
				},
				...tunnels,
			);
		}

		renderTunnel(tunnelId, index)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						height: index === 0 ? TUNNEL_HEIGHT + FIRST_TUNNEL_ADDITIONAL_HEIGHT : TUNNEL_HEIGHT + TUNNEL_MARGIN_TOP,
						marginTop: index === 0 ? 0 : -(TUNNEL_MARGIN_TOP),
					},
				},
				View(
					{
						style: {
							paddingBottom: 6,
							paddingLeft: index === 0 ? 0 : 4,
						},
					},
					Image(
						{
							style: {
								width: index === 0 ? 12 : 8,
								height: index === 0 ? 21 : 23,
							},
							tintColor: AppTheme.colors.base3,
							svg: {
								content: index === 0 ? svgImages.tunnelFirstVector : svgImages.tunnelVector,
							},
						},
					),
				),
				View(
					{
						style: {
							marginLeft: 6,
							flex: 1,
							alignSelf: 'flex-end',
						},
					},
					Crm.Tunnel({
						tunnelId,
						entityTypeId: this.props.entityTypeId,
					}),
				),
			);
		}
	}

	const svgImages = {
		tunnelFirstVector: `<svg width="12" height="21" viewBox="0 0 12 21" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="12" height="21"/><circle cx="5" cy="5" r="3.75" fill="#c9ccd0" stroke="white" stroke-width="1.5"/><path d="M5 5V11.5625V17C5 18.6569 6.34315 20 8 20H11" stroke="${AppTheme.colors.base5}" stroke-width="1.5" stroke-linecap="round"/></svg>`,
		tunnelVector: `<svg width="8" height="23" viewBox="0 0 8 23" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="8" height="23" fill="none"/><path d="M1 -7V20C1 21.1046 1.89543 22 3 22H7" stroke="${AppTheme.colors.base5}" stroke-width="1.5" stroke-linecap="round"/></svg>`,
	};

	const mapStateToProps = (state, ownProps) => {
		const stage = ownProps.stage ?? selectStageById(state, ownProps.id);
		const tunnelIds = stage.tunnels ?? [];

		return {
			stage,
			counter: selectStageCounterById(state, ownProps.id),
			tunnels: selectItemsByIds(state, tunnelIds),
		};
	};

	module.exports = {
		CrmStageListItem: connect(mapStateToProps)(CrmStageListItem),
		FIRST_TUNNEL_ADDITIONAL_HEIGHT,
		TUNNEL_HEIGHT,
		TUNNEL_MARGIN_TOP,
		MIN_STAGE_HEIGHT,
	};
});
