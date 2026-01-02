import { ajax, Cache } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { BaasContent } from './contents/baas-content';
import { LicenseContent } from './contents/license-content';
import { MarketContent } from './contents/market-content';
import { PartnerContent } from './contents/partner-content';
import { PurchaseHistoryContent } from './contents/purchase-history-content';
import { TelephonyContent } from './contents/telephony-content';
import { UpdatesContent } from './contents/updates-content';
import type { PopupOptions , ConfigOptions } from './types/options';
import { PopupComponentsMaker } from 'ui.popupcomponentsmaker';

export class Popup extends EventEmitter
{
	#cache = new Cache.MemoryCache();

	constructor(options: PopupOptions)
	{
		super();
		this.setOptions(options);
		this.setEventNamespace('BX.Intranet.LicenseWidget.Popup');
		this.setEventHandlers();
	}

	setOptions(options: PopupOptions): void
	{
		this.#cache.set('options', options);
	}

	getOptions(): PopupOptions
	{
		return this.#cache.get('options', {});
	}

	show(): void
	{
		this.getBasePopup().show();
		this.emit('show');
	}

	close(): void
	{
		this.getBasePopup().close();
	}

	getBasePopup(): PopupComponentsMaker
	{
		return this.#cache.remember('popup', () => {
			this.emit('init');

			return new PopupComponentsMaker({
				target: this.getOptions().target,
				width: 374,
				content: this.#getContent(),
				popupLoader: this.getOptions().loader,
			});
		});
	}

	#getContent(): Array<ConfigOptions>
	{
		return this.#cache.remember('content', () => {
			const content = [];

			if (this.getOptions().content.license.isAvailable)
			{
				content.push(this.#getLicenseContent().getConfig());
			}

			if (this.getOptions().content.baas.isAvailable && this.getOptions().content.market.isAvailable)
			{
				content.push({
					html: [
						this.#getMarketContent(true).getConfig(),
						this.#getBaasContent(true).getConfig(),
					],
				});
			}
			else if (this.getOptions().content.market.isAvailable)
			{
				content.push(this.#getMarketContent(false).getConfig());
			}
			else if (this.getOptions().content.baas.isAvailable)
			{
				content.push(this.#getBaasContent(false).getConfig());
			}

			content.push(this.#getPurchaseHistoryContent().getConfig());

			if (this.getOptions().content.telephony.isAvailable)
			{
				content.push({
					html: [
						this.#getTelephonyContent().getConfig(),
						this.#getUpdatesContent().getConfig(),
					],
				});
			}
			else
			{
				content.push(this.#getUpdatesContent().getConfig());
			}

			if (this.getOptions().content.partner.isAvailable)
			{
				content.push(this.#getPartnerContent().getConfig());
			}

			return content;
		});
	}

	#getLicenseContent(): LicenseContent
	{
		return this.#cache.remember('license-content', () => {
			return new LicenseContent({
				...this.getOptions().content.license,
			});
		});
	}

	#getMarketContent(small: boolean): MarketContent
	{
		return this.#cache.remember('market-content', () => {
			return new MarketContent({
				...this.getOptions().content.market,
				isSmall: small,
			});
		});
	}

	#getBaasContent(small: boolean): BaasContent
	{
		return this.#cache.remember('baas-content', () => {
			return new BaasContent({
				...this.getOptions().content.baas,
				licensePopupTarget: this.getOptions().target,
				licensePopup: this,
				isAdmin: this.getOptions().isAdmin,
				isSmall: small,
			});
		});
	}

	#getPurchaseHistoryContent(): PurchaseHistoryContent
	{
		return this.#cache.remember('purchase-history-content', () => {
			return new PurchaseHistoryContent({
				...this.getOptions().content['purchase-history'],
			});
		});
	}

	#getTelephonyContent(): TelephonyContent
	{
		return this.#cache.remember('telephony-content', () => {
			return new TelephonyContent({
				...this.getOptions().content.telephony,
			});
		});
	}

	#getUpdatesContent(): UpdatesContent
	{
		return this.#cache.remember('updates-content', () => {
			return new UpdatesContent({
				...this.getOptions().content.updates,
			});
		});
	}

	#getPartnerContent(): PartnerContent
	{
		return this.#cache.remember('partner-content', () => {
			return new PartnerContent({
				...this.getOptions().content.partner,
			});
		});
	}

	setEventHandlers(): void
	{
		const close = () => {
			this.close();
		};

		this.subscribe('init', () => {
			EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onOpenStart', close);
			EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'BX.Intranet.LicenseWidget.Popup:openChild', close);
		});
	}
}