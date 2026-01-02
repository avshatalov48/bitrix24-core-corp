import { FeaturePromotersRegistry } from 'ui.info-helper';
import { Dom, Loc, Type } from 'main.core';
import { Router } from 'crm.router';
import { Counter, CounterStyle } from 'ui.cnt';
import { PULL } from 'pull.client';
import { BaseMainTool } from './base-main-tool';
import { Analytics } from '../analytics';

export class MyDocumentsTool extends BaseMainTool
{
	getIconClass(): string
	{
		return '--o-file';
	}

	onClick(): void
	{
		Analytics.send(Analytics.EVENT_CLICK_MY_DOCUMENTS);
		if (this.options.isLocked)
		{
			FeaturePromotersRegistry.getPromoter({ code: 'limit_office_e_signature' }).show();

			return;
		}

		const userId = Number(Loc.getMessage('USER_ID'));

		if (userId > 0)
		{
			Router.openSlider(
				`${Loc.getMessage('SITE_DIR')}company/personal/user/${userId}/sign?noRedirect=Y`,
				{ width: 1000, cacheable: false },
			);
		}
	}

	getCounter(): ?Counter
	{
		return this.cache.remember('counter', () => {
			if (Number(this.options.counter) < 1)
			{
				return null;
			}

			PULL.subscribe({
				moduleId: 'sign',
				command: this.options.counterEventName,
				callback: (params) => {
					if (!Type.isNumber(params?.needActionCount))
					{
						return;
					}

					this.options.counter = params.needActionCount;

					if (params?.needActionCount > 0)
					{
						this.getCounter().update(params.needActionCount);
					}
					else
					{
						this.getCounter().destroy();
						const icon = this.getLayout().querySelector('.intranet-avatar-widget-item__icon');
						Dom.removeClass(icon, '--active');
						this.cache.delete('counter');
					}
				},
			});

			return new Counter({
				color: Counter.Color.DANGER,
				size: Counter.Size.MEDIUM,
				value: this.options.counter,
				style: CounterStyle.FILLED_ALERT,
				useAirDesign: true,
			});
		});
	}

	getId(): string
	{
		return 'my-documents';
	}
}
