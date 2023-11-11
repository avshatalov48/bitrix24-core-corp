(() => {
	const require = (ext) => jn.require(ext);

	const { EventEmitter } = require('event-emitter');
	const { DocumentCardComponent } = require('catalog/store/document-card/component');

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

	DocumentCardComponent.createDetailCardComponent({
		card: result.card,
		permissions: result.permissions,
		endpoint: 'catalogmobile.Controller.DocumentDetails.StoreDocumentDetails',
		desktopUrl,
		eventEmitter: EventEmitter.createWithUid(payload.guid || Random.getString()),
		isDocumentConducted: (item) => item && item.STATUS === 'Y',
	});
})();
