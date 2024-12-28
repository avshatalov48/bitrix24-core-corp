/**
 * @module im/messenger/controller/sidebar/collab/profile-buttons-controller
 */

jn.define('im/messenger/controller/sidebar/collab/profile-buttons-controller', (require, exports, module) => {
	/* global InAppNotifier */
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { isOnline } = require('device/connection');

	const { Icon } = require('ui-system/blocks/icon');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ProfileButtonView } = require('im/messenger/controller/sidebar/collab/profile-button-view');
	const { CollabEntity } = require('im/messenger/const');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { Theme } = require('im/lib/theme');
	const { AnalyticsService } = require('im/messenger/provider/service');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--collab-profile-buttons');

	/**
	 * @class CollabProfileButtonsController
	 * @typedef {LayoutComponent<SidebarProfileCollabBtnProps, SidebarProfileCollabBtnState>} SidebarProfileBtn
	 */
	class CollabProfileButtonsController extends LayoutComponent
	{
		/**
		 * @constructor
		 * @param {SidebarProfileCollabBtnProps} props
		 */
		constructor(props)
		{
			super(props);

			this.store = serviceLocator.get('core').getStore();
			this.storeManager = serviceLocator.get('core').getStoreManager();
			this.dialogId = props.dialogId;
			this.widget = props.widget;
			this.sidebarService = props.sidebarService;
			const collabInfo = this.#getCollabInfo();

			this.state = {
				isMute: this.sidebarService.isMuteDialog(),
				collabId: collabInfo?.collabId,
				[CollabEntity.files]: collabInfo?.entities[CollabEntity.files]?.counter,
				[CollabEntity.calendar]: collabInfo?.entities[CollabEntity.calendar]?.counter,
				[CollabEntity.tasks]: collabInfo?.entities[CollabEntity.tasks]?.counter,
			};
		}

		render()
		{
			// iOS ignores the size of nested elements
			// that's why width and height are set explicitly and the marginBottom is removed
			const marginBottom = Application.getPlatform() === 'ios' ? 0 : 24;

			return ScrollView(
				{
					style: {
						marginBottom,
						width: '100%',
						height: 102,
					},
					horizontal: true,
					showsHorizontalScrollIndicator: false,
				},
				View(
					{
						style: {
							display: 'flex',
							flexDirection: 'row',
							justifyContent: 'flex-start',
							alignItems: 'center',
							paddingHorizontal: 18,
							marginBottom: 26,
						},
					},
					...this.createButtons(),
				),
			);
		}

		/**
		 * @desc Creates layout elements for view block buttons under info
		 * @return {Array<IconButton>}
		 */
		createButtons()
		{
			return [
				new ProfileButtonView(
					{
						icon: Icon.FILE,
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_FILES'),
						counter: this.state[CollabEntity.files],
						disable: !this.state.collabId,
						callback: async () => {
							const collabId = this.state.collabId;
							if (!Type.isNumber(collabId))
							{
								return;
							}

							try
							{
								const { openCollabFiles } = await requireLazy('disk:opener/collab-files');

								AnalyticsService.getInstance().sendCollabEntityOpened({
									dialogId: this.dialogId,
									entityType: CollabEntity.files,
								});

								await openCollabFiles({
									collabId,
									onStorageLoadFailure: (response, instance) => {
										instance.parentWidget.back();
									},
								}, this.widget);
							}
							catch (error)
							{
								logger.error(`${this.constructor.name}: files button tap error:`, error);
							}
						},
					},
				),
				new ProfileButtonView(
					{
						icon: Icon.CALENDAR_WITH_SLOTS,
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_CALENDAR'),
						counter: this.state[CollabEntity.calendar],
						disable: !this.state.collabId,
						callback: async () => {
							const collabId = this.state.collabId;
							if (!Type.isNumber(collabId))
							{
								return;
							}

							try
							{
								const { Entry } = await requireLazy('calendar:entry');

								AnalyticsService.getInstance().sendCollabEntityOpened({
									dialogId: this.dialogId,
									entityType: CollabEntity.calendar,
								});

								if (Entry)
								{
									void Entry.openGroupCalendarView({
										groupId: collabId,
										layout: this.widget,
									});
								}
							}
							catch (error)
							{
								logger.error(`${this.constructor.name}: calender button tap error:`, error);
							}
						},
					},
				),
				new ProfileButtonView(
					{
						icon: Icon.CIRCLE_CHECK,
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_TASKS'),
						counter: this.state[CollabEntity.tasks],
						disable: !this.state.collabId,
						callback: async () => {
							const collabId = this.state.collabId;
							if (!Type.isNumber(collabId))
							{
								return;
							}

							try
							{
								const { Entry } = await requireLazy('tasks:entry');

								AnalyticsService.getInstance().sendCollabEntityOpened({
									dialogId: this.dialogId,
									entityType: CollabEntity.tasks,
								});

								void Entry.openTaskList({
									collabId,
								});
							}
							catch (error)
							{
								logger.error(`${this.constructor.name}: tasks button tap error:`, error);
							}
						},
					},
				),
				this.renderSeparator(),
				new ProfileButtonView(
					{
						icon: this.state.isMute ? Icon.NOTIFICATION_OFF : Icon.NOTIFICATION,
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_MUTE'),
						callback: () => this.onClickMuteBtn(),
						testId: 'SIDEBAR_COLLAB_BUTTON_MUTE',
					},
				),
				new ProfileButtonView(
					{
						icon: Icon.SEARCH,
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_SEARCH'),
						callback: () => this.onClickSearchBtn(),
						disable: true,
					},
				),
			];
		}

		renderSeparator()
		{
			return View(
				{
					style: {
						height: 32,
						width: 1,
						backgroundColor: '#D9D9D9',
						marginRight: 12,
						marginLeft: 4,
					},
				},
			);
		}

		onClickSearchBtn()
		{
			const locValue = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_NOTICE_COMING_SOON');
			InAppNotifier.showNotification(
				{
					backgroundColor: Theme.colors.baseBlackFixed,
					message: locValue,
				},
			);
		}

		onClickMuteBtn()
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const oldStateMute = this.sidebarService.isMuteDialog();
			this.store.dispatch('sidebarModel/changeMute', { dialogId: this.dialogId, isMute: !oldStateMute });

			if (oldStateMute)
			{
				this.sidebarService.muteService.unmuteChat(this.dialogId);
				this.setState({ isMute: false });
			}
			else
			{
				this.sidebarService.muteService.muteChat(this.dialogId);
				this.setState({ isMute: true });
			}
		}

		/**
		 * @param {object} mutation
		 * @param {MutationPayload<CollabSetEntityCounterData, CollabSetEntityCounterActions>} mutation.payload
		 * @param {CollabSetEntityCounterData} mutation.payload.data
		 */
		onUpdateButtonCounter(mutation)
		{
			logger.info(`${this.constructor.name}.onUpdateButtonCounter---------->`, mutation);
			const { dialogId, entity, counter } = mutation.payload.data;
			if (this.dialogId !== dialogId)
			{
				return;
			}

			this.setState({
				[entity]: counter,
			});
		}

		/**
		 * @param {object} mutation
		 * @param {MutationPayload<CollabSetData, CollabSetActions>} mutation.payload
		 * @param {CollabSetData} mutation.payload.data
		 */
		onUpdateCollabInfo(mutation)
		{
			logger.info(`${this.constructor.name}.onUpdateCollabInfo---------->`, mutation);
			const { dialogId, collabId, entities } = mutation.payload.data;
			if (this.dialogId !== dialogId)
			{
				return;
			}

			this.setState({
				collabId,
				[CollabEntity.files]: entities[CollabEntity.files].counter,
				[CollabEntity.calendar]: entities[CollabEntity.calendar].counter,
				[CollabEntity.tasks]: entities[CollabEntity.tasks].counter,
			});
		}

		componentDidMount()
		{
			logger.log(`${this.constructor.name}.view.componentDidMount`);
			this.bindListener();
			this.subscribeStoreEvents();
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindListener()
		{
			this.unsubscribeStoreEvents = this.unsubscribeStoreEvents.bind(this);
			this.onUpdateButtonCounter = this.onUpdateButtonCounter.bind(this);
			this.onUpdateCollabInfo = this.onUpdateCollabInfo.bind(this);
		}

		subscribeStoreEvents()
		{
			logger.log(`${this.constructor.name} subscribeStoreEvents`);
			this.storeManager.on('dialoguesModel/collabModel/set', this.onUpdateCollabInfo);
			this.storeManager.on('dialoguesModel/collabModel/setEntityCounter', this.onUpdateButtonCounter);
			BX.addCustomEvent('onCloseSidebarWidget', this.unsubscribeStoreEvents);
		}

		unsubscribeStoreEvents()
		{
			logger.log(`${this.constructor.name} unsubscribeStoreEvents`);
			this.storeManager.off('dialoguesModel/collabModel/set', this.onUpdateCollabInfo);
			this.storeManager.off('dialoguesModel/collabModel/setEntityCounter', this.onUpdateButtonCounter);
			BX.removeCustomEvent('onCloseSidebarWidget', this.unsubscribeStoreEvents);
		}

		/**
		 * @return {CollabItem}
		 */
		#getCollabInfo()
		{
			return this.store.getters['dialoguesModel/collabModel/getByDialogId'](this.dialogId);
		}
	}

	module.exports = { CollabProfileButtonsController };
});
