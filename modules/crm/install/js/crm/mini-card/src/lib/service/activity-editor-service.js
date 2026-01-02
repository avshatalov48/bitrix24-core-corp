import { ajax as Ajax, Runtime, Text } from 'main.core';

export class ActivityEditorService
{
	#cache: Object;

	constructor()
	{
		this.#cache = {};
	}

	async loadActivityEditor(
		ownerTypeId: number,
		ownerId: number,
		activityEditorContainer: HTMLElement,
	): Promise<?BX.CrmActivityEditor>
	{
		if (this.#cache[ownerTypeId]?.[ownerId])
		{
			return this.#cache[ownerTypeId][ownerId];
		}

		const editorId = Text.getRandom(16);

		try
		{
			const response = await Ajax.runAction('crm.item.minicard.getActivityEditor', {
				data: {
					ownerTypeId,
					ownerId,
					editorId,
				},
			});

			await Runtime.html(activityEditorContainer, response?.data?.html ?? '');

			this.#cache[ownerTypeId] ??= {};
			this.#cache[ownerTypeId][ownerId] = BX.CrmActivityEditor.items[editorId];
		}
		catch (error)
		{
			console.error('BX.Crm.MiniCard.ActivityEditorService: Cannot load activity editor:', error);

			this.#cache[ownerTypeId] ??= {};
			this.#cache[ownerTypeId][ownerId] = BX.CrmActivityEditor.getDefault();
		}

		return this.#cache[ownerTypeId][ownerId];
	}
}
