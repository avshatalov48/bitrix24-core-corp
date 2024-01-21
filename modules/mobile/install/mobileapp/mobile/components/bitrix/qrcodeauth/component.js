(function() {
	const redirectUrl = BX.componentParameters.get('redirectUrl', '');
	const external = BX.componentParameters.get('external', false);
	const showHint = BX.componentParameters.get('showHint', true);
	const hintText = BX.componentParameters.get('hintText', '');
	const urlData = BX.componentParameters.get('urlData', {});
	let description = null;
	if (BX.componentParameters.get('type', 'default') === 'crm')
	{
		description = new CRMDescriptionLayout();
	}

	const onsuccess = () => BX.postComponentEvent('onQRAuthSuccess');

	let component = null;
	if (external === false)
	{
		component = new QRCodeAuthComponent({ redirectUrl, showHint, hintText }, description);
	}
	else
	{
		const url = urlData.url || null;
		component = new QRCodeScannerComponent({ redirectUrl, external, url, onsuccess, ui: layout });
	}
	layout.showComponent(component);
})();
