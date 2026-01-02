import { BaseFooterTool } from './base-footer-tool';
import { Analytics } from '../analytics';
import { EventEmitter } from 'main.core.events';

export class ThemeTool extends BaseFooterTool
{
	onClick(): void
	{
		Analytics.send(Analytics.EVENT_CLICK_CHANGE_PORTAL_THEME);
		EventEmitter.emit('BX.Intranet.AvatarWidget.Popup:openChild');
		BX.Intranet.Bitrix24.ThemePicker.Singleton.showDialog(false);
	}

	getId(): string
	{
		return 'theme';
	}
}
