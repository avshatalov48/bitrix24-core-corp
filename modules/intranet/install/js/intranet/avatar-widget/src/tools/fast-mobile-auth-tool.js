import { BaseSecondaryTool } from './base-secondary-tool';
import { EventEmitter } from 'main.core.events';
import { Analytics } from '../analytics';

export class FastMobileAuthTool extends BaseSecondaryTool
{
	getIconClass(): string
	{
		return '--o-qr-code';
	}

	onClick(): void
	{
		Analytics.send(Analytics.EVENT_CLICK_FAST_MOBILE_AUTH);
		EventEmitter.emit('BX.Intranet.AvatarWidget.FastMobileAuthTool:onClick');
	}

	getId(): string
	{
		return 'fast-mobile-auth';
	}
}
