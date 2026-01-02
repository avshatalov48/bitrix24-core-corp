/**
 * @module im/messenger/controller/chat-composer/lib/area/messages-auto-delete
 */
jn.define('im/messenger/controller/chat-composer/lib/area/messages-auto-delete', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { Theme } = require('im/lib/theme');
	const { MessagesAutoDeleteContextMenu } = require('im/messenger/lib/ui/context-menu/messages-auto-delete');
	const { MessagesAutoDeleteDelay } = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { Notification, ToastType } = require('im/messenger/lib/ui/notification');

	/**
	 * @class SettingsPanel
	 * @typedef {LayoutComponent<MessagesAutoDeletePanelProps, MessagesAutoDeletePanelState>} SettingsPanel
	 */
	class MessagesAutoDeletePanel extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				delay: 0,
			};
			this.bindMethods();
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'space-between',
						height: 41,
						marginHorizontal: 18,
						paddingHorizontal: 6,
						marginTop: 24,
						marginBottom: 16,
						paddingBottom: 12,
						borderBottomWidth: 1,
						borderBottomColor: Theme.colors.bgSeparatorSecondary,
					},
				},
				View(
					{
						style: {
							flex: 1,
							flexDirection: 'row',
							alignItems: 'flex-start',
							justifyContent: 'center',
						},
					},
					IconView({
						size: 24,
						color: this.getIconColor(),
						icon: Icon.TIMER_DOT,
					}),
					Text({
						style: {
							width: '100%',
							color: Theme.colors.base1,
							fontSize: 16,
							fontWeight: 400,
							flexShrink: 1,
						},
						text: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_AUTO_DELETE_MESSAGES_TITLE'),
						ellipsize: 'end',
						numberOfLines: 1,
					}),
				),
				View(
					{
						ref: (ref) => {
							this.viewRef = ref;
						},
						style: {
							flexDirection: 'row',
						},
						onClick: () => {
							this.openMessagesAutoDeleteContextMenu();
						},
					},
					Text({
						style: {
							color: this.getTextColor(),
							fontSize: 15,
							fontWeight: 400,
							flexShrink: 1,
						},
						text: this.getText(),
						numberOfLines: 1,
					}),
					IconView({
						size: 20,
						color: Theme.color.base4,
						icon: Icon.CHEVRON_TO_THE_RIGHT,
					}),
				),
			);
		}

		bindMethods()
		{
			this.onChangeDelay = this.#onChangeDelay.bind(this);
		}

		getIconColor()
		{
			if (this.state.delay === MessagesAutoDeleteDelay.off)
			{
				return Theme.color.base3;
			}

			return Theme.color.accentMainPrimary;
		}

		getTextColor()
		{
			if (this.state.delay === MessagesAutoDeleteDelay.off)
			{
				return Theme.colors.base4;
			}

			return Theme.colors.accentMainPrimary;
		}

		getText()
		{
			switch (this.state.delay)
			{
				case MessagesAutoDeleteDelay.hour:
				{
					return Loc.getMessage('IMMOBILE_CHAT_COMPOSER_AUTO_DELETE_MESSAGES_HOUR');
				}

				case MessagesAutoDeleteDelay.day:
				{
					return Loc.getMessage('IMMOBILE_CHAT_COMPOSER_AUTO_DELETE_MESSAGES_DAY');
				}

				case MessagesAutoDeleteDelay.week:
				{
					return Loc.getMessage('IMMOBILE_CHAT_COMPOSER_AUTO_DELETE_MESSAGES_WEEK');
				}

				case MessagesAutoDeleteDelay.month:
				{
					return Loc.getMessage('IMMOBILE_CHAT_COMPOSER_AUTO_DELETE_MESSAGES_MONTH');
				}

				default:
				{
					return Loc.getMessage('IMMOBILE_CHAT_COMPOSER_AUTO_DELETE_MESSAGES_TURN_OFF');
				}
			}
		}

		openMessagesAutoDeleteContextMenu()
		{
			if (!Feature.isMessagesAutoDeleteEnabled)
			{
				Notification.showToast(ToastType.messagesAutoDeleteDisabled);
				this.onChangeDelay(0);

				return;
			}

			MessagesAutoDeleteContextMenu
				.createByFileId({
					ref: this.viewRef,
					selectedItem: this.state.delay,
					onItemSelected: this.onChangeDelay,
				})
				.open();
		}

		/**
		 * @param {number} delay
		 */
		#onChangeDelay(delay)
		{
			this.setState({ delay });
			this.props.onChange(this.state.delay);
		}
	}

	module.exports = { MessagesAutoDeletePanel };
});
