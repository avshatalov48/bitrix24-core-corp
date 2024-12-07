(() => {
	const require = (ext) => jn.require(ext);
	const { SignDialog } = require('sign/dialog');
	const documentTitle = BX.componentParameters.get('title', '');
	const memberId = BX.componentParameters.get('memberId', 0);
	const role = BX.componentParameters.get('role', 'signer');

	SignDialog.show({
		type: SignDialog.RESPONSE_BANNER_TYPE,
		layoutWidget: layout,
		documentTitle,
		memberId,
		role,
	});
})();
