/**
 * @module calendar/model/sync/connection
 */
jn.define('calendar/model/sync/connection', (require, exports, module) => {
	const { Oauth } = require('calendar/model/sync/oauth');
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { SyncAjax } = require('calendar/ajax');
	const { getParameterByName } = require('utils/url');

	class SyncConnection
	{
		constructor(props)
		{
			this.props = props;
		}

		deactivateConnection(connectionId)
		{
			return SyncAjax.deactivateConnection(connectionId);
		}

		connect()
		{
			switch (this.props.type)
			{
				case 'google':
					return this.connectToGoogle();
				case 'office365':
					return this.connectToOffice365();
				case 'icloud':
					return this.connectToIcloud();
				default:
					return null;
			}
		}

		updateConnection()
		{
			return SyncAjax.updateConnections();
		}

		connectToGoogle()
		{
			SyncAjax.getConnectionLink('google')
				.then((response) => {
					const connectionLink = response.data.connectionLink;
					const oauth = new Oauth({
						connectionLink,
					});

					// eslint-disable-next-line promise/catch-or-return
					oauth.run().then(({ url }) => {
						const mode = getParameterByName(url, 'mode');
						if (mode === 'bx_mobile')
						{
							this.saveGoogleConnection();
							this.props.onAuthComplete();
						}
						else
						{
							this.showConnectionAlert('', Loc.getMessage('M_CALENDAR_CONNECTION_ALERT_GOOGLE'));
						}
					});
				})
				.catch(() => {
					this.showConnectionAlert('', Loc.getMessage('M_CALENDAR_CONNECTION_ALERT_GOOGLE'));
				})
			;
		}

		connectToOffice365()
		{
			SyncAjax.getConnectionLink('office365')
				.then((response) => {
					const connectionLink = response.data.connectionLink;
					const oauth = new Oauth({
						connectionLink,
					});

					// eslint-disable-next-line promise/catch-or-return
					oauth.run().then(({ url }) => {
						const mode = getParameterByName(url, 'mode');
						if (mode === 'bx_mobile')
						{
							this.saveOffice365Connection();
							this.props.onAuthComplete();
						}
						else
						{
							this.showConnectionAlert('', Loc.getMessage('M_CALENDAR_CONNECTION_ALERT_OFFICE365'));
						}
					});
				})
				.catch(() => {
					this.showConnectionAlert('', Loc.getMessage('M_CALENDAR_CONNECTION_ALERT_OFFICE365'));
				})
			;
		}

		connectToIcloud()
		{
			this.props.openIcloudDialog();
		}

		showConnectionAlert(message = '', description = '')
		{
			Alert.alert(message, description);
		}

		saveGoogleConnection()
		{
			return SyncAjax.createGoogleConnection()
				.then((response) => {
					if (response?.data?.status === 'error')
					{
						this.props.onConnectionError();
					}
					else
					{
						this.props.onConnectionCreated();
					}
				})
				.catch((response) => {
					this.props.onConnectionError();
				})
			;
		}

		saveOffice365Connection()
		{
			return SyncAjax.createOffice365Connection()
				.then((response) => {
					if (response?.data?.status === 'error')
					{
						this.props.onConnectionError();
					}
					else
					{
						this.props.onConnectionCreated();
					}
				})
				.catch((response) => {
					this.props.onConnectionError();
				})
			;
		}

		createIcloudConnection(appleId, appPass)
		{
			return SyncAjax.createIcloudConnection(appleId, appPass)
				.then((response) => {
					if (response?.data?.status === 'error')
					{
						return {
							connectionId: null,
						};
					}
					else
					{
						return response.data;
					}
				})
				.catch((response) => {
					return {
						connectionId: null,
					};
				})
			;
		}

		syncIcloudConnection(connectionId)
		{
			return SyncAjax.syncIcloudConnection(connectionId)
				.then((response) => {
					if (response?.data?.status === 'error')
					{
						this.props.onConnectionError();
					}
					else
					{
						this.props.onConnectionCreated();
					}
				})
				.catch((response) => {
					this.props.onConnectionError();
				})
			;
		}
	}

	module.exports = { SyncConnection };
});
