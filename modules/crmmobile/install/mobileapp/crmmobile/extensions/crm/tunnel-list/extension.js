/**
 * @module crm/tunnel-list
 */
jn.define('crm/tunnel-list', (require, exports, module) => {
	const { TunnelListItem } = require('crm/tunnel-list/item');
	const { CategorySelectActions } = require('crm/category-list/actions');
	const { Robot } = require('crm/tunnel-list/item/robot');
	const { CategoryListView } = require('crm/category-list-view');
	const { throttle } = require('utils/function');
	const { clone } = require('utils/object');

	class TunnelList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				tunnels: this.prepareTunnels(this.props.tunnels),
			};

			this.selectedCategory = null;
			this.selectedStage = null;

			this.openCategoryListHandler = throttle(this.openCategoryList, 500, this);
			this.onChangeTunnelDestinationHandler = this.onChangeTunnelDestination.bind(this);
			this.onDeleteTunnelHandler = this.onDeleteTunnel.bind(this);
		}

		componentWillReceiveProps(nextProps)
		{
			this.state.tunnels = this.prepareTunnels(nextProps.tunnels);
		}

		componentDidMount()
		{
			BX.addCustomEvent('Crm.TunnelList::selectCategoryOnCreateTunnel', (category) => {
				this.selectedCategory = category;
			});
			BX.addCustomEvent('Crm.TunnelList::selectStageOnCreateTunnel', (stage) => {
				this.selectedStage = stage;
			});
			BX.addCustomEvent('Crm.TunnelList::onCreateTunnel', () => {
				if (this.selectedCategory && this.selectedStage)
				{
					this.onCreateTunnel();
				}
			});
			BX.addCustomEvent('Crm.StageDetail::onCreateTunnel', (tunnel) => {
				this.setState({
					tunnels: [
						...this.state.tunnels,
						tunnel,
					],
				});
			});
		}

		prepareTunnels(tunnels)
		{
			return tunnels.map((tunnel, index) => {
				return {
					...tunnel,
					id: index,
				};
			});
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: '#ffffff',
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
							color: '#525c69',
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
					categoryId: this.props.categoryId,
					onDeleteTunnel: this.onDeleteTunnelHandler,
					documentFields: this.props.documentFields,
					globalConstants: this.props.globalConstants,
					entityTypeId: this.props.entityTypeId,
					onChangeTunnelDestination: this.onChangeTunnelDestinationHandler,
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
			} = this.props;
			const { tunnels } = this.state;

			this.setState({
				tunnels: [
					...tunnels,
					{
						isNewTunnel: true,
						srcCategoryId,
						srcStageId,
						srcStageStatusId,
						srcStageColor,
						dstCategoryId: this.selectedCategory.id,
						dstCategoryName: this.selectedCategory.name,
						dstStageId: this.selectedStage.id,
						dstStageName: this.selectedStage.name,
						dstStageStatusId: this.selectedStage.statusId,
						dstStageColor: this.selectedStage.color,
						robot: new Robot(),
					},
				],
			}, () => {
				BX.postComponentEvent('Crm.TunnelList::onUpdateTunnels', [this.state.tunnels]);
			});
		}

		onChangeTunnelDestination(tunnel, selectedStage, selectedCategory)
		{
			const { tunnels } = this.state;
			const modifiedTunnels = clone(tunnels);
			const tunnelIndex = modifiedTunnels.findIndex((item) => item.robot.name === tunnel.robot.name);
			modifiedTunnels[tunnelIndex] = {
				...modifiedTunnels[tunnelIndex],
				dstCategoryId: selectedCategory.id,
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
				BX.postComponentEvent('Crm.TunnelList::onUpdateTunnels', [this.state.tunnels]);
			});
		}

		onDeleteTunnel(tunnel)
		{
			const { tunnels } = this.state;

			return new Promise((resolve) => {
				const deletedTunnelIndex = tunnels.findIndex((item) => item.robot.name === tunnel.robot.name);
				if (deletedTunnelIndex !== -1)
				{
					const modifiedTunnels = clone(tunnels);
					modifiedTunnels.splice(deletedTunnelIndex, 1);
					this.setState({
						tunnels: modifiedTunnels,
					});
					BX.postComponentEvent('Crm.TunnelList::onUpdateTunnels', [this.state.tunnels]);
				}
				resolve();
			});
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
							borderColor: '#ffffff',
							justifyContent: 'flex-start',
						},
						icon: {
							marginRight: 22,
							marginLeft: 25,
							width: 12,
							height: 12,
						},
						text: {
							color: '#333333',
							fontWeight: 'normal',
							fontSize: 18,
						},
					},
					onClick: this.openCategoryListHandler,
				}),
			);
		}

		openCategoryList()
		{
			void CategoryListView.open({
				entityTypeId: this.props.entityTypeId,
				categoryId: this.props.categoryId,
				selectAction: CategorySelectActions.CreateTunnel,
				readOnly: true,
				enableSelect: true,
				showCounters: false,
				disabledCategoryIds: [
					this.props.categoryId,
				],
				disabledStageIds: [
					this.props.stageId,
				],
			});
		}
	}

	const svgImages = {
		addTunnelIcon: '<svg width="12" height="13" viewBox="0 0 12 13" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7 0.5H5V5.5H0V7.5H5V12.5H7V7.5H12V5.5H7V0.5Z" fill="#828B95"/></svg>',
	};

	module.exports = { TunnelList };
});
