/**
 * @module im/messenger/controller/chat-composer/lib/view/managers
 */
jn.define('im/messenger/controller/chat-composer/lib/view/managers', (require, exports, module) => {
	const { Loc } = require('loc');
	const { isEqual } = require('utils/object');
	const { withPressed } = require('utils/color');
	const { Icon } = require('assets/icons');
	const { Theme } = require('im/lib/theme');
	const { Item } = require('im/messenger/lib/ui/base/item');
	const { buttonIcons } = require('im/messenger/assets/common');
	const { SidebarActionType, EventType, ComponentCode } = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('chat-composer--managers-view');
	const { ParticipantManager } = require('im/messenger/controller/participant-manager');

	/**
	 * @class ManagersView
	 * @typedef {LayoutComponent<ManagersViewProps, ManagersViewState>} ManagersView
	 */
	class ManagersView extends LayoutComponent
	{
		/**
		 * @constructor
		 * @param {ManagersViewProps} props
		 */
		constructor(props)
		{
			super(props);
			this.state = { users: this.props.users };
		}

		componentWillUnmount()
		{
			logger.log(`${this.constructor.name}.componentWillUnmount`);
			this.props.callbacks.onDestroyView();
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			if (nextState.users.length !== this.state.users.length) // early exit from check
			{
				return true;
			}

			if (nextState.users.length === this.state.users.length)
			{
				const oldManagersIdsList = new Set(this.state.users.map((user) => user.id));
				for (const user of nextState.users)
				{
					if (!oldManagersIdsList.has(user.id))
					{
						return true;
					}
				}
			}

			return !isEqual(this.props, nextProps);
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
								item.isYou,
								ref,
							);
						},
						additionalComponent: this.getEllipsisButton(item),
					});
				},
			});
		}

		buildItems()
		{
			const { users } = this.state;
			const doneItems = [];

			doneItems.push({
				type: 'addrow',
				key: '-1',
			});

			users.forEach((item, index) => {
				doneItems.push(this.setStyleItem(item, index));
			});

			return doneItems;
		}

		/**
		 * @param {object} item
		 * @return {LayoutComponent}
		 */
		getEllipsisButton(item)
		{
			const onLongClickItem = this.onLongClickItem.bind(this);

			return {
				create()
				{
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
						ImageButton({
							style: {
								width: 24,
								height: 24,
							},
							svg: { content: buttonIcons.ellipsis() },
							onClick: () => {
								onLongClickItem(item.key, item.userId, item.isYou, this.viewRef);
							},
							testId: 'ITEM_ELLIPSIS_BUTTON_MANAGERS_VIEW',
						}),
					);
				},
			};
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
				isYouTitle: item.isYouTitle,
				subtitle: item.desc,
				avatarUri: item.imageUrl,
				avatarColor: item.imageColor,
				status: item.statusSvg,
				crownStatus: item.crownStatus,

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
							color: Theme.colors.base1,
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
		 * @private
		 */
		getAddParticipantRow()
		{
			const text = Loc.getMessage('IMMOBILE_CHAT_COMPOSER_MANAGERS_ADD_ROW');
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
							this.onClickAddManager();
						},
						testId: 'ADD_MANAGER_BUTTON',
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
								logger.error(`${this.constructor.name}.getAddParticipantRow.Image.onFailure`);
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
		 * @desc Handler long click item
		 * @param {string} key
		 * @param {number} userId
		 * @param {boolean} isYou
		 * @param {LayoutComponent} ref
		 * @private
		 */
		onLongClickItem(key, userId, isYou, ref)
		{
			const actionsItems = [];

			actionsItems.push({
				id: SidebarActionType.send,
				title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_SEND'),
				callback: this.onClickSendMessage.bind(this, userId),
				icon: Icon.MESSAGE,
				testId: 'SIDEBAR_USER_CONTEXT_MENU_SEND',
			});

			if (!isYou)
			{
				actionsItems.push({
					id: SidebarActionType.commonRemoveManager,
					title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_COMMON_REMOVE_MANAGER'),
					callback: this.onClickRemoveManager.bind(this, userId),
					icon: Icon.CIRCLE_CROSS,
					testId: 'SIDEBAR_USER_CONTEXT_MENU_COMMON_REMOVE_MANAGER',
				});
			}

			return this.openParticipantManager(ref, actionsItems);
		}

		/**
		 * @param {LayoutComponent} ref
		 * @param {Array<ActionItem>} actionsItems
		 */
		openParticipantManager(ref, actionsItems = [])
		{
			return ParticipantManager.open({ actionsItems, ref });
		}

		/**
		 * @desc Handler on click send user
		 * @param {number} userId
		 * @return void
		 */
		onClickSendMessage(userId)
		{
			MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId: userId }, ComponentCode.imMessenger);
		}

		/**
		 * @desc Handler on click remove
		 * @param {number} userId
		 * @return void
		 */
		onClickRemoveManager(userId)
		{
			logger.log(`${this.constructor.name}.onClickRemoveManager`, userId);
			this.props.callbacks.onClickRemoveManager(userId);
		}

		/**
		 * @desc Handler on click add
		 * @return void
		 */
		onClickAddManager()
		{
			logger.log(`${this.constructor.name}.onClickAddManager`);
			this.props.callbacks.onClickAddManager();
		}
	}

	module.exports = { ManagersView };
});
