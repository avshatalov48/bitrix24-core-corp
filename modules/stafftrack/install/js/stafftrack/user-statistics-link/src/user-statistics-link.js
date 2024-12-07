import { Loc } from 'main.core';
import { QrAuthorization } from 'ui.qrauthorization';

export class UserStatisticsLink
{
	static CHECK_IN_INTENT = 'check-in';
	static CHECK_IN_SETTINGS_INTENT = 'check-in-settings';

	constructor(props = {})
	{
		this.qrAuth = new QrAuthorization({
			title: this.getTitle(props.intent),
			content: this.getContent(props.intent),
			intent: props.intent || UserStatisticsLink.CHECK_IN_INTENT,
			showFishingWarning: true,
			showBottom: false,
		});
	}

	show()
	{
		this.qrAuth.show();
	}

	getTitle(intent): string
	{
		if (intent === UserStatisticsLink.CHECK_IN_SETTINGS_INTENT)
		{
			return Loc.getMessage('STAFFTRACK_CHECK_IN_SETTINGS_QRCODE_TITLE');
		}

		return Loc.getMessage('STAFFTRACK_USER_STATISTICS_LINK_QRCODE_TITLE');
	}

	getContent(intent): string
	{
		if (intent === UserStatisticsLink.CHECK_IN_SETTINGS_INTENT)
		{
			return Loc.getMessage('STAFFTRACK_CHECK_IN_SETTINGS_QRCODE_BODY');
		}

		return Loc.getMessage('STAFFTRACK_USER_STATISTICS_LINK_QRCODE_BODY');
	}
}
