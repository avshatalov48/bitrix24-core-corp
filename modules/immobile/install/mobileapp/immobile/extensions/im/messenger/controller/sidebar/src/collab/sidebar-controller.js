/**
 * @module im/messenger/controller/sidebar/collab/sidebar-controller
 */

jn.define('im/messenger/controller/sidebar/collab/sidebar-controller', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');

	const { CollabSidebarView } = require('im/messenger/controller/sidebar/collab/sidebar-view');
	const { SidebarService } = require('im/messenger/controller/sidebar/chat/sidebar-service');
	const { SidebarRestService } = require('im/messenger/controller/sidebar/chat/sidebar-rest-service');
	const { SidebarUserService } = require('im/messenger/controller/sidebar/chat/sidebar-user-service');
	const { SidebarFilesService } = require('im/messenger/controller/sidebar/chat/tabs/files/service');
	const { SidebarLinksService } = require('im/messenger/controller/sidebar/chat/tabs/links/service');
	const { ChatDataProvider, RecentDataProvider } = require('im/messenger/provider/data');
	const { Notification, ToastType } = require('im/messenger/lib/ui/notification');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { showDeleteCollabAlert, showLeaveCollabAlert } = require('im/messenger/lib/ui/alert');
	const { AnalyticsService } = require('im/messenger/provider/service');
	const { SidebarHeaderContextMenuActionType } = require('im/messenger/const');
	const { ParticipantsService } = require('im/messenger/controller/sidebar/chat/tabs/participants/participants-service');
	const { ChatPermission, UserPermission } = require('im/messenger/lib/permission-manager');
	const { BaseSidebarController } = require('im/messenger/controller/sidebar/base/sidebar-controller');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { EventType } = require('im/messenger/const');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--collab-sidebar-controller');

	class CollabSidebarController extends BaseSidebarController
	{
		/**
		 * @constructor
		 * @param {object} options
		 * @param {string} options.dialogId
		 */
		constructor(options)
		{
			super(options);
			this.sidebarService = new SidebarService(this.store, options.dialogId);
			this.sidebarRestService = new SidebarRestService(options.dialogId);
			this.participantsService = new ParticipantsService(options);
			this.sidebarUserService = null;
			this.headerContextMenuButtons = [];
			this.dialogWidget = null;
		}

		open(parentWidget = PageManager)
		{
			logger.log(`${this.constructor.name}.open`);
			this.createWidget(parentWidget);
		}

		createWidget(parentWidget = PageManager)
		{
			if (parentWidget !== PageManager)
			{
				this.dialogWidget = parentWidget;
			}

			parentWidget.openWidget(
				'layout',
				{
					titleParams: {
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_WIDGET_TITLE_COLLAB'),
						type: 'entity',
					},
					rightButtons: this.getRightButtons(),
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
			this.createView();
			this.initTabsData();
			this.widget.showComponent(this.view);
			this.subscribeStoreEvents();
			this.subscribeWidgetEvents();
			this.subscribeBXCustomEvents();
		}

		canEdit()
		{
			return ChatPermission.iaCanUpdateDialogByRole(this.dialogId);
		}

		canLeave()
		{
			const userId = serviceLocator.get('core').getUserId();

			return UserPermission.canLeaveFromCollab(userId) && ChatPermission.isCanLeaveFromChat(this.dialogId);
		}

		canDelete()
		{
			return ChatPermission.isCanDeleteChat(this.dialogId);
		}

		async onHeaderMenuEditDialog()
		{
			logger.info(`${this.constructor.name}.onHeaderMenuEditDialog`);
			const collabId = this.store.getters['dialoguesModel/collabModel/getCollabIdByDialogId'](this.dialogId);
			const { openCollabEdit } = await requireLazy('collab/create');

			try
			{
				await openCollabEdit({
					collabId,
					onUpdate: () => {
						AnalyticsService.getInstance().sendDialogEditButtonDoneDialogInfoClick(this.dialogId);
					},
				});
				AnalyticsService.getInstance().sendDialogEditHeaderMenuClick(this.dialogId);
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}.onHeaderMenuEditDialog`, error);
			}
		}

		onHeaderMenuDeleteDialog(data)
		{
			showDeleteCollabAlert({
				deleteCallback: () => {
					void this.deleteChat();
				},
				cancelCallback: () => {
					AnalyticsService.getInstance().sendChatDeleteCanceled({
						dialogId: this.dialogId,
					});
				},
			});

			AnalyticsService.getInstance().sendChatDeletePopupShown({
				dialogId: this.dialogId,
			});
		}

		async deleteChat()
		{
			const { type } = this.store.getters['dialoguesModel/getById'](this.dialogId);
			this.sidebarRestService.deleteChat()
				.then(async () => {
					// delete chat from other messenger contexts
					MessengerEmitter.broadcast(EventType.dialog.external.delete, {
						dialogId: this.dialogId,
						chatType: type,
						shouldShowAlert: false,
						deleteByCurrentUserFromMobile: true,
					});
					const recentProvider = new RecentDataProvider();
					recentProvider
						.delete({ dialogId: this.dialogId })
						.catch((error) => {
							logger.error(`${this.constructor.name}.deleteCollab delete recent error`, error);
						})
					;

					const chatProvider = new ChatDataProvider();
					chatProvider
						.delete({ dialogId: this.dialogId })
						.catch((error) => {
							logger.error(`${this.constructor.name}.deleteCollab delete chat error`, error);
						})
					;

					this.widget.back();
					if (this.dialogWidget)
					{
						this.dialogWidget.back();
					}

					Notification.showToast(ToastType.deleteCollab);
				})
				.catch((errors) => {
					logger.error(`${this.constructor.name}.deleteCollab delete chat error`, errors);

					let message = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_COLLAB_ERROR_DELETE_COLLAB');

					const isTasksNotEmpty = errors.some((error) => error.code === 'TASKS_NOT_EMPTY');
					if (isTasksNotEmpty)
					{
						message = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_COLLAB_DELETE_ERROR');
					}

					Notification.showErrorToast({ message });
				})
			;

			AnalyticsService.getInstance().sendChatDeleteConfirmed({
				dialogId: this.dialogId,
			});
		}

		onHeaderMenuLeaveDialog()
		{
			showLeaveCollabAlert({
				leaveCallback: () => {
					this.participantsService.onClickLeaveChat()
						.catch((error) => logger.error(`${this.constructor.name}.onHeaderMenuLeaveDialog`, error));
				},
			});
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindMethods()
		{
			super.bindMethods();
			this.onCloseWidget = this.onCloseWidget.bind(this);
			this.onHiddenWidget = this.onHiddenWidget.bind(this);
			this.onDestroySidebar = this.onDestroySidebar.bind(this);
		}

		setUserService()
		{
			this.sidebarUserService = new SidebarUserService(this.dialogId, false);
		}

		initTabsData()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const isInitedSidebar = this.store.getters['sidebarModel/isInited'](this.dialogId);

			if (dialogData && !isInitedSidebar)
			{
				Promise.all([
					new SidebarFilesService(dialogData.chatId).loadNextPage(),
					new SidebarLinksService(dialogData.chatId).loadNextPage(),
				])
					.catch((error) => {
						logger.error(`${this.constructor.name}.initTabsData:`, error);
					});
			}
		}

		createView()
		{
			this.view = new CollabSidebarView(this.preparePropsSidebarView());
		}

		subscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.subscribeStoreEvents`);
		}

		subscribeWidgetEvents()
		{
			super.subscribeWidgetEvents();
			logger.log(`${this.constructor.name}.subscribeWidgetEvents`);
			this.widget.on(EventType.view.close, this.onCloseWidget);
			this.widget.on(EventType.view.hidden, this.onHiddenWidget);
		}

		subscribeBXCustomEvents()
		{
			logger.log(`${this.constructor.name}.subscribeBXCustomEvents`);
			BX.addCustomEvent(EventType.sidebar.destroy, this.onDestroySidebar);
			BX.addCustomEvent(EventType.dialog.external.delete, this.onDeleteChat);
		}

		unsubscribeBXCustomEvents()
		{
			logger.log(`${this.constructor.name}.unsubscribeBXCustomEvents`);
			BX.removeCustomEvent(EventType.sidebar.destroy, this.onDestroySidebar);
			BX.removeCustomEvent(EventType.dialog.external.delete, this.onDeleteChat);
		}

		unsubscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.unsubscribeStoreEvents`);
		}

		/**
		 * @desc Prepare data props for build view
		 * @return {CollabSidebarViewProps}
		 */
		preparePropsSidebarView()
		{
			return {
				headData: {
					...this.sidebarUserService.getAvatarDataById(),
					...this.sidebarUserService.getTitleDataById(),
				},
				sidebarService: this.sidebarService,
				widget: this.widget,
				dialogId: this.dialogId,
				restService: this.sidebarRestService,
				guestCount: this.store.getters['dialoguesModel/collabModel/getGuestCountByDialogId'](this.dialogId),
			};
		}

		onDestroySidebar()
		{
			this.widget.back();
		}

		onCloseWidget()
		{
			logger.info(`${this.constructor.name}.onCloseWidget`);
			BX.onCustomEvent(EventType.sidebar.closeWidget);
			this.unsubscribeStoreEvents();
			this.unsubscribeBXCustomEvents();
		}

		onHiddenWidget()
		{
			logger.info(`${this.constructor.name}.onHiddenWidget`);
		}

		isSuperEllipseAvatar()
		{
			return true;
		}
	}

	module.exports = { CollabSidebarController };
});
