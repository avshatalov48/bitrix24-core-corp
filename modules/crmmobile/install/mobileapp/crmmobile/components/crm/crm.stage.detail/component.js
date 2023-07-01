(() => {
	const require = (ext) => jn.require(ext);

	const SUCCESS_SEMANTICS = 'S';

	const { Alert } = require('alert');
	const { NotifyManager } = require('notify-manager');
	const { throttle } = require('utils/function');
	const { stringify } = require('utils/string');
	const { getStageNavigationIcon } = require('crm/assets/stage');
	const { EntityName } = require('crm/entity-name');
	const { TunnelList } = require('crm/tunnel-list');
	const { StageStorage } = require('crm/storage/stage');

	class CrmStageDetail extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.stage = BX.prop.get(this.props, 'stage', {});
			this.stageName = BX.prop.getString(this.stage, 'name', '');

			this.state = {
				stage: this.props.stage,
				color: this.props.stage.color,
			};

			this.tunnelsEnabled = BX.prop.getBoolean(this.props, 'tunnelsEnabled', false);
			this.tunnels = this.props.stage.tunnels;

			this.isChanged = false;
			this.isTunnelsChanged = false;

			this.deleteStageHandler = throttle(this.deleteStage, 500, this);
		}

		componentDidMount()
		{
			layout.enableNavigationBarBorder(false);
			this.updateNavigationTitleAndIcon();
			layout.setRightButtons([
				{
					name: BX.message('M_CRM_STAGE_DETAIL_SAVE'),
					type: 'text',
					color: '#2066b0',
					callback: () => this.saveAndClose(),
				},
			]);
			BX.addCustomEvent('Crm.TunnelList::onUpdateTunnels', (tunnels) => {
				this.tunnels = tunnels;
				this.isChanged = true;
				this.isTunnelsChanged = true;
			});
		}

		saveAndClose()
		{
			if (this.isChanged)
			{
				this.updateStage().then(() => {
					layout.close();
				});
			}
			else
			{
				layout.close();
			}
		}

		prepareTunnelStageColors()
		{
			this.tunnels = this.tunnels.map((tunnel) => {
				tunnel.srcStageColor = this.state.color || tunnel.srcStageColor;

				return tunnel;
			});
		}

		render()
		{
			this.prepareTunnelStageColors();

			return View(
				{
					style: {
						flex: 1,
					},
				},
				ScrollView(
					{
						resizableByKeyboard: true,
						safeArea: {
							bottom: true,
							top: true,
							left: true,
							right: true,
						},
						style: styles.container,
					},
					this.renderContent(),
				),
			);
		}

		renderContent()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
					onClick: () => Keyboard.dismiss(),
					onPan: () => Keyboard.dismiss(),
				},
				this.renderStageName(),
				this.renderTunnelList(),
				this.renderColorPicker(),
				this.renderDeleteButton(),
			);
		}

		renderStageName()
		{
			return View(
				{
					style: styles.nameFieldContainer,
				},
				new EntityName({
					title: BX.message('M_CRM_STAGE_DETAIL_NAME'),
					name: this.stageName,
					placeholder: BX.message('M_CRM_STAGE_DETAIL_DEFAULT_STAGE_NAME'),
					required: true,
					showRequired: false,
					config: {
						deepMergeStyles: styles.nameField,
						selectionOnFocus: this.stageName === BX.message('M_CRM_STAGE_DETAIL_DEFAULT_STAGE_DEFAULT_NAME'),
						enableKeyboardHide: true,
					},
					onChange: (value) => {
						this.isChanged = true;
						this.stageName = value;
						this.updateNavigationTitle();
					},
				}),
			);
		}

		renderTunnelList()
		{
			if (!this.tunnelsEnabled)
			{
				return null;
			}

			const { entityTypeId } = this.props;

			return View(
				{
					style: {
						marginBottom: 18,
					},
				},
				new TunnelList({
					tunnels: this.tunnels,
					categoryId: this.props.categoryId,
					documentFields: this.props.documentFields,
					entityTypeId,
					stageId: this.state.stage.id,
					stageStatusId: this.state.stage.statusId,
					stageColor: this.state.color,
				}),
			);
		}

		renderColorPicker()
		{
			return View(
				{
					style: {
						marginBottom: 18,
					},
				},
				new UI.ColorPicker({
					currentColor: this.state.stage.color,
					onChangeColor: (color) => this.onChangeColor(color),
				}),
			);
		}

		onChangeColor(color)
		{
			if (this.state.color !== color)
			{
				this.isChanged = true;
				this.setState({ color }, () => {
					this.updateNavigationTitleAndIcon();
				});
			}
		}

		getTitleForNavigation()
		{
			return (
				stringify(this.stageName) === ''
					? BX.message('M_CRM_STAGE_DETAIL_FUNNEL_EMPTY')
					: BX.message('M_CRM_STAGE_DETAIL_FUNNEL').replace('#STAGE_NAME#', this.stageName)
			);
		}

		updateNavigationTitle()
		{
			const text = this.getTitleForNavigation();

			layout.setTitle({ text }, true);
		}

		updateNavigationTitleAndIcon()
		{
			layout.setTitle({
				text: this.getTitleForNavigation(),
				svg: {
					content: getStageNavigationIcon(this.state.color),
				},
			});
		}

		renderDeleteButton()
		{
			const { stage } = this.state;

			if (stage.semantics === SUCCESS_SEMANTICS)
			{
				return null;
			}

			return new BaseButton({
				style: {
					...styles.deleteButton,
				},
				icon: svgImages.deleteIcon,
				text: BX.message('M_CRM_STAGE_DETAIL_DELETE'),
				onClick: () => this.openAlertOnDelete(stage.id),
			});
		}

		openAlertOnDelete(stageId)
		{
			Alert.confirm(
				'',
				BX.message('M_CRM_STAGE_DETAIL_DELETE_TEXT'),
				[
					{
						type: 'cancel',
					},
					{
						text: BX.message('M_CRM_STAGE_DETAIL_DELETE_OK'),
						type: 'destructive',
						onPress: () => {
							this.deleteStageHandler(stageId).then((() => {
								layout.close();
							}));
						},
					},
				],
			);
		}

		updateStage()
		{
			const {
				entityTypeId,
				categoryId,
			} = this.props;

			const {
				stage: {
					statusId: stageStatusId,
				},
			} = this.state;

			return new Promise((resolve, reject) => {
				NotifyManager.showLoadingIndicator();

				StageStorage
					.updateStage(
						entityTypeId,
						categoryId,
						{
							statusId: stageStatusId,
							name: this.stageName,
							color: this.state.color,
							tunnels: this.prepareTunnelsBeforeSave(),
						},
					)
					.then(() => {
						NotifyManager.hideLoadingIndicatorWithoutFallback();

						const stage = {
							...this.state.stage,
							name: this.stageName,
							color: this.state.color,
							tunnels: this.tunnels,
						};

						BX.postComponentEvent('Crm.StageDetail::onUpdateStage', [stage]);

						if (this.isTunnelsChanged)
						{
							BX.postComponentEvent('Crm.StageDetail::onUpdateTunnels', [this.tunnels, categoryId]);
						}

						resolve();
					})
					.catch((response) => {
						NotifyManager.hideLoadingIndicatorWithoutFallback();
						ErrorNotifier.showErrors(response.errors);
						reject();
					})
				;
			});
		}

		deleteStage()
		{
			const { entityTypeId, categoryId } = this.props;
			const { statusId } = this.state.stage;

			return new Promise((resolve, reject) => {
				NotifyManager.showLoadingIndicator();

				StageStorage
					.deleteStage(
						entityTypeId,
						categoryId,
						statusId,
					)
					.then(() => {
						NotifyManager.hideLoadingIndicatorWithoutFallback();
						BX.postComponentEvent('Crm.StageDetail::onDeleteStage', [this.state.stage]);
						resolve();
					})
					.catch((response) => {
						NotifyManager.hideLoadingIndicatorWithoutFallback();
						void ErrorNotifier.showErrors(response.errors);
						reject();
					})
				;
			});
		}

		prepareTunnelsBeforeSave()
		{
			if (!Array.isArray(this.tunnels))
			{
				return [];
			}

			return this.tunnels.map((tunnel) => {
				if (tunnel.isNewTunnel)
				{
					return {
						srcCategory: tunnel.srcCategoryId,
						srcStage: tunnel.srcStageStatusId,
						dstCategory: tunnel.dstCategoryId,
						dstStage: tunnel.dstStageStatusId,
					};
				}

				return {
					srcCategory: tunnel.srcCategoryId,
					srcStage: tunnel.srcStageStatusId,
					dstCategory: tunnel.dstCategoryId,
					dstStage: tunnel.dstStageStatusId,
					robot: {
						Name: tunnel.robot.name,
					},
				};
			});
		}
	}

	const styles = {
		container: {
			backgroundColor: '#eef2f4',
			flexDirection: 'column',
			flex: 1,
		},
		nameFieldContainer: {
			marginBottom: 18,
		},
		nameField: {
			wrapper: {
				paddingTop: 0,
				paddingBottom: 0,
			},
			title: {
				marginBottom: 0,
			},
		},
		deleteButton: {
			button: {
				padding: 20,
				borderRadius: 12,
				backgroundColor: '#fff',
				borderColor: '#fff',
				flexDirection: 'row',
				alignItems: 'center',
				height: 'auto',
				justifyContent: 'flex-start',
				marginBottom: 8,
			},
			icon: {
				width: 15,
				height: 20,
				marginRight: 16,

			},
			text: {
				color: '#333',
				fontSize: 18,
				fontWeight: 'normal',
			},
		},
	};

	const svgImages = {
		deleteIcon: '<svg width="16" height="21" viewBox="0 0 16 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.22602 0H6.15062V1.54677H1.43631C0.64306 1.54677 0 2.18983 0 2.98309V4.64037H15.377V2.98309C15.377 2.18983 14.7339 1.54677 13.9407 1.54677H9.22602V0Z" fill="#828B95"/><path d="M1.53777 6.18721H13.8394L12.6864 19.2351C12.6427 19.7294 12.2287 20.1084 11.7326 20.1084H3.64459C3.14842 20.1084 2.73444 19.7294 2.69077 19.2351L1.53777 6.18721Z" fill="#828B95"/></svg>',
	};

	BX.onViewLoaded(() => {
		layout.showComponent(new CrmStageDetail({
			entityTypeId: BX.componentParameters.get('entityTypeId'),
			stage: BX.componentParameters.get('stage'),
			categoryId: BX.componentParameters.get('categoryId'),
			tunnelsEnabled: BX.componentParameters.get('tunnelsEnabled'),
			documentFields: BX.componentParameters.get('documentFields'),
		}));
	});
})();
