(() => {
	BX.addCustomEvent('onDiskFolderOpen', (params = {}) => {
		ComponentHelper.openList({
			name: 'user.disk',
			object: 'list',
			canOpenInDefault: true,
			version: availableComponents['user.disk'].version,
			componentParams: {
				userId: env.userId,
				...params,
			},
			widgetParams: {
				useSearch: true,
				doNotHideSearchResult: true,
			},
		});
	});
})();