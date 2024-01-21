import { Loc, Event, Type } from 'main.core';
import { Alert } from 'ui.alerts';

export class DisableAlert
{
	constructor(options = {}): void
	{
		if (!Type.isElementNode(options.alertContainer))
		{
			throw new Error('Livefeed.DisableAlert: \'alertContainer\' must be a DOM element.');
		}

		if (!Type.isInteger(options.daysUntilDisable))
		{
			throw new TypeError('Livefeed.DisableAlert: \'daysUntilDisable\' must be integer');
		}

		this.alertContainer = options.alertContainer;
		this.daysUntilDisable = options.daysUntilDisable;
		this.closeBtnCallback = (Type.isFunction(options.closeBtnCallback)) ? options.closeBtnCallback : () => {};

		this.alert = new Alert({
			text: this.getText(),
			color: Alert.Color.WARNING,
			icon: Alert.Icon.INFO,
			closeBtn: true,
			animate: true,
		});

		Event.bind(this.alert.getCloseBtn(), 'click', this.closeBtnCallback);
	}

	render(): void
	{
		this.alert.renderTo(this.alertContainer);
	}

	getText(): string
	{
		const helpdeskCode = '18371940';

		return Loc.getMessagePlural('CRM_LIVE_FEED_DISABLE_ALERT_TEXT', this.daysUntilDisable, {
			'#DAYS_UNTIL_DISABLE#': this.daysUntilDisable,
			'[helpdesklink]': `<a href="##" onclick="top.BX.Helper.show('redirect=detail&code=${helpdeskCode}');">`,
			'[/helpdesklink]': '</a>',
		});
	}
}
