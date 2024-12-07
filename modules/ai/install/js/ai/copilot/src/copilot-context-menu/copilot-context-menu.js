import { type CopilotBanner } from 'ai.copilot-banner';
import { type Prompt } from 'ai.engine';
import { BaseError, Dom, Extension, Loc, Runtime, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import type { CopilotTextControllerEngine as CopilotTextControllerEngineType } from 'ai.copilot.copilot-text-controller';
import {
	AboutCopilotMenuItem,
	MarketMenuItem,
	ProviderMenuItem,
	SettingsMenuItem,
	FeedbackMenuItem,
	OpenCopilotMenuItem,
	ConnectModelMenuItem,
} from 'ai.copilot.copilot-text-controller';
import { OpenFeedbackFormCommand } from '../../copilot-text-controller/src/menu-item-commands/index';
import { CopilotMenu, CopilotMenuEvents } from '../copilot-menu/copilot-menu';
import { CopilotContextMenuResultPopup, CopilotContextMenuResultPopupEvents } from './copilot-context-menu-result-popup';
import { CopilotContextMenuLoader, CopilotContextMenuLoaderEvents } from './copilot-context-menu-loader';
import { BaseMenuItem } from '../copilot-menu/copilot-menu-item';
import type { CopilotMenuItem } from '../index';
import { CopilotContextMenuErrorPopup, CopilotContextMenuErrorPopupEvents } from './copilot-readonly-error-popup';
import { CopilotEula } from '../copilot-eula/copilot-eula';
import { CopilotAnalytics } from '../copilot-analytics';
import { AjaxErrorHandler } from 'ai.ajax-error-handler';
import { checkCopilotAgreement } from '../helpers/check-copilot-agreement';

export type CopilotContextMenuOptions = {
	category: string;
	moduleId: string;
	contextId: string;
	bindElement?: HTMLElement | bindElementPosition;
	contextParameters?: any;
	context?: string;
	selectedText?: string;
	extraResultMenuItems?: CopilotMenuItem[];
	angle?: boolean;
}

export class CopilotContextMenu extends EventEmitter
{
	#bindElement: HTMLElement;
	#copilotTextControllerEngine: CopilotTextControllerEngineType;
	#context: string;
	#selectedText: string;
	#generalMenu: CopilotMenu;
	#resultPopup: CopilotContextMenuResultPopup;
	#loaderPopup: CopilotContextMenuLoader;
	#errorPopup: CopilotContextMenuErrorPopup;
	#extraResultMenuItems: CopilotMenuItem[];
	#angle: boolean;
	#initEngineOptions: InitEngineOptions;

	static #copilotBanner: CopilotBanner;

	constructor(options: CopilotContextMenuOptions)
	{
		super(options);
		this.setEventNamespace('AI.CopilotReadonly');

		this.#validateOptions(options);

		this.#bindElement = options.bindElement;
		this.#context = options.context || '';
		this.#selectedText = options.selectedText || '';
		this.#extraResultMenuItems = options.extraResultMenuItems ?? [];
		this.#angle = options.angle === true;

		this.#initEngineOptions = {
			moduleId: options.moduleId,
			contextId: options.contextId,
			category: options.category,
			contextParameters: options.contextParameters,
		};
	}

	async init(): Promise<void>
	{
		if (Extension.getSettings('ai.copilot').isRestrictByEula)
		{
			await CopilotEula.init();
		}

		try
		{
			await this.#initEngine(this.#initEngineOptions);

			this.#initGeneralMenu();

			if (this.#isNeedToShowCopilotBanner())
			{
				const { AppsInstallerBanner } = await Runtime.loadExtension('ai.copilot-banner');

				CopilotContextMenu.#copilotBanner = new AppsInstallerBanner({});
			}
		}
		catch (e)
		{
			console.error('Init error', e);
			throw e;
		}
	}

	#isNeedToShowCopilotBanner(): boolean
	{
		return this.#copilotTextControllerEngine.isCopilotFirstLaunch() && CopilotContextMenu.#copilotBanner === undefined;
	}

	#isShowCopilotAgreementPopup(): boolean
	{
		return Extension.getSettings('ai.copilot').isShowAgreementPopup ?? false;
	}

	getResultText(): string | null
	{
		return this.#resultPopup?.getResult() || null;
	}

	show(): void
	{
		const isRestrictedByEula = CopilotEula.checkRestricted();
		if (isRestrictedByEula)
		{
			this.#getAnalytics().sendEventOpen('error_agreement');

			return;
		}

		if (CopilotContextMenu.#copilotBanner)
		{
			this.#showAfterCopilotBanner();

			return;
		}

		if (this.#isShowCopilotAgreementPopup())
		{
			const moduleId = this.#initEngineOptions.moduleId;
			const contextId = this.#initEngineOptions.contextId;

			// eslint-disable-next-line promise/catch-or-return
			checkCopilotAgreement({
				moduleId,
				contextId,
				events: {
					onAccept: () => {
						this.#showGeneralMenu();
						this.#getAnalytics().sendEventOpen('success');
					},
				},
			})
				.then((isAccepted) => {
					if (isAccepted)
					{
						this.#showGeneralMenu();
						this.#getAnalytics().sendEventOpen('success');
					}
				});

			return;
		}

		this.#showGeneralMenu();
		this.#getAnalytics().sendEventOpen('success');
	}

	#showAfterCopilotBanner(): void
	{
		CopilotContextMenu.#copilotBanner.show();
		CopilotContextMenu.#copilotBanner.subscribe('action-finish-success', () => {
			CopilotContextMenu.#copilotBanner = null;
			this.#copilotTextControllerEngine.setCopilotBannerLaunchedFlag();
			setTimeout(() => {
				this.show();
			}, 300);
		});
	}

	hide(): void
	{
		this.#destroyGeneralMenu();
		this.#resultPopup?.destroy();
		this.#destroyErrorPopup();
	}

	isShown(): boolean
	{
		return this.#generalMenu?.isShown()
			|| this.#resultPopup?.isShown()
			|| this.#loaderPopup?.isShown()
			|| this.#errorPopup?.isShown();
	}

	adjustPosition(): void
	{
		this.#generalMenu?.adjustPosition();
		this.#errorPopup?.adjustPosition();
		this.#loaderPopup?.adjustPosition();
		this.#resultPopup?.adjustPosition();
	}

	setContext(context: string): void
	{
		this.#validateContextOption(context);
		this.#context = context || '';
		this.#copilotTextControllerEngine?.setContext(this.#context);
	}

	setSelectedText(selectedText: string): void
	{
		this.#validateContextOption(selectedText);

		this.#selectedText = selectedText || '';

		this.#copilotTextControllerEngine?.setContext(this.#selectedText);
	}

	setBindElement(bindElement: HTMLElement | bindElementPosition): void
	{
		this.#bindElement = bindElement;
		this.#generalMenu?.setBindElement(bindElement);
		this.#errorPopup?.setBindElement(bindElement);
		this.#resultPopup?.setBindElement(bindElement);
		this.#loaderPopup?.setBindElement(bindElement);
	}

	async #completions(): void
	{
		try
		{
			this.#destroyGeneralMenu();
			this.#showLoaderPopup();

			this.#copilotTextControllerEngine.setAnalyticParameters(this.#getAnalyticParametersForCompletions());

			const result: string | null = await this.#copilotTextControllerEngine.completions();

			if (result)
			{
				this.#setResultPopupText(result);
				this.#hideLoaderPopup();
				this.#showResultPopup();
			}
		}
		catch (e)
		{
			this.#handleCompletionsError(e);
		}
	}

	#handleCompletionsError(error: BaseError): void
	{
		this.#hideLoaderPopup();

		const code = error.getCode();

		switch (code)
		{
			case 'AI_ENGINE_ERROR_OTHER': {
				error.setMessage(Loc.getMessage('AI_COPILOT_ERROR_OTHER'));

				const command = new OpenFeedbackFormCommand({
					category: this.#copilotTextControllerEngine.getCategory(),
					isBeforeGeneration: false,
					copilotTextController: this.#copilotTextControllerEngine,
				});

				error.setCustomData({
					clickHandler: () => command.execute(),
					clickableText: Loc.getMessage('AI_COPILOT_ERROR_CONTACT_US'),
				});

				this.#showErrorPopup(error);

				break;
			}

			case 'LIMIT_IS_EXCEEDED_BAAS': {
				this.#showErrorPopup(error);

				break;
			}
			case 'LIMIT_IS_EXCEEDED_MONTHLY':
			case 'LIMIT_IS_EXCEEDED_DAILY':
			case 'SERVICE_IS_NOT_AVAILABLE_BY_TARIFF': {
				this.hide();

				break;
			}

			default:
			{
				if (Type.isStringFilled(error.getCode()) === false)
				{
					error.setCode('undefined');
				}

				this.#showErrorPopup(error);
			}
		}

		requestAnimationFrame(() => {
			AjaxErrorHandler.handleTextGenerateError({
				baasOptions: {
					bindElement: this.#bindElement,
					context: this.#copilotTextControllerEngine.getContextId(),
					useAngle: true,
				},
				errorCode: error.getCode(),
			});
		});
	}

	#getAnalyticParametersForCompletions(): {[key: string]: string}
	{
		const analytics = this.#getAnalytics();

		return {
			category: analytics.getCategory(),
			type: analytics.getType(),
			c_sub_section: analytics.getCSubSection(),
			c_element: analytics.getCElement(),
		};
	}

	async #initEngine(initEngineOptions: InitEngineOptions): void
	{
		const { CopilotTextControllerEngine } = await Runtime.loadExtension('ai.copilot.copilot-text-controller');

		this.#copilotTextControllerEngine = new CopilotTextControllerEngine({
			moduleId: initEngineOptions.moduleId,
			contextParameters: initEngineOptions.contextParameters,
			contextId: initEngineOptions.contextId,
			category: initEngineOptions.category,
		});

		this.#copilotTextControllerEngine.setContext(this.#selectedText || this.#context);

		await this.#copilotTextControllerEngine.init();
	}

	#getGeneralMenuItems(): CopilotMenuItem[]
	{
		const promptsWithoutZeroPrompt = this.#copilotTextControllerEngine.getPrompts().slice(1);

		const promptsMenuItems = promptsWithoutZeroPrompt.map((prompt: Prompt): CopilotMenuItem => {
			return this.#getPromptMenuItemFromPrompt(prompt);
		});

		return [
			...promptsMenuItems,
			{
				separator: true,
			},
			(new OpenCopilotMenuItem({
				children: this.#getProviderMenuItems(),
			})).getOptions(),
			(new AboutCopilotMenuItem()).getOptions(),
			(new FeedbackMenuItem({
				engine: this.#copilotTextControllerEngine,
				isBeforeGeneration: true,
			})).getOptions(),
		];
	}

	#getPromptMenuItemFromPrompt(prompt: Prompt): CopilotMenuItem
	{
		const promptChildren = prompt.children || [];
		const promptMenuItem = new BaseMenuItem({
			code: prompt.code,
			text: prompt.title,
			icon: prompt.icon,
			onClick: () => {
				this.#copilotTextControllerEngine.setCommandCode(prompt.code);
				this.#copilotTextControllerEngine.setContext(this.#selectedText || this.#context);
				this.#completions();
			},
			children: promptChildren.map((childPrompt) => {
				return this.#getPromptMenuItemFromPrompt(childPrompt);
			}),
		});

		if (prompt.separator)
		{
			return {
				separator: true,
				section: prompt.section,
				title: prompt.title,
			};
		}

		return promptMenuItem.getOptions();
	}

	#getProviderMenuItems(): BaseMenuItem
	{
		const providers = this.#copilotTextControllerEngine.getEngines().map((engine) => {
			return new ProviderMenuItem({
				code: engine.code,
				text: engine.title,
				selected: this.#copilotTextControllerEngine.getSelectedEngineCode() === engine.code,
				onClick: () => {
					this.#copilotTextControllerEngine.setSelectedEngineCode(engine.code);
					this.#generalMenu.replaceMenuItemSubmenu((new OpenCopilotMenuItem({
						children: this.#getProviderMenuItems(),
					})).getOptions());
				},
			});
		});

		const result = [
			...providers,
			{
				separator: true,
			},
			new ConnectModelMenuItem(),
			new MarketMenuItem(),
		];

		if (this.#copilotTextControllerEngine.getPermissions()?.can_edit_settings)
		{
			result.push(new SettingsMenuItem());
		}

		return result;
	}

	#showResultPopup(): void
	{
		if (!this.#resultPopup)
		{
			this.#initResultPopup();
		}

		this.#resultPopup.show();
	}

	#setResultPopupText(text: string): void
	{
		if (!this.#resultPopup)
		{
			this.#initResultPopup();
		}

		this.#resultPopup.setResult(text);
	}

	#initResultPopup(): void
	{
		this.#resultPopup = new CopilotContextMenuResultPopup({
			bindElement: this.#bindElement,
			additionalResultMenuItems: this.#extraResultMenuItems,
			engine: this.#copilotTextControllerEngine,
			analytics: this.#getAnalytics(),
		});

		this.#resultPopup.subscribe(CopilotContextMenuResultPopupEvents.SAVE, () => {
			this.#resultPopup.destroy();
			this.#errorPopup.destroy();
			this.#destroyGeneralMenu();
		});

		this.#resultPopup.subscribe(CopilotContextMenuResultPopupEvents.CANCEL, () => {
			this.hide();
		});

		this.#resultPopup.subscribe(CopilotContextMenuResultPopupEvents.CHANGE_REQUEST, () => {
			this.#resultPopup.destroy();
			this.#showGeneralMenu();
		});
	}

	#initLoaderPopup(): void
	{
		this.#loaderPopup = new CopilotContextMenuLoader({
			bindElement: this.#bindElement,
		});

		this.#loaderPopup.subscribe(CopilotContextMenuLoaderEvents.CANCEL, () => {
			this.#copilotTextControllerEngine.cancelCompletion();
			this.#loaderPopup.destroy();
			this.#showGeneralMenu();
		});
	}

	#showLoaderPopup(): void
	{
		if (!this.#loaderPopup)
		{
			this.#initLoaderPopup();
		}

		this.#loaderPopup.show();
	}

	#hideLoaderPopup(): void
	{
		this.#loaderPopup?.destroy();
	}

	#showGeneralMenu()
	{
		if (!this.#generalMenu)
		{
			this.#initGeneralMenu();
		}

		this.#generalMenu.setBindElement(this.#bindElement);
		this.#generalMenu.adjustPosition();

		this.#generalMenu.open();
	}

	#destroyGeneralMenu()
	{
		this.#generalMenu?.close();
		this.#generalMenu = null;
	}

	#initGeneralMenu()
	{
		this.#generalMenu = new CopilotMenu({
			items: this.#getGeneralMenuItems(),
			bindElement: this.#bindElement,
			cacheable: false,
			autoHide: true,
			forceTop: false,
			angle: this.#getGeneralMenuAngleOptions(),
			bordered: false,
		});

		this.#generalMenu.subscribe(CopilotMenuEvents.close, () => {
			this.#generalMenu = null;
		});
	}

	#getGeneralMenuAngleOptions(): {offset?: number, position?: string} | null
	{
		if (!this.#angle)
		{
			return null;
		}

		if (Type.isElementNode(this.#bindElement))
		{
			return {
				offset: Dom.getPosition(this.#bindElement).width,
				position: 'top',
			};
		}

		if (Type.isObject(this.#bindElement))
		{
			return {
				position: 'top',
			};
		}

		return null;
	}

	#showErrorPopup(error: BaseError): void
	{
		if (!this.#errorPopup)
		{
			this.#initErrorPopup(error);
		}

		this.#errorPopup.setError(error);
		this.#errorPopup.show();
	}

	#destroyErrorPopup(): void
	{
		this.#errorPopup?.destroy();
	}

	#initErrorPopup(error: BaseError): void
	{
		this.#errorPopup = new CopilotContextMenuErrorPopup({
			error,
			bindElement: this.#bindElement,
		});

		this.#errorPopup.subscribe(CopilotContextMenuErrorPopupEvents.CANCEL, () => {
			this.hide();
		});

		this.#errorPopup.subscribe(CopilotContextMenuErrorPopupEvents.CHANGE_REQUEST, () => {
			this.#errorPopup.destroy();
			this.#showGeneralMenu();
		});

		this.#errorPopup.subscribe(CopilotContextMenuErrorPopupEvents.REPEAT, () => {
			this.#errorPopup.destroy();
			this.#completions();
		});
	}

	#validateOptions(options: CopilotContextMenuOptions): void
	{
		if (Type.isObject(options) === false)
		{
			throw new BaseError('AI.CopilotContextMenu: options is required for constructor.');
		}

		if (Type.isStringFilled(options.moduleId) === false)
		{
			throw new BaseError('AI.CopilotContextMenu: moduleId is required option and must be string.');
		}

		if (Type.isStringFilled(options.category) === false)
		{
			throw new BaseError('AI.CopilotContextMenu: category is required option and must be string.');
		}

		if (Type.isStringFilled(options.contextId) === false)
		{
			throw new BaseError('AI.CopilotContextMenu: contextId is required option and must be string');
		}

		this.#validateContextOption(options.context);

		if (options.angle && Type.isBoolean(options.angle) === false)
		{
			throw new BaseError('AI.CopilotContextMenu: angle option must be boolean');
		}
	}

	#validateContextOption(context: string): void
	{
		if (context && Type.isString(context) === false)
		{
			throw new BaseError('AI.CopilotContextMenu: context option must be string');
		}
	}

	#getAnalytics(): CopilotAnalytics
	{
		const analytics = new CopilotAnalytics()
			.setCategoryReadonly()
			.setP1('prompt', this.#copilotTextControllerEngine.getCommandCode())
			.setP2('provider', this.#copilotTextControllerEngine.getSelectedEngineCode());

		if (this.#context)
		{
			analytics.setContextElementReadonlyCommon();
		}
		else
		{
			analytics.setContextElementReadonlyQuote();
		}

		return analytics;
	}
}

type InitEngineOptions = {
	moduleId: string;
	contextId: string;
	contextParameters: any;
	category: string;
}

type bindElementPosition = {
	top: number;
	left: number;
}
