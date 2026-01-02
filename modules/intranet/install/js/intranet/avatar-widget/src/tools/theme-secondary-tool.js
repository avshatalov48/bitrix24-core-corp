import { BaseSecondaryTool } from './base-secondary-tool';
import { Analytics } from '../analytics';
import { EventEmitter } from 'main.core.events';

export class ThemeSecondaryTool extends BaseSecondaryTool
{
	getIconClass(): string
	{
		return '--o-palette';
	}

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
