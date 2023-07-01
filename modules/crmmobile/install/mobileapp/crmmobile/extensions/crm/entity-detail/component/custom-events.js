/**
 * @module crm/entity-detail/component/custom-events
 */
jn.define('crm/entity-detail/component/custom-events', (require, exports, module) => {
	const { checkForChanges } = require('layout/ui/detail-card/action/check-for-changes');
	const { ModeSelectionMenu } = require('crm/receive-payment/mode-selection');
	const { PaymentDocument, DeliveryDocument } = require('crm/entity-document');
	const { AnalyticsLabel } = require('analytics-label');
	const { Feature } = require('feature');

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
			'EntityPaymentDocument::Click',
			/**
			 * @param {DetailCardComponent} detailCard
			 * @param document
			 */
			(detailCard, document) => {
				const { isAvailableReceivePayment } = detailCard.getComponentParams();
				PaymentDocument.open({
					document,
					entityModel: detailCard.entityModel,
					uid: detailCard.uid,
					isAvailableReceivePayment,
				});
			},
		],
		[
			'EntityDeliveryDocument::Click',
			/**
			 * @param {DetailCardComponent} detailCard
			 * @param document
			 */
			(detailCard, document) => {
				DeliveryDocument.open({
					document,
					entityModel: detailCard.entityModel,
					uid: detailCard.uid,
				});
			},
		],
	];

	module.exports = { customEvents };
});
