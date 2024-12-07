/**
 * @module calendar/layout/sharing-joint/link-item
 */
jn.define('calendar/layout/sharing-joint/link-item', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { Alert } = require('alert');
	const { withPressed } = require('utils/color');
	const { cross } = require('assets/common');
	const { Moment } = require('utils/date');
	const { date } = require('utils/date/formats');
	const { BottomSheet } = require('bottom-sheet');
	const { Avatars } = require('calendar/layout/avatars');
	const { Analytics } = require('calendar/sharing/analytics');
	const { LinkPage } = require('calendar/layout/sharing-joint/link-page');

	class LinkItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.openLinkPage = this.openLinkPage.bind(this);
			this.showDeleteLinkPopup = this.showDeleteLinkPopup.bind(this);
			this.sendLink = this.sendLink.bind(this);
		}

		get model()
		{
			return this.props.model;
		}

		get link()
		{
			return this.props.link;
		}

		get layoutWidget()
		{
			return this.props.layoutWidget;
		}

		redraw()
		{
			this.setState({ time: Date.now() });
		}

		render()
		{
			return View(
				{},
				!this.link.deleted && this.renderContainer(),
			);
		}

		renderContainer()
		{
			return View(
				{
					style: styles.container,
					onClick: this.openLinkPage,
				},
				View(
					{
						style: styles.containerRow,
					},
					this.renderAvatars(),
					this.renderDate(),
					this.renderSendButton(),
					this.renderCross(),
				),
			);
		}

		openLinkPage()
		{
			const component = (layoutWidget) => new LinkPage({
				layoutWidget,
				link: this.link,
				deleteLink: this.showDeleteLinkPopup,
				sendLink: this.sendLink,
			});

			void new BottomSheet({ component })
				.setParentWidget(this.layoutWidget)
				.setBackgroundColor(AppTheme.colors.bgNavigation)
				.disableContentSwipe()
				.setMediumPositionPercent(80)
				.open()
			;
		}

		renderAvatars()
		{
			return View(
				{
					style: styles.avatarsContainer,
				},
				new Avatars({
					avatars: this.link.members.map((user) => user.avatar),
					size: 32,
					density: 0.5,
					limit: 4,
				}),
			);
		}

		renderDate()
		{
			const moment = Moment.createFromTimestamp(new Date(this.link.dateCreate).getTime() / 1000);

			return View(
				{
					style: styles.dateContainer,
				},
				Text({
					text: Loc.getMessage('CALENDARMOBILE_SHARING_CREATED').toLocaleUpperCase(env.languageId),
					style: styles.dateCreateTitle,
				}),
				Text({
					text: moment.format(date),
					style: styles.dateText,
				}),
			);
		}

		renderSendButton()
		{
			return View(
				{
					style: styles.sendButton,
					onClick: this.sendLink,
				},
				Text({
					text: Loc.getMessage('CALENDARMOBILE_SHARING_SEND'),
					style: styles.sendButtonText,
				}),
			);
		}

		renderCross()
		{
			return View(
				{
					style: styles.crossContainer,
					onClick: this.showDeleteLinkPopup,
				},
				Image({
					tintColor: AppTheme.colors.base6,
					svg: {
						content: cross(),
					},
					style: styles.crossIcon,
				}),
			);
		}

		showDeleteLinkPopup()
		{
			return new Promise((resolve, reject) => {
				Alert.confirm(
					Loc.getMessage('CALENDARMOBILE_SHARING_DELETE_LINK_ALERT_TITLE'),
					Loc.getMessage('CALENDARMOBILE_SHARING_DELETE_LINK_ALERT'),
					[
						{
							type: 'cancel',
							onPress: () => reject(),
						},
						{
							text: BX.message('CALENDARMOBILE_SHARING_DELETE_LINK_CONFIRM'),
							type: 'destructive',
							onPress: () => {
								this.deleteLink();

								resolve();
							},
						},
					],
				);
			});
		}

		deleteLink()
		{
			this.model.deleteLink(this.link);
			this.link.deleted = true;
			this.redraw();

			this.showLinkDeletedMessage();
		}

		showLinkDeletedMessage()
		{
			const message = Loc.getMessage('CALENDARMOBILE_SHARING_LINK_DELETED');
			const time = 3;

			Notify.showUniqueMessage(message, '', { time });
		}

		sendLink()
		{
			const params = {
				peopleCount: this.link.members.length,
				ruleChanges: this.model.getSettings().getChanges(),
			};

			Analytics.sendLinkCopiedList(this.model.getContext(), params);

			this.model.increaseFrequentUse(this.link.hash);

			dialogs.showSharingDialog({
				message: this.link.shortUrl,
			});
		}
	}

	const styles = {
		container: {
			flexDirection: 'row',
			paddingTop: 13,
			paddingBottom: 13,
			paddingLeft: 18,
			backgroundColor: withPressed(AppTheme.colors.bgContentPrimary),
			borderBottomWidth: 1,
			borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
		},
		containerRow: {
			flexDirection: 'row',
			flex: 1,
			alignItems: 'center',
		},
		avatarsContainer: {
			flex: 1,
		},
		dateContainer: {
			paddingRight: 20,
		},
		dateCreateTitle: {
			fontSize: 9,
			color: AppTheme.colors.base3,
		},
		dateText: {
			fontSize: 15,
			color: AppTheme.colors.base3,
		},
		sendButton: {
			paddingRight: 20,
			height: '100%',
			justifyContent: 'center',
		},
		sendButtonText: {
			color: withPressed(AppTheme.colors.accentMainLinks),
			fontSize: 15,
		},
		crossContainer: {
			height: '100%',
			justifyContent: 'center',
			paddingRight: 18,
		},
		crossIcon: {
			width: 23,
			height: 23,
		},
	};

	module.exports = { LinkItem };
});
