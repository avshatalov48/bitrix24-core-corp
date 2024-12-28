import type { EngineInfo, ImageCopilotFormat, ImageCopilotStyle } from 'ai.engine';
import { Engine, Text as TextPayload } from 'ai.engine';
import { Dom, Loc } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { AjaxErrorHandler } from 'ai.ajax-error-handler';
import type { CopilotAnalytics } from '../../src/copilot-analytics';
import { ImageConfiguratorErrorMenuItems } from './menu-items/error-menu-items';
import { ImageConfiguratorResultMenuItems } from './menu-items/result-menu-items';
import { ImageConfiguratorPopup, ImageConfiguratorPopupEvents } from './image-configurator-popup';
import type { CopilotInput, CopilotInputEvents, CopilotMenu } from 'ai.copilot';

export type saveEventData = {
	imageUrl: string | null;
}

type CopilotImageControllerOptions = {
	inputField: CopilotInput;
	copilotContainer: HTMLElement;
	engine: Engine;
	copilotInputEvents: CopilotInputEvents;
	copilotMenu: CopilotMenu;
	popupWithoutBackBtn: boolean;
	useInsertAboveAndUnderMenuItems: boolean;
	analytics: CopilotAnalytics;
}

export class CopilotImageController extends EventEmitter
{
	#analytics: CopilotAnalytics;
	#copilotContainer: HTMLElement;
	#inputField: CopilotInput;
	#engine: Engine;
	#imageConfiguratorPopup: ImageConfiguratorPopup;
	#errorMenu: CopilotMenu;
	#resultMenu: CopilotMenu;
	#resultImageUrl: string | null;
	#copilotInputEvents: CopilotInputEvents;
	#CopilotMenu: CopilotMenu;
	#popupWithoutBackBtn: boolean;
	#currentGenerateRequestId: number;
	#useInsertAboveAndUnderTextMenuItems: boolean;
	#formats: ImageCopilotFormat[] = [];
	#styles: ImageCopilotStyle[] = [];
	#engines: EngineInfo[] = [];

	#inputFieldCancelLoadingEventHandler: Function;
	#inputFieldSubmitEventHandler: Function;
	#inputFieldAdjustHeightEventHandler: Function;

	constructor(options: CopilotImageControllerOptions)
	{
		super(options);

		this.setEventNamespace('AI.CopilotImage');

		this.#resultImageUrl = null;
		this.#inputField = options.inputField;
		this.#copilotContainer = options.copilotContainer;
		this.#engine = options.engine;
		this.#copilotInputEvents = options.copilotInputEvents;
		this.#CopilotMenu = options.copilotMenu;
		this.#popupWithoutBackBtn = options.popupWithoutBackBtn === true;
		this.#useInsertAboveAndUnderTextMenuItems = options.useInsertAboveAndUnderMenuItems;
		this.#analytics = options.analytics;

		this.#inputFieldSubmitEventHandler = this.#handleInputFieldSubmitEvent.bind(this);
		this.#inputFieldCancelLoadingEventHandler = this.#handleInputFieldCancelLoadingEvent.bind(this);
		this.#inputFieldAdjustHeightEventHandler = this.#handleInputFieldAdjustHeightEvent.bind(this);
	}

	setCopilotContainer(copilotContainer: HTMLElement)
	{
		this.#copilotContainer = copilotContainer;
	}

	getResultImageUrl(): string | null
	{
		return this.#resultImageUrl;
	}

	isContainsTarget(target: HTMLElement): boolean
	{
		const isImageConfiguratorPopup = this.#imageConfiguratorPopup?.isContainsTarget(target);
		const isErrorMenu = this.#errorMenu?.contains(target);
		const isResultMenu = this.#resultMenu?.contains(target);

		return isImageConfiguratorPopup || isErrorMenu || isResultMenu;
	}

	async init(): void
	{
		const res = await this.#engine.getImageCopilotTooling();

		this.#formats = res.data.params.formats;
		this.#engines = res.data.engines;
		this.#styles = res.data.params.styles;
	}

	showImageConfigurator(): void
	{
		if (!this.#imageConfiguratorPopup)
		{
			this.#initImageConfiguratorPopup();
		}

		this.#imageConfiguratorPopup.show();
	}

	showMenu(): void
	{
		this.#resultMenu?.show();
		this.#errorMenu?.show();
		this.#imageConfiguratorPopup?.show();
	}

	#initImageConfiguratorPopup(): void
	{
		this.#imageConfiguratorPopup = new ImageConfiguratorPopup({
			bindElement: this.#copilotContainer,
			popupId: 'ai-image-configuration-popup',
			popupOffset: {
				top: 8,
			},
			withoutBackBtn: this.#popupWithoutBackBtn,
			imageConfiguratorOptions: {
				styles: this.#styles,
				formats: this.#formats,
				engines: this.#engines,
			},
		});

