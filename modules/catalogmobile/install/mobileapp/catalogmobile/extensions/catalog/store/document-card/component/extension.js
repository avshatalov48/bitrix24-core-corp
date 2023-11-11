/**
 * @module catalog/store/document-card/component
 */
jn.define('catalog/store/document-card/component', (require, exports, module) => {

	const { PureComponent } = require('layout/pure-component');
	const { CatalogStoreActivationWizard } = require('catalog/store/activation-wizard');
	const { DetailCardComponent } = require('layout/ui/detail-card');
	const { DocumentType } = require('catalog/store/document-type');

	/**
	 * @class DocumentCardComponent
	 */
	class DocumentCardComponent extends PureComponent
	{
		static createDetailCardComponent(params)
		{
			const mainTab = params.card.tabs.find((tab) => tab.id === 'main');
			if (mainTab)
			{
				mainTab.desktopUrl = params.desktopUrl;
			}

			DetailCardComponent
				.create(params.card)
				.setItemActions([
					{
						id: 'conductDocument',
						type: 'primary',
						title: BX.message('MOBILE_STORE_DOCUMENT_CARD_COMPONENT_ACTION_CONDUCT_DOCUMENT'),
						onClickCallback: (item) => {
							return new Promise((resolve, reject) => {
								BX.ajax.runAction(
										`${params.endpoint}.conduct`,
										{
											json: {
												entityId: item.ID,
												docType: item.DOC_TYPE,
											},
										},
								)
									.then((response) => {
										params.eventEmitter.emit(
											'Catalog.StoreDocument::onConduct',
											[
												item.DOC_TYPE,
											],
										);
										resolve(response.data);
									}, (response) => {
										reject(this.processErrorAction(response));
									})
									.catch((response) => {
										reject(this.processErrorAction(response));
									});
							});
						},
						onActiveCallback: (item) => {
							return (
								this.hasPermission(params.permissions, item, 'catalog_store_document_conduct')
								&& !params.isDocumentConducted(item)
							);
						},
					},
				])
				.renderTo(layout)
				.setAnalyticsProvider((model) => {
					return {
						entity: 'store-document',
						type: model.DOC_TYPE,
					};
				})
				.setMenuActionsProvider((detailCard, callbacks) => {
					const { entityModel } = detailCard;
					const result = [];

					const isCancelDocumentActive = (
						this.hasPermission(params.permissions, entityModel, 'catalog_store_document_cancel')
						&& params.isDocumentConducted(entityModel)
					);
					if (isCancelDocumentActive)
					{
						result.push({
							id: 'cancelDocument',
							sectionCode: 'action',
							onItemSelected: (event, menuItem) => {
								const analyticsAction = { id: 'cancelDocument' };
								callbacks.onActionStart(analyticsAction);
								BX.ajax.runAction(
										`${params.endpoint}.cancel`,
										{
											json: {
												entityId: entityModel.ID,
												docType: entityModel.DOC_TYPE,
											},
										},
								)
									.then((response) => {
										params.eventEmitter.emit(
											'Catalog.StoreDocument::onCancel',
											[
												entityModel.DOC_TYPE,
											],
										);
										callbacks.onActionSuccess(analyticsAction, response.data);
									}, (response) => {
										callbacks.onActionFailure(analyticsAction, this.processErrorAction(response));
									});
							},
							title: BX.message('MOBILE_STORE_DOCUMENT_CARD_COMPONENT_ACTION_CANCEL_DOCUMENT'),
							iconUrl: '/bitrix/mobileapp/catalogmobile/extensions/catalog/store/document-card/component/images/cancel_document.png',
						});
					}

					result.push({
						type: UI.Menu.Types.DESKTOP,
						showHint: false,
						showTopSeparator: isCancelDocumentActive,
						data: {
							qrUrl: params.desktopUrl,
						},
					});

					const articleCode = this.getArticleCodeByType(entityModel.DOC_TYPE);
					if (articleCode)
					{
						result.push({
							type: UI.Menu.Types.HELPDESK,
							data: {
								articleCode,
							},
						});
					}

					return result;
				});
		}

		static getArticleCodeByType(type)
		{
			switch (type)
			{
				// adjustment
				case DocumentType.StoreAdjustment:
					return '14662772';

				// arrival
				case DocumentType.Arrival:
					return '14662786';

				default:
					return null;
			}
		}

		static processErrorAction(response)
		{
			const errors = response.errors.length > 0 ? response.errors : [{ message: 'Could not perform action' }];
			const isErrorsProcessed = CatalogStoreActivationWizard.openIfNeeded(errors);

			return { errors, showErrors: !isErrorsProcessed };
		}

		static hasPermission(permissions, item, permission)
		{
			return !item || permissions.document[item.DOC_TYPE][permission] === true;
		}
	}

	module.exports = { DocumentCardComponent };
});
