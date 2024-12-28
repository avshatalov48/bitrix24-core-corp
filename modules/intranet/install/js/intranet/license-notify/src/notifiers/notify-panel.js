import { Cache, Tag, Loc, Dom } from 'main.core';
import type { LicenseNotifyPanelParams } from '../types/options';
import { DateTimeFormat } from 'main.date';
import { LicenseNotifier } from './license-notifier';

export class NotifyPanel extends LicenseNotifier
{
	#cache = new Cache.MemoryCache();
	static #classActivity: string = 'bx24-tariff-notify-show';

	constructor(options: LicenseNotifyPanelParams)
	{
		super();
		this.setOptions(options);
	}

	setOptions(options: LicenseNotifyPanelParams): void
	{
		this.#cache.set('options', options);
	}

	getOptions(): ?LicenseNotifyPanelParams
	{
		return this.#cache.get('options', null);
	}

	#getPanel(): HTMLElement
	{
		const onclick = () => {
			this.close();
		};

		return this.#cache.remember('panel-template', () => {
			return Tag.render`
				<div class="bx24-tariff-notify bx24-tariff-notify-show bx24-tariff-notify-panel">
					<div class="bx24-tariff-notify-wrap bx24-tariff-notify-red">
						<span class="bx24-tariff-notify-text">
						 ${this.#getMessage()}
						</span>
						<span onclick="${onclick}" class="bx24-tariff-notify-text-reload">
							<span class="bx24-tariff-notify-text-reload-title">
								x
							</span>
						</span>
					</div>
				</div>
			`;
		});
	}

	#getMessage(): string
	{
		return Loc.getMessage(
			'INTRANET_NOTIFY_PANEL_FOOTER_LICENSE_NOTIFICATION_TEXT',
			{
				'#BLOCK_DATE#': this.#getBlockDate(),
				'#LINK_BUY#': this.getOptions().urlBuy,
				'#ARTICLE_LINK#': this.getOptions().urlArticle,
			},
		);
	}

	#getBlockDate(): string
	{
		const format = DateTimeFormat.getFormat('DAY_MONTH_FORMAT');

		return DateTimeFormat.format(format, Number(this.getOptions().blockDate));
	}

	show(): void
	{
		const mainTable = document.querySelector('.bx-layout-table');

		if (mainTable)
		{
			Dom.insertBefore(this.#getPanel(), mainTable);
		}
	}

	close(): void
	{
		if (Dom.hasClass(this.#getPanel(), NotifyPanel.#classActivity))
		{
			Dom.removeClass(this.#getPanel(), NotifyPanel.#classActivity);
		}
	}
}
