(() => {
	/* global tabs */

	const require = (ext) => jn.require(ext);
	const { getFeatureRestriction, tariffPlanRestrictionsReady } = require('tariff-plan-restriction');

	const TabId = {
		RecentFilesGrid: 'RecentFilesGrid',
		MyFilesGrid: 'MyFilesGrid',
		SharedFilesGrid: 'SharedFilesGrid',
	};

	const TabReady = {
		[TabId.RecentFilesGrid]: false,
		[TabId.MyFilesGrid]: false,
		[TabId.SharedFilesGrid]: false,
	};

	const ComponentId = {
		[TabId.RecentFilesGrid]: 'disk.tabs.recent',
		[TabId.MyFilesGrid]: 'disk.tabs.my',
		[TabId.SharedFilesGrid]: 'disk.tabs.shared',
	};

	const sendCommand = (tabId, command) => {
		if (TabReady[tabId])
		{
			tabs.setActiveItem(ComponentId[tabId]);
			BX.postComponentEvent(command, [], ComponentId[tabId]);
		}
		else
		{
			const onReady = (readyTabId) => {
				if (readyTabId === tabId)
				{
					BX.postComponentEvent(command, [], ComponentId[tabId]);
					BX.removeCustomEvent('disk.tabs:onTabReady', onReady);
				}
			};

			BX.addCustomEvent('disk.tabs:onTabReady', onReady);
			tabs.setActiveItem(ComponentId[tabId]);
		}
	};

	this.tabs.on('onTabSelected', (tab, changed) => {
		if (tab.id === ComponentId[TabId.SharedFilesGrid])
		{
			tariffPlanRestrictionsReady()
				.then(() => {
					const commonStorageRestriction = getFeatureRestriction('disk_common_storage');
					if (commonStorageRestriction.isRestricted())
					{
						commonStorageRestriction.showRestriction({});
					}
				})
				.catch(error => console.error(error));
		}
	});

	BX.addCustomEvent('disk.tabs:onTabReady', (tabId) => {
		TabReady[tabId] = TabId[tabId] ? true : undefined;
	});

	BX.addCustomEvent('disk.tabs.recent:onFloatingButtonClick', () => {
		sendCommand(TabId.MyFilesGrid, 'disk.tabs:openUploaderDialogCommand');
	});

	BX.addCustomEvent('disk.tabs.recent:onFloatingButtonLongClick', () => {
		sendCommand(TabId.MyFilesGrid, 'disk.tabs:openCreateFolderDialogCommand');
	});
})();
