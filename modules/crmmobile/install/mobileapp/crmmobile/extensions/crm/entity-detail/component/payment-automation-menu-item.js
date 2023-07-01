/**
 * @bxjs_lang_path extension.php
 * @module crm/entity-detail/component/payment-automation-menu-item
 */
jn.define('crm/entity-detail/component/payment-automation-menu-item', (require, exports, module) => {
	const { StageListItem } = require('crm/stage-list/item');
	const { stayStageItem } = require('crm/stage-list');
	const { StageListView } = require('crm/stage-list-view');
	const { CategoryStorage } = require('crm/storage/category');
	const { Loc } = require('loc');
	const { getEntityMessage } = require('crm/loc');
	const { WidgetHeaderButton } = require('layout/ui/widget-header-button');
	const { NotifyManager } = require('notify-manager');
	const { PlanRestriction } = require('layout/ui/plan-restriction');

	const pathToIcons = `${currentDomain}/bitrix/mobileapp/crmmobile/components/crm/crm.entity.details/icons/`;

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
					})
						.then((response) => {
							this.setState({
								stageOnOrderPaid: response.data.stageOnOrderPaid,
								stageOnDeliveryFinished: response.data.stageOnDeliveryFinished,
								loading: false,
							});
						})
						.catch(() => {
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

			void StageListView.open(
				{
					entityTypeId: this.props.entityTypeId,
					categoryId: this.props.categoryId,
					activeStageId,
					readOnly: true,
					canMoveStages: false,
					enableStageSelect: true,
					onStageSelect: ({ id }) => this.onSelectedStage(id, data),
					stageParams: {
						showTunnels: false,
						showTotal: false,
						showCounters: false,
						showCount: false,
						showAllStagesItem: false,
						showStayStageItem: true,
					},
				},
				{},
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
			return new StageListItem({
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
			const { entityTypeId, categoryId } = this.props;

			const category = CategoryStorage.getCategory(entityTypeId, categoryId);

			const stages = [
				stayStageItem(false),
				...category.processStages,
				...category.successStages,
				...category.failedStages,
			];

			return stages;
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
				})
					.then((response) => {
						resolve(response);
						NotifyManager.hideLoadingIndicator(true);
						setTimeout(() => this.layout.close(), 500);
					});
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
					text: getEntityMessage('M_CRM_ACTION_PAYMENT_AUTOMATION_SELECT_STAGE_TEXT', this.props.entityTypeId),
				}),
			);
		}
	}

	const getPaymentAutomationMenuItem = (entityId, entityTypeId, categoryId, isAutomationAvailable) => {
		return {
			id: 'paymentAutomationItem',
			sectionCode: 'action',
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
			iconUrl: `${pathToIcons}payment_automation.png`,
		};
	};

	const openAutomationBackdrop = (entityId, entityTypeId, categoryId) => {
		const widgetParams = {
			backgroundColor: '#eef2f4',
			backdrop: {
				swipeAllowed: true,
				forceDismissOnSwipeDown: false,
				horizontalSwipeAllowed: false,
				showOnTop: false,
				adoptHeightByKeyboard: true,
				shouldResizeContent: true,
				navigationBarColor: '#eef2f4',
			},
		};

		PageManager.openWidget('layout', widgetParams)
			.then((widget) => {
				const layoutComponent = new AutomationStageComponent({
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
			});
	};

	const styles = {
		stagesContainer: {
			backgroundColor: '#eef2f4',
		},
		currentStageContainer: {
			backgroundColor: '#ffffff',
			paddingLeft: 16,
			paddingRight: 16,
			paddingTop: 6,
			paddingBottom: 0,
			borderRadius: 12,
			flexGrow: 0,
			flexDirection: 'column',
		},
		currentStageTitle: {
			color: '#a8adb4',
			fontSize: 10,
			marginTop: 8,
			marginBottom: 0,
		},
		currentStageItem: {},
		separator: {
			borderBottomColor: '#ebebeb',
			borderBottomWidth: 1,
			marginBottom: 6,
		},
		loader: {
			width: '100%',
			height: '100%',
			backgroundColor: '#eef2f4',
			flexGrow: 1,
			flexDirection: 'column',
			justifyContent: 'center',
			alignItems: 'center',
		},
		stageHintText: {
			fontSize: 14,
			color: '#a8adb4',
			marginTop: 12,
			marginLeft: 16,
			marginRight: 16,
		},
	};

	module.exports = { getPaymentAutomationMenuItem };
});
