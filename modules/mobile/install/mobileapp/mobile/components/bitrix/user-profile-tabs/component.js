(() => {
	BX.onViewLoaded(async () => {
		const {
			initTabNestedWidgets,
			bindEvents,
		} = await requireLazy('user-profile/tabs-preparer', false);

		if (initTabNestedWidgets && bindEvents)
		{
			// eslint-disable-next-line no-undef
			bindEvents(tabs, Number(BX.componentParameters.get('ownerId', env.userId)));
			// eslint-disable-next-line no-undef
			initTabNestedWidgets(tabs, BX.componentParameters.get('params', {}));
		}
	});
})();
