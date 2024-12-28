/**
 * @module im/messenger/controller/sidebar/comment/sidebar-controller
 */
jn.define('im/messenger/controller/sidebar/comment/sidebar-controller', (require, exports, module) => {
	/* global InAppNotifier */
	const { CommentSidebarView } = require('im/messenger/controller/sidebar/comment/sidebar-view');
	const { SidebarService } = require('im/messenger/controller/sidebar/chat/sidebar-service');
	const { SidebarRestService } = require('im/messenger/controller/sidebar/chat/sidebar-rest-service');
	const { SidebarUserService } = require('im/messenger/controller/sidebar/chat/sidebar-user-service');
	const { buttonIcons } = require('im/messenger/assets/common');
	const { ButtonFactory } = require('im/messenger/lib/ui/base/buttons');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--comment-sidebar-controller');
	const { EventType } = require('im/messenger/const');
	const { Loc } = require('loc');
	const { isOnline } = require('device/connection');
	const { Notification, ToastType } = require('im/messenger/lib/ui/notification');
	const { Theme } = require('im/lib/theme');

	class CommentSidebarController
	{
		/**
		 * @constructor
		 * @param {object} options
		 * @param {string} options.dialogId
		 */
		constructor(options)
		{
			this.options = options;
			this.store = serviceLocator.get('core')
				.getStore();
			this.storeManager = serviceLocator.get('core')
				.getStoreManager();
			this.sidebarService = new SidebarService(this.store, options.dialogId);
			this.sidebarRestService = new SidebarRestService(options.dialogId);
			this.sidebarUserService = null;
			this.dialogId = options.dialogId;
		}

		/**
		 * @desc getter style btn
		 * @return {object}
		 */
		get styleBtn()
		{
			return {
				border: { color: Theme.colors.bgSeparatorPrimary },
				text: { color: Theme.colors.base1 },
			};
		}

		open()
		{
			logger.log(`${this.constructor.name}.open`);
			this.createWidget();
		}

		createWidget()
		{
			PageManager.openWidget(
				'layout',
				{
					titleParams: {
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_WIDGET_TITLE_COMMENT'),
						type: 'entity',
					},
				},
			)
				.then(
					(widget) => {
						this.widget = widget;
						this.onWidgetReady();
					},
				)
				.catch((error) => {
					logger.error(`${this.constructor.name}.PageManager.openWidget.catch:`, error);
				});
		}

		async onWidgetReady()
		{
			logger.log(`${this.constructor.name}.onWidgetReady`);
			this.sidebarService.subscribeInitTabsData();
			this.sidebarService.initTabsData();
			this.sidebarService.setStore();
			this.bindMethods();
			this.setUserService();
			this.setPermissions();
			this.createView();
			this.widget.showComponent(this.view);
			this.subscribeStoreEvents();
			this.subscribeWidgetEvents();
			this.subscribeBXCustomEvents();
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindMethods()
		{
			this.onUpdateStore = this.onUpdateStore.bind(this);
			this.onCloseWidget = this.onCloseWidget.bind(this);
			this.onDestroySidebar = this.onDestroySidebar.bind(this);
			this.onDeleteChat = this.onDeleteChat.bind(this);
		}

		setUserService()
		{
			this.sidebarUserService = new SidebarUserService(this.dialogId, false);
		}

		/**
		 * @desc Method setting permissions for user calls and  manage participants
		 * @void
		 */
		setPermissions()
		{}

		createView()
		{
			this.view = new CommentSidebarView(this.preparePropsSidebarView());
		}

		subscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.subscribeStoreEvents`);
			this.storeManager.on('sidebarModel/update', this.onUpdateStore);
			this.storeManager.on('dialoguesModel/update', this.onUpdateStore);
		}

		subscribeWidgetEvents()
		{
			logger.log(`${this.constructor.name}.subscribeWidgetEvents`);
			this.widget.on(EventType.view.close, this.onCloseWidget);
			this.widget.on(EventType.view.hidden, this.onCloseWidget);
		}

		subscribeBXCustomEvents()
		{
			logger.log(`${this.constructor.name}.subscribeBXCustomEvents`);
			BX.addCustomEvent(EventType.sidebar.destroy, this.onDestroySidebar);
			BX.addCustomEvent(EventType.dialog.external.delete, this.onDeleteChat);
		}

		unsubscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.unsubscribeStoreEvents`);
			this.storeManager.off('sidebarModel/update', this.onUpdateStore);
			this.storeManager.off('dialoguesModel/update', this.onUpdateStore);
		}

		unsubscribeBXCustomEvents()
		{
			logger.log(`${this.constructor.name}.unsubscribeBXCustomEvents`);
			BX.removeCustomEvent(EventType.sidebar.destroy, this.onDestroySidebar);
			BX.removeCustomEvent(EventType.dialog.external.delete, this.onDeleteChat);
		}

		/**
		 * @desc Prepare data props for build view
		 * @return {CommentSidebarViewProps}
		 */
		preparePropsSidebarView()
		{
			return {
				headData: {
					...this.sidebarUserService.getAvatarDataById(),
					...this.sidebarUserService.getTitleDataById(),
				},
				dialogId: this.dialogId,
				buttonElements: this.createButtons(),
				callbacks: {
					onClickInfoBLock: () => true,
				},
				restService: this.sidebarRestService,
				isSuperEllipseAvatar: this.isSuperEllipseAvatar(),
			};
		}

		/**
		 * @desc Creates layout elements for view block btn under info
		 * @return {Array<IconButton>}
		 */
		createButtons()
		{
			return [
				this.createSubscribeBtn(),
				ButtonFactory.createIconButton(
					{
						icon: buttonIcons.search(Theme.colors.base7),
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_SEARCH'),
						callback: () => this.onClickSearchButton(),
						disable: true,
						style: this.styleBtn,
					},
				),
			];
		}

		/**
		 * @desc Returns btn subscribed layout element ( eyeOn or eyeOff icon )
		 * @param {boolean} [isSubscribed]
		 * @return {object|null}
		 */
		createSubscribeBtn(isSubscribed = !this.sidebarService.isMuteDialog())
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return null;
			}

			return isSubscribed ? ButtonFactory.createIconButton(
				{
					icon: buttonIcons.eyeOnInline(),
					text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_SUBSCRIBED'),
					callback: () => this.onClickSubscribeButton(),
					style: this.styleBtn,
				},
			) : ButtonFactory.createIconButton(
				{
					icon: buttonIcons.eyeOffInline(),
					text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_SUBSCRIBE'),
					callback: () => this.onClickSubscribeButton(),
					style: this.styleBtn,
				},
			);
		}

		onClickSearchButton()
		{
			const locValue = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_NOTICE_COMING_SOON');
			InAppNotifier.showNotification(
				{
					backgroundColor: Theme.colors.baseBlackFixed,
					message: locValue,
				},
			);
		}

		onClickSubscribeButton()
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const oldStateMute = !this.sidebarService.isMuteDialog();
			this.store.dispatch('sidebarModel/changeMute', { dialogId: this.dialogId, isMute: !oldStateMute });

			if (oldStateMute)
			{
				this.sidebarService.commentsService.unsubscribe(this.dialogId);
				Notification.showToast(ToastType.unsubscribeFromComments, this.widget);
			}
			else
			{
				this.sidebarService.commentsService.subscribe(this.dialogId);
				Notification.showToast(ToastType.subscribeToComments, this.widget);
			}
		}

		onUpdateStore(event)
		{
			const { payload } = event;
			logger.info(`${this.constructor.name}.onUpdateStore---------->`, event);

			if (payload.actionName === 'unmute' || payload.actionName === 'mute')
			{
				this.changeMuteButton();
			}
		}

		onDestroySidebar()
		{
			this.widget.back();
		}

		onCloseWidget()
		{
			this.unsubscribeStoreEvents();
			this.unsubscribeBXCustomEvents();
			BX.onCustomEvent(EventType.sidebar.closeWidget);
		}

		onDeleteChat({ dialogId })
		{
			if (String(this.dialogId) !== String(dialogId))
			{
				return;
			}

			this.widget.back();
		}

		/**
		 * @desc Changed icon in btn subscribe ( eyeOn or eyeOff icon )
		 * @void
		 */
		changeMuteButton()
		{
			const subscribeButton = this.createSubscribeBtn();
			BX.onCustomEvent(EventType.sidebar.changeMuteButton, subscribeButton);
		}

		updateAllButton()
		{
			const newStateButton = this.createButtons();
			BX.onCustomEvent(EventType.sidebar.updateAllButton, newStateButton);
		}

		isSuperEllipseAvatar()
		{
			return true;
		}
	}

	module.exports = { CommentSidebarController };
});
