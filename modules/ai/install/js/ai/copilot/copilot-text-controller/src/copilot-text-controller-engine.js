import { Engine, type GetToolingResultData, Text as PayloadText } from 'ai.engine';
import type { AjaxResponse } from 'main.core';
import { Type, BaseError } from 'main.core';
import type { EngineInfo } from './types/engine-info';

type CopilotTextControllerEngineOptions = {
	category: string;
	engine: Engine;
	useResultStack: boolean;
	moduleId: string;
	contextId: string;
	contextParameters: any;
}

export class CopilotTextControllerEngine
{
	#engine: Engine;
	#category: string;
	#context: string;
	#useResultStack: boolean = true;
	#selectedText: string;
	#userMessage: string;
	#commandCode: string;
	#selectedEngineCode: string;
	#currentGenerateRequestId: number;
	#resultStack: string[] = [];

	static #toolingDataByCategory: {data: GetToolingResultData} = {};

	constructor(options: CopilotTextControllerEngineOptions)
	{
		this.#category = options.category;
		this.#useResultStack = options.useResultStack ?? this.#useResultStack;
		this.#initEngine({
			moduleId: options.moduleId,
			category: options.category,
			contextId: options.contextId,
			contextParameters: options.contextParameters,
		});
	}

	async init(): Promise
	{
		if (CopilotTextControllerEngine.#toolingDataByCategory[this.#category] === undefined)
		{
			CopilotTextControllerEngine.#toolingDataByCategory[this.#category] = this.#engine.getTooling('text');
		}

		const res = await CopilotTextControllerEngine.#toolingDataByCategory[this.#category];
		CopilotTextControllerEngine.#toolingDataByCategory[this.#category] = res;

		this.#excludeZeroPromptFromPrompts();

		this.#selectedEngineCode = this.#getSelectedEngineCode(res.data.engines);
	}

	async completions(): Promise<string>
	{
		this.#setEnginePayload();

		const id = Math.round(Math.random() * 10000);

		this.#currentGenerateRequestId = id;

		try
		{
			const res = await this.#engine.textCompletions();

			const result = res.data.result || res.data.last.data;

			if (this.#currentGenerateRequestId !== id)
			{
				return null;
			}

			if (this.#useResultStack)
			{
				this.#addResultToStack(result);
			}

			return result;
		}
		catch (res)
		{
			if (this.#currentGenerateRequestId !== id)
			{
				return null;
			}

			throw getBaseErrorFromResponse(res);
		}
	}

	getPrompts(): Prompt[]
	{
		return this.#getTooling().promptsSystem;
	}

	getPermissions()
	{
		return this.#getTooling().permissions;
	}

	getEngines(): EngineInfo[]
	{
		return this.#getTooling().engines;
	}

	setSelectedEngineCode(code: string)
	{
		this.#selectedEngineCode = code;
	}

	getSelectedEngineCode(): string
	{
		return this.#selectedEngineCode;
	}

	getCategory(): string
	{
		return this.#category;
	}

	getContextId(): string
	{
		return this.#engine.getContextId();
	}

	setContext(context: string): void
	{
		this.#context = context;
	}

	setSelectedText(selectedText: string): void
	{
		this.#selectedText = selectedText;
	}

	getOriginalMessage(): string
	{
		return this.#selectedText || this.#context || '';
	}

	setUserMessage(userMessage: string): void
	{
		this.#userMessage = userMessage;
	}

	getCommandCode(): string
	{
		return this.#commandCode;
	}

	setCommandCode(commandCode: string): void
	{
		this.#commandCode = commandCode;
	}

	cancelCompletion(): void
	{
		this.#currentGenerateRequestId = -1;
	}

	isCopilotFirstLaunch(): boolean
	{
		return Boolean(this.#getTooling().first_launch);
	}

	setCopilotBannerLaunchedFlag(): void
	{
		this.#engine.setBannerLaunched();
	}

	setAnalyticParameters(parameters: {[key: string]: string}): void
	{
		this.#engine.setAnalyticParameters(parameters);
	}

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

	#addResultToStack(result: string): void
	{
		const stackSize = 3;

		this.#resultStack.unshift(result);
		if (this.#resultStack.length > stackSize)
		{
			this.#resultStack.pop();
		}
	}

	#getSelectedEngineCode(engines: EngineInfo[]): string | undefined
	{
		const selectedEngine = engines.find((engine: EngineInfo) => engine.selected);

		return selectedEngine?.code || engines[0]?.code;
	}

	#getTooling(): GetToolingResultData
	{
		return CopilotTextControllerEngine.#toolingDataByCategory[this.#category]?.data;
	}

	#setEnginePayload(): void
	{
		const command = this.#commandCode;
		const userMessage = this.#userMessage || undefined;
		const originalMessage = this.getOriginalMessage();

		const payload = new PayloadText({
			prompt: {
				code: command,
			},
			engineCode: this.#selectedEngineCode,
		});

		payload.setMarkers({
			original_message: this.#isCommandRequiredContextMessage(command) ? originalMessage : undefined,
			user_message: this.#isCommandRequiredUserMessage(command) ? userMessage : undefined,
			current_result: this.#resultStack,
		});

		this.#engine.setPayload(payload);
	}

	#isCommandRequiredUserMessage(commandCode): boolean
	{
		const prompts = this.#getTooling().promptsSystem;
		const searchPrompt: Prompt | undefined = this.#getPromptByCode(prompts, commandCode);

		if (!searchPrompt)
		{
			return false;
		}

		return searchPrompt.required.user_message;
	}

	#isCommandRequiredContextMessage(commandCode): boolean
	{
		const prompts = this.#getTooling().promptsSystem;
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
	}

	#excludeZeroPromptFromPrompts(): void
	{
		const zeroPromptIndex = CopilotTextControllerEngine.#toolingDataByCategory[this.#category].data.promptsSystem
			.findIndex((prompt) => {
				return prompt.code === 'zero_prompt';
			});

		if (zeroPromptIndex > -1)
		{
			CopilotTextControllerEngine.#toolingDataByCategory[this.#category].data.promptsSystem.splice(zeroPromptIndex, 1);
		}
	}
}

type InitEngineOptions = {
	moduleId: string;
	contextId: string;
	contextParameters: any;
	category: string;
}

function getBaseErrorFromResponse(res: AjaxResponse<any>): BaseError | null
{
	if (res instanceof Error)
	{
		return new BaseError(res.message, 'undefined', {});
	}

	if (Type.isString(res))
	{
		return new BaseError(res, 'undefined', {});
	}

	const firstErrorData = res.errors[0];

	if (!firstErrorData)
	{
		return null;
	}

	const {
		message,
		code,
		customData,
	} = firstErrorData;

	return new BaseError(message, code, customData);
}
