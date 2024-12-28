/**
 * @module im/messenger/controller/chat-composer/create/channel
 */
jn.define('im/messenger/controller/chat-composer/create/channel', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { Icon } = require('assets/icons');
	const { NotifyManager } = require('notify-manager');
	const { isEqual } = require('utils/object');

	const { NestedDepartmentSelector } = require('selector/widget/entity/tree-selectors/nested-department-selector');
	const {
		DialogType,
		EventType,
		WidgetTitleParamsType,
		EntitySelectorElementType,
		OpenDialogContextType,
	} = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EntitySelectorHelper } = require('im/messenger/lib/helper');
	const { Notification } = require('im/messenger/lib/ui/notification');

	const { ChatService } = require('im/messenger/provider/service');

	const { ChannelView } = require('im/messenger/controller/chat-composer/lib/view/channel');
	const { DialogTypeView } = require('im/messenger/controller/chat-composer/lib/view/dialog-type');
	const { showClosingSelectorAlert } = require('im/messenger/controller/chat-composer/lib/confirm');

	const logger = LoggerManager.getInstance().getLogger('chat-composer--channel');

	/**
	 * @class CreateChannel
	 */
	class CreateChannel
	{
		constructor()
		{
			this.dialogInfo = {
				name: '',
				description: '',
				avatar: '',
				type: DialogType.channel,
				userCounter: 0,
				members: EntitySelectorHelper.createUserList([serviceLocator.get('core').getUserId()]),
			};
			/** @type {DialogTypeView | null} */
			this.dialogTypeView = null;
			/** @type {ChannelView | null} */
			this.mainView = null;

			this.layoutWidget = null;
			this.selectorWidget = null;
			this.selector = null;
		}

		/**
		 * @param props
		 * @param parentWidget
		 *
		 * @return Promise<LayoutWidget>
		 */
		async open(props = {}, parentWidget = PageManager)
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

			this.mainView = ChannelView.openToCreate(this.getDialogInfoProps());

			const layoutWidget = await parentWidget.openWidget(widgetName, widgetParams);

			this.layoutWidget = layoutWidget;
			layoutWidget.showComponent(this.mainView);

			return layoutWidget;
		}

		getTitleParams()
		{
			return {
				text: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_CREATE_CHANNEL_TITLE'),
				type: 'dialog',
			};
		}

		/**
		 * @return {ChannelViewProps}
		 */
		getDialogInfoProps()
		{
			return {
				name: this.dialogInfo.name,
				description: this.dialogInfo.description,
				avatar: this.dialogInfo.avatar,
				type: this.dialogInfo.type,
				userCounter: this.dialogInfo.userCounter,
				callbacks: {
					onClickDialogTypeAction: this.openDialogTypeView.bind(this),
					onClickParticipantAction: this.onClickParticipantAction.bind(this),
					onClickCreateButton: this.onClickCreate.bind(this),
					onChangeAvatar: this.onChangeAvatar.bind(this),
					onDestroy: () => {
						this.mainView = null;
					},
				},
			};
		}

		openDialogTypeView({ titleType = WidgetTitleParamsType.entity } = {})
		{
			this.layoutWidget.openWidget('layout', {
				titleParams: {
					text: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_DIALOG_TYPE_CHANNEL_TITLE'),
					type: titleType,
				},
			})
				.then((widget) => {
					this.dialogTypeView = new DialogTypeView(
						{
							dialogType: this.dialogInfo.type,
							callbacks: {
								onChangeDialogType: this.onChangeDialogType.bind(this),
								onDestroyView: () => {
									this.dialogTypeView = null;
								},
							},
						},
					);
					widget.showComponent(this.dialogTypeView);
				})
				.catch((error) => {
					logger.error(`${this.constructor.name}.PageManager.openWidget.catch:`, error);
				});
		}

		async onClickParticipantAction({ titleType = WidgetTitleParamsType.entity } = {})
		{
			this.selector = new NestedDepartmentSelector({
				initSelectedIds: this.dialogInfo.members,
				undeselectableIds: EntitySelectorHelper.createUserList([serviceLocator.get('core').getUserId()]),
				widgetParams: {
					title: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_SUBSCRIBERS_TITLE'),
					sendButtonName: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_USER_SELECTOR_DONE_BUTTON'),
				},
				leftButtons: this.#getSelectorButtons(),
				allowMultipleSelection: true,
				closeOnSelect: true,
				events: {
					onClose: (selectedEntity) => {
						this.onCloseParticipantSelector(selectedEntity);
						this.selectorWidget = null;
						this.selector = null;
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

					selector.widget.back(() => {
						selector.onWidgetClosed();
						resolve();
					});
				});
			};

			selector.show({}, this.layoutWidget)
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
		 * @desc update dialog type
		 * @param {boolean} isSetOpenEntityType
		 * @void
		 */
		onChangeDialogType(isSetOpenEntityType)
		{
			this.dialogInfo.type = isSetOpenEntityType ? DialogType.openChannel : DialogType.channel;

			this.dialogTypeView?.setState?.({ dialogType: this.dialogInfo.type });
			this.mainView?.setState?.({ type: this.dialogInfo.type });
		}

		/**
		 * @param {Array<Object>} selectedEntity
		 * @void
		 */
		onCloseParticipantSelector(selectedEntity)
		{
			this.#showSuccessfullyToast();
			this.dialogInfo.members = EntitySelectorHelper.getMemberList(selectedEntity);
		}

		onClickCreate({ title, description })
		{
			this.dialogInfo.name = title;
			this.dialogInfo.description = description;

			this.create()
				.then((result) => {
					NotifyManager.hideLoadingIndicatorWithoutFallback();
					const chatId = result.chatId;

					this.#openChannel(chatId);
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
				type: 'CHANNEL',
				title: this.dialogInfo.name ?? '',
				description: this.dialogInfo.description ?? '',
				ownerId: serviceLocator.get('core').getUserId(),
				memberEntities: this.getMemberEntities(),
				searchable: this.dialogInfo.type === DialogType.openChannel ? 'Y' : 'N',
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
			const isCurrentUserAddedToMembers = this.dialogInfo.members.some(([type, id]) => {
				return type === EntitySelectorElementType.user && Number(id) === currentUserId;
			});

			if (!isCurrentUserAddedToMembers)
			{
				this.dialogInfo.members.push(EntitySelectorHelper.createUserElement(currentUserId));
			}

			return this.dialogInfo.members;
		}

		#openChannel(chatId)
		{
			this.layoutWidget.close();

			MessengerEmitter.emit(EventType.messenger.openDialog, {
				dialogId: `chat${chatId}`,
				context: OpenDialogContextType.chatCreation,
			});
		}

		#showSuccessfullyToast()
		{
			Notification.showToastWithParams(
				{
					message: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_TOAST_SAVE_SUCCESSFULLY'),
					icon: Icon.CIRCLE_CHECK,
				},
				this.layoutWidget,
			);
		}
	}

	module.exports = { CreateChannel };
});
