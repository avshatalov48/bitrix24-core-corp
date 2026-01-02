import { BaseSecondaryTool } from './base-secondary-tool';
import { SidePanel } from 'main.sidepanel';
import { Analytics } from '../analytics';

export class SecuritySecondaryTool extends BaseSecondaryTool
{
	getIconClass(): string
	{
		return '--o-shield-checked';
	}

	onClick(): void
	{
		Analytics.send(Analytics.EVENT_CLICK_2FA_SETUP);
		SidePanel.Instance.open(this.options.url, { width: 1100 });
	}

	getId(): string
	{
		return 'security';
	}
}
