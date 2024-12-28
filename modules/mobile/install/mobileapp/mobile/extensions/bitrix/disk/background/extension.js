(() => {
	BX.addCustomEvent('onDiskFolderOpen', async (params = {}) => {
		const require = (ext) => jn.require(ext);
		const { openNativeViewerByFileId } = require('utils/file');

		const { inAppUrl } = require('in-app-url');
		const { isModuleInstalled } = require('module');

		if (isModuleInstalled('disk'))
		{
			inAppUrl.open(params.url);

			return;
		}

		if (params.objectId)
		{
			openNativeViewerByFileId(params.objectId);
		}
		else
		{
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
		}
	});
})();
