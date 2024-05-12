/**
 * @module crm/entity-detail/component/custom-events
 */
jn.define('crm/entity-detail/component/custom-events', (require, exports, module) => {
	const { checkForChanges } = require('layout/ui/detail-card/action/check-for-changes');
	const { ModeSelectionMenu } = require('crm/receive-payment/mode-selection');
	const { PaymentDocument } = require('crm/entity-document');
	const { AnalyticsLabel } = require('analytics-label');
	const { Feature } = require('feature');
	const { PaymentCreate } = require('crm/terminal/entity/payment-create');
	const { PaymentPayOpener } = require('crm/terminal/entity/payment-pay-opener');
	const { DocumentCardManager } = require('catalog/store/document-card/manager');
	const { InfoHelper } = require('layout/ui/info-helper');

	/**
	 * @type {(string, function(DetailCardComponent, ...*): void)[][]}
	 */
	const customEvents = [
		[
			'Crm.Timeline::onCounterChange',
			/**
			 * @param {DetailCardComponent} detailCard
			 * @param {{needsAttention: number, incomingChannel: number, total: number}} eventData
			 */
			(detailCard, eventData) => {
				const { todoNotificationParams } = detailCard.getComponentParams();
				if (!todoNotificationParams)
				{
					return;
				}

				const { total } = eventData;

				todoNotificationParams.plannedActivityCounter = total;
			},
		],
		[
			'OpportunityButton::Click',
			/**
			 * @param {DetailCardComponent} detailCard
			 */
			(detailCard) => {
				if (!Feature.isReceivePaymentSupported())
				{
					Feature.showDefaultUnsupportedWidget();

					return;
				}

				if (!detailCard.entityModel.IS_SALESCENTER_TOOL_ENABLED)
				{
					InfoHelper.openByCode('limit_crm_sales_center_off', detailCard.layout);

					return;
				}

				checkForChanges(detailCard)
					.then(() => {
						AnalyticsLabel.send({
							event: 'onReceivePaymentButtonClick',
						});
						const menu = new ModeSelectionMenu({
							entityModel: detailCard.entityModel,
							uid: detailCard.uid,
						});
						menu.open();
					});
			},
		],
		[
			'TerminalCreatePayment::Click',
			/**
			 * @param {DetailCardComponent} detailCard
			 */
			(detailCard) => {
				if (!detailCard.entityModel.IS_TERMINAL_TOOL_ENABLED)
				{
					InfoHelper.openByCode('limit_crm_terminal_off', detailCard.layout);

					return;
				}

				checkForChanges(detailCard)
					.then(() => {
						PaymentCreate.open({
							componentParams: {
								entityId: detailCard.entityModel.ID,
								entityTypeId: detailCard.entityModel.ENTITY_TYPE_ID,
								uid: detailCard.uid,
							},
						});
					});
			},
		],
		[
			'ReceivePayment.FinishStepButton::Click',
			/**
			 * @param {DetailCardComponent} detailCard
			 */
			(detailCard) => {
				const timelineTabExists = detailCard.availableTabs.some((tab) => tab.id === 'timeline' && tab.selectable);
				if (timelineTabExists)
				{
					detailCard.setActiveTab('timeline');
				}
			},
		],
		[
			'TerminalEntityCreatePayment.FinishStepButton::Click',
			/**
			 * @param {DetailCardComponent} detailCard
			 */
			(detailCard) => {
				const timelineTabExists = detailCard.availableTabs.some((tab) => tab.id === 'timeline' && tab.selectable);
				if (timelineTabExists)
				{
					detailCard.setActiveTab('timeline');
				}
			},
		],
		[
			'TerminalEntityCreatePayment.OpenPaymentPay::Click',
			/**
			 * @param {DetailCardComponent} detailCard
			 * @param id
			 */
			(detailCard, id) => {
				if (!detailCard.entityModel.IS_TERMINAL_TOOL_ENABLED)
				{
					InfoHelper.openByCode('limit_crm_terminal_off', detailCard.layout);

					return;
				}

				const timelineTabExists = detailCard.availableTabs.some((tab) => tab.id === 'timeline' && tab.selectable);
				if (timelineTabExists)
				{
					detailCard.setActiveTab('timeline');
				}

				PaymentPayOpener.open({
					id,
					uid: detailCard.uid,
					isStatusVisible: false,
				}, detailCard.layout);
			},
		],
		[
			'EntityPaymentDocument::Click',
			/**
			 * @param {DetailCardComponent} detailCard
			 * @param document
			 */
			(detailCard, document) => {
				const isTerminalToolEnabled = detailCard.entityModel.IS_TERMINAL_TOOL_ENABLED;
				const contactId = Number(detailCard.entityModel.CONTACT_ID ?? 0);
				const entityHasContact = contactId > 0;
				const contactHasPhone = detailCard.entityModel.CONTACT_HAS_PHONE === 'Y';

				PaymentDocument.open({
					id: document.ID,
					uid: detailCard.uid,
					isTerminalToolEnabled,
					resendParams: {
						entityHasContact,
						contactHasPhone,
						contactId,
					},
				}, detailCard.layout);
			},
		],
		[
			'EntityRealizationDocument::Click',
			/**
			 * @param {DetailCardComponent} detailCard
			 * @param document
			 */
			(detailCard, document) => {
				if (!detailCard.entityModel.IS_INVENTORY_MANAGEMENT_TOOL_ENABLED)
				{
					InfoHelper.openByCode('limit_store_inventory_management_off', detailCard.layout);

					return;
				}

				DocumentCardManager.open(document);
			},
		],
	];

	module.exports = { customEvents };
});
