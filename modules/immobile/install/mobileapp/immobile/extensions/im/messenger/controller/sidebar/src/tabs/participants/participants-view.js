/**
 * @module im/messenger/controller/sidebar/tabs/participants/participants-view
 */
jn.define('im/messenger/controller/sidebar/tabs/participants/participants-view', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { core } = require('im/messenger/core');
	const { Item } = require('im/messenger/lib/ui/base/item');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { withPressed } = require('utils/color');
	const { LoaderItem } = require('im/messenger/lib/ui/base/loader');
	const { ParticipantManager } = require('im:messenger/controller/participant-manager');
	const { buttonIcons } = require('im/messenger/assets/common');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const { ParticipantsService } = require('im/messenger/controller/sidebar/tabs/participants/participants-service');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { UserAdd } = require('im/messenger/controller/user-add');

	/**
	 * @class SidebarParticipantsView
	 * @typedef {LayoutComponent<SidebarParticipantsViewProps, SidebarParticipantsViewState>} SidebarParticipantsView
	 */
	class SidebarParticipantsView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.store = core.getStore();
			this.storeManager = core.getStoreManager();

			this.state = {
				participants: [],
				permissions: {
					isCanRemoveParticipants: ChatPermission.isCanRemoveParticipants(props.dialogId),
					isCanAddParticipants: true,
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
			this.participantsService = new ParticipantsService(this.props);

			if (this.state.participants.length === 0)
			{
				const participants = this.participantsService.getParticipants();

				if (participants.length > 0)
				{
					this.updateState({ participants });
				}
			}
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
				if (eventParticipants && eventParticipants.length !== currentParticipants.length)
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
					const isEllipsis = item.isYou || this.isGroupDialog();

					return new Item({
						data: item,
						size: 'M',
						isCustomStyle: true,
						nextTo: false,
						isEllipsis,
						onLongClick: () => {
							this.onLongClickItem(item.key, item.userId, item.isYou);
						},
						onEllipsisClick: () => {
							this.onLongClickItem(item.key, item.userId, item.isYou);
						},
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
				isYouTitle: item.isYouTitle,
				subtitle: item.desc,
				avatarUri: item.imageUrl,
				avatarColor: item.imageColor,
				status: item.statusSvg,
				crownStatus: item.crownStatus,

				style: {
					parentView: {
						backgroundColor: Application.getPlatform() === 'ios'
							? '#FFFFFF'
							: withPressed('#FFFFFF'),
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
						justifyContent: 'flex-end',
					},
					itemInfoContainer: {
						flexDirection: 'row',
						borderBottomWidth: 1,
						borderBottomColor: '#e9e9e9',
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
						},
						isYouTitle: {
							marginLeft: 4,
							marginBottom: 4,
							fontSize: 16,
							color: '#959CA4',
							fontWeight: 400,
						},
						subtitle: {
							color: '#959CA4',
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
		 * @desc Returns view row element with add btn
		 * @return {LayoutComponent}
		 * @private
		 */
		getAddParticipantRow()
		{
			const text = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_ROW');

			return View(
				{
					style: {
						flexDirection: 'column',
						backgroundColor: '#FFFFFF',
					},
					clickable: false,
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
							marginHorizontal: 14,
						},
						onClick: () => {
							this.onClickBtnAdd();
						},
					},
					View(
						{
							style: {
								borderBottomWidth: 1,
								borderBottomColor: '#e9e9e9',
							},
						},
						Image({
							style: {
								width: 44,
								height: 44,
								marginBottom: 6,
								marginTop: 6,
								marginHorizontal: 2,
								borderRadius: 22,
							},
							svg: { content: buttonIcons.specialAdd() },
							onFailure: () => {
								Logger.error('SidebarParticipantsView.getAddParticipantRow.Image.onFailure');
							},
						}),
					),
					View(
						{
							style: {
								flexDirection: 'row',
								borderBottomWidth: 1,
								borderBottomColor: '#e9e9e9',
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
									marginLeft: 14,
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
											color: '#525C69',
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
			const orderUsers = this.state.participants.sort((a, b) => a.id - b.id);
			const lastUser = orderUsers[orderUsers.length - 1];
			if (!Type.isUndefined(lastUser))
			{
				this.participantsService.sidebarRestService.getParticipantList(lastUser.id)
					.catch((err) => Logger.error('SidebarParticipantsView.onLoadScrollItems', err));
			}
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
			const indexWithoutAddedRow = index - 1;
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
		 * @param {boolean} isYou
		 * @private
		 */
		onLongClickItem(key, userId, isYou)
		{
			const actions = [];
			const callbacks = {};
			const isGroupDialog = this.isGroupDialog();

			if (isGroupDialog)
			{
				if (isYou)
				{
					actions.push('notes');
					callbacks.notes = this.participantsService.onClickGetNotes;
					actions.push('leave');
					callbacks.leave = this.onClickLeaveChat.bind(this);
				}
				else
				{
					// actions.push('mention');
					// callbacks.mention = this.participantsService.onClickPingUser.bind(this, { key });
					actions.push('send');
					callbacks.send = this.participantsService.onClickSendMessage.bind(this, userId);

					if (this.state.permissions.isCanRemoveParticipants)
					{
						actions.push('remove');
						callbacks.remove = this.onClickRemoveParticipant.bind(this, { key });
					}
				}
			}

			if (!isGroupDialog)
			{
				if (isYou)
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
		 * @desc Call user add widget
		 * @void
		 * @private
		 */
		callParticipantsAddWidget()
		{
			UserAdd.open(
				{
					dialogId: this.props.dialogId,
					title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_TITLE'),
					textRightBtn: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_NAME_BTN'),
					callback: {
						onAddUser: (event) => Logger.log('onAddParticipantInBackDrop', event),
					},
					widgetOptions: { mediumPositionPercent: 65 },
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
		{}

		iosRenderLoadMore()
		{
			if (this.state.participants.length > 0)
			{
				return null;
			}

			const participantsCount = this.participantsService.getUserCounter();
			if (this.state.participants.length < participantsCount)
			{
				this.onLoadScrollItems();
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
	}

	module.exports = { SidebarParticipantsView };
});
