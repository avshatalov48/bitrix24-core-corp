/**
 * @module im/messenger/controller/sidebar-v2/controller/collab
 */
jn.define('im/messenger/controller/sidebar-v2/controller/collab', (require, exports, module) => {
	const { SidebarBaseController } = require('im/messenger/controller/sidebar-v2/controller/base');
	const { CollabSidebarView } = require('im/messenger/controller/sidebar-v2/controller/collab/src/view');
	const { CollabSidebarPermissionManager } = require('im/messenger/controller/sidebar-v2/controller/collab/src/permission-manager');
	const { SidebarParticipantsTab } = require('im/messenger/controller/sidebar-v2/tabs/participants');
	const { SidebarMediaTab } = require('im/messenger/controller/sidebar-v2/tabs/media');
	const { SidebarAudioTab } = require('im/messenger/controller/sidebar-v2/tabs/audio');
	const { SidebarLinksTab } = require('im/messenger/controller/sidebar-v2/tabs/links');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { onAddParticipants } = require('im/messenger/controller/sidebar-v2/user-actions/participants');
	const { onDeleteChat, onClearHistoryChat } = require('im/messenger/controller/sidebar-v2/user-actions/chat');
	const { onLeaveChat } = require('im/messenger/controller/sidebar-v2/user-actions/user');
	const {
		SidebarContextMenuActionId,
		SidebarContextMenuActionPosition,
		SIDEBAR_DEFAULT_TOAST_OFFSET,
	} = require('im/messenger/controller/sidebar-v2/const');
	const {
		createSearchButton,
		createMuteButton,
		createAutoDeleteButton,
	} = require('im/messenger/controller/sidebar-v2/ui/primary-button/factory');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { CollabEntity } = require('im/messenger/const');
	const { Icon } = require('assets/icons');
	const { Haptics } = require('haptics');

	class CollabSidebarController extends SidebarBaseController
	{
		bindMethods()
		{
			super.bindMethods();

			this.onUpdateCollabInfo = this.onUpdateCollabInfo.bind(this);
		}

		subscribeStoreEvents()
		{
			super.subscribeStoreEvents();

			this.storeManager.on('dialoguesModel/collabModel/set', this.onUpdateCollabInfo);
			this.storeManager.on('dialoguesModel/collabModel/setEntityCounter', this.onUpdateCollabInfo);
		}

		unsubscribeStoreEvents()
		{
			super.unsubscribeStoreEvents();

			this.storeManager.off('dialoguesModel/collabModel/set', this.onUpdateCollabInfo);
			this.storeManager.off('dialoguesModel/collabModel/setEntityCounter', this.onUpdateCollabInfo);
		}

		onUpdateCollabInfo(mutation)
		{
			const updatedDialogId = mutation?.payload?.data?.dialogId;
			if (this.dialogId === updatedDialogId)
			{
				this.refreshView();
			}
		}

		/**
		 * @return {{
		 *     guestCount: number,
		 *     collabId: number,
		 *     entities: {},
		 * } | undefined }
		 */
		getCollabInfo()
		{
			return this.store.getters['dialoguesModel/collabModel/getByDialogId'](this.dialogId);
		}

		/**
		 * @param {string} entityType
		 * @return {number}
		 */
		getCollabEntityCounter(entityType)
		{
			return this.getCollabInfo()?.entities[entityType]?.counter ?? 0;
		}

		/**
		 * @return {number|undefined}
		 */
		get collabId()
		{
			return this.getCollabInfo()?.collabId;
		}

		createView(defaultProps)
		{
			return new CollabSidebarView(defaultProps);
		}

		createPermissionManager(defaultProps)
		{
			return new CollabSidebarPermissionManager(defaultProps);
		}

		getWidgetTitle()
		{
			return Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_COLLAB_TITLE');
		}

		// region context menu

		getHeaderContextMenuItems()
		{
			return [
				{
					id: SidebarContextMenuActionId.ADD_PARTICIPANTS,
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_ADD_PARTICIPANTS'),
					icon: Icon.ADD_PERSON,
					testId: 'sidebar-context-menu-add-participants',
					sort: SidebarContextMenuActionPosition.MIDDLE,
					onItemSelected: () => {
						onAddParticipants({
							dialogId: this.dialogId,
							store: this.store,
						}).catch((error) => {
							this.logger.error('onAddParticipants', error);
						});
					},
				},
				...super.getHeaderContextMenuItems(),
			];
		}

		handleDeleteDialogAction()
		{
			onDeleteChat(this.dialogId, {
				onError: (errors) => {
					const message = Array.isArray(errors) && errors.some((error) => error.code === 'TASKS_NOT_EMPTY')
						? Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_COLLAB_DELETE_ERROR_NOT_EMPTY')
						: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_COLLAB_DELETE_ERROR_DEFAULT');

					Notification.showErrorToast({
						message,
						offset: SIDEBAR_DEFAULT_TOAST_OFFSET,
					});
				},
			});
		}

		handleClearHistoryForMeDialogAction()
		{
			onClearHistoryChat({ dialogId: this.dialogId, forAll: false });
		}

		handleClearHistoryForAllDialogAction()
		{
			onClearHistoryChat({ dialogId: this.dialogId, forAll: true });
		}

		async handleEditDialogAction()
		{
			const collabId = this.collabId;

			if (!collabId)
			{
				Haptics.notifyWarning();

				return;
			}

			try
			{
				const { openCollabEdit } = await requireLazy('collab/create');

				await openCollabEdit({
					collabId,
					onUpdate: () => {
						this.analyticsService.sendDialogEditButtonDoneDialogInfoClick(this.dialogId);
					},
				});
				this.analyticsService.sendDialogEditHeaderMenuClick(this.dialogId);
			}
			catch (error)
			{
				this.logger.error('handleEditDialogAction', error);
			}
		}

		handleLeaveDialogAction()
		{
			onLeaveChat(this.dialogId);
		}

		// endregion

		// region primary actions

		/**
		 * @return {SidebarPrimaryActionButton[]}
		 */
		getPrimaryActionButtons()
		{
			const muted = this.dialogHelper.isMuted;

			return [
				{
					id: 'files',
					icon: Icon.FILE,
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_BUTTON_FILES'),
					counter: this.getCollabEntityCounter(CollabEntity.files),
					onClick: () => this.handleOpenFilesAction(),
				},
				{
					id: 'calendar',
					icon: Icon.CALENDAR_WITH_SLOTS,
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_BUTTON_CALENDAR'),
					counter: this.getCollabEntityCounter(CollabEntity.calendar),
					onClick: () => this.handleOpenCalendarAction(),
				},
				{
					id: 'tasks',
					icon: Icon.CIRCLE_CHECK,
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_BUTTON_TASKS'),
					counter: this.getCollabEntityCounter(CollabEntity.tasks),
					onClick: () => this.handleOpenTasksAction(),
				},
				createSearchButton({
					onClick: () => this.handleSearchAction(),
				}),
				createMuteButton({
					onClick: () => this.handleToggleMuteAction(),
					muted,
				}),
				createAutoDeleteButton({
					onClick: (ref) => this.handleToggleAutoDeleteAction(ref),
					selected: this.dialogHelper.isMessagesAutoDeleteDelayEnabled,
				}),
			];
		}

		async handleOpenFilesAction()
		{
			const collabId = this.collabId;

			if (!collabId)
			{
				Haptics.notifyWarning();

				return;
			}

			try
			{
				const { openCollabFiles } = await requireLazy('disk:opener/collab-files');

				this.analyticsService.sendCollabEntityOpened({
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
				this.logger.error('handleOpenFilesAction', error);
			}
		}

		async handleOpenCalendarAction()
		{
			const collabId = this.collabId;

			if (!collabId)
			{
				Haptics.notifyWarning();

				return;
			}

			try
			{
				const { Entry } = await requireLazy('calendar:entry');

				this.analyticsService.sendCollabEntityOpened({
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
				this.logger.error('handleOpenCalendarAction', error);
			}
		}

		async handleOpenTasksAction()
		{
			const collabId = this.collabId;

			if (!collabId)
			{
				Haptics.notifyWarning();

				return;
			}

			try
			{
				const { Entry } = await requireLazy('tasks:entry');

				this.analyticsService.sendCollabEntityOpened({
					dialogId: this.dialogId,
					entityType: CollabEntity.tasks,
				});

				void Entry.openTaskList({
					collabId,
				});
			}
			catch (error)
			{
				this.logger.error('handleOpenTasksAction', error);
			}
		}

		// endregion

		// region tabs

		createTabs()
		{
			const props = this.getTabsProps();

			return [
				new SidebarParticipantsTab(props),
				new SidebarMediaTab(props),
				new SidebarLinksTab(props),
				new SidebarAudioTab(props),
			];
		}

		// endregion
	}

	module.exports = {
		CollabSidebarController,
		ControllerClass: CollabSidebarController,
	};
});
