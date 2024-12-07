/**
 * @module tasks/dashboard/src/navigation-title
 */
jn.define('tasks/dashboard/src/navigation-title', (require, exports, module) => {
	const { Loc } = require('loc');
	const { isEqual } = require('utils/object');
	const { Type } = require('type');

	const isGetConnectionStatusSupported = Type.isFunction(device?.getConnectionStatus);

	const DeviceConnectionStatus = Object.freeze({
		ONLINE: 'online',
		OFFLINE: 'offline',
	});

	/**
	 * @class NavigationTitle
	 */
	class NavigationTitle
	{
		/**
		 * @public
		 */
		static get ConnectionStatus()
		{
			return Object.freeze({
				NETWORK_WAITING: 'NetworkWaiting',
				CONNECTION: 'Connection',
				SYNC: 'Sync',
				NONE: 'None',
			});
		}

		constructor({ layout, statusTitleParamsMap = {} })
		{
			this.layout = layout;
			this.statusTitleParamsMap = statusTitleParamsMap;

			this.titleParams = null;

			this.deviceStatus = isGetConnectionStatusSupported
				? device.getConnectionStatus()
				: DeviceConnectionStatus.ONLINE;
			this.dashboardStatus = NavigationTitle.ConnectionStatus.NONE;

			if (isGetConnectionStatusSupported)
			{
				device.on('connectionStatusChanged', this.onConnectionStatusChanged.bind(this));
			}

			this.redrawHeader();
		}

		/**
		 * @param {'online'|'offline'} deviceStatus
		 */
		onConnectionStatusChanged(deviceStatus)
		{
			if (this.deviceStatus === deviceStatus)
			{
				return;
			}

			this.deviceStatus = deviceStatus;
			this.redrawHeader();
		}

		setDashboardStatus(dashboardStatus)
		{
			if (this.dashboardStatus === dashboardStatus)
			{
				return;
			}

			this.dashboardStatus = dashboardStatus;
			this.redrawHeader();
		}

		redrawHeader()
		{
			let headerTitle;
			let useProgress;

			const appStatus = this.deviceStatus === DeviceConnectionStatus.OFFLINE
				? NavigationTitle.ConnectionStatus.NETWORK_WAITING
				: this.dashboardStatus;

			switch (appStatus)
			{
				case NavigationTitle.ConnectionStatus.NETWORK_WAITING:
					headerTitle = Loc.getMessage('TASKSMOBILE_DASHBOARD_HEADER_NETWORK_WAITING');
					useProgress = true;
					break;

				case NavigationTitle.ConnectionStatus.CONNECTION:
					headerTitle = Loc.getMessage('TASKSMOBILE_DASHBOARD_HEADER_CONNECTION');
					useProgress = true;
					break;

				case NavigationTitle.ConnectionStatus.SYNC:
					headerTitle = Loc.getMessage('TASKSMOBILE_DASHBOARD_HEADER_SYNC');
					useProgress = true;
					break;

				case NavigationTitle.ConnectionStatus.NONE:
				default:
					headerTitle = Loc.getMessage('TASKSMOBILE_DASHBOARD_HEADER');
					useProgress = false;
					break;
			}

			let actualTitleParams = {
				text: headerTitle,
				useProgress,
				largeMode: true,
			};
			if (this.statusTitleParamsMap[appStatus])
			{
				actualTitleParams = {
					...actualTitleParams,
					...this.statusTitleParamsMap[appStatus],
				};
			}

			if (isEqual(this.titleParams, actualTitleParams))
			{
				return;
			}

			this.titleParams = actualTitleParams;

			this.layout.setTitle(this.titleParams);
		}
	}

	module.exports = { NavigationTitle };
});
