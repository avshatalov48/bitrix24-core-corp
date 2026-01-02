(() => {
	BX.onViewLoaded(async () => {
		const { AppRatingManager } = await requireLazy('app-rating-manager', false);
		const triggerEvent = BX.componentParameters.get('triggerEvent', 'unknown_in_component');

		layout.showComponent(
			AppRatingManager.renderAppRatingContent({
				layoutWidget: layout,
				triggerEvent,
			}),
		);
	});
})();