		if (!this.#inputField.getValue())
		{
			this.#imageConfiguratorPopup.disableSubmitButton();
		}

		this.#imageConfiguratorPopup.subscribe(ImageConfiguratorPopupEvents.completions, (e: BaseEvent) => {
			const { style, format, engine } = e.getData();

			this.#setPayload({
				style,
				format,
				engine,
			});

			this.completions();
		});

		this.#imageConfiguratorPopup.subscribe(ImageConfiguratorPopupEvents.selectEngine, (event) => {
			const engineCode = event.getData();
			const oldSelectedEngineCode = this.#imageConfiguratorPopup.getImageConfiguration().engine;
			let isRequestComplete = false;

			setTimeout(() => {
				if (isRequestComplete === false)
				{
					this.#imageConfiguratorPopup.showLoader();
				}
			}, 300);

			this.#engine.getImageEngineParams(engineCode)
				.then((res) => {
					const data = res.data;

					this.#imageConfiguratorPopup.setFormats(data.formats);
				})
				.catch((error) => {
					BX.UI.Notification.Center.notify({
						content: Loc.getMessage('AI_COPILOT_IMAGE_FETCH_NEW_ENGINE_PARAMS_ERROR'),
					});

					this.#imageConfiguratorPopup.setSelectedEngine(oldSelectedEngineCode);

					console.error(error);
				})
				.finally(() => {
					isRequestComplete = true;
					this.#imageConfiguratorPopup.hideLoader();
				});
		});

		this.#imageConfiguratorPopup.subscribe(ImageConfiguratorPopupEvents.back, () => {
			this.#unsubscribeFromInputFieldEvents();
			this.emit('back');
		});
	}

	getAnalytics(): CopilotAnalytics
	{
		const usedTextInput = this.#inputField.usedTextInput();
		const usedVoiceRecord = this.#inputField.usedVoiceRecord();

		if (usedTextInput && usedVoiceRecord)
		{
			this.#analytics.setContextTypeFromTextAndAudio();
		}
		else if (usedTextInput)
		{
			this.#analytics.setContextTypeFromText();
		}
		else if (usedVoiceRecord)
		{
			this.#analytics.setContextTypeFromAudio();
		}

		this.#analytics.setCategoryImage();
		this.#analytics.setTypeImageNew();

		return this.#analytics;
	}

	start(): void
	{
		this.showImageConfigurator();
		this.#subscribeToInputFieldEvents();
	}

	finish(): void
	{
		this.#currentGenerateRequestId = -1;
		this.destroyAllMenus();
		this.#unsubscribeFromInputFieldEvents();
	}

	isShown(): boolean
	{
		return this.#imageConfiguratorPopup.isShown();
	}

	getOpenMenuPopup(): Popup | null
	{
		return this.#errorMenu?.getPopup()
				|| this.#resultMenu?.getPopup()
				|| this.#imageConfiguratorPopup?.getPopup()
				|| null;
	}

	hideAllMenus(): void
	{
		this.#imageConfiguratorPopup?.hide();
		this.#resultMenu?.hide();
		this.#errorMenu?.hide();
	}

	destroyAllMenus(): void
	{
		this.#errorMenu?.close();
		this.#resultMenu?.close();
		this.#imageConfiguratorPopup?.destroy();

		this.#errorMenu = null;
		this.#resultMenu = null;
		this.#imageConfiguratorPopup = null;
	}

	adjustMenusPosition(): void
	{
		this.#imageConfiguratorPopup?.adjustPosition();
		this.#resultMenu?.adjustPosition();
		this.#errorMenu?.adjustPosition();
	}

	async completions(): void
	{
		this.destroyAllMenus();
		Dom.removeClass(this.#copilotContainer, '--error');
		this.#inputField.startGenerating();

		try
		{
			const id = Math.round(Math.random() * 10000);

			this.#currentGenerateRequestId = id;

			const res = await this.#engine.imageCompletions();

			if (this.#currentGenerateRequestId !== id)
			{
				return;
			}

			this.#resultImageUrl = JSON.parse(res.data.result)[0];
			this.#inputField.finishGenerating();
			this.#showResultMenu();

			this.emit('completion-result', new BaseEvent({
				data: {
					imageUrl: this.#resultImageUrl,
				},
			}));
		}
		catch (error)
		{
			this.#inputField.finishGenerating();
			this.#handleCompletionsError(error);
		}
	}

	#subscribeToInputFieldEvents(): void
	{
		this.#inputField.subscribe(this.#copilotInputEvents.input, (event: BaseEvent) => {
			const inputFieldValue = event.getData();

			if (inputFieldValue)
			{
				this.#imageConfiguratorPopup?.enableSubmitButton();
			}
			else
			{
				this.#imageConfiguratorPopup?.disableSubmitButton();
			}
		});
		this.#inputField.subscribe(this.#copilotInputEvents.cancelLoading, this.#inputFieldCancelLoadingEventHandler);
		this.#inputField.subscribe(this.#copilotInputEvents.submit, this.#inputFieldSubmitEventHandler);
		this.#inputField.subscribe(this.#copilotInputEvents.adjustHeight, this.#inputFieldAdjustHeightEventHandler);
	}

	#unsubscribeFromInputFieldEvents(): void
	{
		this.#inputField.unsubscribe(this.#copilotInputEvents.cancelLoading, this.#inputFieldCancelLoadingEventHandler);
		this.#inputField.unsubscribe(this.#copilotInputEvents.submit, this.#inputFieldSubmitEventHandler);
		this.#inputField.unsubscribe(this.#copilotInputEvents.adjustHeight, this.#inputFieldAdjustHeightEventHandler);
	}

	#handleCompletionsError(error): void
	{
		const firstError = error?.errors?.[0];

		if (firstError && firstError?.code === 'LIMIT_IS_EXCEEDED_BAAS')
		{
			this.#inputField.disable();
		}
		else if (firstError && (
			firstError.code === 'LIMIT_IS_EXCEEDED_MONTHLY'
			|| firstError.code === 'LIMIT_IS_EXCEEDED_DAILY'
			|| firstError.code === 'SERVICE_IS_NOT_AVAILABLE_BY_TARIFF'
		))
		{
			this.emit('close');
		}
		else if (firstError)
		{
			Dom.addClass(this.#copilotContainer, '--error');
			this.#showErrorMenu();

			this.#inputField.setErrors([{
				code: firstError.code,
				message: firstError.message,
			}]);
		}
		else
		{
			this.#inputField.setErrors([{
				code: -1,
				message: Loc.getMessage('AI_COPILOT_IMAGE_GENERATION_ERROR'),
			}]);
		}

		AjaxErrorHandler.handleImageGenerateError({
			baasOptions: {
				bindElement: this.#inputField.getContainer().querySelector('.ai__copilot_input-field-baas-point'),
				context: this.#engine.getContextId(),
				useAngle: false,
			},
			errorCode: firstError?.code,
		});
	}

	#setPayload(options: { style: string, format: string, engine: string }): void
	{
		const payload = new TextPayload({
			prompt: this.#inputField.getValue(),
			engineCode: options.engine,
		});

		payload.setMarkers({
			style: options.style,
			format: options.format,
		});

		this.#engine.setPayload(payload);

		this.#engine.setAnalyticParameters({
			type: this.getAnalytics().getType(),
			c_sub_section: this.getAnalytics().getCSubSection(),
			c_section: this.getAnalytics().getCSection(),
			c_element: this.getAnalytics().getCElement(),
		});

		this.getAnalytics()
			.setP1('prompt', options.style)
			.setP2('format', options.format);
	}

	#handleInputFieldSubmitEvent(): void
	{
		if (!this.#imageConfiguratorPopup?.isShown() || !this.#inputField.getValue()?.trim())
		{
			return;
		}

		const { style, format, engine } = this.#imageConfiguratorPopup.getImageConfiguration();

		this.#setPayload({
			style,
			format,
			engine,
		});

		this.completions();
	}

	#handleInputFieldCancelLoadingEvent(): void
	{
		this.#currentGenerateRequestId = -1;
		this.#inputField.finishGenerating();
		this.#inputField.focus();
		this.showImageConfigurator();
	}

	#handleInputFieldAdjustHeightEvent(): void
	{
		this.#resultMenu?.adjustPosition();
		this.#errorMenu?.adjustPosition();
		this.#imageConfiguratorPopup?.adjustPosition();
	}

	#showResultMenu(): void
	{
		if (!this.#resultMenu)
		{
			this.#initResultMenu();
		}

		this.#resultMenu.open();
	}

	#initResultMenu(): void
	{
		this.#resultMenu = new this.#CopilotMenu({
			bindElement: this.#copilotContainer,
			offsetTop: 8,
			offsetLeft: 0,
			items: ImageConfiguratorResultMenuItems.getMenuItems({
				copilotImageController: this,
				inputField: this.#inputField,
				useInsertAboveAndUnderMenuItems: this.#useInsertAboveAndUnderTextMenuItems,
			}),
			keyboardControlOptions: {
				clearHighlightAfterType: false,
				canGoOutFromTop: false,
				highlightFirstItemAfterShow: true,
			},
			cacheable: false,
		});

		this.#resultMenu.setBindElement(this.#copilotContainer, {
			left: 0,
			top: 8,
		});
	}

	#showErrorMenu(): void
	{
		if (!this.#errorMenu)
		{
			this.#initErrorMenu();
		}

		this.#errorMenu.setBindElement(this.#copilotContainer, {
			top: 8,
			left: 0,
		});

		this.#errorMenu?.open();
	}

	#initErrorMenu(): void
	{
		this.#errorMenu = new this.#CopilotMenu({
			bindElement: this.#copilotContainer,
			offsetTop: 8,
			items: ImageConfiguratorErrorMenuItems.getMenuItems({
				copilotImageController: this,
				inputField: this.#inputField,
				copilotContainer: this.#copilotContainer,
			}),
			keyboardControlOptions: {
				canGoOutFromTop: false,
				highlightFirstItemAfterShow: true,
				clearHighlightAfterType: false,
			},
			cacheable: false,
		});
	}
}
