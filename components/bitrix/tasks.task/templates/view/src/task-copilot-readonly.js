import { Dom, Event, Loc, Runtime, Tag } from 'main.core';
import { EventEmitter } from 'main.core.events';

type Params = {
	container: HTMLElement,
	description: string,
	enabledBySettings: boolean,
	copilotParams: {
		moduleId: string,
		contextId: string,
		category: string,
	},
	taskId: string,
	pathToPostCreate: string,
};

export class TaskCopilotReadonly
{
	#params: Params;

	#layout: {
		button: HTMLElement,
	};

	#copilotLoaded: boolean;
	#copilotContextMenu: any;
	#copilotShown: boolean;

	constructor(params: Params)
	{
		this.#params = params;
		this.#layout = {};

		if (this.#params.enabledBySettings)
		{
			void this.#createCopilot();
		}

		Dom.append(this.#render(), this.#params.container);
	}

	#render(): HTMLElement
	{
		this.#layout.button = Tag.render`
			<span class="task-detail-extra-copilot-readonly">
				<a>${Loc.getMessage('TASKS_TASK_BUTTON_COPILOT')}</a>
			</span>
		`;

		Event.bind(this.#layout.button, 'mousedown', this.#onButtonMouseDown.bind(this));
		Event.bind(this.#layout.button, 'click', this.#onButtonClick.bind(this));

		return this.#layout.button;
	}

	async #createCopilot(): Promise
	{
		const { CopilotContextMenu } = await Runtime.loadExtension('ai.copilot');

		const options = {
			moduleId: this.#params.copilotParams.moduleId,
			contextId: this.#params.copilotParams.contextId,
			category: this.#params.copilotParams.category,
			bindElement: this.#getBindElement(),
			angle: true,
			extraResultMenuItems: [
				{
					code: 'insert-into-comment',
					text: Loc.getMessage('TASKS_TASK_BUTTON_COPILOT_COPY_INTO_COMMENT'),
					command: () => {
						const resultText = this.#copilotContextMenu.getResultText();
						this.#copilotContextMenu.hide();

						this.#copyIntoComment(resultText);
					},
				},
				{
					code: 'insert-into-new-task',
					text: Loc.getMessage('TASKS_TASK_BUTTON_COPILOT_COPY_INTO_NEW_TASK'),
					command: () => {
						const resultText = this.#copilotContextMenu.getResultText();
						this.#copilotContextMenu.hide();

						this.#copyIntoNewTask(resultText);
					},
				},
			],
		};

		this.#copilotContextMenu = new CopilotContextMenu(options);

		try
		{
			await this.#copilotContextMenu.init();
			this.#copilotLoaded = true;
		}
		catch (e)
		{
			console.error('Failed to init copilot', e);
		}
	}

	#onButtonMouseDown(): void
	{
		if (!this.#params.enabledBySettings)
		{
			return;
		}

		this.#copilotShown = this.#copilotContextMenu?.isShown();
	}

	#onButtonClick(): void
	{
		if (!this.#params.enabledBySettings)
		{
			BX.UI.InfoHelper.show('limit_copilot_off');

			return;
		}

		if (this.#copilotShown)
		{
			this.#hide();
		}
		else
		{
			this.#show();
		}
	}

	#show(): void
	{
		if (this.#copilotLoaded)
		{
			this.#copilotContextMenu.setContext(this.#params.description);
			this.#copilotContextMenu.show({ bindElement: this.#getBindElement() });
		}
	}

	#getBindElement(): HTMLElement
	{
		return this.#layout.button;
	}

	#hide(): void
	{
		this.#copilotContextMenu.hide();
	}

	#copyIntoComment(text: string): void
	{
		const list = FCList.getInstance({ ENTITY_XML_ID: this.#params.taskId });
		const form = list.form;
		const lhe = LHEPostForm.getHandlerByFormId(list.form.formId);

		if (lhe.oEditor?.IsShown())
		{
			lhe.oEditor.action.Exec('insertHTML', text);
		}

		const iframeInitHandler = () => {
			lhe.oEditor.action.Exec('insertHTML', text);
			BX.removeCustomEvent(lhe.oEditor, 'OnAfterIframeInit', iframeInitHandler);
		};
		lhe.exec(() => {
			BX.addCustomEvent(lhe.oEditor, 'OnAfterIframeInit', iframeInitHandler);
		});

		form.show(list);
	}

	#copyIntoNewTask(text: string): void
	{
		BX.SidePanel.Instance.open(`${this.#params.pathToTaskCreate}#${encodeURIComponent(text)}`);
	}
}