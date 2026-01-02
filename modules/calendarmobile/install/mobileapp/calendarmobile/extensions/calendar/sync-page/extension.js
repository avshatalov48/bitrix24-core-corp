/**
 * @module calendar/sync-page
 */
jn.define('calendar/sync-page', (require, exports, module) => {
	const { SyncPageTitle } = require('calendar/sync-page/title');
	const { EventEmitter } = require('event-emitter');
	const { Color } = require('tokens');
	const { Random } = require('utils/random');

	const { SyncAjax } = require('calendar/ajax');
	const { SyncProviderFactory } = require('calendar/sync-page/provider');
	const { PullCommand } = require('calendar/enums');

	/**
	 * @class SyncPage
	 */
	class SyncPage extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.pullUnsubscribe = null;

			this.uid = Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.initState();
		}

		initState()
		{
			this.state = {
				syncInfo: this.prepareSyncInfo(),
			};
		}

		prepareSyncInfo()
		{
			return Object.keys(this.props.syncInfo).sort().reduce((object, key) => {
				// eslint-disable-next-line no-param-reassign
				object[key] = this.props.syncInfo[key];

				return object;
			}, {});
		}

		componentDidMount()
		{
			this.pullSubscribe();
			this.bindEvents();
		}

		componentWillUnmount()
		{
			this.pullUnsubscribe?.();
			this.unbindEvents();
		}

		bindEvents()
		{
			this.customEventEmitter.on('Calendar.Sync::onConnectionCreated', this.handleConnectionCreated);
			this.customEventEmitter.on('Calendar.Sync::onConnectionDisabled', this.handleConnectionDisabled);
			this.customEventEmitter.on('Calendar.Sync::onReconnection', this.handleConnectionReconnected);
		}

		unbindEvents()
		{
			this.customEventEmitter.off('Calendar.Sync::onConnectionCreated', this.handleConnectionCreated);
			this.customEventEmitter.off('Calendar.Sync::onConnectionDisabled', this.handleConnectionDisabled);
			this.customEventEmitter.off('Calendar.Sync::onReconnection', this.handleConnectionReconnected);
		}

		pullSubscribe()
		{
			this.pullUnsubscribe = BX.PULL.subscribe({
				moduleId: 'calendar',
				callback: (data) => {
					const command = BX.prop.getString(data, 'command', '');
					const params = BX.prop.getObject(data, 'params', {});

					switch (command)
					{
						case PullCommand.REFRESH_SYNC_STATUS:
							this.refreshSyncStatus(params);
							break;
						case PullCommand.DELETE_SYNC_CONNECTION:
							this.deleteSyncConnection(params);
							break;
						default:
							break;
					}
				},
			});
		}

		render()
		{
			return ScrollView(
				{
					style: {
						height: '100%',
					},
				},
				View(
					{
						style: {
							backgroundColor: Color.bgContentPrimary.toHex(),
							borderRadius: 12,
						},
						testId: 'sync_page_container',
					},
					this.renderTitle(),
					this.renderProviders(),
				),
			);
		}

		renderTitle()
		{
			return SyncPageTitle();
		}

		renderProviders()
		{
			return View(
				{
					style: {
						marginBottom: 32,
					},
				},
				...Object.values(this.state.syncInfo).map((providerInfo, index) => {
					return SyncProviderFactory.createByProviderInfo(providerInfo, {
						index,
						customEventEmitter: this.customEventEmitter,
					});
				}),
			);
		}

		refreshSyncStatus(params)
		{
			if (params.syncInfo)
			{
				Object.keys(params.syncInfo).forEach((connectionName) => {
					if (this.state.syncInfo[connectionName])
					{
						this.state.syncInfo[connectionName] = {
							...this.state.syncInfo[connectionName],
							...params.syncInfo[connectionName],
							reconnecting: false,
						};
					}
				});
			}

			this.setState({ syncInfo: this.state.syncInfo });
		}

		deleteSyncConnection(params)
		{
			if (params.syncInfo)
			{
				Object.keys(params.syncInfo).forEach((connectionName) => {
					if (this.state.syncInfo[connectionName])
					{
						this.state.syncInfo[connectionName] = {
							type: connectionName,
							active: false,
							connected: false,
							reconnecting: this.state.syncInfo[connectionName].reconnecting || false,
						};
					}
				});
			}

			this.setState({ syncInfo: this.state.syncInfo });
		}

		handleConnectionCreated = (data) => {
			const { type } = data;

			void SyncAjax.clearSuccessfulConnectionNotifier(type);

			if (this.state.syncInfo[type])
			{
				this.state.syncInfo[type] = {
					...this.state.syncInfo[type],
					active: true,
					connected: true,
					syncOffset: 1,
					status: true,
					reconnecting: false,
				};

				this.setState({ syncInfo: this.state.syncInfo });
			}
		};

		handleConnectionDisabled = (data) => {
			const { type } = data;

			if (this.state.syncInfo[type])
			{
				this.state.syncInfo[type] = {
					type,
					active: false,
					connected: false,
					reconnecting: false,
				};

				this.setState({ syncInfo: this.state.syncInfo });
			}
		};

		handleConnectionReconnected = (data) => {
			const { type } = data;

			if (this.state.syncInfo[type])
			{
				this.state.syncInfo[type] = {
					...this.state.syncInfo[type],
					reconnecting: true,
				};

				this.setState({ syncInfo: this.state.syncInfo });
			}
		};
	}

	module.exports = { SyncPage };
});
