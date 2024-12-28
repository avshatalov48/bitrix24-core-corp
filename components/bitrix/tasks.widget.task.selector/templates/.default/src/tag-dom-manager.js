export class TagDomManager
{
	#inputNodeId = null;
	#scopeContainerId = null;
	#inputPrefix = null;
	#blockName = null;

	constructor(data)
	{
		this.#inputNodeId = data.inputNodeId;
		this.#scopeContainerId = data.scopeContainerId;
		this.#inputPrefix = data.inputPrefix;
		this.#blockName = data.blockName;
	}

	onTagAdd(event)
	{
		const selector = event.getData().selector;
		const { tag } = event.getData().tag;

		if(selector.isMultiple())
		{
			this.#addInput(tag);
		}
		else
		{
			this.#updateInput(tag);
		}
	}

	onTagRemove(event)
	{
		const selector = event.getData().selector;
		const { tag } = event.getData().tag;

		if(selector.isMultiple())
		{
			const input = document.getElementById(this.#inputNodeId + '-' + tag.getId());
			input.setAttribute('value', '');
		}
		else
		{
			const input = document.getElementById(this.#inputNodeId);
			input.setAttribute('value', '');
		}
	}

	#addInput(tag)
	{
		const spanContainer = document.getElementById(this.#scopeContainerId);
		const input = document.createElement('input');
		input.type = 'hidden';
		input.name = this.#inputPrefix + '[' + this.#blockName + ']' + '[' + tag.getId() + '][ID]';
		input.id = this.#inputNodeId + '-' + tag.getId();
		input.value = tag.getId();

		input.setAttribute('data-bx-id', 'task-edit-parent-input');

		spanContainer.appendChild(input);
	}

	#updateInput(tag)
	{
		const input = document.getElementById(this.#inputNodeId);
		input.setAttribute('value', tag.getId());
	}
}
