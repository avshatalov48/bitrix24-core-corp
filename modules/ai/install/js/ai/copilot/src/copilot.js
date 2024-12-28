import { Tag, Type, Dom, Event, Runtime, Cache, Extension } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Popup, PopupManager } from 'main.popup';
import { Engine, Text as PayloadText } from 'ai.engine';
import { type CopilotImageController, type saveEventData } from 'ai.copilot.copilot-image-controller';
import type { CopilotBanner as CopilotBannerType } from 'ai.copilot-banner';
import { checkCopilotAgreement } from './helpers/check-copilot-agreement';

import './css/main.css';
import 'ui.design-tokens';
import { CopilotAnalytics } from './copilot-analytics';
import { CopilotMenu, CopilotMenuEvents, type CopilotMenuItem } from './copilot-menu/index';

import { CopilotResult } from './copilot-result';
import { CopilotInput, CopilotInputEvents } from './copilot-input-field/src/copilot-input';
import { CopilotWarningResultField } from './copilot-warning-result-field';
import type { CopilotTextController } from 'ai.copilot.copilot-text-controller';

export const CopilotMode = Object.freeze({
	TEXT: 'text',
	IMAGE: 'image',
	TEXT_AND_IMAGE: 'text-and-image',
});

export type CopilotOptions = {
	category: string;
	menu: CopilotMenuOption;
	selectedText?: string;
	context?: string;
	moduleId: string;
	contextId: string;
	contextParameters: any;
	autoHide: boolean;
	preventAutoHide: Function;
	prompts: Prompt[];
	menuForceTop: boolean;
	readonly: boolean;
	withImage: boolean;
	onlyImage: boolean;
	mode: CopilotMode.TEXT | CopilotMode.IMAGE | CopilotMode.TEXT_AND_IMAGE;
	useText: boolean;
	useImage: boolean;
	extraMarkers: {[string]: string};
	showResultInCopilot?: boolean;
};

export type CopilotMenuOption = {
	general: CopilotMenuItem[];
	context: CopilotMenuItem[];
}

type Prompt = {
	code: string;
	title: string;
	icon: string;
	section: string;
	required: PromptRequired,
	children: Prompt[],
	workWithResult: boolean;
}

type PromptRequired = {
	context_message: boolean;
	user_message: boolean;
}

type InitEngineOptions = {
	moduleId: string;
	contextId: string;
	contextParameters: any;
	category: string;
	extraMarkers: {[string]: string};
}

type InitCopilotTextControllerOptions = {
	readonly: boolean,
	category: string,
	selectedText: string,
	context: string,
	addImageMenuItem: boolean,
}

export const CopilotEvents = {
	START_INIT: 'start-init',
	FINISH_INIT: 'finish-init',
	FAILED_INIT: 'failed-init',
	HIDE: 'hide',
	IMAGE_SAVE: 'save-image',
	TEXT_SAVE: 'save',
	IMAGE_PLACE_ABOVE: 'place-image-above',
	IMAGE_PLACE_UNDER: 'place-image-under',
	IMAGE_CANCEL: 'cancel-image',
	TEXT_CANCEL: 'cancel',
	IMAGE_COMPLETION_RESULT: 'image-completion-result',
	TEXT_COMPLETION_RESULT: 'text-completion-result',
	TEXT_PLACE_BELOW: 'add_below',
};

const cache = new Cache.MemoryCache();

export async function loadExtensionWrapper(extensionName: string): Promise<any>
{
	return cache.remember(extensionName, () => {
		return Runtime.loadExtension(extensionName);
	});
}

