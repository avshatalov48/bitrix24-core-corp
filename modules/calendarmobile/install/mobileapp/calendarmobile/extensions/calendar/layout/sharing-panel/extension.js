/**
 * @module calendar/layout/sharing-panel
 */
jn.define('calendar/layout/sharing-panel', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { withPressed } = require('utils/color');
	const { Analytics } = require('calendar/sharing/analytics');

	const SharingPanel = (props) => {
		const { isCalendarContext, publicShortUrl, model } = props;

		return View(
			{
				testId: 'SharingPanelDescriptionHeader',
				style: styles.wrapper(isCalendarContext),
			},
			Description(isCalendarContext),
			isCalendarContext && SendButton(publicShortUrl, model),
			ViewButton(publicShortUrl),
		);
	};

	const Description = (isCalendarContext) => {
		return Text(
			{
				style: styles.descriptionText,
				text: isCalendarContext
					? Loc.getMessage('L_ML_DESCRIPTION_1')
					: Loc.getMessage('L_ML_DESCRIPTION_2')
				,
			},
		);
	};

	const SendButton = (publicShortUrl, model) => {
		return View(
			{
				testId: 'SharingPanelSharingDialog',
				style: styles.sendButtonContainer,
				onClick: () => {
					const params = {
						peopleCount: 1,
						ruleChanges: model.getSettings().getChanges(),
					};

					Analytics.sendLinkCopied(model.getContext(), Analytics.linkTypes.solo, params);

					dialogs.showSharingDialog({
						message: `${Loc.getMessage('L_ML_SHARE_LINK_MESSAGE')}\r\n${publicShortUrl}`,
					});
				},
			},
			Text(
				{
					style: styles.sendButtonText,
					text: Loc.getMessage('L_ML_BUTTON_SHARE'),
				},
			),
		);
	};

	const ViewButton = (publicShortUrl) => {
		return View(
			{
				testId: 'SharingPanelViewAsGuest',
				style: styles.viewButtonContainer,
				onClick: () => {
					Application.openUrl(publicShortUrl);
				},
			},
			Image(
				{
					svg: {
						content: icons.link,
					},
					style: {
						height: 13,
						width: 13,
					},
				},
			),
			Text(
				{
					style: styles.viewButtonText,
					text: Loc.getMessage('L_ML_BUTTON_VIEW_AS_GUEST'),
				},
			),
		);
	};

	const icons = {
		link: '<svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.82215 2.3614V0.830566L1.16374 0.83099C0.657476 0.83099 0.24707 1.2414 0.24707 1.74766V11.8222C0.24707 12.3284 0.657476 12.7388 1.16374 12.7388H11.2382C11.7445 12.7388 12.1549 12.3284 12.1549 11.8222L12.1546 8.1419L10.6237 8.14098L10.6244 10.75L10.617 10.8324C10.5782 11.0462 10.3911 11.2083 10.1661 11.2083H2.23592L2.15353 11.2009C1.93972 11.1621 1.77759 10.975 1.77759 10.75V2.81984L1.78497 2.73745C1.82378 2.52364 2.01092 2.36151 2.23592 2.36151L4.82215 2.3614ZM12.1549 1.74766C12.1549 1.2414 11.7445 0.83099 11.2382 0.83099L7.57857 0.830566V2.3614H9.46782L5.42551 6.40431L6.59974 7.57854L10.6237 3.55398V5.40107L12.1546 5.40198L12.1549 1.74766Z" fill="#A8ADB4"/></svg>',
	};

	const styles = {
		wrapper: (isCalendarContext) => {
			return {
				marginBottom: 30,
				marginTop: 24,
				paddingHorizontal: isCalendarContext ? 45 : 30,
			};
		},
		descriptionText: {
			textAlign: 'center',
			fontSize: 14,
			lineHeight: 20,
			color: AppTheme.colors.base3,
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
		viewButtonContainer: {
			flexGrow: 1,
			flexDirection: 'row',
			marginTop: 8,
			height: 48,
			alignItems: 'center',
			justifyContent: 'center',
		},
		viewButtonText: {
			fontSize: 15,
			fontWeight: '400',
			marginLeft: 8,
			color: AppTheme.colors.base2,
		},
	};

	module.exports = { SharingPanel };
});
