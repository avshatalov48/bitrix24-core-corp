import { type Role, type Engine, type GetToolingResultData, Text as PayloadText, type Prompt } from 'ai.engine';
import { Dom, Type, Loc, Runtime, ajax } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Popup, PopupManager } from 'main.popup';
import type { CopilotInput, CopilotInputEvents, CopilotMenu, CopilotMenuEvents, CopilotMenuItem, CopilotResult } from 'ai.copilot';
import type { CopilotAnalytics } from '../../src/copilot-analytics';
import { type CopilotMenuItemRoleInfo } from '../../src/copilot-menu/copilot-menu';
import type { CopilotWarningResultField } from '../../src/copilot-warning-result-field';
import { CopilotTextControllerEngine } from './copilot-text-controller-engine';
import type { RolesDialog as RolesDialogType, RolesDialogOptions, SelectRoleEventDataType } from 'ai.roles-dialog';
import type { PromptMasterPopup as PromptMasterPopupType } from 'ai.prompt-master';

import { EditResultCommand, OpenFeedbackFormCommand } from './menu-item-commands/index';
import { CopilotErrorMenuItems } from './menu-items/copilot-error-menu-items';
import { CopilotProvidersMenuItems } from './menu-items/copilot-providers-menu-items';
import { CopilotResultMenuItems } from './menu-items/copilot-result-menu-items';
import type { EngineInfo } from './types/engine-info';
import { CopilotGeneralMenuItems } from './menu-items/copilot-general-menu-items';
import { AjaxErrorHandler } from 'ai.ajax-error-handler';
import { UI } from 'ui.notification';

export * from './menu-item/index';
export {
	CopilotTextControllerEngine,
};

type CopilotTextControllerOptions = {
	engine: Engine;
	inputField: CopilotInput;
	category: string;
	selectedText: string;
	context: string;
	readonly: boolean;
	resultField: any;
	copilotPopup: Popup;
	warningField: CopilotWarningResultField;
	addImageMenuItem: boolean;
	copilotInputEvents: CopilotInputEvents;
	copilotMenu: CopilotMenu;
	copilotMenuEvents: CopilotMenuEvents;
	analytics: CopilotAnalytics;
	showResultInCopilot: ?boolean;
}

export class CopilotTextController extends EventEmitter
{
	#engine: Engine;
	#inputField: CopilotInput;
	#resultField: CopilotResult;
	#copilotContainer: HTMLElement;
	#category: string;
	#readonly: boolean;
	#selectedEngineCode: string;
	#selectedPromptCodeWithSimpleTemplate: ?string = null;
	#generalMenu: CopilotMenu;
	#resultMenu: CopilotMenu;
	#errorMenu: CopilotMenu;
	#selectedText: string;
	#context: string;
	#resultStack: [] = [];
	#currentGenerateRequestId: number;
	#errorsCount: number = 0;
	#generationResultText: string | null = null;
	#warningField: CopilotWarningResultField;
	#addImageMenuItem: boolean;
	#copilotInputEvents: CopilotInputEvents;
	#CopilotMenu: CopilotMenu;
	#copilotMenuEvents: CopilotMenuEvents;
	#analytics: CopilotAnalytics;
	#currentRole: Role;
	#rolesDialog: RolesDialogType;
	#showResultInCopilot: ?boolean;

	#inputFieldContainerClickEventHandler: Function;
	#inputFieldSubmitEventHandler: Function;
	#inputFieldInputEventHandler: Function;
	#inputFieldGoOutFromBottomEventHandler: Function;
	#inputFieldStartRecordingEventHandler: Function;
	#inputFieldStopRecordingEventHandler: Function;
	#inputFieldCancelLoadingEventHandler: Function;
	#inputFieldAdjustHeightEventHandler: Function;

	static #toolingDataByCategory: {data: GetToolingResultData} = {};

