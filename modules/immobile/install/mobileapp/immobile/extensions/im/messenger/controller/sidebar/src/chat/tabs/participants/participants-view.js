/**
 * @module im/messenger/controller/sidebar/chat/tabs/participants/participants-view
 */
jn.define('im/messenger/controller/sidebar/chat/tabs/participants/participants-view', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Feature: MobileFeature } = require('feature');
	const { Icon } = require('assets/icons');
	const { withPressed } = require('utils/color');
	const { SocialNetworkUserSelector } = require('selector/widget/entity/socialnetwork/user');

	const { Theme } = require('im/lib/theme');
	const { buttonIcons } = require('im/messenger/assets/common');
	const {
		SidebarActionType,
		ErrorType,
		DialogType,
	} = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--participants-view');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Item } = require('im/messenger/lib/ui/base/item');
	const { LoaderItem } = require('im/messenger/lib/ui/base/loader');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const { DialogHelper, UserHelper } = require('im/messenger/lib/helper');
	const { ChatTitle, ChatAvatar } = require('im/messenger/lib/element');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { ParticipantManager } = require('im/messenger/controller/participant-manager');
	const { AnalyticsService } = require('im/messenger/provider/service');
	const { showLeaveChatAlert } = require('im/messenger/lib/ui/alert');

	const { ParticipantsService } = require('im/messenger/controller/sidebar/chat/tabs/participants/participants-service');
	const { BaseSidebarTabView } = require('im/messenger/controller/sidebar/chat/tabs/base/view');

	/**
	 * @class SidebarParticipantsView
	 * @typedef {LayoutComponent<SidebarParticipantsViewProps, SidebarParticipantsViewState>} SidebarParticipantsView
	 */
	class SidebarParticipantsView extends BaseSidebarTabView
	{
		constructor(props)
		{
			super(props);
			this.store = serviceLocator.get('core').getStore();
			this.storeManager = serviceLocator.get('core').getStoreManager();
			this.participantsService = this.getParticipantsService();

			this.state = {
				participants: this.participantsService.getParticipantsFromStore(),
				permissions: {
					isCanRemoveParticipants: ChatPermission.isCanRemoveParticipants(props.dialogId),
					isCanAddParticipants: ChatPermission.isCanAddParticipants(props.dialogId),
					isCanLeave: ChatPermission.isCanLeaveFromChat(props.dialogId),
				},
			};

			this.loader = new LoaderItem({
				enable: true,
				text: '',
			});
		}

		/**
		 * @return {ParticipantsService}
		 */
		getParticipantsService()
		{
			return new ParticipantsService(this.props);
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindMethods()
		{
			super.bindMethods();
			this.onUpdateDialogStore = this.onUpdateDialogStore.bind(this);
			this.onCloseUserSelector = this.onCloseUserSelector.bind(this);
		}

		/**
		 * @desc Handler dialog store update
		 * @param {object} event
		 * @void
		 */
		onUpdateDialogStore(event)
		{
			logger.info(`${this.constructor.name}.onUpdateDialogStore---------->`, event);
			const { payload } = event;

			if (payload.actionName === 'addParticipants' || payload.actionName === 'removeParticipants'
				|| payload.actionName === 'updateManagerList' || payload.actionName === 'updateRole')
			{
				const newParticipants = this.participantsService.getParticipantsFromStore();
				this.updateState({ participants: newParticipants });
			}
			else if (payload.data.fields?.managerList)
			{
				const eventManagers = payload.data.fields.managerList;
				const currentManagers = this.state.participants.filter((participant) => participant.isManager);

				if (eventManagers.length !== currentManagers.length) // early exit
				{
					const newParticipants = this.participantsService.getParticipantsFromStore();
					this.updateState({ participants: newParticipants });

					return;
				}

				if (eventManagers.length === currentManagers.length)
				{
					const oldManagersIdsList = new Set(currentManagers.map((user) => user.id));
					for (const userId of eventManagers)
					{
						if (!oldManagersIdsList.has(userId))
						{
							const newParticipants = this.participantsService.getParticipantsFromStore();
							this.updateState({ participants: newParticipants });

							break;
						}
					}
				}
			}
			else
			{
				const eventParticipants = payload.data.fields.participants;
				const currentParticipants = this.state.participants;

				if (Type.isArray(eventParticipants) && eventParticipants.length !== currentParticipants.length)
				{
					const newParticipants = this.participantsService.getParticipantsFromStore();
					this.updateState({ participants: newParticipants });
				}
			}
		}

		/**
		 * @desc Method update state component
		 * @param {object} newState
		 * @void
		 */
		updateState(newState)
		{
			this.setState(newState);
		}

		subscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.subscribeStoreEvents`);
			this.storeManager.on('dialoguesModel/update', this.onUpdateDialogStore);
			this.storeManager.on('dialoguesModel/copilotModel/update', this.onUpdateDialogStore);
		}

		unsubscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.unsubscribeStoreEvents`);
			this.storeManager.off('dialoguesModel/update', this.onUpdateDialogStore);
			this.storeManager.off('dialoguesModel/copilotModel/update', this.onUpdateDialogStore);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						backgroundColor: Theme.colors.bgContentPrimary,
					},
				},
				this.renderListView(),
			);
		}

		renderListView()
		{
			const items = this.buildItems();
			const platform = Application.getPlatform();

			return ListView({
				ref: (ref) => {
					if (ref)
					{
						this.listViewRef = ref;
					}
				},
				style: {
					marginTop: 12,
					flexDirection: 'column',
					flex: 1,
				},
				data: [{ items }],
				renderItem: (item) => {
					if (item.type === 'addrow')
					{
						return this.getAddParticipantRow();
					}

					return new Item({
						data: item,
						size: 'M',
						isCustomStyle: true,
						nextTo: false,
						onLongClick: (data, ref) => {
							this.onLongClickItem(
								item.key,
								item.userId,
								this.setItemEntity(item),
								ref,
							);
						},
						onClick: () => {
							this.onClickItem(item.userId);
						},
						additionalComponent: this.isEllipsis(item) ? this.getEllipsisButton(item) : null,
						isSuperEllipseAvatar: item.isSuperEllipseAvatar,
					});
				},
				onLoadMore: platform === 'ios' ? this.iosOnLoadMore.bind(this) : this.androidOnLoadMore.bind(this),
				renderLoadMore: platform === 'ios' ? this.iosRenderLoadMore.bind(this) : this.androidRenderLoadMore.bind(this),
			});
		}

		/**
		 * @param {object} item
		 * @return object
		 */
		setItemEntity(item)
		{
			return { isYou: item.isYou, isCopilot: item.isCopilot, isManager: item.isManager };
		}

		buildItems()
		{
			const { participants, permissions: { isCanAddParticipants: isCanAdd } } = this.state;
			const doneItems = [];

			if (participants.length === 0)
			{
				return doneItems;
			}

			if (isCanAdd)
			{
				doneItems.push({
					type: 'addrow',
					key: '-1',
				});
			}

			participants.forEach((item, index) => {
				doneItems.push(this.setStyleItem(item, index));
			});

			return doneItems;
		}

		/**
		 * @param {object} item
		 * @param {number} index
		 * @return object
		 */
		setStyleItem(item, index)
		{
			return {
				type: 'item',
				key: index.toString(),
				userId: item.id,
				title: item.title,
				isYou: item.isYou,
				isCopilot: item.isCopilot,
				isManager: item.isManager,
				isYouTitle: item.isYouTitle,
				subtitle: item.desc,
				avatarUri: item.imageUrl,
				avatarColor: item.imageColor,
				// TODO: switch to ChatAvatar for CoPilot
				avatar: item.isCopilot ? null : ChatAvatar.createFromDialogId(item.id).getListItemAvatarProps(),
				status: item.statusSvg,
				crownStatus: item.crownStatus,
				isSuperEllipseAvatar: item.isSuperEllipseAvatar,

				style: {
					parentView: {
						backgroundColor: withPressed(Theme.colors.bgContentPrimary),
					},
					itemContainer: {
						flexDirection: 'row',
						alignItems: 'center',
						marginHorizontal: 14,
					},
					avatarContainer: {
						marginTop: 6,
						marginBottom: 6,
						paddingHorizontal: 2,
						paddingVertical: 3,
						position: 'relative',
						zIndex: 1,
						flexDirection: 'column',
						justifyContent: item.statusSvg.length > 1 ? 'flex-end' : 'flex-start',
					},
					itemInfoContainer: {
						flexDirection: 'row',
						borderBottomWidth: 1,
						borderBottomColor: Theme.colors.bgSeparatorSecondary,
						flex: 1,
						alignItems: 'center',
						marginBottom: 6,
						marginTop: 6,
						height: '100%',
						marginLeft: 16,
					},
					itemInfo: {
						mainContainer: {
							flex: 1,
							marginRight: '5%',
						},
						title: {
							marginBottom: 4,
							fontSize: 16,
							fontWeight: 500,
							color: item.id ? ChatTitle.createFromDialogId(item.id).getTitleColor() : Theme.colors.base1,
						},
						isYouTitle: {
							marginLeft: 4,
							marginBottom: 4,
							fontSize: 16,
							color: Theme.colors.base4,
							fontWeight: 400,
						},
						subtitle: {
							color: Theme.colors.base3,
							fontSize: 14,
							fontWeight: 400,
							textStyle: 'normal',
							align: 'baseline',
						},
					},
				},
			};
		}

		/**
		 * @desc Returns view a row element with added btn
		 * @return {LayoutComponent}
		 * @protected
		 */
		getAddParticipantRow()
		{
			const userHelper = UserHelper.createByUserId(MessengerParams.getUserInfo().id);
			if (!userHelper)
			{
				return;
			}

			const isCreateGroupChat = !this.props.isCopilot && !this.isGroupDialog();
			if (userHelper.isExtranetOrCollaber && isCreateGroupChat)
			{
				return;
			}

			let text = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_ROW_MSGVER_1');
			if (isCreateGroupChat)
			{
				text = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_ROW_GROUP');
			}

			const buttonIcon = buttonIcons.specialAdd();

			return View(
				{
					style: {
						flexDirection: 'column',
						backgroundColor: Theme.colors.bgContentPrimary,
					},
					clickable: false,
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
							marginHorizontal: 14,
							borderBottomWidth: 1,
							borderBottomColor: Theme.colors.bgSeparatorSecondary,
						},
						onClick: () => {
							this.onClickButtonAdd();
						},
					},
					View(
						{},
						Image({
							style: {
								width: 44,
								height: 44,
								marginBottom: 6,
								marginTop: 6,
								marginHorizontal: 2,
								borderRadius: 22,
							},
							svg: { content: buttonIcon },
							onFailure: () => {
								logger.error('SidebarParticipantsView.getAddParticipantRow.Image.onFailure');
							},
						}),
					),
					View(
						{
							style: {
								flexDirection: 'row',
								flexGrow: 2,
								alignItems: 'center',
								marginBottom: 6,
								marginTop: 6,
								height: '100%',
							},
						},
						View(
							{
								style: {
									marginLeft: 11,
								},
							},
							View(
								{
									style: {
										flexDirection: 'column',
										justifyContent: 'flex-start',
									},
								},
								View(
									{
										style: {
											flexDirection: 'row',
											alignItems: 'flex-start',
											justifyContent: 'flex-start',
										},
									},
									Text({
										style: {
											marginBottom: 2,
											fontSize: 16,
											fontWeight: '400',
											color: Theme.colors.base2,
										},
										text,
										ellipsize: 'end',
										numberOfLines: 1,
									}),
								),
							),
						),
					),
				),
			);
		}

		/**
		 * @desc check is add ellipsis button
		 * @param {object} item
		 * @return {boolean}
		 * @protected
		 */
		isEllipsis(item)
		{
			let isEllipsis = item.isYou || this.isGroupDialog();
			if (item.isCopilot)
			{
				isEllipsis = false;
			}

			return isEllipsis;
		}

		onClickButtonAdd()
		{
			this.openParticipantsAddWidget();
		}

		/**
		 * @desc Handler load more event by scroll down ( staring rest call participants with pagination )
		 * @void
		 * @protected
		 */
		onLoadScrollItems()
		{
			this.participantsService.sidebarRestService.getParticipantList()
				.catch((err) => logger.error('SidebarParticipantsView.onLoadScrollItems', err));
		}

		/**
		 * @desc Handler remove participant
		 * @param {object} event
		 * @param {string} event.key  - string key item
		 * @void
		 * @protected
		 */
		onClickRemoveParticipant(event)
		{
			setTimeout(() => {
				navigator.notification.confirm(
					'',
					(buttonId) => {
						if (buttonId === 2)
						{
							const {
								key,
								userId,
							} = event;
							const itemPos = this.listViewRef.getElementPosition(key);
							this.removeParticipant(itemPos.index, itemPos.section, userId);
						}
					},
					Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_REMOVE_PARTICIPANT_CONFIRM_TITLE'),
					[
						Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_REMOVE_PARTICIPANT_CONFIRM_NO'),
						Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_REMOVE_PARTICIPANT_CONFIRM_YES'),
					],
				);
			}, 10);
		}

		/**
		 * @desc Handler leave chat
		 * @void
		 * @protected
		 */
		onClickLeaveChat()
		{
			showLeaveChatAlert({
				leaveCallback: () => {
					this.participantsService.onClickLeaveChat()
						.catch((error) => logger.error(`${this.constructor.name}.onHeaderMenuLeaveDialog`, error));
				},
			});
		}

		/**
		 * @desc Remove participant
		 * @param {number} index
		 * @param {number} section
		 * @param {number} userId
		 * @void
		 * @protected
		 */
		removeParticipant(index, section, userId)
		{
			this.participantsService.deleteParticipant(userId)
				.catch((errors) => {
					if (errors[0]?.code === ErrorType.dialog.delete.userInvitedFromStructure)
					{
						Notification.showToastWithParams({
							message: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_USER_INVITED_FROM_STRUCTURE_DELETE_ERROR'),
							backgroundColor: Theme.colors.accentMainAlert,
						});
					}
				})
			;
		}

		/**
		 * @desc Add participants
		 * @param {number} index
		 * @param {number} section
		 * @param {Array<object>} participants
		 * @void
		 * @protected
		 */
		addParticipants(index, section, participants)
		{
			this.listViewRef.insertRows(participants, section, index, 'automatic')
				.catch((err) => logger.error(err));
		}

		/**
		 * @desc Handler long click item
		 * @param {string} key
		 * @param {number} userId
		 * @param {object} isEntity
		 * @param {boolean} isEntity.isYou
		 * @param {boolean?} isEntity.isCopilot
		 * @param {boolean?} isEntity.isManager
		 * @param {LayoutComponent} ref
		 * @protected
		 */
		onLongClickItem(key, userId, isEntity, ref)
		{
			const actionsItems = [];
			const isGroupDialog = this.isGroupDialog();
			const participantsCount = this.state.participants.length;

			if (isEntity.isCopilot)
			{
				return false;
			}

			if (isGroupDialog)
			{
				if (isEntity.isYou)
				{
					actionsItems.push({
						id: SidebarActionType.notes,
						title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_NOTES'),
						callback: this.participantsService.onClickGetNotes,
						icon: Icon.FLAG,
						testId: 'SIDEBAR_USER_CONTEXT_MENU_NOTES',
					});
					if (this.state.permissions.isCanLeave)
					{
						// TODO copilot dialog always is group chat, then need check count participants
						if (this.props.isCopilot && participantsCount > 2)
						{
							actionsItems.push({
								id: SidebarActionType.leave,
								title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_LEAVE'),
								callback: this.onClickLeaveChat.bind(this),
								icon: Icon.DAY_OFF,
								testId: 'SIDEBAR_USER_CONTEXT_MENU_LEAVE',
							});
						}

						if (!this.props.isCopilot)
						{
							actionsItems.push({
								id: SidebarActionType.leave,
								title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_LEAVE'),
								callback: this.onClickLeaveChat.bind(this),
								icon: Icon.DAY_OFF,
								testId: 'SIDEBAR_USER_CONTEXT_MENU_LEAVE',
							});
						}
					}
				}
				else
				{
					actionsItems.push({
						id: SidebarActionType.mention,
						title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_MENTION'),
						callback: this.participantsService.onClickPingUser.bind(this, userId),
						icon: Icon.MENTION,
						testId: 'SIDEBAR_USER_CONTEXT_MENU_MENTION',
					}, {
						id: SidebarActionType.send,
						title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_SEND'),
						callback: this.participantsService.onClickSendMessage.bind(this, userId),
						icon: Icon.MESSAGE,
						testId: 'SIDEBAR_USER_CONTEXT_MENU_SEND',
					});

					const isCurrentUserOwner = DialogHelper.createByDialogId(this.props.dialogId)?.isCurrentUserOwner;
					if (isCurrentUserOwner)
					{
						if (isEntity.isManager)
						{
							actionsItems.push({
								id: SidebarActionType.channelRemoveManager,
								title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_CHANNEL_REMOVE_MANAGER'),
								callback: this.participantsService.onClickRemoveManager.bind(this, userId),
								icon: Icon.CIRCLE_CROSS,
								testId: 'SIDEBAR_USER_CONTEXT_MENU_CHANNEL_REMOVE_MANAGER',
							});
						}
						else
						{
							actionsItems.push({
								id: SidebarActionType.channelAddManager,
								title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_CHANNEL_ADD_MANAGER'),
								callback: this.participantsService.onClickAddManager.bind(this, userId),
								icon: Icon.CROWN,
								testId: 'SIDEBAR_USER_CONTEXT_MENU_CHANNEL_ADD_MANAGER',
							});
						}
					}

					const isCanDelete = this.state.permissions.isCanRemoveParticipants;
					if (isCanDelete && ChatPermission.isCanRemoveUserById(userId, this.props.dialogId))
					{
						actionsItems.push({
							id: SidebarActionType.remove,
							title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_REMOVE'),
							callback: this.onClickRemoveParticipant.bind(this, {
								key,
								userId,
							}),
							icon: Icon.BAN,
							testId: 'SIDEBAR_USER_CONTEXT_MENU_REMOVE',
						});
					}
				}
			}

			if (!isGroupDialog)
			{
				if (isEntity.isYou)
				{
					actionsItems.push({
						id: SidebarActionType.notes,
						title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_NOTES'),
						callback: this.participantsService.onClickGetNotes,
						icon: Icon.FLAG,
						testId: 'SIDEBAR_USER_CONTEXT_MENU_NOTES',
					});
				}
				else
				{
					return false;
				}
			}

			return this.openParticipantManager(ref, actionsItems);
		}

		/**
		 * @param {Array<ActionItem>} actionsItems
		 * @param {LayoutComponent} ref
		 */
		openParticipantManager(ref, actionsItems = [])
		{
			return ParticipantManager.open({ actionsItems, ref });
		}

		/**
		 * @desc Handler click item
		 * @param {number} userId
		 * @protected
		 */
		onClickItem(userId)
		{
			if (Type.isUndefined(userId))
			{
				return false;
			}

			const isBot = this.participantsService.isBotById(userId);
			if (isBot)
			{
				return false;
			}

			return UserProfile.show(userId, {
				backdrop: true,
				openingDialogId: this.props.dialogId,
			});
		}

		getEllipsisButton(item)
		{
			const setItemEntity = this.setItemEntity.bind(this);
			const onLongClickItem = this.onLongClickItem.bind(this);

			return {
				create()
				{
					const imageButtonProps = {
						style: {
							width: 24,
							height: 24,
						},
						onClick: () => {
							onLongClickItem(item.key, item.userId, setItemEntity(item), this.viewRef);
						},
						testId: 'ITEM_ELLIPSIS_BUTTON',
					};

					if (MobileFeature.isAirStyleSupported())
					{
						imageButtonProps.iconName = Icon.MORE.getIconName();
					}
					else
					{
						imageButtonProps.svg = {
							content: buttonIcons.ellipsis(),
						};
					}

					return View(
						{
							ref: (ref) => {
								if (ref)
								{
									this.viewRef = ref;
								}
							},
							style: {
								alignSelf: 'center',
							},
						},
						ImageButton(imageButtonProps),
					);
				},
			};
		}

		/**
		 * @desc Call user add widget
		 * @void
		 * @protected
		 */
		async openParticipantsAddWidget()
		{
			logger.log(`${this.constructor.name}.onClickBtnAdd`);

			const dialog = this.store.getters['dialoguesModel/getById'](this.props.dialogId);
			if (!dialog)
			{
				return Promise.reject(new Error('openParticipantsAddWidget: unknown dialog'));
			}

			this.sendAnalyticsAboutOpeningParticipantAddWidget();

			if (dialog.type === DialogType.collab)
			{
				const collabId = this.store.getters['dialoguesModel/collabModel/getCollabIdByDialogId'](this.props.dialogId);
				if (Type.isNumber(collabId))
				{
					const { openCollabInvite, CollabInviteAnalytics } = await requireLazy('collab/invite');
					openCollabInvite({
						collabId,
						analytics: new CollabInviteAnalytics()
							.setSection(CollabInviteAnalytics.Section.CHAT_SIDEBAR)
							.setChatId(dialog.chatId),
					});
				}

				return Promise.resolve();
			}

			SocialNetworkUserSelector.make({
				initSelectedIds: [],
				createOptions: {
					enableCreation: false,
				},
				allowMultipleSelection: true,
				closeOnSelect: true,
				provider: {
					context: 'IMMOBILE_SIDEBAR_ADD_PARTICIPANTS',
					options: {
						recentItemsLimit: 20,
						maxUsersInRecentTab: 20,
					},
				},
				events: {
					onClose: this.onCloseUserSelector,
				},
				widgetParams: {
					title: this.getUserAddWidgetTitle(),
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
					},
					sendButtonName: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_NAME_BTN'),
				},
			})
				.show({})
				.catch(logger.error);
		}

		sendAnalyticsAboutOpeningParticipantAddWidget()
		{
			AnalyticsService.getInstance().sendUserAddButtonClicked({ dialogId: this.props.dialogId });
		}

		getUserAddWidgetTitle()
		{
			return Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_TITLE_MSGVER_1');
		}

		androidOnLoadMore()
		{
			if (this.state.participants.length > 0)
			{
				this.loader.disable();
			}

			const participantsCount = this.participantsService.getUserCounter();
			if (this.state.participants.length < participantsCount)
			{
				this.loader.enable();
				this.onLoadScrollItems();
			}
		}

		iosOnLoadMore()
		{
			const participantsCount = this.participantsService.getUserCounter();
			if (this.state.participants.length < participantsCount)
			{
				this.onLoadScrollItems();
			}
		}

		iosRenderLoadMore()
		{
			const participantsCount = this.participantsService.getUserCounter();
			if (this.state.participants.length >= participantsCount)
			{
				return null;
			}

			return this.loader;
		}

		androidRenderLoadMore()
		{
			return this.loader;
		}

		isGroupDialog()
		{
			return DialogHelper.isDialogId(this.props.dialogId);
		}

		isCopilotGroupDialog()
		{
			return this.state.participants.length > 2;
		}

		scrollToBegin()
		{
			this.listViewRef?.scrollToBegin(true);
		}

		/**
		 * @param {Array<object>} selectedUsers
		 */
		onCloseUserSelector(selectedUsers)
		{
			const isCreateChat = !this.isGroupDialog();
			const addUsersIds = selectedUsers.map((user) => user.id);
			const currentUsersIdsList = new Set(
				this.state.participants.map((user) => user.id),
			);
			const uniqueId = addUsersIds.filter((id) => !currentUsersIdsList.has(id));
			if (uniqueId.length > 0)
			{
				if (isCreateChat)
				{
					this.participantsService.sidebarRestService.addChat([...uniqueId, ...currentUsersIdsList])
						.catch((error) => logger.log(
							`${this.constructor.name}.sidebarRestService.addChat.catch:`,
							error,
						));
				}
				else
				{
					this.participantsService.sidebarRestService.addParticipants(uniqueId)
						.catch((error) => logger.log(
							`${this.constructor.name}.sidebarRestService.addParticipants.catch:`,
							error,
						));
				}
			}
		}
	}

	module.exports = { SidebarParticipantsView };
});
