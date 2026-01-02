/**
 * @bxjs_lang_path extension.php
 * @module crm/entity-detail/component/payment-automation-menu-item
 */
jn.define('crm/entity-detail/component/payment-automation-menu-item', (require, exports, module) => {
	const { StageStep } = require('layout/ui/stage-list/item/step');
	const { stayStageItem } = require('layout/ui/stage-list');
	const { CrmStageListView } = require('crm/stage-list-view');
	const { Loc } = require('loc');
	const { getEntityMessage } = require('crm/loc');
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
	const { Color, Indent } = require('tokens');
	const { ButtonSize, ButtonDesign, Button } = require('ui-system/form/buttons/button');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { Area } = require('ui-system/layout/area');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Text3 } = require('ui-system/typography/text');
	const { getMediumHeight } = require('utils/page-manager');

	const TEST_ID_PREFIX = 'payment-automation';

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
					style: {
						backgroundColor: Color.bgPrimary.toHex(),
						flexGrow: 1,
						justifyContent: 'center',
						alignItems: 'center',
					},
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
			return new StageStep({
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
			return this.state.loading
				? this.renderLoader()
				: this.renderContentWrapper();
		}

		renderContentWrapper()
		{
			return Box(
				{
					resizableByKeyboard: true,
					safeArea: { bottom: true },
					footer: this.renderFooter(),
				},
				this.renderContent(),
			);
		}

		renderContent()
		{
			return AreaList(
				{
					testId: `${TEST_ID_PREFIX}-area-list`,
					ref: this.#bindScrollViewRef,
					resizableByKeyboard: true,
					showsVerticalScrollIndicator: true,
				},
				Area(
					{
						divider: true,
						testId: `${TEST_ID_PREFIX}-stage-after-payment-area`,
						isFirst: true,
						title: Loc.getMessage('M_CRM_ACTION_PAYMENT_AUTOMATION_STAGE_AFTER_PAYMENT'),
					},
					View(
						{
							onClick: () => this.openStagesWindow({ type: 'stageOnOrderPaid' }),
						},
						this.getStageListItem(this.getStageByStatusId(this.state.stageOnOrderPaid)),
					),
				),
				Area(
					{
						testId: `${TEST_ID_PREFIX}-stage-after-delivery-area`,
						title: Loc.getMessage('M_CRM_ACTION_PAYMENT_AUTOMATION_STAGE_AFTER_DELIVERY'),
					},
					View(
						{
							onClick: () => this.openStagesWindow({ type: 'stageOnDeliveryFinished' }),
						},
						this.getStageListItem(this.getStageByStatusId(this.state.stageOnDeliveryFinished)),
						Text3({
							text: getEntityMessage(
								'M_CRM_ACTION_PAYMENT_AUTOMATION_SELECT_STAGE_TEXT',
								this.props.entityTypeId,
							),
							style: {
								marginTop: Indent.XL3.toNumber(),
							},
							color: Color.base3,
						}),
					),
				),
			);
		}

		#bindScrollViewRef = (ref) => {
			this.scrollViewRef = ref;
		};

		renderFooter()
		{
			return BoxFooter(
				{
					safeArea: Application.getPlatform() !== 'android',
					keyboardButton: {
						text: Loc.getMessage('M_CRM_ACTION_PAYMENT_AUTOMATION_STAGE_SAVE'),
						onClick: () => this.saveStages(),
						testId: `${TEST_ID_PREFIX}-save-buttom`,
						disabled: this.state.error,
					},
				},
				Button(
					{
						design: ButtonDesign.FILLED,
						size: ButtonSize.L,
						text: Loc.getMessage('M_CRM_ACTION_PAYMENT_AUTOMATION_STAGE_SAVE'),
						stretched: true,
						onClick: () => this.saveStages(),
						testId: `${TEST_ID_PREFIX}-save-button`,
					},
				),
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
			backgroundColor: Color.bgSecondary.toHex(),
			backdrop: {
				swipeAllowed: true,
				forceDismissOnSwipeDown: false,
				horizontalSwipeAllowed: false,
				showOnTop: false,
				adoptHeightByKeyboard: true,
				shouldResizeContent: true,
				navigationBarColor: Color.bgSecondary.toHex(),
				mediumPositionHeight: getMediumHeight({ height: 362 }),
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
					type: 'dialog',
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

	module.exports = { getPaymentAutomationMenuItem };
});
