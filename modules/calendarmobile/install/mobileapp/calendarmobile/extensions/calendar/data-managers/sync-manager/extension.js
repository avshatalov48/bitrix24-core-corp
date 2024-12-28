/**
 * @module calendar/data-managers/sync-manager
 */
jn.define('calendar/data-managers/sync-manager', (require, exports, module) => {
	const { Color } = require('tokens');
	const { PullCommand } = require('calendar/enums');

	class SyncManager
	{
		constructor()
		{
			this.syncInfo = {};
		}

		setSyncInfo(syncInfo)
		{
			this.syncInfo = syncInfo;
		}

		openSyncPage()
		{
			// eslint-disable-next-line no-undef
			ComponentHelper.openLayout({
				name: 'calendar:calendar.sync.detail',
				canOpenInDefault: true,
				componentParams: {
					syncInfo: this.syncInfo,
				},
				widgetParams: {
					modal: true,
					leftButtons: [{
						svg: {
							content: icons.downArrowButton,
						},
						isCloseButton: true,
					}],
				},
			});
		}

		handlePull(data)
		{
			const command = BX.prop.getString(data, 'command', '');
			const params = BX.prop.getObject(data, 'params', {});

			if (params.syncInfo)
			{
				if (command === PullCommand.REFRESH_SYNC_STATUS)
				{
					this.refreshSyncStatus(params.syncInfo);
				}
				else if (command === PullCommand.DELETE_SYNC_CONNECTION)
				{
					this.deleteSyncConnection(params.syncInfo);
				}
			}

			this.checkSyncStatusChange();
		}

		refreshSyncStatus(syncInfo)
		{
			Object.keys(syncInfo).forEach((connectionName) => {
				if (this.syncInfo[connectionName])
				{
					this.syncInfo[connectionName] = {
						...this.syncInfo[connectionName],
						...syncInfo[connectionName],
					};
				}
			});
		}

		deleteSyncConnection(syncInfo)
		{
			Object.keys(syncInfo).forEach((connectionName) => {
				if (this.syncInfo[connectionName])
				{
					this.syncInfo[connectionName] = {
						type: connectionName,
						active: false,
						connected: false,
					};
				}
			});
		}

		checkSyncStatusChange()
		{
			const newStatus = this.getSummarySyncStatus();

			if (newStatus !== this.status)
			{
				this.status = newStatus;
				BX.postComponentEvent('Calendar.Sync::onSyncStatusChanged', [{
					status: this.status,
				}]);
			}
		}

		getSummarySyncStatus()
		{
			let status = syncStatus.default;

			for (const connectionName of Object.keys(this.syncInfo))
			{
				const providerInfo = this.syncInfo[connectionName];
				if (providerInfo)
				{
					if (this.isSuccessConnected(providerInfo))
					{
						status = syncStatus.success;
					}
					else if (this.isErrorConnected(providerInfo))
					{
						status = syncStatus.error;
						break;
					}
				}
			}

			return status;
		}

		getSyncItemIconColor()
		{
			switch (this.getSummarySyncStatus())
			{
				case syncStatus.success:
					return Color.accentMainSuccess.toHex();
				case syncStatus.error:
					return Color.accentMainAlert.toHex();
				default:
					return Color.base3.toHex();
			}
		}

		isSuccessConnected(providerInfo)
		{
			return providerInfo.connected === true && providerInfo.status === true;
		}

		isErrorConnected(providerInfo)
		{
			return providerInfo.connected === true && providerInfo.status === false;
		}
	}

	const syncStatus = {
		default: 'default',
		success: 'success',
		error: 'error',
	};

	const icons = {
		downArrowButton: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z" fill="#A8ADB4"/></svg>',
	};

	module.exports = { SyncManager: new SyncManager() };
});
