import { Engine } from 'ai.engine';
import { Text as PayloadText } from 'ai.payload.textpayload';
import { Tag, Browser, Type, Dom, bind, unbind, Extension, Runtime } from 'main.core';
import { Popup } from 'main.popup';

import { PickerText } from './picker-text';
import { PickerImage } from './picker-image';
import { ScrollTopButton } from './ui/scroll-top-button';
import { Header } from './ui/header';
import { PickerAnalytic } from './picker-analytic';

import { Loc } from './loc';
import UI from './ui/index';
import {
	HistoryItem,
	PickerOptions,
} from './types';

import './css/main.css';

interface LangSpace {
	text: 'text',
	image: 'image',
}

export class Picker
{
	static LangSpace: LangSpace = {
		text: 'text',
		image: 'image',
	};

	#startMessage: string;
	#onSelectCallback: () => {};
	#onTariffRestriction: Function;
	#engine: Engine;
	#popups: Map;
	#currentPopup: Popup;
	#popupContainer: ?HTMLElement;
	#contentWrapper: HTMLElement | null;
	#scrollTopButton: ScrollTopButton;
	#saveImages: boolean;
	#engines: Object;
	#promptsHistory: Object;

	#articleCode: number | null;
	#analytic: PickerAnalytic;
	#analyticLabel: string;

	#pickerImage: PickerImage | null;
	#pickerText: PickerText | null;
	#verticalMargin: number;

