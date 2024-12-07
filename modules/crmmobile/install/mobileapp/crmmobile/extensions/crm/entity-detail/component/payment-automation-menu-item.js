/**
 * @bxjs_lang_path extension.php
 * @module crm/entity-detail/component/payment-automation-menu-item
 */
jn.define('crm/entity-detail/component/payment-automation-menu-item', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { CrmStageListItem } = require('crm/stage-list/item');
	const { stayStageItem } = require('layout/ui/stage-list');
	const { CrmStageListView } = require('crm/stage-list-view');
	const { Loc } = require('loc');
	const { getEntityMessage } = require('crm/loc');
	const { WidgetHeaderButton } = require('layout/ui/widget-header-button');
	const { NotifyManager } = require('notify-manager');
	const { PlanRestriction } = require('layout/ui/plan-restriction');
	const { connect } = require('statemanager/redux/connect');
	const {
		getCrmKanbanUniqId,
		selectStagesIdsBySemantics,
	} = require('crm/statemanager/redux/slices/kanban-settings');
	const {
		selectEntities,
	} = require('crm/statemanager/redux/slices/stage-settings');
	const { Icon } = require('ui-system/blocks/icon');

	/**
	 * @class AutomationStageComponent
	 */
	class AutomationStageComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.layout = props.layout;

			this.state = {
				loading: false,
				stageOnOrderPaid: '',
				stageOnDeliveryFinished: '',
				changedPaidStage: false,
				changedDeliveryStage: false,
			};

			this.stages = this.getStages();
			this.setCurrentStages();

			this.saveButton = new WidgetHeaderButton({
				widget: this.layout,
				text: Loc.getMessage('M_CRM_ACTION_PAYMENT_AUTOMATION_STAGE_SAVE'),
				loadingText: Loc.getMessage('M_CRM_ACTION_PAYMENT_AUTOMATION_STAGE_SAVING'),
				onClick: () => this.saveStages(),
			});
		}

		setCurrentStages()
		{
			this.setState(
				{
					loading: true,
				},
				() => {
					BX.ajax.runAction('salescenter.AutomationStage.getStages', {
						data: {
							entityId: this.props.entityId,
							entityTypeId: this.props.entityTypeId,
						},
					}).then((response) => {
						this.setState({
							stageOnOrderPaid: response.data.stageOnOrderPaid,
							stageOnDeliveryFinished: response.data.stageOnDeliveryFinished,
							loading: false,
						});
					}).catch(() => {
						this.setState({
							stageOnOrderPaid: '',
							stageOnDeliveryFinished: '',
							loading: false,
						});
					});
				},
			);
		}

		onSelectedStage(id, data)
		{
			const stage = this.getStageById(id);
			if (data.type === 'stageOnOrderPaid' && stage.statusId !== this.state.stageOnOrderPaid)
			{
				this.setState({
					stageOnOrderPaid: stage.statusId,
					changedPaidStage: true,
				});
			}

			if (data.type === 'stageOnDeliveryFinished' && stage.statusId !== this.state.stageOnDeliveryFinished)
			{
				this.setState({
					stageOnDeliveryFinished: stage.statusId,
					changedDeliveryStage: true,
				});
			}
		}

		openStagesWindow(data)
		{
			let activeStageId = 0;
			if (data.type === 'stageOnOrderPaid')
			{
				const stage = this.getStageByStatusId(this.state.stageOnOrderPaid);
				activeStageId = stage.id;
			}

			if (data.type === 'stageOnDeliveryFinished')
			{
				const stage = this.getStageByStatusId(this.state.stageOnDeliveryFinished);
				activeStageId = stage.id;
			}

			void CrmStageListView.open(
				{
					kanbanSettingsId: getCrmKanbanUniqId(this.props.entityTypeId, this.props.categoryId),
					entityTypeId: this.props.entityTypeId,
					categoryId: this.props.categoryId,
					activeStageId,
					readOnly: true,
					canMoveStages: false,
					enableStageSelect: true,
					onStageSelect: (id) => this.onSelectedStage(id, data),
					stageParams: {
						showTunnels: false,
						showTotal: false,
						showCounters: false,
						showCount: false,
						showAllStagesItem: false,
						showStayStageItem: true,
					},
				},
				this.layout,
			);
		}

		renderLoader()
		{
			return View(
				{
					style: styles.loader,
				},
				new Loader({
					style: {
						width: 50,
						height: 50,
					},
					animating: true,
					size: 'large',
				}),
			);
		}

		renderCurrentStageItems()
		{
			return View(
				{
					style: styles.currentStageContainer,
				},
				this.renderOnPaymentStage(),
				this.renderSeparator(),
				this.renderOnDeliveryStage(),
			);
		}

		renderOnPaymentStage()
		{
			const text = Loc.getMessage('M_CRM_ACTION_PAYMENT_AUTOMATION_STAGE_AFTER_PAYMENT')
				.toLocaleUpperCase(env.languageId);

			return View(
				{},
				Text({
					style: styles.currentStageTitle,
					text,
				}),
				View(
					{
						style: styles.currentStageItem,
						onClick: () => this.openStagesWindow({ type: 'stageOnOrderPaid' }),
					},
					this.getStageListItem(this.getStageByStatusId(this.state.stageOnOrderPaid)),
				),
			);
		}

		renderOnDeliveryStage()
		{
			const text = Loc.getMessage('M_CRM_ACTION_PAYMENT_AUTOMATION_STAGE_AFTER_DELIVERY')
				.toLocaleUpperCase(env.languageId);

			return View(
				{},
				Text({
					style: styles.currentStageTitle,
					text,
				}),
				View(
					{
						style: styles.currentStageItem,
						onClick: () => this.openStagesWindow({ type: 'stageOnDeliveryFinished' }),
					},
					this.getStageListItem(this.getStageByStatusId(this.state.stageOnDeliveryFinished)),
				),
			);
		}

		renderSeparator()
		{
			return View({
				style: styles.separator,
			});
		}

		getStageByStatusId(statusId)
		{
			return this.stages.find((stage) => stage.statusId === statusId) || null;
		}

		getStageById(id)
		{
			return this.stages.find((stage) => stage.id === id) || null;
		}

		getStageListItem(stage)
		{
			return CrmStageListItem({
				readOnly: true,
				canMoveStages: false,
				onSelectedStage: null,
				stage,
				onOpenStageDetail: null,
				enableStageSelect: null,
				hideBadge: true,
				showArrow: true,
			});
		}

		getStages()
		{
			const { stages } = this.props;

			return [
				stayStageItem(false),
				...Object.values(stages),
			];
		}

		saveStages()
		{
			if (!this.state.changedDeliveryStage && !this.state.changedPaidStage)
			{
				this.layout.close();

				return Promise.resolve();
			}

			return new Promise((resolve) => {
				NotifyManager.showLoadingIndicator();

				BX.ajax.runAction('salescenter.AutomationStage.saveStages', {
					data: {
						entityId: this.props.entityId,
						entityTypeId: this.props.entityTypeId,
						stages: {
							stageOnOrderPaid: this.state.stageOnOrderPaid,
							stageOnDeliveryFinished: this.state.stageOnDeliveryFinished,
						},
					},
				}).then((response) => {
					resolve(response);
					NotifyManager.hideLoadingIndicator(true);
					setTimeout(() => this.layout.close(), 500);
				}).catch(console.error);
			});
		}

		render()
		{
			return View(
				{
					style: styles.stagesContainer,
					title: Loc.getMessage('M_CRM_ACTION_PAYMENT_AUTOMATION_TITLE'),
				},
				this.state.loading
					? this.renderLoader()
					: this.renderCurrentStageItems(),
				Text({
					style: styles.stageHintText,
					text: getEntityMessage(
						'M_CRM_ACTION_PAYMENT_AUTOMATION_SELECT_STAGE_TEXT',
						this.props.entityTypeId,
					),
				}),
			);
		}
	}

	const getPaymentAutomationMenuItem = (entityId, entityTypeId, categoryId, isAutomationAvailable) => {
		return {
			id: 'paymentAutomationItem',
			sectionCode: 'top',
			onItemSelected: () => {
				if (isAutomationAvailable)
				{
					openAutomationBackdrop(entityId, entityTypeId, categoryId);
				}
				else
				{
					PlanRestriction.open(
						{
							title: Loc.getMessage('M_CRM_ACTION_PAYMENT_AUTOMATION_TITLE'),
						},
						PageManager,
					);
				}
			},
			title: Loc.getMessage('M_CRM_ACTION_PAYMENT_AUTOMATION'),
			icon: Icon.PAYMENT,
		};
	};

	const openAutomationBackdrop = (entityId, entityTypeId, categoryId) => {
		const widgetParams = {
			backgroundColor: AppTheme.colors.bgSecondary,
			backdrop: {
				swipeAllowed: true,
				forceDismissOnSwipeDown: false,
				horizontalSwipeAllowed: false,
				showOnTop: false,
				adoptHeightByKeyboard: true,
				shouldResizeContent: true,
				navigationBarColor: AppTheme.colors.bgSecondary,
			},
		};

		PageManager.openWidget('layout', widgetParams)
			.then((widget) => {
				const layoutComponent = connect(mapStateToProps)(AutomationStageComponent)({
					entityId,
					entityTypeId,
					categoryId,
					layout: widget,
				});
				widget.setTitle({
					text: Loc.getMessage('M_CRM_ACTION_PAYMENT_AUTOMATION_TITLE'),
				});
				widget.enableNavigationBarBorder(false);
				widget.showComponent(layoutComponent);
			}).catch(console.error);
	};

	const mapStateToProps = (state, { entityTypeId, categoryId }) => {
		const stageIdsBySemantics = selectStagesIdsBySemantics(state, getCrmKanbanUniqId(entityTypeId, categoryId)) || {};

		const stageIds = [
			...stageIdsBySemantics.processStages,
			...stageIdsBySemantics.successStages,
			...stageIdsBySemantics.failedStages,
		];

		return {
			stages: selectEntities(state, stageIds),
		};
	};

	const styles = {
		stagesContainer: {
			backgroundColor: AppTheme.colors.bgSecondary,
		},
		currentStageContainer: {
			backgroundColor: AppTheme.colors.bgContentPrimary,
			paddingLeft: 16,
			paddingRight: 16,
			paddingTop: 6,
			paddingBottom: 0,
			borderRadius: 12,
			flexGrow: 0,
			flexDirection: 'column',
		},
		currentStageTitle: {
			color: AppTheme.colors.base4,
			fontSize: 10,
			marginTop: 8,
			marginBottom: 0,
		},
		currentStageItem: {},
		separator: {
			borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
			borderBottomWidth: 1,
			marginBottom: 6,
		},
		loader: {
			width: '100%',
			height: '100%',
			backgroundColor: AppTheme.colors.bgPrimary,
			flexGrow: 1,
			flexDirection: 'column',
			justifyContent: 'center',
			alignItems: 'center',
		},
		stageHintText: {
			fontSize: 14,
			color: AppTheme.colors.base4,
			marginTop: 12,
			marginLeft: 16,
			marginRight: 16,
		},
	};

	module.exports = { getPaymentAutomationMenuItem };
});
