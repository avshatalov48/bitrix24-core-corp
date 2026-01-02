import { ajax, Event, Runtime } from 'main.core';
import { BaseCache, MemoryCache } from 'main.core.cache';
import { LicenseWidget as Bitrix24LicenseWidget } from 'bitrix24.license-widget';
import { LicenseWidget as IntranetLicenseWidget } from 'intranet.license-widget';
import { WidgetLoader } from 'intranet.widget-loader';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { FeaturePromotersRegistry } from 'ui.info-helper';
import {Counter, CounterColor, CounterStyle} from 'ui.cnt';
import { PULL } from 'pull.client';

type LicenseButtonOptions = {
	ordersCount: {
		awaitingPayment: number,
		failedPayment: number,
		awaitingInvoice: number,
		inCheckout: number,
	},
	ordersInfo: {
		lastCheckoutTime: number,
		showDelay: number,
		hideAfterCheckoutDelay: number,
		checkoutPath: string,
		invoicePath: string,
		isAvailable: boolean,
	},
	shouldShow: boolean,
	ordersTotalCount: number,
	skeleton: Object,
	isSidePanelDemoLicense: boolean,
	isAdmin: boolean,
	isCloud: boolean,
	infrastructureForm: {
		id: string,
		secCode: string,
	},
};

export class LicenseButton
{
	static #options: LicenseButtonOptions;
	static #buttonWrapper: HTMLElement;
	static #cache: BaseCache<any> = new MemoryCache();

	static init(options: LicenseButtonOptions): void
	{
		this.#options = options;
		this.#buttonWrapper = document.querySelector('[data-id="licenseWidgetWrapper"]');
		this.#setEventHandlers();

		if (this.#options.isCloud && this.#options.shouldShow)
		{
			this.#setCounterValue(this.#options.ordersTotalCount);
		}

		Event.bind(this.#buttonWrapper, 'click', () => {
			Event.unbindAll(this.#buttonWrapper);
			this.#getWidgetLoader()
				.createSkeletonFromConfig(options.skeleton)
				.show();
			Runtime.loadExtension([this.#getExtensionWidgetName()]).then(() => {
				this.#showWidget();
			}).catch(() => {});
		});
	}

	static #getExtensionWidgetName(): string
	{
		if (this.#options.isCloud)
		{
			return 'bitrix24.license-widget';
		}

		return 'intranet.license-widget';
	}

	static #showWidget(): void
	{
		this.#getContent().then((response) => {
			this.#getWidgetLoader().clearBeforeInsertContent();
			let licenseData = null;

			if (this.#options.isCloud)
			{
				licenseData = response.data;
				licenseData.loader = this.#getWidgetLoader().getPopup();
				licenseData.wrapper = this.#buttonWrapper;
			}
			else
			{
				licenseData = {
					loader: this.#getWidgetLoader().getPopup(),
					buttonWrapper: this.#buttonWrapper,
					data: response.data,
				};
			}

			this.#getWidget().setOptions(licenseData).show();
			Event.bind(this.#buttonWrapper, 'click', () => {
				this.#getWidget().show();
			});
		}).catch(() => {});
	}