	constructor(options: PickerOptions)
	{
		// super(options);
		this.#engine = new Engine();
		this.#popups = new Map();
		this.#popupContainer = options.popupContainer || document.body;
		this.#startMessage = options.startMessage;
		this.#onSelectCallback = options.onSelect;
		this.#onTariffRestriction = options.onTariffRestriction;
		this.#articleCode = null;
		this.#analyticLabel = options.analyticLabel;
		this.#saveImages = options.saveImages === true;
		this.#engines = {};
		this.#promptsHistory = {};
		this.#verticalMargin = 25;

		this.#analytic = new PickerAnalytic({
			analyticLabel: this.#analyticLabel,
		});

		this.#engine
			.setModuleId(options.moduleId)
			.setContextId(options.contextId)
			.setHistoryState(options.history);

		this.#pickerImage = null;
		this.#pickerText = null;
	}

	async initTooling(): void
	{
		const res = await this.#engine.getTooling('text');
		this.#engines.text = res.data.engines;
		this.#promptsHistory.text = res.data.history;

		return true;
	}

	/**
	 * Sets language space. For different interface may be used different phrases.
	 * See all bunches of phrases in lang/config.php.
	 *
	 * @param {LangSpace} spaceCode
	 * @return {Picker}
	 */
	setLangSpace(spaceCode: LangSpace): this
	{
		Loc.getInstance().setSpace(spaceCode);

		return this;
	}

	setSelectCallback(callback): void
	{
		this.#onSelectCallback = callback;
	}

	setEngineParameters(parameters)
	{
		if (this.#engine)
		{
			this.#engine.setParameters(parameters);
			if (this.#pickerImage)
			{
				this.#pickerImage.setEngineParameters(parameters);
			}

			if (this.#pickerText)
			{
				this.#pickerText.setEngineParameters(parameters);
			}
		}
	}

	setStartMessage(message: string)
	{
		this.#startMessage = Type.isString(message) ? message : this.#startMessage;
	}

	/**
	 * Shows popup for text completion.
	 */
	text(): void
	{
		this.#analytic.labels.open();
		this.#articleCode = 17_587_362;

		const popup = this.#popups.get('text');

		if (this.#pickerText)
		{
			const scroll = this.#popupContainer === document.body ? window.pageYOffset : 0;

			popup.setBindElement({
				left: this.#popupContainer.offsetWidth - popup.getWidth() - 25,
				top: 25 + scroll,
			});

			popup.adjustPosition();

			this.#currentPopup = popup;

			this.#pickerText.resetResultUsedFlag();

			this.#show();
		}
		else
		{
			this.#initPickerText();
			this.#registerPopup('text', this.#pickerText.render({
				textMessageText: this.#startMessage,
			}), {
				contentClassname: '',
			});

			this.#pickerText.resetResultUsedFlag();

			this.#show();
		}
	}

	/**
	 * Shows popup for image completion.
	 */
	async image(): void
	{
		const isRestrictedByEula = Extension.getSettings('ai.picker').get('isRestrictedByEula');

		let Feature = null;
		if (isRestrictedByEula)
		{
			Feature = await Runtime.loadExtension('bitrix24.license.feature');

			try
			{
				await Feature.Feature.checkEulaRestrictions('ai_available_by_version');
			}
			catch (err)
			{
				if (Type.isFunction(err?.callback))
				{
					err?.callback();
				}
			}
		}
		else
		{
			this.#analytic.labels.open();
			this.#articleCode = 17_586_054;

			if (this.#pickerImage)
			{
				const popup = this.#popups.get('image');
				const scroll = this.#popupContainer === document.body ? window.pageYOffset : 0;
				this.#currentPopup = popup;

				popup.setBindElement({
					left: this.#popupContainer.offsetWidth - popup.getWidth() - 25,
					top: 25 + scroll,
				});

				popup.adjustPosition();
			}
			else
			{
				this.#initPickerImage();
				this.#registerPopup('image', this.#pickerImage.render(), {
					width: 550,
					contentClassname: '--image',
					headerClassname: '--image',
				});
			}

			this.#pickerImage.resetResultUsedFlag();

			this.#show();
		}
	}

	/**
	 * Called when user want to use HistoryItem somewhere outside.
	 * @param {HistoryItem} item
	 * @param {Promise} promise
	 */
	#onSelect(item: HistoryItem, promise)
	{
		if (Type.isFunction(this.#onSelectCallback))
		{
			this.#onSelectCallback(item, promise);
		}

		this.#currentPopup.close();
	}

	/**
	 * Shows selected popup.
	 */
	#show(): void
	{
		this.#currentPopup.show();
	}

	#getScrollWidth(): number
	{
		const div = Tag.render`<div style="overflow-y: scroll; width: 50px; height: 50px; opacity: 0; pointer-events: none; position: absolute;"></div>`;
		Dom.append(div, document.body);
		const scrollWidth = div.offsetWidth - div.clientWidth;
		Dom.remove(div);

		return scrollWidth;
	}

	#fixOverlayFreez(popupId: string)
	{
		if (!popupId)
		{
			return;
		}

		const overlayNode = this.#popups.get(popupId).overlay.element;
		Dom.style(overlayNode, 'padding-right', `${this.#getScrollWidth()}px`);
	}

	/**
	 * Registers certain popup.
	 *
	 * @param {string} popupId
	 * @param content
	 * @param options
	 */
	#registerPopup(popupId: string, content: HTMLElement, options): void
	{
		const popupWidth = options?.width || 450;
		const contentClassname = options.contentClassname || '';
		const headerClassname = options.headerClassname || '';
		const adjustPosition = this.#adjustPopupPosition.bind(this);

		if (!this.#popups.has(popupId))
		{
			this.#popups.set(popupId, new Popup({
				bindElement: this.#getPopupPosition(popupWidth),
				className: 'ai__picker-popup',
				autoHide: true,
				closeByEsc: false,
				width: popupWidth,
				height: this.#getPopupMaxHeight(),
				disableScroll: true,
				padding: 0,
				borderRadius: '12px',
				contentBorderRadius: '12px',
				overlay: {
					backgroundColor: '#fff',
					opacity: 50,
				},
				animation: {
					showClassName: 'ai__picker-popup-show',
					closeClassName: 'ai__picker-popup-hide',
					closeAnimationType: 'animation',
				},
				targetContainer: this.#popupContainer,
				events: {
					onPopupShow: () => {
						this.#fixOverlayFreez(popupId);
						Dom.style(document.body, 'overflow-x', 'hidden');
					},
					onPopupAfterClose: () => {
						Dom.style(document.body, 'overflow-x', null);
					},
					onAfterShow: () => {
						bind(window, 'resize', adjustPosition);
					},
					onPopupClose: () => {
						this.#sendCancelAnalyticLabelIfNeeded();
						if (this.#pickerImage)
						{
							this.#pickerImage.closeAllMenus();
						}

						if (this.#pickerText)
						{
							this.#pickerText.closeAllMenus();
						}

						unbind(window, 'resize', adjustPosition);
					},
				},
			}));
		}

		this.#currentPopup = this.#popups.get(popupId);

		if (this.#currentPopup.isShown())
		{
			this.#currentPopup.close();
		}

		this.#setContent(this.#currentPopup, this.#renderPopupContent(content, { contentClassname, headerClassname }));
	}

	#sendCancelAnalyticLabelIfNeeded(): void
	{
		if (this.#pickerText && !this.#pickerText.isResultUsed())
		{
			this.#analytic.labels.cancel();
		}

		if (this.#pickerImage && !this.#pickerImage.isResultUsed())
		{
			this.#analytic.labels.cancel();
		}
	}

	#adjustPopupPosition(): void
	{
		this.#currentPopup.setBindElement(this.#getPopupPosition());
		this.#currentPopup.setHeight(this.#getPopupMaxHeight());
		this.#currentPopup.adjustPosition();

		Dom.style(this.#contentWrapper, 'height', `${this.#getContentMaxHeight()}px`);
	}

	#getPopupPosition(popupWidthParam: number): {top: number, left: number}
	{
		const scroll = this.#popupContainer === document.body ? window.pageYOffset : 0;
		const popupWidth = popupWidthParam || this.#currentPopup.getWidth();

		return {
			left: this.#popupContainer.offsetWidth - popupWidth - 25,
			top: 25 + scroll,
		};
	}

	#getPopupMaxHeight(): number
	{
		const height = this.#popupContainer.clientHeight > window.innerHeight
			? window.innerHeight
			: this.#popupContainer.clientHeight;

		return height - this.#verticalMargin * 2;
	}

	/**
	 * Sets content to certain popup.
	 * Content depends on specified fields before popup registration.
	 *
	 * @param {Popup} popup
	 * @param content
	 */
	// eslint-disable-next-line class-methods-use-this
	#setContent(popup: Popup, content: HTMLElement): void
	{
		popup.setContent(content);
	}

	#getContentMaxHeight(): number
	{
		const headerHeight = 94;

		return this.#currentPopup.getHeight() - headerHeight;
	}

	#renderPopupContent(contentElem: HTMLElement, options): HTMLElement
	{
		const contentStyle = `height: ${this.#getContentMaxHeight()}px`;

		const contentClassname = options?.contentClassname || '';
		const headerClassname = options?.headerClassname || '';

		this.#contentWrapper = Tag.render`
			<div
				class="ai__picker_content ${Browser.isMac() ? '--is-mac-os' : ''} ${contentClassname}"
				style="${contentStyle}"
			>
				${contentElem}
			</div>
		`;

		bind(this.#contentWrapper, 'scroll', () => {
			if (this.#contentWrapper.scrollTop > 200)
			{
				this.#scrollTopButton.show();
			}
			else
			{
				this.#scrollTopButton.hide();
			}

			if (this.#pickerImage)
			{
				this.#pickerImage.closeAllMenus();
			}

			if (this.#pickerText)
			{
				this.#pickerText.closeAllMenus();
			}
		});

		this.#scrollTopButton = new ScrollTopButton();
		this.#scrollTopButton.hide();

		this.#scrollTopButton.subscribe('click', () => {
			this.#contentWrapper.scrollTo({
				top: 0,
			});
		});

		return Tag.render`
			<div class="ai__picker">
				<div>
					<div class="ai__picker-header">
						${this.#renderPopupHeader({ className: headerClassname })}
					</div>
				</div>
				${this.#contentWrapper}
				${this.#scrollTopButton.render()}
			</div>
		`;
	}

	#renderPopupHeader(options = {}): Header
	{
		const header = new UI.Header({
			articleCode: this.#articleCode,
			className: options.className,
		});

		header.subscribe('click-close-icon', () => {
			this.#currentPopup.close();
		});

		return header.render();
	}

	async #initPickerText(): void
	{
		if (this.#pickerText)
		{
			return;
		}

		this.#pickerText = new PickerText({
			onTariffRestriction: this.#onTariffRestriction,
			onSelect: this.#handleSelect.bind(this),
			onCopy: this.#handleCopy.bind(this),
		});

		const generate = (prompt: string, engineCode: string) => {
			this.#engine.setPayload(new PayloadText({
				engineCode,
				prompt,
			}));

			this.#analytic.labels.generate(prompt);

			return this.#engine.textCompletions();
		};

		this.#pickerText.setOnGenerate(generate);
		this.#pickerText.setStartMessage(this.#startMessage);
		this.#pickerText.setEngine(this.#engine);
		this.#pickerText.setContext(this.#popupContainer);
		this.#pickerText.initTooling('text');

		this.#pickerText.subscribe('select', this.#handleSelect.bind(this));
	}

	#initPickerImage(): void
	{
		if (this.#pickerImage)
		{
			return;
		}

		this.#pickerImage = new PickerImage({
			onTariffRestriction: this.#onTariffRestriction,
			onSelect: this.#handleImageSelect.bind(this),
			context: this.#popupContainer,
		});

		const generate = ((prompt: string, engineCode: string) => {
			this.#engine.setPayload(new PayloadText({
				prompt,
				engineCode,
			}));

			this.#analytic.labels.generate(prompt);

			this.#engine.setAnalyticParameters({
				type: 'create_image',
				c_section: this.#getCSection(),
			});

			return this.#engine.imageCompletions();
		});

		this.#pickerImage.setOnGenerate(generate);
		this.#pickerImage.setStartMessage(this.#startMessage);
		this.#pickerImage.setEngine(this.#engine);
		this.#pickerImage.setContext(this.#popupContainer);
		this.#pickerImage.initTooling('image');

		this.#pickerImage.subscribe('select', this.#handleSelect.bind(this));
	}

	#handleSelect(item)
	{
		this.#analytic.labels.paste();
		this.#onSelect(item);
	}

	#handleCopy(item): void
	{
		BX.clipboard.copy(item.data);
		this.#analytic.labels.copy();
	}

	async #handleImageSelect(pictureUrl): void
	{
		this.#analytic.labels.paste();

		if (this.#saveImages)
		{
			const promise = new Promise((resolve, reject) => {
				this.#engine.saveImage(pictureUrl)
					.then((res) => {
						resolve(res.data);
					})
					.catch((err) => {
						reject(err);
					});
			});
			this.#onSelect(pictureUrl, promise);
		}
		else
		{
			this.#onSelect(pictureUrl);
		}
	}

	#getCSection(): string
	{
		if (!this.#analyticLabel)
		{
			return '';
		}

		return this.#analyticLabel
			.split('_')
			.map((word) => {
				return word[0].toUpperCase() + word.slice(1);
			})
			.join('');
	}
}
