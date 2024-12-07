/**
 * @module calendar/layout/sharing-panel
 */
jn.define('calendar/layout/sharing-panel', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { BottomSheet } = require('bottom-sheet');
	const { withPressed } = require('utils/color');

	const { Icons } = require('calendar/layout/icons');
	const { LinkList } = require('calendar/layout/sharing-joint');
	const { Color } = require('tokens');
	const { Analytics } = require('calendar/sharing/analytics');

	/**
	 * @class SharingPanel
	 */
	class SharingPanel extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				creatingLink: false,
			};

			this.handleSendButtonClick = this.handleSendButtonClick.bind(this);
			this.handleHistoryButtonClick = this.handleHistoryButtonClick.bind(this);
		}

		get model()
		{
			return this.props.model;
		}

		render()
		{
			return View(
				{
					testId: 'SharingPanelDescriptionHeader',
					style: styles.wrapper,
				},
				this.renderSendButton(),
				this.renderHistoryButton(),
			);
		}

		renderSendButton()
		{
			return View(
				{
					testId: 'SharingPanelSharingDialog',
					style: styles.sendButtonContainer,
					onClick: this.handleSendButtonClick,
				},
				!this.state.creatingLink && Text(
					{
						style: styles.sendButtonText,
						text: Loc.getMessage('L_ML_BUTTON_SHARE'),
					},
				),
				this.state.creatingLink && Loader({
					style: {
						width: 30,
						height: 30,
						alignSelf: 'center',
					},
					tintColor: AppTheme.colors.base7,
					animating: true,
				}),
			);
		}

		async handleSendButtonClick()
		{
			if (this.state.creatingLink)
			{
				return;
			}

			const params = {
				peopleCount: this.model.getMembers().length,
				ruleChanges: this.model.getSettings().getChanges(),
			};

			const type = params.peopleCount > 1 ? Analytics.linkTypes.multiple : Analytics.linkTypes.solo;
			Analytics.sendLinkCopied(this.model.getContext(), type, params);

			let publicShortUrl = '';
			setTimeout(() => this.setState({ creatingLink: publicShortUrl === '' }));
			publicShortUrl = await this.model.getJointPublicShortUrl();
			this.setState({ creatingLink: false });

			dialogs.showSharingDialog({
				message: publicShortUrl,
			});
		}

		renderHistoryButton()
		{
			return View(
				{
					testId: 'SharingPanelViewAsGuest',
					style: styles.historyButtonContainer,
					onClick: this.handleHistoryButtonClick,
				},
				Image({
					tintColor: AppTheme.colors.base3,
					svg: {
						content: Icons.people,
					},
					style: {
						height: 24,
						width: 24,
					},
				}),
				Text({
					style: styles.historyButtonText,
					text: Loc.getMessage('CALENDARMOBILE_SHARING_PANEL_JOINT_SLOTS'),
				}),
			);
		}

		handleHistoryButtonClick()
		{
			const component = (layoutWidget) => new LinkList({
				layoutWidget,
				model: this.model,
			});

			void new BottomSheet({ component })
				.setParentWidget(this.props.layoutWidget)
				.setBackgroundColor(Color.bgNavigation.toHex())
				.disableContentSwipe()
				.setMediumPositionPercent(80)
				.open()
			;
		}
	}

	const styles = {
		wrapper: {
			marginBottom: 30,
			paddingHorizontal: 45,
		},
		sendButtonContainer: {
			flexGrow: 1,
			flexDirection: 'row',
			justifyContent: 'center',
			alignItems: 'center',
			marginTop: 16,
			height: 48,
			borderRadius: 12,
			backgroundColor: withPressed(AppTheme.colors.accentMainPrimaryalt),
		},
		sendButtonText: {
			fontSize: 17,
			fontWeight: '500',
			ellipsize: 'end',
			numberOfLines: 1,
			color: AppTheme.colors.baseWhiteFixed,
		},
		historyButtonContainer: {
			flexGrow: 1,
			flexDirection: 'row',
			marginTop: 8,
			height: 48,
			alignItems: 'center',
			justifyContent: 'center',
		},
		historyButtonText: {
			fontSize: 15,
			fontWeight: '400',
			marginLeft: 8,
			color: AppTheme.colors.base2,
		},
	};

	module.exports = { SharingPanel };
});
