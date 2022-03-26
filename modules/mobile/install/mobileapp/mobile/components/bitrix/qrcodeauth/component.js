(function(){
	let redirectUrl = BX.componentParameters.get('redirectUrl', "");
	let external = BX.componentParameters.get('external', false);
	let showHint = BX.componentParameters.get('showHint', true);
	let hintText = BX.componentParameters.get('hintText', "");
	let urlData = BX.componentParameters.get('urlData', {});
	let description = null;
	if (BX.componentParameters.get('type', "default") === 'crm') {
		description = new CRMDescriptionLayout()
	}

	let onsuccess = ()=> BX.postComponentEvent("onQRAuthSuccess")

	let component = null
	if (external === false)
	{
		component = new QRCodeAuthComponent({redirectUrl, showHint, hintText}, description);
	}
	else
	{
		let url = urlData["url"] ? urlData["url"] : null;
		component = new QRCodeScannerComponent({redirectUrl, external, url, onsuccess, ui: layout})
	}
	layout.showComponent(component);
})();