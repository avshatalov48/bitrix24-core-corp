/**
 * @module stafftrack/check-in/pages/disabled-check-in
 */
jn.define('stafftrack/check-in/pages/disabled-check-in', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { PureComponent } = require('layout/pure-component');
	const { NotifyManager } = require('notify-manager');

	const { Area } = require('ui-system/layout/area');
	const { Button } = require('ui-system/form/buttons/button');
	const { Link3, LinkMode } = require('ui-system/blocks/link');
	const { StatusBlock } = require('ui-system/blocks/status-block');

	const { SettingsPage } = require('stafftrack/check-in/pages/settings');
	const { disabledCheckInIcon } = require('stafftrack/ui');
	const { FeatureAjax } = require('stafftrack/ajax');

	class DisabledCheckInPage extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.onDepartmentHeadChatButtonClick = this.onDepartmentHeadChatButtonClick.bind(this);
			this.onTurnOnButtonClick = this.onTurnOnButtonClick.bind(this);
		}

		get userId()
		{
			return this.props.userInfo?.id || 0;
		}

		get isAdmin()
		{
			return this.props.isAdmin;
		}

		render()
		{
			return Area(
				{
					isFirst: true,
					style: {
						alignItems: 'center',
						marginBottom: Indent.XL4.toNumber(),
					},
				},
				this.renderStatusBlock(),
				this.renderButtons(),
			);
		}

		renderStatusBlock()
		{
			return StatusBlock({
				testId: 'stafftrack-check-in-settings-status-block',
				image: Image({
					resizeMode: 'contain',
					style: {
						width: 291,
						height: 140,
					},
					svg: {
						content: disabledCheckInIcon,
					},
				}),
				title: this.getTitle(),
				description: this.getDescription(),
				buttons: this.getStatusBlockButtons(),
			});
		}

		renderButtons()
		{
			return View(
				{
					style: {
						marginTop: Indent.XL4.toNumber(),
						width: '100%',
						alignItems: 'center',
					},
				},
				this.isAdmin ? this.renderTurnOnButton() : this.renderDepartmentHeadChatButton(),
			);
		}

		renderDepartmentHeadChatButton()
		{
			return Button({
				testId: 'stafftrack-check-in-settings-admin-chat',
				text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_OPEN_CHAT_WITH_ADMIN'),
				color: Color.baseWhiteFixed,
				backgroundColor: Color.accentMainPrimary,
				stretched: true,
				style: {
					marginTop: Indent.XL4.toNumber(),
				},
				onClick: this.onDepartmentHeadChatButtonClick,
			});
		}

		renderTurnOnButton()
		{
			return Button({
				testId: 'stafftrack-check-in-settings-turn-on',
				text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_GO_TO_SETTINGS'),
				color: Color.baseWhiteFixed,
				backgroundColor: Color.accentMainPrimary,
				stretched: true,
				style: {
					marginTop: Indent.XL4.toNumber(),
				},
				onClick: this.onTurnOnButtonClick,
			});
		}

		getTitle()
		{
			return Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_DISABLED');
		}

		getDescription()
		{
			if (this.isAdmin)
			{
				return Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_TURNED_OFF_ADMIN');
			}

			return Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_TURNED_OFF');
		}

		getStatusBlockButtons()
		{
			const result = [];

			if (!this.isAdmin)
			{
				result.push(Link3({
					testId: 'stafftrack-check-in-settings-help-link',
					text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_HELP'),
					mode: LinkMode.PLAIN,
					useInAppLink: false,
					onClick: this.props.onHelpClick,
				}));
			}

			return result;
		}

		async onDepartmentHeadChatButtonClick()
		{
			void NotifyManager.showLoadingIndicator();

			const result = await FeatureAjax.createDepartmentHeadChat('enable_check_in');

			if (result?.data?.chatId)
			{
				void NotifyManager.hideLoadingIndicator(true);

				const dialogId = `chat${result.data.chatId}`;
				BX.postComponentEvent('ImMobile.Messenger.Dialog:open', [{ dialogId }], 'im.messenger');

				if (this.props.onDepartmentHeadChatButtonClick)
				{
					this.props.onDepartmentHeadChatButtonClick();
				}
			}
			else
			{
				void NotifyManager.hideLoadingIndicator(false);
			}
		}

		onTurnOnButtonClick()
		{
			const { layoutWidget } = this.props;

			SettingsPage.show({
				isAdmin: true,
				parentLayout: layoutWidget,
			});
		}
	}

	module.exports = { DisabledCheckInPage };
});
