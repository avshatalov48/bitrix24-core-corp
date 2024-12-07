/**
 * @module im/messenger/controller/sidebar/tabs/participants/participants-view
 */
jn.define('im/messenger/controller/sidebar/tabs/participants/participants-view', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Item } = require('im/messenger/lib/ui/base/item');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { withPressed } = require('utils/color');
	const { LoaderItem } = require('im/messenger/lib/ui/base/loader');
	const { ParticipantManager } = require('im:messenger/controller/participant-manager');
	const { buttonIcons } = require('im/messenger/assets/common');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const { ParticipantsService } = require('im/messenger/controller/sidebar/tabs/participants/participants-service');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { UserAdd } = require('im/messenger/controller/user-add');
	const { ChatTitle } = require('im/messenger/lib/element');
	const { BotCode } = require('im/messenger/const');
	const AppTheme = require('apptheme');

	/**
	 * @class SidebarParticipantsView
	 * @typedef {LayoutComponent<SidebarParticipantsViewProps, SidebarParticipantsViewState>} SidebarParticipantsView
	 */
	class SidebarParticipantsView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.store = serviceLocator.get('core').getStore();
			this.storeManager = serviceLocator.get('core').getStoreManager();
			/** @type {ParticipantsService} */
			this.participantsService = new ParticipantsService(this.props);

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

		componentDidMount()
		{
			Logger.log('Participants.view.componentDidMount');
			this.bindListener();
			this.subscribeStoreEvents();
		}

		componentDidUpdate()
		{
			Logger.log('Participants.view.componentDidUpdate');
		}

		componentWillUnmount()
		{
			Logger.log('Participants.view.componentWillUnmount');
			this.unsubscribeStoreEvents();
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindListener()
		{
			this.onUpdateDialogStore = this.onUpdateDialogStore.bind(this);
			this.unsubscribeStoreEvents = this.unsubscribeStoreEvents.bind(this);
		}

		/**
		 * @desc Handler dialog store update
		 * @param {object} event
		 * @void
		 */
		onUpdateDialogStore(event)
		{
			Logger.info('Sidebar.Tab.Participants.onUpdateStore---------->', event);
			const { payload } = event;

			if (payload.actionName === 'addParticipants' || payload.actionName === 'removeParticipants')
			{
				const newParticipants = this.participantsService.getParticipantsFromStore();
				this.updateState({ participants: newParticipants });
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
			Logger.log('Sidebar.Tab.Participants.subscribeStoreEvents');
			this.storeManager.on('dialoguesModel/update', this.onUpdateDialogStore);
			BX.addCustomEvent('onCloseSidebarWidget', this.unsubscribeStoreEvents);
		}

		unsubscribeStoreEvents()
		{
			Logger.log('Sidebar.Tab.Participants.unsubscribeStoreEvents');
			this.storeManager.off('dialoguesModel/update', this.onUpdateDialogStore);
			BX.removeCustomEvent('onCloseSidebarWidget', this.unsubscribeStoreEvents);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						backgroundColor: AppTheme.colors.bgContentPrimary,
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
						onLongClick: () => {
							this.onLongClickItem(
								item.key,
								item.userId,
								{ isYou: item.isYou, isCopilot: item.isCopilot },
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

		setStyleItem(item, index)
		{
			return {
				type: 'item',
				key: index.toString(),
				userId: item.id,
				title: item.title,
				isYou: item.isYou,
				isCopilot: item.isCopilot,
				isYouTitle: item.isYouTitle,
				subtitle: item.desc,
				avatarUri: item.imageUrl,
				avatarColor: item.imageColor,
				status: item.statusSvg,
				crownStatus: item.crownStatus,
				isSuperEllipseAvatar: item.isSuperEllipseAvatar,

				style: {
					parentView: {
						backgroundColor: withPressed(AppTheme.colors.bgContentPrimary),
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
						borderBottomColor: AppTheme.colors.bgSeparatorSecondary,
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
							color: item.id ? ChatTitle.createFromDialogId(item.id).getTitleColor() : AppTheme.colors.base1,
						},
						isYouTitle: {
							marginLeft: 4,
							marginBottom: 4,
							fontSize: 16,
							color: AppTheme.colors.base4,
							fontWeight: 400,
						},
						subtitle: {
							color: AppTheme.colors.base3,
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
		 * @private
		 */
		getAddParticipantRow()
		{
			let text = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_ROW_MSGVER_1');
			if (!this.props.isCopilot && !this.isGroupDialog())
			{
				text = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_ROW_GROUP');
			}

			const buttonIcon = this.props.isCopilot
				? buttonIcons.specialAdd(AppTheme.colors.chatOtherCopilot1, AppTheme.colors.accentMainCopilot)
				: buttonIcons.specialAdd();

			return View(
				{
					style: {
						flexDirection: 'column',
						backgroundColor: AppTheme.colors.bgContentPrimary,
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
							borderBottomColor: AppTheme.colors.bgSeparatorSecondary,
						},
						onClick: () => {
							this.onClickBtnAdd();
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
								Logger.error('SidebarParticipantsView.getAddParticipantRow.Image.onFailure');
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
											color: AppTheme.colors.base2,
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
		 * @private
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

		onClickBtnAdd()
		{
			this.callParticipantsAddWidget();
		}

		/**
		 * @desc Handler load more event by scroll down ( staring rest call participants with pagination )
		 * @void
		 * @private
		 */
		onLoadScrollItems()
		{
			this.participantsService.sidebarRestService.getParticipantList()
				.catch((err) => Logger.error('SidebarParticipantsView.onLoadScrollItems', err));
		}

		/**
		 * @desc Handler remove participant
		 * @param {object} event
		 * @param {string} event.key  - string key item
		 * @void
		 * @private
		 */
		onClickRemoveParticipant(event)
		{
			setTimeout(() => {
				navigator.notification.confirm(
					'',
					(buttonId) => {
						if (buttonId === 2)
						{
							const { key } = event;
							const itemPos = this.listViewRef.getElementPosition(key);
							this.removeParticipant(itemPos.index, itemPos.section);
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
		 * @private
		 */
		onClickLeaveChat()
		{
			setTimeout(() => {
				navigator.notification.confirm(
					'',
					(buttonId) => {
						if (buttonId === 2)
						{
							this.participantsService.onClickLeaveChat();
						}
					},
					Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_LEAVE_CHAT_CONFIRM_TITLE'),
					[
						Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_LEAVE_CHAT_CONFIRM_NO'),
						Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_LEAVE_CHAT_CONFIRM_YES'),
					],
				);
			}, 10);
		}

		/**
		 * @desc Remove participant
		 * @param {number} index
		 * @param {number} section
		 * @void
		 * @private
		 */
		removeParticipant(index, section)
		{
			const indexWithoutAddedRow = this.state.permissions.isCanAddParticipants ? index - 1 : index;
			const deletedUser = this.state.participants.find((el, i) => i === indexWithoutAddedRow);
			const onComplete = () => {
				this.state.participants = this.state.participants.filter((el, i) => i !== indexWithoutAddedRow);

				this.participantsService.deleteParticipant(deletedUser.id);
			};
			this.listViewRef.deleteRow(section, index, 'automatic', onComplete);
		}

		/**
		 * @desc Add participants
		 * @param {number} index
		 * @param {number} section
		 * @param {Array<object>} participants
		 * @void
		 * @private
		 */
		addParticipants(index, section, participants)
		{
			this.listViewRef.insertRows(participants, section, index, 'automatic')
				.catch((err) => Logger.error(err));
		}

		/**
		 * @desc Handler long click item
		 * @param {string} key
		 * @param {number} userId
		 * @param {object} isEntity
		 * @param {boolean} isEntity.isYou
		 * @param {boolean} isEntity.isCopilot
		 * @private
		 */
		onLongClickItem(key, userId, isEntity)
		{
			const actions = [];
			const callbacks = {};
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
					actions.push('notes');
					callbacks.notes = this.participantsService.onClickGetNotes;
					if (this.state.permissions.isCanLeave)
					{
						// TODO copilot dialog always is group chat, then need check count participants
						if (this.props.isCopilot && participantsCount > 2)
						{
							actions.push('leave');
							callbacks.leave = this.onClickLeaveChat.bind(this);
						}

						if (!this.props.isCopilot)
						{
							actions.push('leave');
							callbacks.leave = this.onClickLeaveChat.bind(this);
						}
					}
				}
				else
				{
					actions.push('mention');
					callbacks.mention = this.participantsService.onClickPingUser.bind(this, userId);
					actions.push('send');
					callbacks.send = this.participantsService.onClickSendMessage.bind(this, userId);

					const isCanDelete = this.state.permissions.isCanRemoveParticipants;
					if (isCanDelete && ChatPermission.isCanRemoveUserById(userId, this.props.dialogId))
					{
						actions.push('remove');
						callbacks.remove = this.onClickRemoveParticipant.bind(this, { key });
					}
				}
			}

			if (!isGroupDialog)
			{
				if (isEntity.isYou)
				{
					actions.push('notes');
					callbacks.notes = this.participantsService.onClickGetNotes;
				}
				else
				{
					return false;
				}
			}

			return ParticipantManager.open({ actions, callbacks });
		}

		/**
		 * @desc Handler click item
		 * @param {number} userId
		 * @private
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

			return UserProfile.show(userId, { backdrop: true });
		}

		getEllipsisButton(item)
		{
			return View(
				{
					style: {
						alignSelf: 'center',
					},
				},
				ImageButton({
					style: {
						width: 24,
						height: 24,
					},
					svg: { content: buttonIcons.ellipsis() },
					onClick: () => {
						this.onLongClickItem(item.key, item.userId, { isYou: item.isYou, isCopilot: item.isCopilot });
					},
					testId: 'ITEM_ELLIPSIS_BUTTON',
				}),
			);
		}

		/**
		 * @desc Call user add widget
		 * @void
		 * @private
		 */
		callParticipantsAddWidget()
		{
			const botFilter = (user) => {
				if (user?.botData?.code)
				{
					return user?.botData?.code === BotCode.copilot;
				}

				return true;
			};

			const copilotFilter = (user) => {
				if (user?.botData?.code)
				{
					return user?.botData?.code !== BotCode.copilot;
				}

				return true;
			};

			const usersCustomFilter = this.props.isCopilot ? botFilter : copilotFilter;

			UserAdd.open(
				{
					dialogId: this.props.dialogId,
					title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_TITLE_MSGVER_1'),
					textRightBtn: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_NAME_BTN'),
					callback: {
						onAddUser: (event) => Logger.log('onAddParticipantInBackDrop', event),
					},
					widgetOptions: { mediumPositionPercent: 65 },
					usersCustomFilter,
					isCopilotDialog: this.props.isCopilot,
				},
			);
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
	}

	module.exports = { SidebarParticipantsView };
});
