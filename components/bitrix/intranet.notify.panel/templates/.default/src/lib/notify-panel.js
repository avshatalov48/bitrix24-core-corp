import {Cache, Tag, Loc, Dom} from "main.core";
import type {LicenseNotifyPanelParams, NotifyPanelOptions} from "../types/options";
import {DateTimeFormat} from "main.date";

export class NotifyPanel
{
	#cache = new Cache.MemoryCache();
	static #classActivity: string = 'bx24-tariff-notify-show';

	constructor(options: NotifyPanelOptions)
	{
		this.setOptions(options);
	}

	setOptions(options: NotifyPanelOptions): void
	{
		this.#cache.set('options', options);
	}

	getOptions(): ?NotifyPanelOptions
	{
		return this.#cache.get('options', null);
	}

	#getParams(): LicenseNotifyPanelParams
	{
		return this.getOptions().params;
	}

	#getPanel(): HTMLElement
	{
		const onclick = () => {
			this.close();
		}

		return this.#cache.remember('panel-template', () => {
			return Tag.render`
				<div class="bx24-tariff-notify bx24-tariff-notify-show bx24-tariff-notify-panel">
					<div class="bx24-tariff-notify-wrap ${this.#getColorClass()}">
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

	#getColorClass()
	{
		return (this.getOptions().color && this.getOptions().color === 'blue')
			? 'bx24-tariff-notify-blue'
			: 'bx24-tariff-notify-red';
	}

	#getMessage()
	{
		if (this.getOptions().type === 'license-expired')
		{
			return Loc.getMessage(
				'INTRANET_NOTIFY_PANEL_FOOTER_LICENSE_NOTIFICATION_TEXT',
				{
					'#BLOCK_DATE#': this.#getBlockDate(),
					'#LINK_BUY#': this.#getParams().urlBuy,
					'#ARTICLE_LINK#': this.#getParams().urlArticle,
				}
			);
		}
	}

	#getBlockDate(): string
	{
		const format = DateTimeFormat.getFormat('DAY_MONTH_FORMAT');

		return DateTimeFormat.format(format, Number(this.getOptions().params.blockDate));
	}

	show()
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