/**
 * @module crm/tunnel-list
 */
jn.define('crm/tunnel-list', (require, exports, module) => {
	const { TunnelListItem } = require('crm/tunnel-list/item');
	const { CategorySelectActions } = require('crm/category-list/actions');
	const { throttle } = require('utils/function');
	const { clone } = require('utils/object');
	const { connect } = require('statemanager/redux/connect');
	const AppTheme = require('apptheme');
	const { getTunnelUniqueId, selectItemsByIds } = require('crm/statemanager/redux/slices/tunnels');

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
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderRadius: 12,
					},
				},
				View(
					{
						style: {
							paddingTop: 10,
							paddingLeft: 20,
							paddingBottom: this.state.tunnels.length > 0 ? 9 : 0,
						},
					},
					Text({
						style: {
							color: AppTheme.colors.base2,
							fontSize: 15,
							fontWeight: '500',
						},
						text: BX.message('TUNNEL_LIST_TITLE'),
					}),
				),
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
							dstCategoryId: this.selectedKanbanSettings.kanbanSettingsId,
						}),
						isNewTunnel: true,
						srcCategoryId,
						srcStageId,
						srcStageStatusId,
						srcStageColor,
						dstCategoryId: this.selectedKanbanSettings.kanbanSettingsId,
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
					style: {
						paddingTop: 4,
						paddingBottom: 6,
					},
				},
				new BaseButton({
					icon: svgImages.addTunnelIcon,
					text: BX.message('TUNNEL_LIST_ADD_BUTTON_TEXT'),
					style: {
						button: {
							borderColor: AppTheme.colors.bgSeparatorPrimary,
							justifyContent: 'flex-start',
						},
						icon: {
							tintColor: AppTheme.colors.base3,
							marginRight: 16,
							marginLeft: 25,
							width: 12,
							height: 12,
						},
						text: {
							color: AppTheme.colors.base1,
							fontWeight: 'normal',
							fontSize: 18,
						},
					},
					onClick: this.openCategoryListHandler,
				}),
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

	const svgImages = {
		addTunnelIcon: '<svg width="12" height="13" viewBox="0 0 12 13" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7 0.5H5V5.5H0V7.5H5V12.5H7V7.5H12V5.5H7V0.5Z" fill="#828B95"/></svg>',
	};

	module.exports = {
		TunnelList: connect(mapStateToProps)(TunnelList),
	};
});
