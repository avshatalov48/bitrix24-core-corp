/**
 * @module catalog/store/document-card/manager
 */
jn.define('catalog/store/document-card/manager', (require, exports, module) => {

	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');

	/**
	 * @class DocumentCard
	 */
	class DocumentCardManager extends PureComponent
	{
		static open(params)
		{
			ComponentHelper.openLayout({
				name: 'catalog:catalog.realization.document.details',
				componentParams: {
					payload: {
						entityId: params.id || null,
						docType: 'W',
						context: {
							ownerId: params.ownerId || null,
							ownerTypeId: params.ownerTypeId || null,
							paymentId: params.paymentId || null,
							orderId: params.orderId || null,
						},
						guid: params.uid || null,
					},
				},
				widgetParams: {
					titleParams: {
						detailText: Loc.getMessage('MOBILE_STORE_DOCUMENT_CARD_MANAGER_REALIZATION_DETAIL_TEXT_MSGVER_1'),
						detailTextColor: '#a8adb4',
						imageUrl: '/bitrix/mobileapp/catalogmobile/extensions/catalog/store/document-card/component/images/type_w.png',
						text: params && params.title ? params.title : Loc.getMessage('MOBILE_STORE_DOCUMENT_CARD_MANAGER_REALIZATION_NEW_TITLE'),
						useLargeTitleMode: false,
					},
					modal: true,
					leftButtons: [{
						svg: {
							content: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z" fill="#A8ADB4"/></svg>',
						},
						isCloseButton: true,
					}],
				},
			});
		}
	}

	module.exports = { DocumentCardManager };
});