export class Copilot extends EventEmitter
{
	#copilotPopup: Popup;
	#inputField: CopilotInput;
	#resultField: CopilotResult;
	#engine: Engine;
	#preventAutoHide: void | null;
	#autoHide: boolean;
	#container: HTMLElement | null = null;
	#warningField: CopilotWarningResultField = null;
	#copilotBanner: CopilotBannerType;
	#copilotAgreementWasApplied: boolean;
	#copilotImageController: CopilotImageController;
	#copilotTextController: CopilotTextController;
	#readonly: boolean;
	#category: string;
	#selectedText: string;
	#context: string;
	#analytics: CopilotAnalytics;
	#useText: boolean;
	#useImage: boolean;
	#showResultInCopilot: ?boolean;
	#windowResizeHandler: Function;

	static #staticEulaRestrictCallback: Function | false = null;

	static showBanner: boolean = null;

	/**
	 * If function returns TRUE - ai using is restricted,
	 * If FALSE - ai using is available
	 * @returns {Promise<boolean>}
	 */
	static async checkEulaRestrict(): Promise<boolean>
	{
		const Feature = await loadExtensionWrapper('bitrix24.license.feature');
		if (!Feature?.Feature)
		{
			return false;
		}

		const isRestrictionCheckInProgress = Type.isFunction(Copilot.#staticEulaRestrictCallback?.then);
		const isRestrictionNotChecked = Copilot.#staticEulaRestrictCallback === null;

		if (isRestrictionNotChecked || isRestrictionCheckInProgress)
		{
			try
			{
				if (isRestrictionNotChecked)
				{
					Copilot.#staticEulaRestrictCallback = Feature.Feature.checkEulaRestrictions('ai_available_by_version');
				}

				await Copilot.#staticEulaRestrictCallback;

				Copilot.#staticEulaRestrictCallback = false;

				return false;
			}
			catch (err)
			{
				if (err.callback)
				{
					Copilot.#staticEulaRestrictCallback = err.callback;

					return true;
				}

				console.error(err);

				return false;
			}
		}

		return Type.isFunction(Copilot.#staticEulaRestrictCallback);
	}

	constructor(options: CopilotOptions)
	{
		super(options);

		this.setEventNamespace('AI.Copilot');
		this.#category = options.category;
		this.#selectedText = options.selectedText;
		this.#readonly = options.readonly === true;
		this.#context = options.context;
		this.#useText = Type.isBoolean(options.useText) ? options.useText : true;
		this.#useImage = options.useImage === true;
		this.#showResultInCopilot = options.showResultInCopilot;

		this.#initEngine({
			category: options.category,
			contextId: options.contextId,
			moduleId: options.moduleId,
			contextParameters: options.contextParameters,
			extraMarkers: options.extraMarkers,
		});

		this.#inputField = new CopilotInput({
			readonly: options.readonly === true,
		});

		this.#resultField = new CopilotResult();
		this.#warningField = new CopilotWarningResultField();

		this.#autoHide = options.autoHide ?? false;
		this.#preventAutoHide = Type.isFunction(options.preventAutoHide) ? options.preventAutoHide : () => false;
	}

	render(): HTMLElement
	{
		this.#container = Tag.render`
			<div class="ai__copilot ai__copilot-scope">
				${this.#resultField.render()}
				${this.#inputField.render()}
				${this.#warningField.render()}
			</div>
		`;

		return this.#container;
	}

	async init(): void
	{
		this.emit(CopilotEvents.START_INIT);

		try
		{
			if (Extension.getSettings('ai.copilot').isRestrictByEula)
			{
				await Copilot.checkEulaRestrict();
			}

			if (this.#useText)
			{
				await this.#initCopilotTextController({
					readonly: this.#readonly,
					category: this.#category,
					selectedText: this.#selectedText,
					context: this.#context,
					addImageMenuItem: this.#useImage,
				});

				await this.#initCopilotTextControllerMenu();

				if (this.#copilotTextController.isFirstLaunch())
				{
					const { AppsInstallerBanner } = await Runtime.loadExtension('ai.copilot-banner');

					this.#copilotBanner = new AppsInstallerBanner({});
				}
			}
			else
			{
				await this.#initCopilotImageController();
			}

			this.emit(CopilotEvents.FINISH_INIT);
		}
		catch (err)
		{
			console.error(err);
			this.emit(CopilotEvents.FAILED_INIT);
		}
	}

	show(options: {}): void
	{
		if (this.isInitFinished() === false)
		{
			console.error('AI.Copilot: The copilot cannot be opened until initialization is complete.');

			return;
		}

		if (this.#copilotTextController && this.#copilotTextController.isPromptsLoaded() === false)
		{
			console.error('AI.Copilot: Prompts were not loaded!');

			return;
		}

		if (this.#copilotBanner)
		{
			this.#showCopilotAfterCopilotBanner(options);

			return;
		}

		if (Extension.getSettings('ai.copilot').get('isShowAgreementPopup') && !this.#copilotAgreementWasApplied)
		{
			this.#showCopilotAfterApplyAgreement(options);

			return;
		}

		if (Type.isFunction(Copilot.#staticEulaRestrictCallback))
		{
			Copilot.#staticEulaRestrictCallback();

			this.#getAnalytics(true).sendEventOpen('error_agreement');

			return;
		}

		if (!this.#copilotPopup)
		{
			Event.bind(document, 'mousedown', this.#mouseDownHandler.bind(this));
		}

		this.#copilotPopup?.destroy();
		this.#initCopilotPopup({
			width: options.width,
			bindElement: options.bindElement,
		});

		this.#copilotPopup.show();

		if (this.#useText)
		{
			this.#copilotTextController?.setCopilotContainer(this.#container);
			this.#copilotTextController?.start();
			this.#inputField.setUseForImages(false);
		}
		else
		{
			this.#copilotImageController.setCopilotContainer(this.#container);
			this.#copilotImageController.start();
			this.#inputField.setUseForImages(true);
		}

		this.#getAnalytics(true).sendEventOpen('success');

		this.#inputField.focus();

		this.#adjustMenus();
	}

	hide(): void
	{
		this.#copilotPopup?.close();
	}

	isShown(): boolean
	{
		return this.#copilotPopup?.isShown() ?? false;
	}

	isInitFinished(): boolean
	{
		if (this.#useText)
		{
			return Boolean(this.#copilotTextController?.isInitFinished());
		}

		return true;
	}

	adjustWidth(width)
	{
		this.#copilotPopup?.setWidth(width);
	}

	adjust(options)
	{
		if (!this.#copilotPopup || this.#copilotPopup?.isDestroyed())
		{
			return;
		}

		if (options.hide)
		{
			this.#copilotPopup?.setMaxWidth(0);
			this.#copilotPopup?.setMinWidth(0);
			this.#hideMenus();
			this.adjustPosition(options.position);
			this.#getBaasPopup()?.close();
		}
		else
		{
			this.#copilotPopup?.setMaxWidth(null);
			this.#copilotPopup?.setMinWidth(null);
			this.adjustPosition(options.position);
			this.#adjustMenus();
			this.#copilotImageController?.showMenu();
			this.#copilotTextController?.showMenu();
			this.#getBaasPopup()?.adjustPosition();
		}
	}

	#getBaasPopup(): ?Popup
	{
		return PopupManager.getPopups().find((popup) => popup.getId().includes('baas'));
	}

	adjustPosition(position)
	{
		if (!this.#copilotPopup)
		{
			return;
		}

		if (position)
		{
			this.#copilotPopup.setBindElement(position);
		}
		this.#copilotPopup.adjustPosition({
			forceBindPosition: true,
		});

		this.#getBaasPopup()?.adjustPosition({
			forceBindPosition: true,
			forceTop: true,
		});
		this.#adjustMenus();
	}

	setSelectedText(text: string): void
	{
		this.#copilotTextController?.setSelectedText(text);
	}

	setContext(text: string): void
	{
		this.#copilotTextController?.setContext(text);
	}

	setContextParameters(contextParameters: any): void
	{
		this.#engine.setContextParameters(contextParameters);
	}

	setExtraMarkers(extraMarkers: {[string]: string}): void
	{
		const extraMarkersWithoutSystemMarkers = {
			...extraMarkers,
			original_message: undefined,
			user_message: undefined,
		};

		const payload = this.#engine?.getPayload() || new PayloadText();

		payload.setMarkers({
			...payload.getMarkers(),
			...extraMarkersWithoutSystemMarkers,
		});

		this.#engine.setPayload(payload);

		this.#copilotTextController?.setExtraMarkers(extraMarkers);
	}

	getPosition(): { inputField: DOMRect, menu: DOMRect} {
		return {
			inputField: Dom.getPosition(this.#copilotPopup.getPopupContainer()),
			menu: Dom.getPosition(this.#getOpenMenu()?.getPopupContainer()),
		};
	}

	#initCopilotPopup(options: {width: number, bindElement: any}): void
	{
		this.#copilotPopup = new Popup({
			className: 'ai__copilot_input-popup',
			bindElement: options.bindElement,
			content: this.render(),
			padding: 0,
			width: options.width,
			contentNoPaddings: true,
			borderRadius: '0px',
			autoHide: this.#autoHide,
			closeByEsc: true,
			cacheable: false,
			autoHideHandler: (event) => this.#autoHideHandler(event),
			events: {
				onPopupClose: () => {
					Dom.removeClass(this.#container, '--error');
					this.#copilotTextController?.finish();
					this.#copilotImageController?.finish();
					this.#inputField.stopRecording();
					this.#getBaasPopup()?.close();
					this.emit(CopilotEvents.HIDE);
					Event.unbind(window, 'resize', this.#windowResizeHandler);
				},
				onPopupShow: () => {
					this.#windowResizeHandler = () => (this.#inputField.adjustHeight());
					Event.bind(window, 'resize', this.#windowResizeHandler);
					this.#inputField.clearErrors();
				},
			},
		});
	}

	#showCopilotAfterCopilotBanner(showCopilotOptions): void
	{
		this.#copilotBanner.show();

		this.#copilotBanner.subscribe('action-finish-success', () => {
			Copilot.showBanner = false;
			this.#copilotBanner = null;
			this.#engine.setBannerLaunched();
			setTimeout(() => {
				this.show(showCopilotOptions);
			}, 300);
		});
	}

	#initEngine(initEngineOptions: InitEngineOptions): void
	{
		this.#engine = new Engine();
		this.#engine
			.setModuleId(initEngineOptions.moduleId)
			.setContextId(initEngineOptions.contextId)
			.setContextParameters(initEngineOptions.contextParameters)
			.setParameters({
				promptCategory: initEngineOptions.category,
			});

		this.setExtraMarkers(initEngineOptions.extraMarkers);
	}

	async #initCopilotImageController(): void
	{
		const { CopilotImageController: ImageController } = await loadExtensionWrapper('ai.copilot.copilot-image-controller');

		this.#copilotImageController = new ImageController({
			inputField: this.#inputField,
			engine: this.#engine,
			copilotContainer: this.#container,
			copilotInputEvents: CopilotInputEvents,
			copilotMenu: CopilotMenu,
			popupWithoutBackBtn: this.#useImage && this.#useText === false,
			useInsertAboveAndUnderMenuItems: this.#useText,
			analytics: this.#getAnalytics(true),
		});

		await this.#copilotImageController.init();

		this.#copilotImageController.subscribe('back', () => {
			this.#copilotImageController.finish();
			this.#copilotTextController.start();
			this.#inputField.setUseForImages(false);
		});

		this.#copilotImageController.subscribe('close', () => {
			this.#copilotImageController.finish();
			this.hide();
		});

		this.#copilotImageController.subscribe('save', (event: BaseEvent<saveEventData>) => {
			this.emit(CopilotEvents.IMAGE_SAVE, new BaseEvent({
				data: {
					imageUrl: event.getData().imageUrl,
				},
			}));
		});

		this.#copilotImageController.subscribe('place-above', () => {
			this.emit(CopilotEvents.IMAGE_PLACE_ABOVE);
		});

		this.#copilotImageController.subscribe('place-under', () => {
			this.emit(CopilotEvents.IMAGE_PLACE_UNDER);
		});

		this.#copilotImageController.subscribe('cancel', () => {
			this.emit(CopilotEvents.IMAGE_CANCEL);
		});

		this.#copilotImageController.subscribe('completion-result', (event: BaseEvent) => {
			this.emit(CopilotEvents.IMAGE_COMPLETION_RESULT, new BaseEvent({
				data: {
					imageUrl: event.getData().imageUrl,
				},
			}));
		});
	}

	async #initCopilotTextController(options: InitCopilotTextControllerOptions): void
	{
		const { CopilotTextController: TextController } = await Runtime.loadExtension('ai.copilot.copilot-text-controller');

		this.#copilotTextController = new TextController({
			engine: this.#engine,
			inputField: this.#inputField,
			readonly: options.readonly,
			category: options.category,
			selectedText: options.selectedText,
			context: options.context,
			addImageMenuItem: options.addImageMenuItem,
			warningField: this.#warningField,
			resultField: this.#resultField,
			copilotInputEvents: CopilotInputEvents,
			copilotMenu: CopilotMenu,
			copilotMenuEvents: CopilotMenuEvents,
			analytics: this.#getAnalytics(),
			showResultInCopilot: this.#showResultInCopilot,
		});

		this.#copilotTextController.subscribe('aiResult', (event: BaseEvent) => {
			this.emit('aiResult', {
				result: event.getData().result,
			});

			this.emit(CopilotEvents.TEXT_COMPLETION_RESULT, {
				result: event.getData().result,
			});
		});

		this.#copilotTextController.subscribe('prompt-master-show', () => {
			this.#copilotPopup.setClosingByEsc(false);
		});

		this.#copilotTextController.subscribe('prompt-master-destroy', () => {
			this.#copilotPopup.setClosingByEsc(true);
		});

		this.#copilotTextController.subscribe('close', () => {
			this.#copilotTextController.finish();
			this.hide();
		});

		this.#copilotTextController.subscribe('save', (event: BaseEvent) => {
			this.emit(CopilotEvents.TEXT_SAVE, {
				...event.getData(),
			});
		});

		this.#copilotTextController.subscribe('add_below', (event: BaseEvent) => {
			this.emit(CopilotEvents.TEXT_PLACE_BELOW, {
				...event.getData(),
			});
		});

		this.#copilotTextController.subscribe('show-image-configurator', async () => {
			await this.#initCopilotImageController();
			this.#copilotTextController.finish();
			this.#inputField.setUseForImages(true);
			this.#copilotImageController.start();
			this.#inputField.focus();
		});

		this.#copilotTextController.subscribe('cancel', () => {
			this.emit(CopilotEvents.TEXT_CANCEL);
		});
	}

	async #initCopilotTextControllerMenu(): void
	{
		await this.#copilotTextController.init();
	}

	#getOpenMenu(): Popup | null
	{
		return this.#copilotTextController?.getOpenMenu()?.getPopup() || this.#copilotImageController?.getOpenMenuPopup();
	}

	#mouseDownHandler(event)
	{
		this.wasMouseDownOnSelf = this.#copilotPopup.getPopupContainer()?.contains(event.target);
	}

	#autoHideHandler(event): boolean
	{
		const target = event.target;

		const isSelf = this.#copilotPopup.getPopupContainer().contains(target);
		const isWarningFieldInfoSlider = this.#warningField.getInfoSliderContainer()?.contains(target);
		const preventAutoHide = this.#preventAutoHide(event);
		const isClickOnSlider = Boolean(event.target.closest('.side-panel'));
		const isClickOnRolesDialog = Boolean(event.target.closest('.ai_roles-dialog_popup'));
		const isClickOnPromptMasterPopup = Boolean(event.target.closest('.ai__prompt-master-popup'));
		const isClickOnOverlay = Boolean(event.target.closest('.popup-window-overlay'));
		const isClickOnAnotherPopup = Boolean(event.target.closest('.popup-window'));
		const isClickOnBaasPopup = Boolean(this.#getBaasPopup()?.getPopupContainer().contains(target));
		const isClickOnNotificationBalloon = Boolean(event.target.closest('.ui-notification-balloon'));

		const shouldBeHidden = !isSelf
			&& !this.#copilotTextController?.isContainsElem(target)
			&& !this.#copilotImageController?.isContainsTarget(target)
			&& !preventAutoHide
			&& !this.wasMouseDownOnSelf
			&& !isWarningFieldInfoSlider
			&& !isClickOnSlider
			&& !isClickOnRolesDialog
			&& !isClickOnPromptMasterPopup
			&& !isClickOnAnotherPopup
			&& !isClickOnBaasPopup
			&& !isClickOnOverlay
			&& !isClickOnNotificationBalloon
		;

		if (shouldBeHidden)
		{
			this.hide();
		}

		this.wasMouseDownOnSelf = false;

		return false;
	}

	#hideMenus()
	{
		this.#copilotTextController?.hideAllMenus();
		this.#copilotImageController?.hideAllMenus();
	}

	#adjustMenus()
	{
		this.#copilotTextController?.adjustMenusPosition();
		this.#copilotImageController?.adjustMenusPosition();
	}

	#getAnalytics(withReset = false): CopilotAnalytics
	{
		if (!this.#analytics || withReset)
		{
			this.#analytics = new CopilotAnalytics();
			this.#analytics
				.setContextSection(this.#category)
			;

			if (this.#useText)
			{
				if (this.#readonly)
				{
					this.#initAnalyticForReadOnly();
				}
				else
				{
					this.#initAnalyticForText();
				}
			}
			else if (this.#useImage)
			{
				this.#analytics.setCategoryImage();
			}
		}

		return this.#analytics;
	}

	#initAnalyticForText(): void
	{
		this.#analytics.setCategoryText();

		if (!this.#copilotTextController)
		{
			return;
		}

		if (!this.#copilotTextController.getSelectedText()?.trim() && !this.#copilotTextController.getContext()?.trim())
		{
			this.#analytics.setTypeTextNew();
		}
		else if (this.#copilotTextController.getSelectedText())
		{
			this.#analytics.setTypeTextEdit();
		}
		else
		{
			this.#analytics.setTypeTextReply();
		}

		if (this.#copilotTextController.getSelectedText())
		{
			this.#analytics.setContextElementPopupButton();
		}
		else
		{
			this.#analytics.setContextElementSpaceButton();
		}
	}

	#initAnalyticForReadOnly(): void
	{
		this.#analytics.setCategoryReadonly();

		if (!this.#copilotTextController)
		{
			return;
		}

		if (this.#copilotTextController.getSelectedText())
		{
			this.#analytics.setContextElementReadonlyQuote();
		}
		else
		{
			this.#analytics.setContextElementReadonlyCommon();
		}
	}

	async #showCopilotAfterApplyAgreement(showCopilotOptions: Object): void
	{
		this.#copilotAgreementWasApplied = await checkCopilotAgreement({
			moduleId: this.#engine.getModuleId(),
			contextId: this.#engine.getContextId(),
			events: {
				onAccept: () => {
					this.#copilotAgreementWasApplied = true;
					this.show(showCopilotOptions);
				},
				onCancel: () => {
					this.#copilotAgreementWasApplied = false;
					this.#copilotAgreementWasApplied = undefined;
				},
			},
		});

		if (this.#copilotAgreementWasApplied)
		{
			this.show(showCopilotOptions);
		}
	}
}
