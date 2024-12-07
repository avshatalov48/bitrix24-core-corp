import { Engine } from 'ai.engine';
import { Text as PayloadText } from 'ai.payload.textpayload';
import { Extension, Reflection, Runtime } from 'main.core';
import type { CopilotAgreement as CopilotAgreementClass, CopilotAgreementOptions } from 'ai.copilot-agreement';

const CommandCodes = Object.freeze({
	createChecklist: 'create_checklist',
});

type CommandExecutorOptions = {
	category?: string;
	moduleId: string;
	contextId: string;
	contextParameters?: any;
}

export class CommandExecutor
{
	#engine: Engine;
	#isAgreementAccepted: boolean;

	constructor(options: CommandExecutorOptions)
	{
		this.#checkOptions(options);
		this.#initEngine({
			moduleId: options.moduleId,
			contextId: options.contextId,
			contextParameters: options.contextParameters || {},
		});

		this.#isAgreementAccepted = Extension.getSettings('ai.command-executor').isAgreementAccepted === true;
	}

	async makeChecklistFromText(text: string): Promise<string>
	{
		return new Promise((resolve, reject) => {
			if (!text)
			{
				throw new Error('AI.CommandExecutor.makeChecklistFromText: text is required parameter');
			}

			if (this.#isAgreementAccepted === false)
			{
				this.#checkAgreement(
					() => {
						this.#isAgreementAccepted = true;

						this.makeChecklistFromText(text)
							.then((result) => {
								resolve(result);
							})
							.catch((err) => {
								reject(err);
							});
					},
					() => {
						reject(new Error('Agreement is not accepted'));
					},
				);
			}
			else
			{
				const payload = new PayloadText({
					prompt: {
						code: CommandCodes.createChecklist,
					},
				});

				payload.setMarkers({
					original_message: text,
				});

				this.#engine.setPayload(payload);
				this.#engine.setAnalyticParameters({
					c_section: 'tasks',
				});

				this.#engine.textCompletions(payload)
					.then((result) => {
						resolve(result.data.result);
					})
					.catch((err) => {
						const errorFromServer = err?.errors?.[0];

						if (errorFromServer?.code === 'CLOUD_REGISTRATION_DATA_NOT_FOUND')
						{
							this.#showNotification(errorFromServer.message);
						}

						reject(err);
					});
			}
		});
	}

	#checkOptions(options: CommandExecutorOptions): void
	{
		if (!options.moduleId)
		{
			throw new Error('BX.AI.CommandExecutor: moduleId is required option');
		}

		if (!options.contextId)
		{
			throw new Error('BX.AI.CommandExecutor: contextId is required option');
		}
	}

	#initEngine(options: InitEngineOptions)
	{
		this.#engine = new Engine();

		this.#engine
			.setContextId(options.contextId)
			.setModuleId(options.moduleId)
			.setContextParameters(options.contextParameters);
	}

	async #checkAgreement(onAccept: Function, onCancel: Function): Promise<boolean>
	{
		const { CopilotAgreement } = await Runtime.loadExtension('ai.copilot-agreement');

		const options: CopilotAgreementOptions = {
			moduleId: this.#engine.getModuleId(),
			contextId: this.#engine.getContextId(),
			events: {
				onAccept,
				onCancel,
			},
		};

		const agreement: CopilotAgreementClass = new CopilotAgreement(options);

		return agreement.checkAgreement();
	}

	async #showNotification(message: string): void
	{
		await Runtime.loadExtension('ui.notification');

		const notificationCenter = Reflection.getClass('BX.UI.Notification.Center');

		notificationCenter.notify({
			id: 'command-executor-notification',
			content: message,
		});
	}
}

type InitEngineOptions = {
	moduleId: string;
	contextId: string;
	contextParameters?: any;
}
