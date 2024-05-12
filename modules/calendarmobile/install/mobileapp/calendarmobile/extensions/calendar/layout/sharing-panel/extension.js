/**
 * @module calendar/layout/sharing-panel
 */
jn.define('calendar/layout/sharing-panel', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { withPressed } = require('utils/color');
	const { BottomSheet } = require('bottom-sheet');
	const { Analytics } = require('calendar/sharing/analytics');
	const { Icons } = require('calendar/layout/icons');
	const { SharingContext } = require('calendar/model/sharing');
	const { LinkList } = require('calendar/layout/sharing-joint');

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
			const isCalendarContext = this.model.getContext() === SharingContext.CALENDAR;

			return View(
				{
					testId: 'SharingPanelDescriptionHeader',
					style: styles.wrapper(isCalendarContext),
				},
				isCalendarContext && this.renderSendButton(),
				isCalendarContext && this.renderHistoryButton(),
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
				Image(
					{
						tintColor: AppTheme.colors.base3,
						svg: {
							content: Icons.people,
						},
						style: {
							height: 24,
							width: 24,
						},
					},
				),
				Text(
					{
						style: styles.historyButtonText,
						text: Loc.getMessage('CALENDARMOBILE_SHARING_PANEL_JOINT_SLOTS'),
					},
				),
			);
		}

		handleHistoryButtonClick()
		{
			const component = new LinkList({
				model: this.model,
			});

			// eslint-disable-next-line promise/catch-or-return
			new BottomSheet({ component })
				.setParentWidget(this.props.layoutWidget)
				.setBackgroundColor(AppTheme.colors.bgNavigation)
				.disableContentSwipe()
				.setMediumPositionPercent(80)
				.open()
				.then((widget) => component.setLayoutWidget(widget))
			;
		}
	}

	const styles = {
		wrapper: (isCalendarContext) => {
			return {
				marginBottom: 30,
				paddingHorizontal: isCalendarContext ? 45 : 30,
			};
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
			color: AppTheme.colors.base8,
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
