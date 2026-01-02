/**
 * @module crm/tunnel-list
 */
jn.define('crm/tunnel-list', (require, exports, module) => {
	const { TunnelListItem } = require('crm/tunnel-list/item');
	const { CategorySelectActions } = require('crm/category-list/actions');
	const { throttle } = require('utils/function');
	const { clone } = require('utils/object');
	const { connect } = require('statemanager/redux/connect');
	const { getTunnelUniqueId, selectItemsByIds } = require('crm/statemanager/redux/slices/tunnels');
	const { Area } = require('ui-system/layout/area');
	const { Indent, Color } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	/**
	 * @class TunnelList
	 */
	class TunnelList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				tunnels: [...this.tunnels],
			};

			this.selectedKanbanSettings = null;
			this.selectedStage = null;

			this.openCategoryListHandler = throttle(this.openCategoryList, 500, this);
			this.onChangeTunnelDestinationHandler = this.onChangeTunnelDestination.bind(this);
			this.onDeleteTunnelHandler = this.onDeleteTunnel.bind(this);
		}

		get layout()
		{
			return BX.prop.get(this.props, 'layout', null);
		}

		get tunnels()
		{
			return BX.prop.getArray(this.props, 'tunnels', []);
		}

		render()
		{
			return Area(
				{
					title: BX.message('TUNNEL_LIST_TITLE'),
					divider: true,
				},
				this.renderTunnels(),
				this.renderAddTunnel(),
			);
		}

		renderTunnels()
		{
			if (this.state.tunnels.length === 0)
			{
				return null;
			}

			return View(
				{},
				...this.state.tunnels.map((tunnel) => new TunnelListItem({
					tunnel,
					kanbanSettingsId: this.props.kanbanSettingsId,
					categoryId: this.props.categoryId,
					onDeleteTunnel: this.onDeleteTunnelHandler,
					documentFields: this.props.documentFields,
					globalConstants: this.props.globalConstants,
					entityTypeId: this.props.entityTypeId,
					onChangeTunnelDestination: this.onChangeTunnelDestinationHandler,
					layout: this.layout,
				})),
			);
		}

		onCreateTunnel()
		{
			const {
				categoryId: srcCategoryId,
				stageId: srcStageId,
				stageStatusId: srcStageStatusId,
				stageColor: srcStageColor,
				onChangeTunnels,
			} = this.props;

			this.setState({
				tunnels: [
					...this.state.tunnels,
					{
						id: getTunnelUniqueId({
							srcCategoryId,
							srcStageId,
							dstStageId: this.selectedStage.id,
							dstCategoryId: this.selectedKanbanSettings.categoryId,
						}),
						isNewTunnel: true,
						srcCategoryId,
						srcStageId,
						srcStageStatusId,
						srcStageColor,
						dstCategoryId: this.selectedKanbanSettings.categoryId,
						dstCategoryName: this.selectedKanbanSettings.name,
						dstStageId: this.selectedStage.id,
						dstStageName: this.selectedStage.name,
						dstStageStatusId: this.selectedStage.statusId,
						dstStageColor: this.selectedStage.color,
					},
				],
			}, () => {
				if (onChangeTunnels)
				{
					onChangeTunnels(this.state.tunnels);
				}
			});
		}

		onChangeTunnelDestination(tunnel, selectedStage, selectedCategory)
		{
			const { onChangeTunnels } = this.props;
			const { tunnels } = this.state;
			const modifiedTunnels = clone(tunnels);
			const tunnelIndex = modifiedTunnels.findIndex((item) => item.robot.name === tunnel.robot.name);
			modifiedTunnels[tunnelIndex] = {
				...modifiedTunnels[tunnelIndex],
				dstCategoryId: selectedCategory.categoryId,
				dstCategoryName: selectedCategory.name,
				dstStageId: selectedStage.id,
				dstStageName: selectedStage.name,
				dstStageStatusId: selectedStage.statusId,
				dstStageColor: selectedStage.color,
				isNewTunnel: false,
			};
			this.setState({
				tunnels: modifiedTunnels,
			}, () => {
				if (onChangeTunnels)
				{
					onChangeTunnels(this.state.tunnels);
				}
			});
		}

		onDeleteTunnel(tunnel)
		{
			const { onChangeTunnels } = this.props;
			const { tunnels } = this.state;

			const deletedTunnelIndex = tunnels.findIndex((item) => item.id === tunnel.id);
			if (deletedTunnelIndex !== -1)
			{
				const modifiedTunnels = clone(tunnels);
				modifiedTunnels.splice(deletedTunnelIndex, 1);
				this.setState({
					tunnels: modifiedTunnels,
				}, () => {
					if (onChangeTunnels)
					{
						onChangeTunnels(this.state.tunnels);
					}
				});
			}
		}

		renderAddTunnel()
		{
			return View(
				{
					testId: 'tunnel-list-add-button',
					onClick: this.openCategoryListHandler,
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginTop: Indent.M.toNumber(),
					},
				},
				IconView({
					size: 24,
					icon: Icon.PLUS,
					color: Color.base4,
				}),
				View(
					{
						style: {
							flex: 1,
							marginLeft: Indent.M.toNumber(),
						},
					},
					Text4({
						text: BX.message('TUNNEL_LIST_ADD_BUTTON_TEXT'),
						color: Color.base4,
						numberOfLines: 1,
						ellipsize: 'end',
					}),
				),
			);
		}

		async openCategoryList()
		{
			const { CategoryListView } = await requireLazy('crm:category-list-view');

			void CategoryListView.open(
				{
					entityTypeId: this.props.entityTypeId,
					kanbanSettingsId: this.props.kanbanSettingsId,
					selectAction: CategorySelectActions.CreateTunnel,
					readOnly: true,
					enableSelect: true,
					showCounters: false,
					disabledCategoryIds: [this.props.kanbanSettingsId],
					disabledStageIds: [this.props.stageId],
					onViewHidden: (params) => {
						const {
							selectedStage,
							selectedKanbanSettings,
						} = params;
						if (
							this.selectedKanbanSettings
							&& this.selectedKanbanSettings.id === selectedKanbanSettings.id
							&& this.selectedStage
							&& this.selectedStage.id === selectedStage.id
						)
						{
							return;
						}

						this.selectedStage = selectedStage;
						this.selectedKanbanSettings = selectedKanbanSettings;
						if (this.selectedStage && this.selectedKanbanSettings)
						{
							this.onCreateTunnel();
						}
					},
				},
				{},
				this.layout,
			);
		}
	}

	const mapStateToProps = (state, { tunnelIds }) => ({
		tunnels: selectItemsByIds(state, tunnelIds),
	});

	module.exports = {
		TunnelList: connect(mapStateToProps)(TunnelList),
	};
});