	constructor(options: CopilotTextControllerOptions)
	{
		super();

		this.#engine = options.engine;
		this.#inputField = options.inputField;
		this.#category = options.category;
		this.#resultField = options.resultField;
		this.#readonly = options.readonly === true;
		this.#warningField = options.warningField;
		this.#context = options.context;
		this.#selectedText = options.selectedText;
		this.#addImageMenuItem = options.addImageMenuItem === true;
		this.#copilotInputEvents = options.copilotInputEvents;
		this.#CopilotMenu = options.copilotMenu;
		this.#copilotMenuEvents = options.copilotMenuEvents;
		this.#analytics = options.analytics;
		this.#showResultInCopilot = options.showResultInCopilot;

		this.#inputFieldContainerClickEventHandler = this.#handleInputContainerClickEvent.bind(this);
		this.#inputFieldSubmitEventHandler = this.#handleInputFieldSubmitEvent.bind(this);
		this.#inputFieldInputEventHandler = this.#handleInputFieldInputEvent.bind(this);
		this.#inputFieldGoOutFromBottomEventHandler = this.#handleInputFieldGoOutFromBottomEvent.bind(this);
		this.#inputFieldStartRecordingEventHandler = this.#handleInputFieldStartRecordingEvent.bind(this);
		this.#inputFieldStopRecordingEventHandler = this.#handleInputFieldStopRecordingEvent.bind(this);
		this.#inputFieldCancelLoadingEventHandler = this.#handleInputFieldCancelLoadingEvent.bind(this);
		this.#inputFieldAdjustHeightEventHandler = this.#handleInputFieldAdjustHeightEvent.bind(this);

		this.setEventNamespace('AI.Copilot.TextController');
	}

	setSelectedPromptCodeWithSimpleTemplate(code: string): void
	{
		this.#selectedPromptCodeWithSimpleTemplate = code;
	}

	getSelectedPromptCodeWithSimpleTemplate(): ?string
	{
		return this.#selectedPromptCodeWithSimpleTemplate;
	}

	setCopilotContainer(copilotContainer: HTMLElement): void
	{
		this.#copilotContainer = copilotContainer;
	}

	setSelectedText(text: string): void
	{
		if (Type.isString(text))
		{
			this.#selectedText = text;
		}
	}

	getSelectedText(): string
	{
		return this.#selectedText;
	}

	setContext(text: string): void
	{
		this.#context = text;
	}

	getContext(): string
	{
		return this.#context;
	}

	setSelectedEngine(engineCode: string): void
	{
		this.#setSelectedEngine(engineCode);
	}

	setExtraMarkers(extraMarkers: Object = {}): void
	{
		const payload = this.#engine?.getPayload() || new PayloadText();

		payload.setMarkers({
			...payload.getMarkers(),
			...extraMarkers,
		});

		this.#engine.setPayload(payload);
	}

