(() => {
	let desktopUrl = '';
	const payload = BX.componentParameters.get('payload', {});

	if (payload.entityId)
	{
		desktopUrl = '/shop/documents/details/#ID#/'.replace('#ID#', payload.entityId);
	}
	else if (payload.docType)
	{
		desktopUrl = '/shop/documents/details/0/?DOCUMENT_TYPE=#DOC_TYPE#'.replace('#DOC_TYPE#', payload.docType);
	}


	const mainTab = result.card.tabs.find(tab => tab.id === 'main')
	if (mainTab)
	{
		mainTab.desktopUrl = desktopUrl;
	}

	const processErrorAction = (response) => {
		const errors = response.errors.length ? response.errors : [{message: 'Could not perform action'}];
		const isErrorsProcessed = CatalogStoreActivationWizard.openIfNeeded(errors);

		return {
			errors: errors,
			showErrors: !isErrorsProcessed
		};
	}

	const isDocumentConducted = (item) => item && item.STATUS === 'Y';
	const hasPermission = (item, permission) => {
		return (
			!item
			|| result.permissions.document[item['DOC_TYPE']][permission] === true
		);
	};

	const getArticleCodeByType = (type) => {
		switch (type)
		{
			// adjustment
			case 'S':
				return '14662772';

			// arrival
			case 'A':
				return '14662786';
		}

		return null;
	};

	const { DetailCardComponent } = jn.require('layout/ui/detail-card');

	DetailCardComponent
		.create(result.card)
		.setItemActions([
			{
				id: 'conductDocument',
				type: 'primary',
				title: BX.message('M_CSDD_ACTION_CONDUCT_DOCUMENT'),
				onClickCallback: (item) => {
					return new Promise((resolve, reject) => {
						BX.ajax.runAction(
							'mobile.catalog.storeDocumentDetails.conduct',
							{
								json: {
									entityId: item.ID
								}
							}
						)
							.then((response) => {
								resolve(response.data);
							}, (response) => {
								reject(processErrorAction(response));
							})
							.catch((response) => {
								reject(processErrorAction(response));
							});
					});
				},
				onActiveCallback: (item) => {
					return (
						hasPermission(item, 'catalog_store_document_conduct')
						&& !isDocumentConducted(item)
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
				hasPermission(entityModel, 'catalog_store_document_cancel')
				&& isDocumentConducted(entityModel)
			);
			if (isCancelDocumentActive)
			{
				result.push({
					id: 'cancelDocument',
					sectionCode: 'action',
					onItemSelected: (event, menuItem) => {
						const analyticsAction = {id: 'cancelDocument'};
						callbacks.onActionStart(analyticsAction);

						BX.ajax.runAction(
							'mobile.catalog.storeDocumentDetails.cancel',
							{
								json: {
									entityId: entityModel.ID
								}
							}
						)
							.then((response) => {
								callbacks.onActionSuccess(analyticsAction, response.data);
							}, (response) => {
								callbacks.onActionFailure(analyticsAction, processErrorAction(response));
							});
					},
					title: BX.message('M_CSDD_ACTION_CANCEL_DOCUMENT'),
					iconUrl: '/bitrix/mobileapp/mobile/components/bitrix/catalog.store.document.details/images/cancel_document.png'
				});
			}

			result.push({
				type: UI.Menu.Types.DESKTOP,
				showHint: false,
				showTopSeparator: isCancelDocumentActive,
				data: {
					qrUrl: desktopUrl
				}
			});

			const articleCode = getArticleCodeByType(entityModel.DOC_TYPE);
			if (articleCode)
			{
				result.push({
					type: UI.Menu.Types.HELPDESK,
					data: {
						articleCode
					}
				});
			}

			return result;
		})
	;
})();
