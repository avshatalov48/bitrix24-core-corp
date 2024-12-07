/**
 * @module calendar/sync-page
 */
jn.define('calendar/sync-page', (require, exports, module) => {
	const { SyncProviderFactory } = require('calendar/sync-page/provider');
	const { Title } = require('calendar/sync-page/title');
	const { EventEmitter } = require('event-emitter');
	const { SyncAjax } = require('calendar/ajax');
	const { Color } = require('tokens');

	/**
	 * @class SyncPage
	 */
	class SyncPage extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.pullConfig = {
				commands: [
					'refresh_sync_status',
					'delete_sync_connection',
				],
			};
			this.unsubscribe = null;

			// eslint-disable-next-line no-undef
			this.uid = Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.onConnectionCreated = this.handleConnectionCreated.bind(this);
			this.onConnectionDisabled = this.handleConnectionDisabled.bind(this);

			this.state = {
				syncInfo: this.props.syncInfo,
			};
		}

		componentDidMount()
		{
			this.pullSubscribe();
			this.customEventEmitter.on('Calendar.Sync::onConnectionCreated', this.onConnectionCreated);
			this.customEventEmitter.on('Calendar.Sync::onConnectionDisabled', this.onConnectionDisabled);
		}

		componentWillUnmount()
		{
			if (this.unsubscribe)
			{
				this.unsubscribe();
			}

			this.customEventEmitter.off('Calendar.Sync::onConnectionCreated', this.onConnectionCreated);
			this.customEventEmitter.off('Calendar.Sync::onConnectionDisabled', this.onConnectionDisabled);
		}

		pullSubscribe()
		{
			this.unsubscribe = BX.PULL.subscribe({
				moduleId: 'calendar',
				callback: (data) => {
					const command = BX.prop.getString(data, 'command', '');
					const params = BX.prop.getObject(data, 'params', {});

					if (!this.pullConfig.commands.includes(command))
					{
						return;
					}

					switch (command)
					{
						case 'refresh_sync_status':
							this.refreshSyncStatus(params);
							break;
						case 'delete_sync_connection':
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
					Title(),
					this.renderProviders(),
				),
			);
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
						};
					}
				});
			}

			this.setState({ syncInfo: this.state.syncInfo });
		}

		handleConnectionCreated(data)
		{
			const type = data.type;

			SyncAjax.clearSuccessfulConnectionNotifier(type);

			if (this.state.syncInfo[type])
			{
				this.state.syncInfo[type] = {
					...this.state.syncInfo[type],
					active: true,
					connected: true,
					syncOffset: 1,
					status: true,
				};

				this.setState({ syncInfo: this.state.syncInfo });
			}
		}

		handleConnectionDisabled(data)
		{
			const type = data.type;

			if (this.state.syncInfo[type])
			{
				this.state.syncInfo[type] = {
					type,
					active: false,
					connected: false,
				};

				this.setState({ syncInfo: this.state.syncInfo });
			}
		}
	}

	module.exports = { SyncPage };
});