	static #getWidget(): IntranetLicenseWidget | Bitrix24LicenseWidget
	{
		return this.#cache.remember('widget', () => {
			if (this.#options.isCloud)
			{
				return Bitrix24LicenseWidget.getInstance();
			}

			return IntranetLicenseWidget.getInstance();
		});
	}

	static #getWidgetLoader(): WidgetLoader
	{
		return this.#cache.remember('widgetLoader', () => {
			return new WidgetLoader({
				bindElement: this.#buttonWrapper,
				width: 374,
				id: 'bx-license-header-popup',
			});
		});
	}

	static #getContent(): Promise
	{
		return this.#cache.remember('content', () => {
			return new Promise((resolve, reject) => {
				if (this.#options.isCloud)
				{
					ajax.runComponentAction('bitrix:bitrix24.license.widget', 'getData', {
						mode: 'class',
					})
						.then((response) => resolve(response))
						.catch((response) => reject(response))
					;
				}
				else
				{
					ajax.runAction('intranet.license.widget.getContent')
						.then((response) => resolve(response))
						.catch((response) => reject(response))
					;
				}
			});
		});
	}

	static #getCounter(): Counter
	{
		return this.#cache.remember('counter', () => {
			return new Counter({
				color: CounterColor.DANGER,
				useAirDesign: true,
				style: CounterStyle.FILLED_ALERT,
			});
		});
	}

	static #getCounterWrapper(): HTMLElement
	{
		return this.#cache.remember('counter-wrapper', () => {
			return this.#buttonWrapper.querySelector('.air-header-button__counter');
		});
	}

	static #setCounterValue(value: number): void
	{
		if (value < 1)
		{
			this.#getCounter().destroy();
			this.#cache.delete('counter');
		}

		if (value > 0 && this.#getCounterWrapper())
		{
			this.#getCounter().update(value);
			this.#getCounter().renderTo(this.#getCounterWrapper());
		}
	}

	static #setEventHandlers(): void
	{
		if (this.#options.isCloud && this.#options.isSidePanelDemoLicense)
		{
			BX.SidePanel.Instance.bindAnchors({
				rules: [
					{
						condition: [
							/\/settings\/license_demo.php/,
						],
						handler(event)
						{
							FeaturePromotersRegistry.getPromoter({ code: 'limit_demo' }).show();
							event.stopPropagation();
							event.preventDefault();
						},
					},
				],
			});
		}

		if (this.#options.isCloud && this.#options.isAdmin)
		{
			PULL.subscribe({
				moduleId: 'bitrix24',
				command: 'updateCountOrdersAwaitingPayment',
				callback: (params) => {
					this.#updateOptionsFromPull(params);
				},
			});
			EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'Bitrix24InfrastructureSlider:show', this.#showInfrastructureSlider.bind(this));
		}
	}

	static #updateOptionsFromPull(params): void
	{
		if (!this.#options.isCloud)
		{
			return;
		}

		this.#options.ordersCount = params.orders.ordersCount;
		this.#options.ordersInfo = params.orders.ordersInfo;

		if (params.shouldShow)
		{
			this.#setCounterValue(params.ordersTotalCount);
		}
		else
		{
			this.#setCounterValue(0);
		}

		this.#emitOrdersUpdate(params);
	}

	static #emitOrdersUpdate(params): void
	{
		EventEmitter.emit('BX.Bitrix24.Orders:updateOrdersAwaitingPayment', new BaseEvent({
			data: {
				orders: {
					ordersCount: params.orders?.ordersCount,
					ordersInfo: params.orders?.ordersInfo,
				},
				shouldShow: params.shouldShow,
				ordersTotalCount: params.ordersTotalCount,
			},
		}));
	}

	static #showInfrastructureSlider(): void
	{
		const params = this.#options.infrastructureForm;

		BX.SidePanel.Instance.open(
			'bx-infrastructure-slider',
			{
				contentCallback: () => {
					return `<script data-b24-form="inline/${params.id}/${params.secCode}" data-skip-moving="true"></script>`;
				},
				width: 664,
				loader: 'default-loader',
				cacheable: false,
				closeByEsc: false,
				data: { rightBoundary: 0 },
				events: {
					onOpen: () => {
						(function(w, d, u) {
							const s = d.createElement('script');
							s.async = true;
							s.src = `${u}?${Date.now() / 180_000 | 0}`;
							const h = d.getElementsByTagName('script')[0];
							h.parentNode.insertBefore(s, h);
						})(window, document, `https://bitrix24.team/upload/crm/form/loader_${params.id}_${params.secCode}.js`);
					},
					onOpenComplete: () => {
						top.addEventListener('b24:form:send:success', (event) => {
							if (event.detail.object.identification.id === params.id)
							{
								ajax.runComponentAction('bitrix:bitrix24.license.widget', 'setOptionWaitingInfrastructure', {
									mode: 'class',
									data: {},
								});
							}
						});
					},
				},
			},
		);
	}
}
