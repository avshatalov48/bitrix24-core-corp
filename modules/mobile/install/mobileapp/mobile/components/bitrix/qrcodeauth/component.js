(function() {
	const require = (ext) => jn.require(ext);
	const { CRMDescriptionLayout } = require('qrauth/crm');
	const { QRCodeAuthComponent, QRCodeScannerComponent } = require('qrauth');

	const redirectUrl = BX.componentParameters.get('redirectUrl', '');
	const external = BX.componentParameters.get('external', false);
	const showHint = BX.componentParameters.get('showHint', true);
	const hintText = BX.componentParameters.get('hintText', '');
	const urlData = BX.componentParameters.get('urlData', {});
	const analyticsSection = BX.componentParameters.get('analyticsSection', '');
	let description = null;
	if (BX.componentParameters.get('type', 'default') === 'crm')
	{
		description = new CRMDescriptionLayout();
	}

	const onsuccess = () => BX.postComponentEvent('onQRAuthSuccess');

	let component = null;
	if (external === false)
	{
		component = new QRCodeAuthComponent({ redirectUrl, showHint, hintText, description, analyticsSection });
	}
	else
	{
		const url = urlData.url || null;
		component = new QRCodeScannerComponent({ redirectUrl, external, url, onsuccess, ui: layout, analyticsSection });
	}

	layout.showComponent(component);
})();
