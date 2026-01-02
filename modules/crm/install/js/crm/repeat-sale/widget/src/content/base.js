import { Builder } from 'crm.integration.analytics';
import { ajax as Ajax, Dom, Loc, Tag, Type } from 'main.core';
import 'ui.design-tokens';
import type { PopupOptions } from 'main.popup';
import { Popup } from 'main.popup';
import { sendData } from 'ui.analytics';
import { Confetti } from 'ui.confetti';
import { Lottie } from 'ui.lottie';
import { UI } from 'ui.notification';
import type { WidgetParams, WidgetTypeEnum } from '../widget';

export class Base
{
	#data: ?Object = null;
	#popup: ?Popup = null;
	#bindElement: ?HTMLElement = null;
	#isConfettiShowed: boolean = true;
	#isPreparing: boolean = false;
	params: WidgetParams = {};

	constructor(params: WidgetParams = {})
	{
		this.params = params;

		if (Type.isBoolean(this.params.showConfetti))
		{
			this.#isConfettiShowed = !this.params.showConfetti;
		}
	}

	getType(): WidgetTypeEnum
	{
		throw new Error('Must be implement in child class');
	}

	async show(forceShowConfetti: boolean = false, onCloseCallback: ?Function = null): void
	{
		const data = await this.getData();

		if (data === null)
		{
			return;
		}

		if (this.#popup === null)
		{
			this.#popup = new Popup(this.getPopupParams(data, { forceShowConfetti, onCloseCallback }));
		}

		this.#popup.show();
		this.#popup.adjustPosition();
	}

	getPopupParams(data: Object, params: Object = {}): PopupOptions
	{
		return {
			id: `crm_repeat_sale_widget_${this.getType()}`,
			bindElement: this.#getBindElementData(),
			content: this.getPopupContent(data),
			cacheable: false,
			isScrollBlock: false,
			className: `crm-repeat-sale-widget-popup --${this.getType()}`,
			closeByEsc: true,
			closeIcon: true,
			padding: 16,
			width: this.getPopupWidth(),
			maxHeight: 500,
			overlay: null,
			autoHide: this.isAutoHidePopup(),
			events: {
				onclose: () => {
					this.onClose();

					if (Type.isFunction(params?.onCloseCallback))
					{
						params.onCloseCallback();
					}
				},
				onFirstShow: () => {
					this.onFirstShow();

					if (this.#isConfettiShowed && params?.forceShowConfetti !== true)
					{
						return;
					}

					setTimeout(() => {
						this.#showConfetti();
						this.#isConfettiShowed = true;
					}, 100);
				},
			},
		};
	}

	#getBindElementData(): Object
	{
		const bindElement = this.#getParentBindElement();

		const bindElementRect = bindElement.getBoundingClientRect();

		return {
			top: bindElementRect.top + bindElementRect.height + 5 + window.pageYOffset,
			left: bindElementRect.right - (bindElement.clientWidth / 2) - (this.getPopupWidth() / 2) + window.pageXOffset,
		};
	}

	#getParentBindElement(): HTMLElement
	{
		const hasParentButton = Boolean(this.#bindElement.closest('button'));
		const hasParentLink = Boolean(this.#bindElement.closest('a'));

		if (hasParentButton)
		{
			return this.#bindElement.closest('button');
		}

		if (hasParentLink)
		{
			return this.#bindElement.closest('a');
		}

		return this.#bindElement;
	}

	getPopupWidth(): number
	{
		return 469;
	}

	isAutoHidePopup(): boolean
	{
		return false;
	}

	onFirstShow(): void
	{
		// may be implement in child class
	}

	getPopupContent(data: ?Object = null): HTMLElement
	{
		throw new Error('Must be implement in child class');
	}

	setPopupContent(content: HTMLElement): void
	{
		this.#popup.setContent(content);
	}

	onClose(): void
	{
		this.#sendAnalyticsCloseEvent();

		if (this.params.showConfetti)
		{
			void Ajax.runAction('crm.repeatsale.widget.incrementShowedConfettiCount');
		}

		this.#popup = null;
	}

	#sendAnalyticsCloseEvent(): void
	{
		const type = this.getAnalyticsType();
		const subSection = this.getAnalyticsSubSection();

		sendData(Builder.RepeatSale.Banner.CloseEvent.createDefault(type, subSection).buildData());
	}

	getAnalyticsType(): string
	{
		return '';
	}

	#showConfetti(): void
	{
		const container = this.#popup?.getPopupContainer();
		if (!container)
		{
			return;
		}

		let canvas = null;

		if (container.getElementsByTagName('canvas').length === 0)
		{
			canvas = Tag.render`<canvas></canvas>`;

			Dom.style(canvas, {
				position: 'fixed',
				top: 0,
				left: 0,
				pointerEvents: 'none',
				zIndex: '9',
				width: '100%',
				height: '100%',
			});

			Dom.append(canvas, this.#popup.getPopupContainer());
		}
		else
		{
			canvas = container.getElementsByTagName('canvas')[0];
		}

		const confetti = Confetti.create(canvas, {
			resize: true,
			useWorker: true,
		});

		confetti({
			particleCount: 400,
			origin: {
				y: 1.2,
				x: 0,
			},
			spread: 100,
		});
	}

	async getData(): Object
	{
		this.#data = await this.fetchData();

		return this.#data;
	}

	async fetchData(): Promise
	{
		if (this.#isPreparing)
		{
			return Promise.resolve(null);
		}

		this.#isPreparing = true;

		return new Promise((resolve) => {
			Ajax
				.runAction(this.getFetchUrl(), { data: this.getFetchParams() })
				.then(
					(response) => {
						this.#isPreparing = false;
						if (response.status === 'success')
						{
							resolve(response.data);

							return;
						}

						this.showError();
					},
					() => {
						this.#isPreparing = false;
						this.showError();
					},
				)
				.catch((response) => {
					this.#isPreparing = false;
					this.showError();

					throw response;
				})
			;
		});
	}

	getFetchUrl(): string
	{
		throw new Error('Must be implement in child class');
	}

	getFetchParams(): Object
	{
		return {};
	}

	showError(): void
	{
		const messageCode = 'CRM_REPEAT_SALE_WIDGET_ERROR';

		UI.Notification.Center.notify({
			content: Loc.getMessage(messageCode),
			autoHideDelay: 6000,
		});
	}

	setBindElement(element: HTMLElement): Base
	{
		this.#bindElement = element;

		return this;
	}

	getAnalyticsSubSection(): ?string
	{
		return this.#getParentBindElement().dataset.subsection ?? null;
	}

	isShown(): boolean
	{
		return this.#popup?.isShown();
	}

	close(): void
	{
		this.#popup?.close();
	}

	renderLottieAnimation(): HTMLElement
	{
		const container = Tag.render`
			<div class="crm-rs__w-lottie-container">
				<div ref="lottie" class="crm-rs__w-lottie"></div>
			</div>
		`;

		const mainAnimation = Lottie.loadAnimation({
			path: '/bitrix/js/crm/repeat-sale/widget/lottie/animation.json',
			container: container.lottie,
			renderer: 'svg',
			loop: true,
			autoplay: true,
		});

		mainAnimation.setSpeed(0.75);

		return container.root;
	}
}
