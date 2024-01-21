/**
 * @module calendar/event-list-view/sync-button
 */
jn.define('calendar/event-list-view/sync-button', (require, exports, module) => {
	const AppTheme = require('apptheme');

	class SyncButton
	{
		STATUS_DEFAULT = 'default';
		STATUS_SUCCESS = 'success';
		STATUS_ERROR = 'error';

		constructor(props)
		{
			this.props = props;
			this.syncInfo = props.syncInfo;
			this.status = this.getSummarySyncStatus();

			this.openSyncPage = this.openSyncPage.bind(this);
		}

		getContent()
		{
			return {
				svg: {
					content: this.getSyncIcon(),
				},
				type: 'sync-button',
				badgeCode: 'calendar_sync',
				callback: this.openSyncPage,
			};
		}

		getSyncIcon()
		{
			switch (this.status)
			{
				case this.STATUS_DEFAULT:
					return icons.default;
				case this.STATUS_SUCCESS:
					return icons.success;
				case this.STATUS_ERROR:
					return icons.error;
			}
		}

		handlePull(data)
		{
			const command = BX.prop.getString(data, 'command', '');
			const params = BX.prop.getObject(data, 'params', {});

			if (params.syncInfo)
			{
				if (command === 'refresh_sync_status')
				{
					this.refreshSyncStatus(params.syncInfo);
				}
				else if (command === 'delete_sync_connection')
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
				if (this.props.onSyncStatusChanged)
				{
					this.props.onSyncStatusChanged();
				}
			}
		}

		getSummarySyncStatus()
		{
			let status = this.STATUS_DEFAULT;

			Object.keys(this.syncInfo).forEach((connectionName) => {
				const providerInfo = this.syncInfo[connectionName];
				if (providerInfo)
				{
					if (this.isSuccessConnected(providerInfo))
					{
						status = this.STATUS_SUCCESS;
					}
					if (this.isErrorConnected(providerInfo))
					{
						status = this.STATUS_ERROR;

						return status;
					}
				}
			})

			return status;
		}

		isSuccessConnected(providerInfo)
		{
			return providerInfo.connected === true && providerInfo.status === true;
		}

		isErrorConnected(providerInfo)
		{
			return providerInfo.connected === true && providerInfo.status === false;
		}

		openSyncPage()
		{
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
	}

	const icons = {
		default: `<svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.8139 12.3548L17.7324 12.3529C17.7324 9.43516 15.3665 7.05653 12.4488 7.05653C11.1429 7.05653 9.9472 7.53063 9.02518 8.31628L7.5261 6.81811C8.83367 5.65226 10.5586 4.94336 12.4487 4.94336C16.5341 4.94336 19.8465 8.26938 19.8465 12.3548H22.2658L18.5082 16.0997L14.8139 12.3548ZM5.05107 12.3544L2.55957 12.3543L6.24577 8.65723L9.92943 12.3534H7.16518C7.16518 15.2711 9.5311 17.6236 12.4488 17.6236C13.7005 17.6236 14.8501 17.1893 15.755 16.4623L17.2559 17.9641C15.9626 19.0703 14.2839 19.7386 12.4489 19.7386C8.36344 19.7386 5.05107 16.4399 5.05107 12.3544Z" fill="${AppTheme.colors.base4}"/></svg>`,
		success: `<svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.8139 12.3548L17.7324 12.3529C17.7324 9.43516 15.3665 7.05653 12.4488 7.05653C11.1429 7.05653 9.9472 7.53063 9.02518 8.31628L7.5261 6.81811C8.83367 5.65226 10.5586 4.94336 12.4487 4.94336C16.5341 4.94336 19.8465 8.26938 19.8465 12.3548H22.2658L18.5082 16.0997L14.8139 12.3548ZM5.05107 12.3544L2.55957 12.3543L6.24577 8.65723L9.92943 12.3534H7.16518C7.16518 15.2711 9.5311 17.6236 12.4488 17.6236C13.7005 17.6236 14.8501 17.1893 15.755 16.4623L17.2559 17.9641C15.9626 19.0703 14.2839 19.7386 12.4489 19.7386C8.36344 19.7386 5.05107 16.4399 5.05107 12.3544Z" fill="${AppTheme.colors.accentMainSuccess}"/></svg>`,
		error: '<svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.68913 20.7036C7.14262 21.0188 7.76579 20.9067 8.08101 20.4532L18.14 5.98162C18.4552 5.52813 18.3431 4.90497 17.8896 4.58975L17.4672 4.29615C17.0137 3.98094 16.3905 4.09303 16.0753 4.54653L6.01636 19.0181C5.70115 19.4716 5.81324 20.0948 6.26674 20.41L6.68913 20.7036ZM7.28962 14.4848L5.96572 16.3894C5.12907 15.1933 4.63835 13.7374 4.63835 12.1649L2.14685 12.1647L5.83305 8.46768L9.15509 11.801L8.90288 12.1638H6.75246C6.75246 12.9975 6.94562 13.7851 7.28962 14.4848ZM17.3197 12.1634L15.4742 12.1646L15.0304 12.8031L18.0955 15.9102L21.8531 12.1653H19.4338C19.4338 10.6906 19.0022 9.31494 18.2585 8.15888L16.905 10.1062C17.172 10.7391 17.3197 11.4344 17.3197 12.1634ZM10.4582 19.381L11.8146 17.4295C11.8881 17.4326 11.9619 17.4341 12.0361 17.4341C13.2878 17.4341 14.4374 16.9997 15.3423 16.2728L16.8431 17.7745C15.5499 18.8808 13.8712 19.5491 12.0362 19.5491C11.4946 19.5491 10.9667 19.4911 10.4582 19.381ZM12.5663 6.8934L13.89 4.989C13.2975 4.8355 12.6762 4.75382 12.0359 4.75382C10.1458 4.75382 8.42095 5.46271 7.11338 6.62857L8.61246 8.12674C9.53448 7.34109 10.7301 6.86698 12.036 6.86698C12.215 6.86698 12.3919 6.87593 12.5663 6.8934Z" fill="#FF5752"/></svg>',
		downArrowButton: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z" fill="#A8ADB4"/></svg>',
	};

	module.exports = { SyncButton };
});