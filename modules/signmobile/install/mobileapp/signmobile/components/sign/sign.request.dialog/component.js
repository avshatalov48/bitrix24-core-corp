(() => {
	const require = (ext) => jn.require(ext);
	const { SignDialog } = require('sign/dialog');

	const documentTitle = BX.componentParameters.get('title', '');
	const memberId = BX.componentParameters.get('memberId', 0);
	const url = BX.componentParameters.get('url', false);
	const role = BX.componentParameters.get('role');
	const isGoskey = BX.componentParameters.get('isGoskey');
	const isExternal = BX.componentParameters.get('isExternal');
	const initiatedByType = BX.componentParameters.get('initiatedByType');

	if (role === 'reviewer')
	{
		SignDialog.show({
			type: SignDialog.REQUEST_REVIEW_BANNER_TYPE,
			layoutWidget: layout,
			documentTitle,
			memberId,
			role,
			url,
			initiatedByType,
		});
	}

	if (role === 'signer')
	{
		SignDialog.show({
			type: SignDialog.REQUEST_BANNER_TYPE,
			layoutWidget: layout,
			documentTitle,
			memberId,
			role,
			url,
			isGoskey,
			isExternal,
			initiatedByType,
		});
	}
})();
