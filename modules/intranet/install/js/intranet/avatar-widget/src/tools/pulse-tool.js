import { ajax, Runtime } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { BaseFooterTool } from './base-footer-tool';
import { Analytics } from '../analytics';

export class PulseTool extends BaseFooterTool
{
	onClick(): void
	{
		Analytics.send(Analytics.EVENT_CLICK_PULSE);
		ajax.runAction('intranet.user.widget.getUserStatComponent', {
			mode: 'class',
		}).then((response) => {
			Runtime.html(null, response.data.html).then(() => {
				if (window.openIntranetUStat)
				{
					EventEmitter.emit('BX.Intranet.AvatarWidget.Popup:openChild');
					openIntranetUStat();
				}
			}).catch(() => {});
		}).catch(() => {});
	}

	getId(): string
	{
		return 'pulse';
	}
}
