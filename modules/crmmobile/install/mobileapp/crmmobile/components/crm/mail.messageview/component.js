(() => {
	const { getChainPromise } = jn.require('crm/mail/message/tools/connector');
	const { MessageChain } = jn.require('crm/mail/chain');

	layout.setTitle({ text: BX.message('MESSAGE_VIEW_TITLE') });

	const id = BX.componentParameters.get('threadId', null);

	getChainPromise(id).then((response) => {
		BX.onViewLoaded(() => {
			layout.showComponent(new MessageChain({
				threadId: id,
				chain: response.data,
			}));
		});
	});
})();
