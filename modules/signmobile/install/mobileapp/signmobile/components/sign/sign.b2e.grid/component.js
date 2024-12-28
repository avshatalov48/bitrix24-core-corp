(() => {
	const require = (ext) => jn.require(ext);
	const { Grid } = require('sign/grid');
	const { Master } = require('sign/master');
	const { isSendDocumentByEmployeeEnabled } = require('sign/connector');
	const startE2bMaster = BX.componentParameters.get('startE2bMaster', false);
	const master = new Master(startE2bMaster);

	isSendDocumentByEmployeeEnabled().then(({ data }) => {
		const { isE2bAvailable } = data;
		if (startE2bMaster && isE2bAvailable)
		{
			master.openMaster(layout);
		}

		BX.onViewLoaded(() => {
			layout.showComponent(
				new Grid({
					currentUserId: Number(env.userId),
					isE2bAvailable,
				}),
			);
		});
	})
})();
