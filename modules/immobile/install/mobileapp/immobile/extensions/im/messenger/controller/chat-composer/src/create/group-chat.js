/**
 * @module im/messenger/controller/chat-composer/create/group-chat
 */
jn.define('im/messenger/controller/chat-composer/create/group-chat', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { NotifyManager } = require('notify-manager');
	const { isEqual } = require('utils/object');

	const { NestedDepartmentSelector } = require('selector/widget/entity/tree-selectors/nested-department-selector');
	const {
		DialogType,
		EventType,
		EntitySelectorElementType,
		OpenDialogContextType,
	} = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EntitySelectorHelper } = require('im/messenger/lib/helper');

	const { ChatService } = require('im/messenger/provider/service');

	const { GroupChatView } = require('im/messenger/controller/chat-composer/lib/view/group-chat');
	const { showClosingSelectorAlert } = require('im/messenger/controller/chat-composer/lib/confirm');

	const logger = LoggerManager.getInstance().getLogger('chat-composer--channel');

	/**
	 * @class CreateGroupChat
	 */
	class CreateGroupChat
	{
		constructor()
		{
			this.dialogInfo = {
				name: '',
				description: '',
				avatar: '',
				type: DialogType.openChannel,
				userCounter: 0,
				members: EntitySelectorHelper.createUserList([serviceLocator.get('core').getUserId()]),
			};
			/** @type {Array<NestedDepartmentSelectorItem>} */
			this.participants = [];
			/** @type {ChannelView | null} */
			this.mainView = null;

			this.layoutWidget = null;
			this.selectorWidget = null;
		}

		/**
		 * @param props
		 * @param parentWidget
		 *
		 * @return Promise<LayoutWidget>
		 */
		async open(props = {}, parentWidget = PageManager)
		{
			this.selector = new NestedDepartmentSelector({
				initSelectedIds: this.dialogInfo.members,
				undeselectableIds: EntitySelectorHelper.createUserList([serviceLocator.get('core').getUserId()]),
				widgetParams: {
					title: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_CREATE_GROUP_CHAT_TITLE'),
					sendButtonName: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_USER_SELECTOR_CONTINUE_BUTTON'),
				},
				leftButtons: this.#getSelectorButtons(),
				allowMultipleSelection: true,
				closeOnSelect: true,
				events: {
					onClose: (selectedEntity) => {
						/*
						Move to the recent tab because if the last selected tab was department and in the next step,
						the selector will draw the recent items, but the tab will not be updated.
						*/
						this.selectorWidget.setScopeById('recent');

						this.onCloseParticipantSelector(selectedEntity);
					},
				},
				createOptions: {
					enableCreation: false,
				},
				selectOptions: {
					canUnselectLast: true,
					singleEntityByType: false,
				},
				canUseRecent: false,
				provider: {
					context: 'IMMOBILE_UPDATE_GROUP_CHAT_PARTICIPANT',
					options: {
						useLettersForEmptyAvatar: true,
						allowFlatDepartments: true,
						allowSelectRootDepartment: true,
						addMetaUser: false,
					},
				},
			});

			const selector = this.selector.getSelector();

			selector.close = () => {
				selector.onClose();

				return new Promise((resolve) => {
					if (!selector.widget)
					{
						return resolve();
					}

					selector.handleOnEventsCallback('onWidgetClosed', selector.getEntityItems());
				});
			};

			selector.onViewHidden = () => {
				if (selector.widget !== null)
				{
					selector.handleOnEventsCallback('onViewHidden');
				}
			};

			selector.show({}, parentWidget)
				.then((selectorWidget) => {
					this.selectorWidget = selectorWidget;
				})
				.catch((error) => {
					logger.error(`${this.constructor.name}.onClickParticipantAction.selector.show.catch:`, error);
				})
			;
		}

		#getSelectorButtons()
		{
			return [
				{
					id: 'immobile_selector_back_button',
					type: 'back',
					callback: () => {
						const currentItems = this.selector.getSelector().getCurrentSelectedItems().map((item) => {
							return [item.params.type, item.params.id];
						});

						if (isEqual([...this.dialogInfo.members], currentItems))
						{
							this.selectorWidget.back();

							return;
						}

						showClosingSelectorAlert({
							onClose: () => {
								this.selectorWidget.back();
							},
							onCancel: () => {},
						});
					},
				},
			];
		}

		/**
		 * @param props
		 * @param parentWidget
		 *
		 * @return Promise<LayoutWidget>
		 */
		async openMainView(props = {}, parentWidget = PageManager)
		{
			let resolveOpen = () => {};

			let rejectOpen = () => {};
			const openPromise = new Promise((resolve, reject) => {
				resolveOpen = resolve;
				rejectOpen = reject;
			});

			try
			{
				const widgetName = 'layout';
				/**
				 * @type PageManagerProps
				 */
				const widgetParams = {
					titleParams: this.getTitleParams(),
					useLargeTitleMode: true,
					backgroundColor: Color.bgSecondary.toHex(),
				};

				if (parentWidget === PageManager)
				{
					widgetParams.backdrop = {
						mediumPositionPercent: 85,
						horizontalSwipeAllowed: false,
						onlyMediumPosition: true,
					};
				}

				this.mainView = GroupChatView.openToCreate(this.getDialogInfoProps(props));

				parentWidget.openWidget(widgetName, widgetParams)
					.then((layoutWidget) => {
						this.layoutWidget = layoutWidget;
						layoutWidget.showComponent(this.mainView);

						resolveOpen(layoutWidget);
					})
					.catch((error) => {
						logger.error(error);

						rejectOpen(error);
					});
			}
			catch (error)
			{
				logger.error(error);

				rejectOpen(error);
			}

			return openPromise;
		}

		getTitleParams()
		{
			return {
				text: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_CREATE_GROUP_CHAT_TITLE'),
				type: 'dialog',
			};
		}

		/**
		 * @return {ChannelViewProps}
		 */
		getDialogInfoProps(props)
		{
			return {
				...props,
				name: this.dialogInfo.name,
				description: this.dialogInfo.description,
				avatar: this.dialogInfo.avatar,
				type: this.dialogInfo.type,
				userCounter: this.dialogInfo.userCounter,
				callbacks: {
					onClickCreateButton: this.onClickCreate.bind(this),
					onChangeAvatar: this.onChangeAvatar.bind(this),
					onDestroy: () => {
						this.mainView = null;
					},
				},
			};
		}

		onCloseParticipantSelector(selectedEntity)
		{
			this.participants = selectedEntity;

			this.openMainView({
				participantsList: selectedEntity,
			}, this.selectorWidget);
		}

		onClickCreate({ title, description })
		{
			this.dialogInfo.name = title;
			this.dialogInfo.description = description;

			this.create()
				.then((result) => {
					NotifyManager.hideLoadingIndicatorWithoutFallback();
					const chatId = result.chatId;

					this.#openChat(chatId);
				})
				.catch((error) => {
					NotifyManager.hideLoadingIndicator(false);
					logger.error(`${this.constructor.name}.create channel error`, error);
				})
			;
		}

		onChangeAvatar(avatar)
		{
			this.dialogInfo.avatar = avatar;
		}

		async create()
		{
			const config = {
				type: 'CHAT',
				title: this.dialogInfo.name ?? '',
				description: '',
				ownerId: serviceLocator.get('core').getUserId(),
				memberEntities: this.getMemberEntities(),
				searchable: 'N',
			};

			if (this.dialogInfo.avatar)
			{
				config.avatar = this.dialogInfo.avatar;
			}

			const chatService = new ChatService();

			NotifyManager.showLoadingIndicator();

			return chatService.createChat(config);
		}

		getMemberEntities()
		{
			const currentUserId = serviceLocator.get('core').getUserId();

			this.dialogInfo.members = EntitySelectorHelper.getMemberList(this.participants);

			const isCurrentUserAddedToMembers = this.dialogInfo.members.some(([type, id]) => {
				return type === EntitySelectorElementType.user && Number(id) === currentUserId;
			});

			if (!isCurrentUserAddedToMembers)
			{
				this.dialogInfo.members.push(EntitySelectorHelper.createUserElement(currentUserId));
			}

			return this.dialogInfo.members;
		}

		#openChat(chatId)
		{
			this.layoutWidget.close();

			MessengerEmitter.emit(EventType.messenger.openDialog, {
				dialogId: `chat${chatId}`,
				context: OpenDialogContextType.chatCreation,
			});
		}
	}

	module.exports = { CreateGroupChat };
});
