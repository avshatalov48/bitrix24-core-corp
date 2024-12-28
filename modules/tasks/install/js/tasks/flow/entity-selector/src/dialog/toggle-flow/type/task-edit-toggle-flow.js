import { AbstractToggleFlow } from '../abstract-toggle-flow';
import { Dom } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Scope } from '../dictionary/scope';

import type { Item } from 'ui.entity-selector';

export type TaskEditToggleFlowParams = {
	scope: Scope,

	isFeatureTrialable: boolean,
	immutable: Object,

	taskId: number,
	taskDescription: string,
}

export class TaskEditToggleFlow extends AbstractToggleFlow
{
	#params: TaskEditToggleFlowParams;

	constructor(params: TaskEditToggleFlowParams)
	{
		super();

		this.#params = params;
	}

	onSelectFlow(event: BaseEvent, itemBeforeUpdate: Item): void
	{
		const dialog = event.getTarget();

		const selectedItem = event.getData().item;

		const flowId = parseInt(selectedItem.id, 10);
		const groupId = parseInt(selectedItem.customData.get('groupId'), 10);
		const templateId = parseInt(selectedItem.customData.get('templateId'), 10);

		window.onbeforeunload = () => {};

		if (this.#shouldShowConfirmChangeFlow())
		{
			const rollback = () => {
				dialog.getItem(itemBeforeUpdate).select(true);
			};

			this.showConfirmChangeFlow(this.#bindFlow.bind(this, flowId, groupId, templateId), rollback);

			return;
		}

		this.#bindFlow(flowId, groupId, templateId);
	}

	#bindFlow(flowId: number, groupId: number, templateId: number): void
	{
		const currentUri = new BX.Uri(decodeURI(location.href));

		currentUri.setQueryParam('FLOW_ID', flowId);
		currentUri.setQueryParam('GROUP_ID', groupId);
		if (templateId)
		{
			currentUri.setQueryParam('TEMPLATE', templateId);
		}
		else
		{
			currentUri.removeQueryParam('TEMPLATE');
		}

		currentUri.removeQueryParam('EVENT_TYPE');
		currentUri.removeQueryParam('EVENT_TASK_ID');
		currentUri.removeQueryParam('EVENT_OPTIONS');
		currentUri.removeQueryParam('NO_FLOW');

		const immutable = this.#params.immutable;
		Object.entries(immutable).forEach(([key, value]) => {
			currentUri.setQueryParam(key, value);
		});

		const demoSuffix = this.#params.isFeatureTrialable ? 'Y' : 'N';

		currentUri.setQueryParams({
			ta_cat: 'task_operations',
			ta_sec: 'flows',
			ta_sub: 'flows_grid',
			ta_el: 'flow_selector',
			p1: `isDemo_${demoSuffix}`,
		});

		location.href = currentUri.getPath() + currentUri.getQuery();
	}

	onDeselectFlow(event: BaseEvent, selectedItem: ?Item): void
	{
		if (selectedItem !== null)
		{
			return;
		}

		window.onbeforeunload = () => {};

		if (this.#shouldShowConfirmChangeFlow())
		{
			const rollback = () => {
				const dialog = event.getTarget();
				const deselectedItem = event.getData().item;

				dialog.getItem(deselectedItem).select(true);
			};

			this.showConfirmChangeFlow(this.#unBindFlow.bind(this), rollback);

			return;
		}

		this.#unBindFlow();
	}

	#unBindFlow(): void
	{
		const currentUri = new BX.Uri(decodeURI(location.href));

		currentUri.removeQueryParam('FLOW_ID', 'GROUP_ID', 'TEMPLATE');

		currentUri.removeQueryParam('EVENT_TYPE');
		currentUri.removeQueryParam('EVENT_TASK_ID');
		currentUri.removeQueryParam('EVENT_OPTIONS');

		currentUri.setQueryParam('NO_FLOW', 1);

		const immutable = this.#params.immutable;
		Object.entries(immutable).forEach(([key, value]) => {
			currentUri.setQueryParam(key, value);
		});

		location.href = currentUri.getPath() + currentUri.getQuery();
	}

	#shouldShowConfirmChangeFlow(): boolean
	{
		const description = this.#getEditorText().trim();
		const hasDescription = description.length > 0;

		if (!hasDescription)
		{
			return false;
		}

		const isNewTask = Number(this.#params.taskId) === 0;
		if (isNewTask)
		{
			return true;
		}

		const taskDescription = this.#params.taskDescription;

		return !this.#removeBBCode(taskDescription).includes(description);
	}

	#getEditorText(): string
	{
		const container = document.querySelector('[data-bx-id="task-edit-editor-container"]');

		const isBBCode = Dom.style(container.querySelector('.bxhtmled-iframe-cnt'), 'display') === 'none';
		if (isBBCode)
		{
			const textArea = container.querySelector('.bxhtmled-textarea');

			return textArea.value;
		}

		const editor = container.querySelector('.bx-editor-iframe').contentDocument;

		return editor.body.innerText;
	}

	#removeBBCode(text): string
	{
		return text.replaceAll(/\[(user|icon|color|size|url|b|i|u|s)(?:=[^\]]*)?](.*?)\[\/\1]/gi, '$2');
	}
}
