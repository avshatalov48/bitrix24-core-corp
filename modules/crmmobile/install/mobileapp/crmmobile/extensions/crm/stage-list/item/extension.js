/**
 * @module crm/stage-list/item
 */
jn.define('crm/stage-list/item', (require, exports, module) => {
	const { StageListItem, MIN_STAGE_HEIGHT } = require('layout/ui/stage-list/item');
	const { connect } = require('statemanager/redux/connect');
	const {
		selectById: selectStageById,
	} = require('crm/statemanager/redux/slices/stage-settings');
	const {
		selectById: selectStageCounterById,
	} = require('crm/statemanager/redux/slices/stage-counters');
	const { selectItemsByIds } = require('crm/statemanager/redux/slices/tunnels');
	const { Tunnel } = require('crm/tunnel');
	const { Color } = require('tokens');

	const FIRST_TUNNEL_ADDITIONAL_HEIGHT = 7;
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
			if (Array.isArray(stage.tunnels) && stage.tunnels.length === 0)
			{
				return null;
			}

			const tunnels = stage.tunnels.map((tunnelId, index) => this.renderTunnel(tunnelId, index));

			return View(
				{
					style: {
						marginLeft: 16,
						marginTop: -5,
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
							paddingLeft: index === 0 ? 0 : 3.5,
						},
					},
					Image(
						{
							style: {
								width: index === 0 ? 12 : 8,
								height: index === 0 ? 21 : 23,
							},
							svg: {
								content: index === 0 ? firstVector : secondVector,
							},
						},
					),
				),
				View(
					{
						style: {
							marginLeft: 5,
							flex: 1,
							alignSelf: 'flex-end',
						},
					},
					Tunnel({
						tunnelId,
						entityTypeId: this.props.entityTypeId,
					}),
				),
			);
		}
	}

	const firstVector = `<svg width="11" height="20" viewBox="0 0 11 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="4" cy="4" r="3" fill="${Color.base5.toHex()}" stroke="${Color.bgContentPrimary.toHex()}" stroke-width="2"/><path d="M4 5V17C4 18.1046 4.89543 19 6 19H10" stroke="${Color.base5.toHex()}" stroke-width="1.5" stroke-linecap="round"/></svg>`;
	const secondVector = `<svg width="8" height="23" viewBox="0 0 8 23" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.75 0V20C1.75 20.6904 2.30963 21.25 3 21.25H7C7.41418 21.25 7.75 21.5858 7.75 22C7.75 22.4142 7.41418 22.75 7 22.75H3C1.4812 22.75 0.25 21.5188 0.25 20V0H1.75Z" fill="${Color.base5.toHex()}"/></svg>`;

	const mapStateToProps = (state, ownProps) => {
		const stage = ownProps.stage ?? selectStageById(state, ownProps.id);
		const tunnelIds = stage?.tunnels ?? [];

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
