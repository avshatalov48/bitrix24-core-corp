/**
 * @module calendar/sync-page/provider/sync-provider
 */
jn.define('calendar/sync-page/provider/sync-provider', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { IcloudDialog } = require('calendar/sync-page/icloud-dialog');
	const { TimeAgoFormat } = require('layout/ui/friendly-date/time-ago-format');
	const { Moment } = require('utils/date/moment');
	const { SyncSettings } = require('calendar/sync-page/settings');
	const { SyncConnection } = require('calendar/model/sync/connection');
	const { SyncWizardFactory } = require('calendar/sync-page/wizard');

	/**
	 * @class SyncProvider
	 */
	class SyncProvider extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				connectionWaiting: false,
				connectionUpdated: false,
			};

			this.connectionModel = new SyncConnection({
				type: this.type,
				onAuthComplete: () => {
					this.handleStartWizardWaiting();
					this.openSyncWizard();
				},
				onConnectionCreated: () => {
					this.setWizardConnectionCreatedState();
					this.handleEndWizardWaiting();
				},
				onConnectionError: () => {
					this.setWizardErrorState();
					this.handleEndWizardWaiting();
				},
				openIcloudDialog: () => {
					this.openIcloudDialog();
				},
			});

			this.syncWizardFactory = new SyncWizardFactory({
				firstStageComplete: this.type === 'icloud',
				type: this.type,
				title: this.props.title,
				icon: this.props.icon,
				customEventEmitter: this.customEventEmitter,
			});

			this.syncLottieRef = null;

			this.openSettingsMenu = this.openSettingsMenu.bind(this);
			this.updateConnection = this.updateConnection.bind(this);
			this.connectProvider = this.connectProvider.bind(this);
			this.reconnectProvider = this.reconnectProvider.bind(this);
			this.onIcloudConnectionCreated = this.onIcloudConnectionCreated.bind(this);
		}

		get customEventEmitter()
		{
			return this.props.customEventEmitter;
		}

		get connected()
		{
			return this.props.connected;
		}

		get status()
		{
			return this.props.status;
		}

		get icon()
		{
			return this.props.icon;
		}

		get type()
		{
			return this.props.type;
		}

		render()
		{
			return View(
				{
					style: {
						marginTop: 18,
						borderRadius: 12,
						borderColor: this.isSuccessConnection()
							? AppTheme.colors.accentBrandGreen
							: AppTheme.colors.base6,
						borderWidth: this.isSuccessConnection() ? 2 : 1,
						marginLeft: 20,
						marginRight: 20,
						padding: 16,
						paddingRight: 24,
					},
					testId: `sync_page_provider_${this.type}`,
				},
				this.renderContent(),
			);
		}

		renderContent()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
					testId: `sync_page_provider_content`,
				},
				this.renderIcon(),
				View(
					{
						style: {
							flexDirection: 'column',
							marginLeft: 10,
							flex: 1,
						},
					},
					Text(
						{
							text: this.props.title,
							style: {
								fontSize: 18,
								fontWeight: '500',
							},
							testId: `sync_page_provider_title`,
						},
					),
					this.renderSyncDate(),
					this.renderButtons(),
					this.isErrorConnection() && this.renderErrorConnection(),
				),
			);
		}

		renderIcon()
		{
			return View(
				{
					style: {
						width: 66,
						height: 66,
						alignItems: 'center',
						justifyContent: 'center',
						borderRadius: 100,
						backgroundColor: this.isSuccessConnection()
							? AppTheme.colors.accentSoftGreen2
							: AppTheme.colors.bgNavigation,
						backgroundRepeat: 'no-repeat',
						backgroundPosition: 'center',
						backgroundResizeMode: 'cover',
					},
					testId: `sync_page_provider_icon`,
				},
				Image(
					{
						style: {
							width: this.icon.width,
							height: this.icon.height,
						},
						svg: {
							content: this.icon.svg,
						},
						tintColor: this.icon.tintColor,
					},
				),
			);
		}

		renderErrorConnection()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				Text(
					{
						text: Loc.getMessage('M_CALENDAR_SYNC_PROVIDER_ERROR_INFO'),
						style: {
							marginTop: 10,
							fontSize: 16,
						},
					},
				),
			);
		}

		renderButtons()
		{
			if (this.props.connected === false)
			{
				return View(
					{
						style: {
							marginTop: 10,
						},
					},
					this.renderConnectButton(),
				);
			}

			if (this.isErrorConnection())
			{
				return View(
					{
						style: {
							flexDirection: 'row',
							height: 56,
							alignItems: 'center',
						},
					},
					this.renderReconnectButton(),
					this.renderButtonCounter(),
					this.renderSettingsButton(),
				);
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						marginTop: 10,
					},
				},
				this.renderUpdateButton(),
				this.renderSettingsButton(),
			);
		}

		renderConnectButton()
		{
			return View(
				{
					style: {
						...buttonStyle,
						borderWidth: 1,
						borderColor: AppTheme.colors.accentMainPrimaryalt,
						opacity: this.state.connectionWaiting ? 0.5 : 1,
					},
					onClick: this.connectProvider,
					testId: `sync_page_provider_connect_button`,
				},
				Text(
					{
						style: {
							fontSize: 15,
							fontWeight: '600',
							color: AppTheme.colors.base1,
						},
						text: Loc.getMessage('M_CALENDAR_SYNC_PROVIDER_BUTTON_CONNECT'),
					},
				),
			);
		}

		renderUpdateButton()
		{
			return View(
				{
					style: {
						...buttonStyle,
						flexDirection: 'row',
						opacity: this.state.connectionUpdated ? 0.5 : 1,
						flexGrow: 1,
						backgroundColor: AppTheme.colors.accentSoftGreen2,
						borderWidth: this.state.connectionUpdated ? 0 : 1,
						borderColor: AppTheme.colors.accentBrandGreen,
					},
					onClick: this.updateConnection,
					testId: `sync_page_provider_update_button`,
				},
				Text(
					{
						text: Loc.getMessage('M_CALENDAR_SYNC_PROVIDER_BUTTON_UPDATE'),
						style: {
							fontSize: 15,
							fontWeight: '600',
							marginLeft: 5,
						},
					},
				),
			);
		}

		renderReconnectButton()
		{
			return View(
				{
					style: {
						...buttonStyle,
						flexDirection: 'row',
						flexGrow: 1,
						backgroundColor: AppTheme.colors.accentMainPrimaryalt,
					},
					onClick: this.reconnectProvider,
					testId: `sync_page_provider_reconnect_button`,
				},
				Text(
					{
						text: Loc.getMessage('M_CALENDAR_SYNC_PROVIDER_BUTTON_RECONNECT'),
						style: {
							fontSize: 15,
							fontWeight: '400',
							marginLeft: 5,
							color: AppTheme.colors.baseWhiteFixed,
						},
					},
				),
			);
		}

		renderButtonCounter()
		{
			return Image(
				{
					svg: {
						content: icons.counter,
					},
					style: {
						flexDirection: 'row-reverse',
						justifyContent: 'flex-end',
						top: 3,
						right: 50,
						position: 'absolute',
						width: 19,
						height: 19,
					},
				},
			);
		}

		renderSettingsButton()
		{
			if (!this.props.id)
			{
				return null;
			}

			return View(
				{
					style: {
						...buttonStyle,
						borderWidth: 1,
						width: 46,
						borderColor: AppTheme.colors.base6,
						marginLeft: 10,
					},
					onClick: this.openSettingsMenu,
					testId: `sync_page_provider_settings_button`,
				},
				Image(
					{
						svg: {
							content: icons.settings,
						},
						style: {
							width: 16,
							height: 16,
						},
					},
				),
			);
		}

		renderSyncDate()
		{
			if (this.isErrorConnection())
			{
				return null;
			}

			let offset = this.props.syncOffset;

			if (offset === 0)
			{
				offset = 1;
			}

			if (!offset)
			{
				return null;
			}

			const timestamp = Date.now() - offset * 1000;
			const moment = new Moment(timestamp);
			const time = new TimeAgoFormat().format(moment);

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
					testId: `sync_page_provider_sync_date_container`,
				},
				LottieView(
					{
						style: {
							height: 20,
							width: 20,
						},
						data: {
							content: lottie.sync,
						},
						params: {
							loopMode: 'loop',
						},
						ref: (ref) => {
							this.syncLottieRef = ref;
						},
					},
				),
				Text(
					{
						text: time,
						style: {
							marginLeft: 3,
							color: AppTheme.colors.base3,
						},
						testId: `sync_page_provider_sync_date`,
					},
				),
			);
		}

		openIcloudDialog()
		{
			const icloudDialog = new IcloudDialog({
				connectionModel: this.connectionModel,
				onIcloudConnectionCreated: this.onIcloudConnectionCreated,
			});

			return icloudDialog.show();
		}

		onIcloudConnectionCreated(connectionId, appleId)
		{
			this.connectionModel.syncIcloudConnection(connectionId);

			setTimeout(() => {
				this.handleStartWizardWaiting();
				this.openSyncWizard({
					accountName: appleId,
				});
			}, 500);
		}

		openSettingsMenu()
		{
			const settingsMenu = new SyncSettings({
				connectionId: this.props.id,
				type: this.type,
				icon: this.icon,
				syncOffset: this.props.syncOffset,
				title: this.props.title,
				customEventEmitter: this.customEventEmitter,
			});

			return settingsMenu.show();
		}

		updateConnection()
		{
			if (this.state.connectionUpdated)
			{
				return;
			}

			if (this.syncLottieRef)
			{
				this.syncLottieRef.playAnimation();
			}

			this.state.connectionUpdated = true;

			// eslint-disable-next-line promise/catch-or-return
			this.connectionModel.updateConnection().then(() => {
				this.setState({ connectionUpdated: true }, () => {
					if (this.syncLottieRef)
					{
						this.syncLottieRef.cancelAnimation();
					}
				});
			});
		}

		connectProvider()
		{
			if (this.state.connectionWaiting)
			{
				return;
			}

			this.connectionModel.connect();
		}

		reconnectProvider()
		{
			// eslint-disable-next-line promise/catch-or-return
			this.connectionModel.deactivateConnection(this.props.id).then((response) => {
				if (response.errors && response.errors.length > 0)
				{
					console.error('error deactivate');
				}
				else
				{
					this.customEventEmitter.emit('Calendar.Sync::onConnectionDisabled', [{ type: this.type }]);
					this.connectionModel.connect();
				}
			});
		}

		openSyncWizard(additionalProps = {})
		{
			return this.syncWizardFactory.open(additionalProps);
		}

		setWizardErrorState()
		{
			return this.syncWizardFactory.setErrorState();
		}

		setWizardConnectionCreatedState()
		{
			return this.syncWizardFactory.setConnectionCreatedState();
		}

		handleStartWizardWaiting()
		{
			this.setState({ connectionWaiting: true });
		}

		handleEndWizardWaiting()
		{
			this.setState({ connectionWaiting: false });
		}

		isSuccessConnection()
		{
			return this.connected === true && this.status === true;
		}

		isErrorConnection()
		{
			return this.connected === true && this.status === false;
		}
	}

	const icons = {
		sync: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.3447 9.87886L12.3446 9.87801L14.7769 9.87726C14.7769 7.4458 12.8053 5.46361 10.3738 5.46361C9.28555 5.46361 8.28917 5.8587 7.52082 6.51341L6.27159 5.26493C7.36123 4.29338 8.79864 3.70264 10.3737 3.70264C13.7783 3.70264 16.5386 6.47432 16.5386 9.87886H18.5547L15.4233 12.9996L12.3447 9.87886ZM4.20906 9.87886L2.13281 9.87876L5.20465 6.79788L8.27436 9.87799H5.97082C5.97082 12.3095 7.94242 14.2699 10.3739 14.2699C11.417 14.2699 12.3749 13.9079 13.129 13.3021L14.3797 14.5536C13.302 15.4755 11.9031 16.0323 10.3739 16.0323C6.96937 16.0323 4.20906 13.2834 4.20906 9.87886Z" fill="#BDC1C6"/></svg>',
		success: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.74706 15.6533C11.8374 15.6533 15.1533 12.3374 15.1533 8.24706C15.1533 4.15671 11.8374 0.84082 7.74706 0.84082C3.65671 0.84082 0.34082 4.15671 0.34082 8.24706C0.34082 12.3374 3.65671 15.6533 7.74706 15.6533ZM4.86611 7.28236L6.57859 9.056L10.8598 4.71363L12.3607 6.27069L6.57859 12.0528L3.52059 8.99484L4.86611 7.28236Z" fill="#9DCF00"/></svg>',
		error: '<svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.28877 1.33057L15.5946 11.8334C16.0908 12.6604 15.4913 13.7045 14.5402 13.7045H1.92849C0.967109 13.7045 0.367536 12.6604 0.863734 11.8334L7.16959 1.33057C7.64511 0.524249 8.80291 0.524249 9.28877 1.33057ZM8.16199 4.1527C7.6968 4.1527 7.32465 4.52485 7.32465 4.99004V8.12229C7.32465 8.58748 7.6968 8.95962 8.16199 8.95962H8.2757C8.74089 8.95962 9.11304 8.58748 9.11304 8.12229V4.99004C9.11304 4.52485 8.74089 4.1527 8.2757 4.1527H8.16199ZM8.22401 12.1229C8.80291 12.1229 9.27844 11.6474 9.27844 11.0685C9.27844 10.4896 8.80291 10.014 8.22401 10.014C7.64512 10.014 7.16959 10.4896 7.16959 11.0685C7.16959 11.6474 7.64512 12.1229 8.22401 12.1229Z" fill="#F4433E"/></svg>',
		settings: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.42551 7.61336C4.9817 9.69932 6.79416 11.5123 8.88057 11.0686C9.97491 10.8359 10.8355 9.97526 11.0683 8.88092C11.5121 6.79439 9.69898 4.98181 7.61296 5.4258C6.62659 5.63573 5.63539 6.62699 5.42551 7.61336ZM13.1441 5.73357C13.3381 6.11103 13.4875 6.51483 13.5907 6.93681L15.0436 7.1792C15.3533 7.23022 15.5798 7.4979 15.5802 7.81205V8.68217C15.5802 8.99634 15.3538 9.26402 15.0441 9.31504L13.6204 9.55275C13.6027 9.55572 13.5885 9.56893 13.5841 9.58635C13.4518 10.1154 13.2426 10.6127 12.9696 11.068C12.9604 11.0834 12.9611 11.1027 12.9715 11.1174L13.8307 12.3219C14.0146 12.5771 13.9846 12.9267 13.7631 13.1482L13.1477 13.7637C12.9257 13.9861 12.5756 14.0148 12.3214 13.8322L11.1146 12.9702C11.1 12.9598 11.0807 12.9591 11.0653 12.9683C10.6123 13.2383 10.1195 13.4448 9.59482 13.5762C9.57741 13.5806 9.56419 13.5947 9.56122 13.6125L9.32262 15.0442C9.27161 15.3535 9.00398 15.5805 8.68979 15.5805H7.81902C7.50548 15.5805 7.23695 15.3535 7.18707 15.0442L6.94266 13.5833C6.30508 13.4268 5.71023 13.1644 5.18293 12.8081C5.16812 12.798 5.14882 12.7977 5.13368 12.8072L3.89822 13.5833C3.63325 13.7502 3.28572 13.7015 3.07626 13.466L2.49888 12.8157C2.29032 12.5807 2.28205 12.2298 2.47918 11.9863L3.38633 10.8627C3.39768 10.8487 3.39946 10.8293 3.39102 10.8133C3.18831 10.4304 3.03412 10.0191 2.92587 9.58906C2.92149 9.57164 2.9073 9.55847 2.8896 9.55551L1.44976 9.31508C1.14096 9.26409 0.913574 8.99638 0.913574 8.68221V7.81209C0.913574 7.4979 1.14096 7.23022 1.44976 7.17923L2.88965 6.93878C2.90732 6.93583 2.92149 6.92268 2.92589 6.90529C3.05897 6.37859 3.26702 5.8823 3.53923 5.42849C3.54847 5.41308 3.54774 5.39372 3.53731 5.3791L2.70926 4.21925C2.52683 3.96459 2.55529 3.61479 2.77706 3.39255L3.39223 2.77693C3.61447 2.55516 3.96425 2.52671 4.2189 2.70865L5.37643 3.53475C5.39108 3.54521 5.41044 3.54589 5.42585 3.53663C5.88319 3.26175 6.38244 3.05151 6.91398 2.9183C6.93137 2.91394 6.94456 2.89977 6.94753 2.88207L7.18705 1.45004C7.23692 1.14075 7.50548 0.913818 7.81902 0.913818H8.68979C9.00396 0.913818 9.27161 1.14075 9.32262 1.45004L9.56082 2.88154C9.56379 2.89928 9.577 2.91349 9.59442 2.91787C10.2512 3.08246 10.8603 3.36179 11.399 3.73812L12.6414 2.95706C12.9064 2.78967 13.2537 2.83936 13.4632 3.07435L14.041 3.72468C14.2495 3.95989 14.2585 4.31057 14.0607 4.5543L13.1491 5.68469C13.1379 5.69859 13.136 5.71769 13.1441 5.73357Z" fill="#A8ADB4"/></svg>',
		counter: '<svg width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="0.970703" y="0.5" width="18" height="18" rx="9" fill="#FF5752"/><path d="M10.0234 13.5V6.3457H9.99414L7.78516 7.86914V6.62695L10.0117 5.04492H11.3066V13.5H10.0234Z" fill="white"/></svg>',
	};

	const lottie = {
		sync: '{"nm":"Sync Icon","v":"5.9.6","fr":60,"ip":0,"op":65,"w":20,"h":20,"ddd":0,"markers":[],"assets":[{"nm":"[FRAME] Sync Icon - Null / Shape - Null / Shape","fr":60,"id":"lm0a5y3ja58766x6xr","layers":[{"ty":3,"ddd":0,"ind":4,"hd":false,"nm":"Sync Icon - Null","ks":{"a":{"a":0,"k":[0,0]},"o":{"a":0,"k":100},"p":{"a":0,"k":[0,0]},"r":{"a":0,"k":0},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}},"st":0,"ip":0,"op":78,"bm":0,"sr":1},{"ty":3,"ddd":0,"ind":5,"hd":false,"nm":"Shape - Null","parent":4,"ks":{"a":{"a":0,"k":[8,6]},"o":{"a":0,"k":100},"p":{"a":0,"k":[10,10]},"r":{"a":1,"k":[{"t":0,"s":[0],"o":{"x":[0],"y":[0]},"i":{"x":[1],"y":[1]}},{"t":66,"s":[360]}]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0}},"st":0,"ip":0,"op":78,"bm":0,"sr":1},{"ty":4,"ddd":0,"ind":6,"hd":false,"nm":"Shape","parent":5,"ks":{"a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}},"st":0,"ip":0,"op":78,"bm":0,"sr":1,"shapes":[{"ty":"gr","nm":"Group","hd":false,"np":4,"it":[{"ty":"sh","nm":"Path","hd":false,"ks":{"a":0,"k":{"c":true,"v":[[9.9496,6.0111],[12.3192,6.0095],[8.0293,1.7139],[5.2496,2.7356],[4.0325,1.5205],[8.0293,0],[14.0358,6.0111],[16.0001,6.0111],[12.9492,9.0484],[9.9496,6.0111]],"i":[[0,0],[0,0],[2.369,0],[0.7486,-0.6372],[0,0],[-1.5346,0],[0,-3.3135],[0,0],[0,0],[0,0]],"o":[[0,0],[0,-2.36644],[-1.0602900000000002,0],[0,0],[1.0616500000000002,-0.94556],[3.3170800000000007,0],[0,0],[0,0],[0,0],[0,0]]}}},{"ty":"sh","nm":"Path","hd":false,"ks":{"a":0,"k":{"c":true,"v":[[2.0229,6.0111],[0,6.011],[2.9929,3.0125],[5.9838,6.0103],[3.7394,6.0103],[8.0293,10.2847],[10.7136,9.3428],[11.9322,10.5608],[8.0293,12],[2.0228,6.0111],[2.0229,6.0111]],"i":[[0,0],[0,0],[0,0],[0,0],[0,0],[-2.369,0],[-0.7347,0.5896],[0,0],[1.4899,0],[0,3.3135],[0,0]],"o":[[0,0],[0,0],[0,0],[0,0],[0,2.36644],[1.01628,0],[0,0],[-1.0500100000000003,0.8972200000000008],[-3.31708,0],[0,0],[0,0]]}}},{"ty":"fl","o":{"a":0,"k":100},"c":{"a":0,"k":[0.6588235294117647,0.6784313725490196,0.7058823529411765,1]},"nm":"Fill","hd":false,"r":2},{"ty":"tr","a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}]}]}]}],"layers":[{"ddd":0,"ind":1,"ty":0,"nm":"Sync Icon","refId":"lm0a5y3ja58766x6xr","sr":1,"ks":{"a":{"a":0,"k":[0,0]},"p":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"sk":{"a":0,"k":0},"sa":{"a":0,"k":0},"r":{"a":0,"k":0},"o":{"a":0,"k":100}},"ao":0,"w":20,"h":20,"ip":0,"op":78,"st":0,"hd":false,"bm":0}],"meta":{"a":"","d":"","tc":"","g":"Aninix"}}',
	};

	const buttonStyle = {
		height: 36,
		alignItems: 'center',
		justifyContent: 'center',
		borderRadius: 6,
	};

	module.exports = { SyncProvider };
});