	async init(): void
	{
		if (CopilotTextController.#toolingDataByCategory[this.#category] === undefined)
		{
			CopilotTextController.#toolingDataByCategory[this.#category] = this.#engine.getTooling('text');
		}

		const res = await CopilotTextController.#toolingDataByCategory[this.#category];
		CopilotTextController.#toolingDataByCategory[this.#category] = res;

		this.#selectedEngineCode = this.#getSelectedEngineCode(res.data.engines);

		this.#currentRole = res.data.role;
	}

	openGeneralMenu(): void
	{
		if (!this.#generalMenu)
		{
			this.#initGeneralMenu();
		}
		this.#generalMenu.getPopup().subscribeFromOptions({
			onBeforeShow: () => {
				if (this.#readonly)
				{
					this.#inputField.disable();
				}
			},
			onAfterShow: () => {
				if (this.#readonly === false)
				{
					this.#inputField.enable();
					this.#inputField.focus();
				}
			},
		});
		this.#generalMenu.setBindElement(this.#copilotContainer, { top: 8 });
		this.#generalMenu.open();
		this.#generalMenu.show();
	}

	clearResultStack(): void
	{
		this.#resultStack = [];
	}

	showMenu(): void
	{
		this.#resultMenu?.show();
		this.#errorMenu?.show();
		this.#generalMenu?.show();
	}

	getOpenMenu(): CopilotMenu | null
	{
		if (this.#generalMenu?.isShown())
		{
			return this.#generalMenu;
		}

		if (this.#errorMenu?.isShown())
		{
			return this.#errorMenu;
		}

		if (this.#resultMenu?.isShown())
		{
			return this.#resultMenu;
		}

		return null;
	}

	getAiResultText(): string
	{
		return this.#generationResultText;
	}

	getCategory(): string
	{
		return this.#category;
	}

	getLastCommandCode(): string
	{
		return this.#engine.getPayload().getRawData()?.prompt?.code || '';
	}

	isContainsElem(elem: HTMLElement): boolean
	{
		return this.#generalMenu?.contains(elem) || this.#errorMenu?.contains(elem) || this.#resultMenu?.contains(elem);
	}

	generateWithRequiredUserMessage(commandCode: string, promptText: string): void
	{
		if (promptText)
		{
			this.#inputField.setHtmlContent(promptText);
		}

		this.setSelectedPromptCodeWithSimpleTemplate(commandCode);

		this.#inputField.focus(true);
	}

	generateWithoutRequiredUserMessage(commandCode: string, prompts: Prompt[]): void
	{
		this.#setEnginePayload({
			command: commandCode,
			markers: {
				originalMessage: this.#selectedText || this.#context,
				userMessage: this.#inputField.getValue(),
			},
		});

		const commandTextForInputField = this.#getPromptTitleByCommandFromPrompts(prompts, commandCode);

		this.#inputField.setValue(commandTextForInputField);
		this.generate();
	}

	hideAllMenus(): void
	{
		this.#rolesDialog?.hide();
		this.#rolesDialog = null;

		this.#generalMenu?.hide();
		this.#errorMenu?.hide();
		this.#resultMenu?.hide();
	}

	destroyAllMenus(): void
	{
		this.#rolesDialog?.hide();
		this.#rolesDialog = null;

		this.#generalMenu?.close();
		this.#errorMenu?.close();
		this.#resultMenu?.close();

		this.#generalMenu = null;
		this.#errorMenu = null;
		this.#resultMenu = null;
	}

	start(): void
	{
		this.openGeneralMenu();
		this.#subscribeToInputFieldEvents();
	}

	async updateGeneralMenuPrompts(): void
	{
		try
		{
			this.#generalMenu?.setLoader();

			const res = await this.#engine.getTooling('text');

			CopilotTextController.#toolingDataByCategory[this.#category] = res;

			const { promptsOther, promptsSystem, promptsFavorite, engines, permissions } = res.data;

			const items = CopilotGeneralMenuItems.getMenuItems({
				userPrompts: promptsOther,
				systemPrompts: promptsSystem,
				favouritePrompts: promptsFavorite,
				engines,
				selectedEngineCode: this.#selectedEngineCode,
				canEditSettings: permissions.can_edit_settings === true,
				copilotTextController: this,
				addImageMenuItem: this.#addImageMenuItem,
			});

			this.#generalMenu?.updateMenuItemsExceptRoleItem(items);
		}
		catch (e)
		{
			console.error(e);
			UI.Notification.Center.notify({
				id: 'update-copilot-menu-error',
				content: Loc.getMessage('AI_COPILOT_UPDATE_MENU_ERROR'),
			});
		}
		finally
		{
			this.#generalMenu?.removeLoader();
		}
	}

	isFirstLaunch(): boolean
	{
		return this.#getTooling().first_launch;
	}

	finish(): void
	{
		this.reset();
		this.destroyAllMenus();
		this.#unsubscribeToInputFieldEvents();
	}

	reset(): void
	{
		this.#selectedText = '';
		this.#selectedPromptCodeWithSimpleTemplate = null;
		this.#context = '';
		this.#currentGenerateRequestId = -1;
		this.#resultStack = [];

		this.#inputField?.clear();
	}

	isInitFinished(): boolean
	{
		return Boolean(this.#getTooling());
	}

	isPromptsLoaded(): boolean
	{
		return Boolean(this.#getTooling()?.promptsOther);
	}

	clearResultField(): void
	{
		this.#resultField.clearResult();
		this.#adjustMenus();
	}

	isReadonly(): boolean
	{
		return this.#readonly === true;
	}

	#subscribeToInputFieldEvents(): void
	{
		this.#inputField.subscribe(this.#copilotInputEvents.containerClick, this.#inputFieldContainerClickEventHandler);
		this.#inputField.subscribe(this.#copilotInputEvents.submit, this.#inputFieldSubmitEventHandler);
		this.#inputField.subscribe(this.#copilotInputEvents.input, this.#inputFieldInputEventHandler);
		this.#inputField.subscribe(this.#copilotInputEvents.goOutFromBottom, this.#inputFieldGoOutFromBottomEventHandler);
		this.#inputField.subscribe(this.#copilotInputEvents.startRecording, this.#inputFieldStartRecordingEventHandler);
		this.#inputField.subscribe(this.#copilotInputEvents.stopRecording, this.#inputFieldStopRecordingEventHandler);
		this.#inputField.subscribe(this.#copilotInputEvents.cancelLoading, this.#inputFieldCancelLoadingEventHandler);
		this.#inputField.subscribe(this.#copilotInputEvents.adjustHeight, this.#inputFieldAdjustHeightEventHandler);
	}

	#unsubscribeToInputFieldEvents(): void
	{
		this.#inputField.unsubscribe(this.#copilotInputEvents.containerClick, this.#inputFieldContainerClickEventHandler);
		this.#inputField.unsubscribe(this.#copilotInputEvents.submit, this.#inputFieldSubmitEventHandler);
		this.#inputField.unsubscribe(this.#copilotInputEvents.input, this.#inputFieldInputEventHandler);
		this.#inputField.unsubscribe(this.#copilotInputEvents.goOutFromBottom, this.#inputFieldGoOutFromBottomEventHandler);
		this.#inputField.unsubscribe(this.#copilotInputEvents.startRecording, this.#inputFieldStartRecordingEventHandler);
		this.#inputField.unsubscribe(this.#copilotInputEvents.stopRecording, this.#inputFieldStopRecordingEventHandler);
		this.#inputField.unsubscribe(this.#copilotInputEvents.cancelLoading, this.#inputFieldCancelLoadingEventHandler);
		this.#inputField.unsubscribe(this.#copilotInputEvents.adjustHeight, this.#inputFieldAdjustHeightEventHandler);
	}

	#handleInputContainerClickEvent(): void
	{
		if (this.#inputField.isDisabled() && this.#resultMenu?.isShown() && this.#readonly === false)
		{
			const editCommand = new EditResultCommand({
				copilotTextController: this,
				inputField: this.#inputField,
			});

			editCommand.execute();
		}
	}

	#handleInputFieldGoOutFromBottomEvent(): void
	{
		this.#generalMenu.enableArrowsKey();
	}

	#handleInputFieldInputEvent(e: BaseEvent): void
	{
		const text: string = e.getData();

		if (!text)
		{
			this.#selectedPromptCodeWithSimpleTemplate = null;
		}

		this.#generalMenu?.disableArrowsKey();

		requestAnimationFrame(() => {
			this.#adjustMenus();
		});
	}

	#handleInputFieldStartRecordingEvent(): void
	{
		this.#generalMenu?.hide();
	}

	// eslint-disable-next-line consistent-return
	async getDataForFeedbackForm(): Promise<any>
	{
		try
		{
			const feedDataResult = await this.#engine.getFeedbackData();
			const messages = feedDataResult.data.context_messages;
			const authorMessage = feedDataResult.data.original_message;
			const payload = this.#engine.getPayload();

			return payload ? {
				context_messages: messages,
				author_message: authorMessage,
				...payload.getRawData(),
				...payload.getMarkers(),
			} : {};
		}
		catch (error)
		{
			console.error(error);
			const payload = this.#engine.getPayload();

			return payload ? {
				...payload.getRawData(),
				...payload.getMarkers(),
			} : {};
		}
	}

	#handleInputFieldStopRecordingEvent(): void
	{
		this.#generalMenu?.show();
	}

	#handleInputFieldCancelLoadingEvent(): void
	{
		this.#currentGenerateRequestId = -1;
		this.#inputField.finishGenerating();
		this.#inputField.focus();
		if (this.#readonly)
		{
			this.#inputField.clear();
		}

		this.openGeneralMenu();
	}

	#handleInputFieldAdjustHeightEvent(): void
	{
		setTimeout(() => {
			// this.#adjustMenus();
		}, 150);
	}

	#handleInputFieldSubmitEvent(): void
	{
		const userPrompt = this.#inputField.getValue();

		if (!userPrompt)
		{
			return;
		}

		this.#setEnginePayload({
			command: 'zero_prompt',
			markers: {
				userMessage: userPrompt,
				originalMessage: this.#selectedText || this.#context || '',
				current_result: this.#resultStack,
			},
		});

		this.generate();
	}

	#adjustMenus(): void
	{
		this.#generalMenu?.adjustPosition();
	}

	#getTooling(): GetToolingResultData
	{
		return CopilotTextController.#toolingDataByCategory[this.#category]?.data;
	}

	#getSelectedEngineCode(engines: EngineInfo[]): string | undefined
	{
		const selectedEngine = engines.find((engine: EngineInfo) => engine.selected);

		return selectedEngine?.code || engines[0]?.code;
	}

	#initGeneralMenu()
	{
		const {
			promptsOther: userPrompts,
			promptsSystem: systemPrompts,
			promptsFavorite: favouritePrompts,
			engines,
			permissions,
		} = this.#getTooling();

		this.#generalMenu = new this.#CopilotMenu({
			roleInfo: this.#getRoleInfoForMenu({
				withOpenRolesDialogAction: true,
				subtitle: Loc.getMessage('AI_COPILOT_GENERAL_MENU_ROLE_SUBTITLE'),
			}),
			items: CopilotGeneralMenuItems.getMenuItems({
				userPrompts,
				systemPrompts,
				favouritePrompts,
				engines,
				selectedEngineCode: this.#selectedEngineCode,
				canEditSettings: permissions.can_edit_settings === true,
				copilotTextController: this,
				addImageMenuItem: this.#addImageMenuItem,
			}),
			keyboardControlOptions: {
				clearHighlightAfterType: this.#readonly === false,
				canGoOutFromTop: this.#readonly === false,
				highlightFirstItemAfterShow: this.#readonly === true,
			},
			forceTop: true,
			cacheable: false,

		});

		this.#generalMenu.subscribe('set-favourite', async (e: BaseEvent) => {
			const isFavourite = e.getData().isFavourite;
			const promptCode = e.getData().promptCode;

			await this.setPromptIsFavourite(promptCode, isFavourite);
		});

