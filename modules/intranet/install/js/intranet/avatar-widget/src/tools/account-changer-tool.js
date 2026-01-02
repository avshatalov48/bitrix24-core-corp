import { BaseSecondaryTool } from './base-secondary-tool';
import { DesktopAccountList } from 'intranet.desktop-account-list';
import { EventEmitter } from 'main.core.events';
import { Analytics } from '../analytics';
import string from "../../../../../../../salescenter/install/js/salescenter/deliveryselector/src/properties/string";

export class AccountChangerTool extends BaseSecondaryTool
{
	getIconClass(): string
	{
		return '--o-structure-vertical';
	}

	onClick(): void
	{
		if (this.options.type === 'desktop')
		{
			Analytics.send(Analytics.EVENT_CLICK_ACTIVITY_PORTAL_LIST);
			EventEmitter.emit('BX.Intranet.AvatarWidget.Popup:openChild');
			(new DesktopAccountList({ bindElement: document.querySelector('[data-id="bx-avatar-widget"]') })).show();
		}
		else if (this.options.type === 'network')
		{
			Analytics.send(Analytics.EVENT_CLICK_NETWORK);
			window.open(this.options.path, '_blank');
		}
	}

	getId(): string
	{
		return 'account-changer';
	}
}
