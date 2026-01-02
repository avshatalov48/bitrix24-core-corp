import { ajax as Ajax, Text } from 'main.core';
import { UI } from 'ui.notification';

export class Binder
{
	#parentEntityId: number;
	#parentEntityTypeId: number;
	#childEntityTypeId: number;
	#gridId: string;

	constructor(parentEntityTypeId: number, parentEntityId: number, childEntityTypeId: number, gridId: string)
	{
		// eslint-disable-next-line no-param-reassign
		parentEntityTypeId = Text.toInteger(parentEntityTypeId);
		if (parentEntityTypeId > 0)
		{
			this.#parentEntityTypeId = parentEntityTypeId;
		}

		// eslint-disable-next-line no-param-reassign
		parentEntityId = Text.toInteger(parentEntityId);
		if (parentEntityId > 0)
		{
			this.#parentEntityId = parentEntityId;
		}

		// eslint-disable-next-line no-param-reassign
		childEntityTypeId = Text.toInteger(childEntityTypeId);
		if (childEntityTypeId > 0)
		{
			this.#childEntityTypeId = childEntityTypeId;
		}

		this.#gridId = gridId;
	}

	getId(): string
	{
		return `relation-${this.#parentEntityTypeId}-${this.#parentEntityId}-${this.#childEntityTypeId}`;
	}

	getParentEntityId(): ?number
	{
		return this.#parentEntityId ?? null;
	}

	getParenEntityTypeId(): ?number
	{
		return this.#parentEntityTypeId ?? null;
	}

	getChildEntityTypeId(): ?number
	{
		return this.#childEntityTypeId ?? null;
	}

	async bind(selectedIds): Promise
	{
		const data = {
			parentEntityTypeId: this.#parentEntityTypeId,
			parentEntityId: this.#parentEntityId,
			childEntityTypeId: this.#childEntityTypeId,
			selectedIds,
		};

		// eslint-disable-next-line no-async-promise-executor
		return new Promise(async (resolve, reject) => {
			Ajax.runAction('crm.controller.item.relation.update', {
				data,
			}).then((response) => {
				resolve(response);
				this.refreshLayout();
			}).catch((response) => {
				if (response.errors)
				{
					response.errors.forEach(({ message }) => {
						this.showError(message);
					});
				}
			});
		});
	}

	showError(message)
	{
		UI.Notification.Center.notify({
			content: message,
			autoHideDelay: 5000,
		});
	}

	refreshLayout()
	{
		BX.Main.gridManager.getInstanceById(this.#gridId).reload();
	}
}