		this.#generalMenu.subscribe(this.#copilotMenuEvents.clearHighlight, () => {
			this.#generalMenu?.disableArrowsKey();
			this.#inputField.enableEnterAndArrows();
		});

		this.#generalMenu.subscribe(this.#copilotMenuEvents.highlightMenuItem, () => {
			this.#generalMenu?.enableArrowsKey();
			this.#inputField.disableEnterAndArrows();
		});
	}

	async setPromptIsFavourite(promptCode: string, isFavourite: string): void
	{
		try
		{
			this.#setMenuItemPromptIsFavourite(promptCode, isFavourite);

			const data = new FormData();
			data.append('promptCode', promptCode);

			const action = isFavourite ? 'addInFavoriteList' : 'deleteFromFavoriteList';

			await ajax.runAction(`ai.prompt.${action}`, {
				data,
			});
		}
		catch (error)
		{
			const prompts = [...this.#getTooling().promptsOther, ...this.#getTooling().promptsSystem];
			const searchPrompt: Prompt | undefined = this.#getPromptByCode(prompts, promptCode);

			const message = isFavourite
				? Loc.getMessage('AI_COPILOT_ADD_PROMPT_TO_FAVOURITE_ERROR', { '#NAME#': searchPrompt.title })
				: Loc.getMessage('AI_COPILOT_REMOVE_PROMPT_FROM_FAVOURITE_ERROR', { '#NAME#': searchPrompt.title })
			;

			UI.Notification.Center.notify({
				id: `set-favourite-error-${searchPrompt.code}`,
				content: message,
				autoHide: true,
			});

			this.#setMenuItemPromptIsFavourite(promptCode, !isFavourite);
			console.error(error);
		}
	}

	#setMenuItemPromptIsFavourite(promptCode: string, isFavourite: string): void
	{
		if (isFavourite)
		{
			this.#setMenuItemPromptFavourite(promptCode);
		}
		else
		{
			this.#unsetMenuItemPromptFavourite(promptCode);
		}
	}

	#setMenuItemPromptFavourite(promptCode: string): void
	{
		const prompts = [...this.#getTooling().promptsOther, ...this.#getTooling().promptsSystem];
		const searchPrompt: Prompt | undefined = this.#getPromptByCode(prompts, promptCode);

		if (this.#getTooling().promptsFavorite.length === 0)
		{
			this.#generalMenu.insertItemAfterRole(CopilotGeneralMenuItems.getFavouritePromptsSeparatorMenuItem());
		}

		this.#getTooling().promptsFavorite.push(searchPrompt);
		searchPrompt.isFavorite = true;

		const copilotMenuItem = CopilotGeneralMenuItems.getMenuItem(searchPrompt, prompts, this, true);

		this.#generalMenu.insertItemAfter(
			CopilotGeneralMenuItems.getFavouritePromptsSeparatorMenuItem().code,
			copilotMenuItem,
		);
		this.#generalMenu.setItemIsFavourite(this.getMenuItemCodeFromPrompt(promptCode), true);
	}

	#unsetMenuItemPromptFavourite(promptCode: string): void
	{
		const prompts = [...this.#getTooling().promptsOther, ...this.#getTooling().promptsSystem];
		const searchPrompt: Prompt | undefined = this.#getPromptByCode(prompts, promptCode);

		searchPrompt.isFavorite = false;
		const searchPromptIndexInFavouriteList = this.#getTooling().promptsFavorite.findIndex((prompt) => {
			return prompt.code === searchPrompt.code;
		});

		this.#getTooling().promptsFavorite.splice(searchPromptIndexInFavouriteList, 1);

		if (this.#getTooling().promptsFavorite.length === 0)
		{
			this.#generalMenu.removeItem(CopilotGeneralMenuItems.getFavouritePromptsSeparatorMenuItem().code);
		}

		this.#generalMenu.removeItem(this.getMenuItemCodeFromFavouritePrompt(promptCode));
		this.#generalMenu.setItemIsFavourite(this.getMenuItemCodeFromPrompt(promptCode), false);
	}

	getMenuItemCodeFromPrompt(promptCode: string): string
	{
		return promptCode;
	}

	getMenuItemCodeFromFavouritePrompt(promptCode: string): string
	{
		return `${promptCode}:favourite`;
	}

	async #showRolesDialog(): Promise<void>
	{
		if (this.#rolesDialog)
		{
			return Promise.resolve();
		}

		await Runtime.loadExtension('ui.vue3');
		const { RolesDialog, RolesDialogEvents } = await Runtime.loadExtension('ai.roles-dialog');
		const dialogOptions: RolesDialogOptions = {
			moduleId: this.#engine.getModuleId(),
			contextId: this.#engine.getContextId(),
			selectedRoleCode: this.#currentRole?.code,
			title: Loc.getMessage('AI_COPILOT_ROLES_DIALOG_TITLE'),
		};

		this.#rolesDialog = new RolesDialog(dialogOptions);

		this.#rolesDialog.subscribe(RolesDialogEvents.SELECT_ROLE, (e: BaseEvent<SelectRoleEventDataType>) => {
			const role: SelectRoleEventDataType = e.getData().role;

			this.#currentRole = role;

			this.#generalMenu?.updateRoleInfo(role);
			if (this.#rolesDialog?.hide)
			{
				this.#rolesDialog.hide();
			}
		});

		this.#rolesDialog.subscribe(RolesDialogEvents.HIDE, () => {
			this.#rolesDialog = null;
		});

		return this.#rolesDialog.show();
	}

	#getPromptTitleByCommandFromPrompts(prompts: Prompt[], command: string): string
	{
		let result = '';

		for (const currentPrompt of prompts)
		{
			if (currentPrompt.code === command)
			{
				result = currentPrompt.title;
				break;
			}

			const promptChildren = currentPrompt.children;

			if (promptChildren && promptChildren.length > 0)
			{
				const promptTitle = this.#getPromptTitleByCommandFromPrompts(promptChildren, command);
				if (promptTitle)
				{
					result = `${currentPrompt.title} - ${promptTitle}`;
					break;
				}
			}
		}

		return result;
	}

	#setEnginePayload(options = {}): void
	{
		const command = options.command || '';
		const markers = options.markers || {};
		const userMessage = markers.userMessage || undefined;
		const originalMessage = markers.originalMessage || undefined;

		const payload = new PayloadText({
			prompt: {
				code: command,
			},
			engineCode: this.#selectedEngineCode,
			roleCode: this.#useRole() ? this.#currentRole?.code : undefined,
		});

		const oldPayloadMarkers = this.#engine.getPayload()?.getMarkers() ?? {};

		payload.setMarkers({
			...oldPayloadMarkers,
			original_message: this.#isCommandRequiredContextMessage(command) ? originalMessage : undefined,
			user_message: this.#isCommandRequiredUserMessage(command) ? userMessage : undefined,
			current_result: this.#resultStack,
		});

		this.#engine.setPayload(payload);

		const analytic = this.getAnalytics();

		this.#engine.setAnalyticParameters({
			category: analytic.getCategory(),
			type: analytic.getType(),
			c_sub_section: analytic.getCSubSection(),
			c_element: analytic.getCElement(),
		});
	}

	#isCommandRequiredUserMessage(commandCode): boolean
	{
		const prompts = [...this.#getTooling().promptsOther, ...this.#getTooling().promptsSystem];
		const searchPrompt: Prompt | undefined = this.#getPromptByCode(prompts, commandCode);

		if (!searchPrompt)
		{
			return false;
		}

		return searchPrompt.required.user_message || searchPrompt.type === 'simpleTemplate';
	}

	#isCommandRequiredContextMessage(commandCode): boolean
	{
		const prompts = [...this.#getTooling().promptsOther, ...this.#getTooling().promptsSystem];
		const searchPrompt: Prompt | undefined = this.#getPromptByCode(prompts, commandCode);

		if (!searchPrompt)
		{
			return false;
		}

		return searchPrompt.required.context_message;
	}

	#getPromptByCode(prompts: Prompt[], commandCode: string): Prompt | null
	{
		let searchPrompt = null;

		prompts.some((prompt: Prompt) => {
			if (prompt.code === commandCode)
			{
				searchPrompt = prompt;

				return true;
			}

			return prompt.children?.some((childrenPrompt) => {
				if (childrenPrompt.code === commandCode)
				{
					searchPrompt = childrenPrompt;

					return true;
				}

				return false;
			});
		});

		return searchPrompt;
	}

	#setSelectedEngine(engineCode: string): void
	{
		const data = this.#getTooling();
		this.#selectedEngineCode = engineCode;

		this.#generalMenu.replaceMenuItemSubmenu({
			code: 'provider',
			children: CopilotProvidersMenuItems.getMenuItems({
				engines: data.engines,
				selectedEngineCode: engineCode,
				canEditSettings: data.permissions.can_edit_settings,
				copilotTextController: this,
			}),
		});
	}

	async generate(): Promise<void>
	{
		this.#inputField.startGenerating();
		Dom.removeClass(this.#copilotContainer, '--error');
		this.destroyAllMenus();

		const id = Math.round(Math.random() * 10000);

		this.#currentGenerateRequestId = id;

		try
		{
			const res = await this.#engine.textCompletions();

			const result = res.data.result || res.data.last.data;
			if (this.#currentGenerateRequestId !== id)
			{
				return;
			}

			this.#inputField.finishGenerating();
			this.#inputField.disable();

			this.#generationResultText = res.data.result;

			if (
				this.#showResultInCopilot === true
				|| (this.#showResultInCopilot === undefined && this.#selectedText)
				|| this.#readonly
			)
			{
				this.#resultField?.clearResult();
				this.#resultField?.addResult(this.#generationResultText);
			}
			else
			{
				this.emit('aiResult', {
					result,
				});
			}

			this.#addResultToStack(result);

			this.#warningField?.expand();
			this.#openResultMenu();
		}
		catch (res)
		{
			if (this.#currentGenerateRequestId !== id)
			{
				return;
			}

			this.getAnalytics().sendEventError();

			this.#handleGenerateError(res);
		}
	}

	#addResultToStack(result: string): void
	{
		const stackSize = 3;

		this.#resultStack.unshift(result);
		if (this.#resultStack.length > stackSize)
		{
			this.#resultStack.pop();
		}
	}

	// eslint-disable-next-line max-lines-per-function
	#handleGenerateError(res): void
	{
		const maxGenerateRestartErrors = 4;
		const firstErrorCode = res?.errors?.[0]?.code;

		if (res instanceof Error)
		{
			this.#inputField.setErrors([{
				message: res.message,
				code: -1,
				customData: {},
			}]);
		}
		else if (Type.isString(res))
		{
			this.#inputField.setErrors([{
				message: res,
				code: -1,
				customData: {},
			}]);
		}
		else if (firstErrorCode === 100 && this.#errorsCount < maxGenerateRestartErrors)
		{
			this.#errorsCount += 1;

			this.generate();

			return;
		}
		else
		{
			switch (firstErrorCode)
			{
				case 'AI_ENGINE_ERROR_OTHER': {
					const command = new OpenFeedbackFormCommand({
						category: this.getCategory(),
						isBeforeGeneration: false,
						copilotTextController: this,
					});
					res.errors[0].customData = {
						clickHandler: () => command.execute(),
					};

					this.#inputField.setErrors([{
						code: 'AI_ENGINE_ERROR_OTHER',
						message: Loc.getMessage('AI_COPILOT_ERROR_OTHER'),
						customData: {
							clickHandler: () => command.execute(),
						},
					}]);

					break;
				}

				case 'AI_ENGINE_ERROR_PROVIDER': {
					this.#inputField.setErrors([{
						code: 'AI_ENGINE_ERROR_PROVIDER',
						message: Loc.getMessage('AI_COPILOT_ERROR_PROVIDER'),
					}]);

					break;
				}

				case 'LIMIT_IS_EXCEEDED_BAAS': {
					break;
				}

				default: {
					this.#inputField.setErrors(res.errors);
				}
			}
		}

		this.#errorsCount = 0;

		this.#inputField.finishGenerating();

		if (firstErrorCode === 'LIMIT_IS_EXCEEDED_BAAS')
		{
			this.#inputField.disable();
			setTimeout(() => {
				const baasPopup = PopupManager.getPopups().find((popup) => popup.getId().includes('baas'));

				if (!baasPopup)
				{
					return;
				}

				const baasPopupAutoHide = baasPopup.autoHide;

				baasPopup.subscribe('onClose', (e) => {
					baasPopup.setAutoHide(baasPopupAutoHide);
				});

				baasPopup?.setAutoHide(false);
			}, 200);
		}
		else if (firstErrorCode === 'LIMIT_IS_EXCEEDED_MONTHLY'
			|| firstErrorCode === 'LIMIT_IS_EXCEEDED_DAILY'
			|| firstErrorCode === 'SERVICE_IS_NOT_AVAILABLE_BY_TARIFF'
		)
		{
			this.emit('close');
		}
		else
		{
			this.#initErrorMenu();
			this.#errorMenu.adjustPosition();
			this.#errorMenu.open();
			Dom.addClass(this.#copilotContainer, '--error');
		}

		AjaxErrorHandler.handleTextGenerateError({
			baasOptions: {
				bindElement: this.#inputField.getContainer().querySelector('.ai__copilot_input-field-baas-point'),
				context: this.#engine.getContextId(),
				useAngle: false,
			},
			errorCode: firstErrorCode,
		});
	}

	#initErrorMenu(): void
	{
		this.#errorMenu = new this.#CopilotMenu({
			bindElement: this.#copilotContainer,
			offsetTop: 8,
			items: CopilotErrorMenuItems.getMenuItems({
				inputField: this.#inputField,
				copilotTextController: this,
				copilotContainer: this.#copilotContainer,
			}),
			keyboardControlOptions: {
				canGoOutFromTop: false,
				highlightFirstItemAfterShow: true,
				clearHighlightAfterType: false,
			},
		});

		this.#errorMenu.setBindElement(this.#copilotContainer, {
			top: 8,
		});
	}

	adjustMenusPosition(): void
	{
		this.#generalMenu?.adjustPosition();
		this.#errorMenu?.adjustPosition();
		this.#resultMenu?.adjustPosition();
	}

	#openResultMenu()
	{
		if (!this.#resultMenu)
		{
			this.#initResultMenu();
		}

		this.#resultMenu.setBindElement(this.#copilotContainer, { top: 8 });
		this.#resultMenu.open();
	}

	#initResultMenu()
	{
		const items = this.#getResultMenuItems();

		this.#resultMenu = new this.#CopilotMenu({
			items,
			roleInfo: this.#getRoleInfoForMenu({
				withOpenRolesDialogAction: false,
				subtitle: Loc.getMessage('AI_COPILOT_RESULT_MENU_ROLE_SUBTITLE'),
			}),
			keyboardControlOptions: {
				clearHighlightAfterType: false,
				canGoOutFromTop: false,
				highlightFirstItemAfterShow: true,
			},
			cacheable: false,
		});
	}

	#getRoleInfoForMenu(params: GetRoleInfoForMenuParams): CopilotMenuItemRoleInfo | undefined
	{
		if (this.#useRole() === false)
		{
			return undefined;
		}

		const roleInfo = {
			role: this.#currentRole,
			subtitle: params.subtitle,
		};

		if (params.withOpenRolesDialogAction)
		{
			roleInfo.onclick = this.#showRolesDialog.bind(this);
		}

		return roleInfo;
	}

	#useRole(): boolean
	{
		return this.isReadonly() === false;
	}

	#getResultMenuItems(): CopilotMenuItem[]
	{
		const prompts = this.#getTooling().promptsOther;

		if (this.#readonly)
		{
			return CopilotResultMenuItems.getMenuItemsForReadonlyResult(
				this.#category,
				this,
				this.#inputField,
				this.#copilotContainer,
			);
		}

		return CopilotResultMenuItems.getMenuItems({
			prompts,
			selectedText: this.#selectedText,
			copilotTextController: this,
			inputField: this.#inputField,
			copilotContainer: this.#copilotContainer,
			showResultInCopilot: this.#showResultInCopilot,
		}, this.#category);
	}

	// eslint-disable-next-line sonarjs/cognitive-complexity
	getAnalytics(): CopilotAnalytics
	{
		if (!this.#selectedText || this.#selectedText === '')
		{
			this.#analytics.setTypeTextNew();
		}
		else if (this.#readonly)
		{
			this.#analytics.setTypeTextEdit();
		}
		else
		{
			this.#analytics.setTypeTextReply();
		}

		if (this.#engine && this.#engine.getPayload())
		{
			this.#analytics
				.setP1('prompt', this.getLastCommandCode())
				.setP2('provider', this.#selectedEngineCode);
		}

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

		if (this.getSelectedText())
		{
			this.#analytics.setContextElementPopupButton();
		}
		else
		{
			this.#analytics.setContextElementSpaceButton();
		}

		if (this.#readonly)
		{
			if (this.getSelectedText())
			{
				this.#analytics.setContextElementReadonlyQuote();
			}
			else
			{
				this.#analytics.setContextElementReadonlyCommon();
			}
		}

		return this.#analytics;
	}

	async showPromptMasterPopup(): void
	{
		const { PromptMasterPopup, PromptMasterPopupEvents } = await Runtime.loadExtension('ai.prompt-master');

		const popup: PromptMasterPopupType = new PromptMasterPopup({
			masterOptions: {
				prompt: this.#inputField.getValue(),
			},
			popupEvents: {
				onPopupShow: () => {
					this.emit('prompt-master-show');
					this?.#resultMenu?.disableArrowsKey();
				},
				onPopupDestroy: () => {
					this.emit('prompt-master-destroy');
				},
			},
			analyticFields: {
				c_section: this.#category,
			},
		});

		popup.subscribe(PromptMasterPopupEvents.SAVE_SUCCESS, () => {
			this.updateGeneralMenuPrompts();
		});

		popup.show();
	}
}

type GetRoleInfoForMenuParams = {
	withOpenRolesDialogAction: boolean;
	subtitle: string;
};
