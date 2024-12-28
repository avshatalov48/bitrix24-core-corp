/**
 * @module calendar/event-view-form/fields/user-with-chat-buttons
 */
jn.define('calendar/event-view-form/fields/user-with-chat-buttons', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { NotifyManager } = require('notify-manager');
	const { confirmDefaultAction } = require('alert');

	const { UserFieldClass } = require('layout/ui/fields/user');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { withTheme } = require('layout/ui/fields/theme');
	const { FieldWrapper } = require('layout/ui/fields/theme/air/elements/field-wrapper');

	const { EmptyContent } = require('layout/ui/fields/user/theme/air/src/empty-content');
	const { EntityList } = require('layout/ui/fields/user/theme/air/src/entity-list');

	const { EventAjax } = require('calendar/ajax');
	const { CollabManager } = require('calendar/data-managers/collab-manager');
	const { CollabChatMenu, collabChatItemTypes } = require('calendar/event-view-form/collab-chat-menu');

	/**
	 * @class UserWithChatButtonsField
	 */
	class UserWithChatButtonsField extends UserFieldClass
	{
		constructor(props)
		{
			super(props);

			this.collabChatMenu = null;
			this.chatButtonRef = null;

			this.openNormalChat = this.openNormalChat.bind(this);
		}

		get parentId()
		{
			return this.props.parentId;
		}

		get attendees()
		{
			return this.props.attendees;
		}

		get chatId()
		{
			return this.props.chatId;
		}

		get collabId()
		{
			return this.props.collabId;
		}

		hasPermissions()
		{
			return this.props.permissions?.view_full;
		}

		renderRightIcons()
		{
			return View(
				{
					style: {
						opacity: 0,
						flexDirection: 'row',
						justifyContent: 'center',
						alignItems: 'center',
					},
					ref: (ref) => ref?.animate({ duration: 200, opacity: 1 }),
				},
				this.renderChatIcon(),
			);
		}

		renderChatIcon()
		{
			return View(
				{
					testId: 'calendar-event-view-form-chat-button',
					style: {
						marginLeft: Indent.XL.toNumber(),
					},
					onClick: this.handleChatIconClick,
					ref: (ref) => {
						this.chatButtonRef = ref;
					},
				},
				IconView({
					icon: Icon.GO_TO_MESSAGE,
					size: 30,
					color: Color.base3,
				}),
			);
		}

		handleChatIconClick = async () => {
			if (this.collabId > 0 && CollabManager.getCollab(this.collabId))
			{
				this.openCollabChatMenu();

				return;
			}

			void this.openNormalChatWithConfirm();
		};

		openCollabChatMenu()
		{
			this.collabChatMenu = new CollabChatMenu({
				targetElementRef: this.chatButtonRef,
				layoutWidget: this.props.layout,
				onItemSelected: this.onCollabChatMenuItemSelected,
			});

			this.collabChatMenu.show();
		}

		onCollabChatMenuItemSelected = (item) => {
			if (item.id === collabChatItemTypes.collabChat)
			{
				return this.openCollabChat();
			}

			return this.openNormalChatWithConfirm();
		};

		openNormalChatWithConfirm()
		{
			if (this.shouldNotConfirmOpenChat())
			{
				void this.openNormalChat();

				return;
			}

			this.showConfirmOpenChat();
		}

		async openNormalChat()
		{
			void NotifyManager.showLoadingIndicator();

			if (this.chatId > 0)
			{
				return this.openChat(this.chatId);
			}

			const { data } = await EventAjax.getEventChatId(this.parentId);

			return this.openChat(data.chatId);
		}

		openCollabChat()
		{
			void NotifyManager.showLoadingIndicator();

			return this.openChat(CollabManager.getCollabChatId(this.collabId));
		}

		openChat(chatId)
		{
			try
			{
				const dialogId = `chat${chatId}`;

				void requireLazy('im:messenger/api/dialog-opener').then(({ DialogOpener }) => {
					NotifyManager.hideLoadingIndicator(true);
					DialogOpener.open({ dialogId });
				});
			}
			catch (errors)
			{
				NotifyManager.hideLoadingIndicator(false);
				console.error(errors);
			}
		}

		showConfirmOpenChat()
		{
			confirmDefaultAction({
				title: '',
				description: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_OPEN_CHAT_CONFIRM'),
				actionButtonText: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_OPEN_CHAT_CONFIRM_ACTION_BUTTON'),
				onAction: this.openNormalChat,
			});
		}

		shouldNotConfirmOpenChat()
		{
			return this.chatId > 0 || this.attendees.length < 10;
		}
	}

	const AirTheme = ({ field }) => FieldWrapper(
		{ field },
		View(
			{
				style: {
					paddingVertical: field.isMultiple() ? 0 : Indent.L.toNumber(),
					flexDirection: 'row',
					alignItems: 'center',
					...field.getStyles().airContainer,
				},
			},
			field.isEmpty()
				? View(
					{
						testId: `${field.testId}_CONTENT`,
					},
					EmptyContent({
						testId: `${field.testId}_EMPTY_VIEW`,
						icon: field.getDefaultLeftIcon(),
						text: field.getEmptyText(),
					}),
				)
				: EntityList({ field }),
			!field.isEmpty() && field.hasPermissions() && field.renderRightIcons(),
		),
	);

	module.exports = {
		UserWithChatButtonsField: withTheme(UserWithChatButtonsField, AirTheme),
	};
});
