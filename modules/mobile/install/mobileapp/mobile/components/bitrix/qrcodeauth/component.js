(function(){
	let redirectUrl = BX.componentParameters.get('redirectUrl', "");
	let external = BX.componentParameters.get('external', false);
	let urlData = BX.componentParameters.get('urlData', {});
	let description = null;
	if (BX.componentParameters.get('type', "default") === 'crm') {
		description = new CRMDescriptionLayout()
	}

	let onsuccess = ()=> BX.postComponentEvent("onQRAuthSuccess")

	const component = new QRCodeAuthScanner({redirectUrl, external, urlData, onsuccess}, description);
	layout.showComponent(component);
})();