import { Popup } from 'main.popup';
import type { Store } from 'ui.vue3.vuex';

import { Core } from 'tasks.v2.core';
import { Model } from 'tasks.v2.const';

class RelationError
{
	#taskId: number | string;

	setTaskId(taskId: number | string): RelationError
	{
		this.#taskId = taskId;

		return this;
	}

	async showError(errorText: string, fieldId: string): Promise<void>
	{
		void this.$store.dispatch(`${Model.Tasks}/setFieldFilled`, {
			id: this.#taskId,
			fieldName: fieldId,
		});

		await new Promise((resolve) => {
			setTimeout(() => resolve(), 0);
		});

		const scrollContainer = document.querySelector(`[data-task-card-scroll="${this.#taskId}"]`);
		const addButton = scrollContainer.querySelector(`[data-task-relation-add="${fieldId}"]`);

		const popup = new Popup({
			id: 'tasks-relation-error',
			className: 'tasks-hint',
			background: 'var(--ui-color-bg-content-inapp)',
			bindElement: addButton,
			content: errorText,
			angle: true,
			autoHide: true,
			autoHideHandler: () => true,
			cacheable: false,
			animation: 'fading',
			offsetLeft: addButton.offsetWidth / 2,
			targetContainer: scrollContainer,
		});

		popup.show();

		setTimeout(() => popup.close(), 3000);
	}

	get $store(): Store
	{
		return Core.getStore();
	}
}

export const relationError = new RelationError();
