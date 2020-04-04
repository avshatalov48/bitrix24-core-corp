(()=>{
	BX.addCustomEvent("onDiskFolderOpen", folderData =>
	{
		ComponentHelper.openList({
			name:"user.disk",
			object:"list",
			canOpenInDefault: true,
			version:availableComponents["user.disk"].version,
			componentParams:{userId: env.userId, folderId: folderData.folderId},
			widgetParams:{
				useSearch: true,
				doNotHideSearchResult: true
			}
		});
	});

})();