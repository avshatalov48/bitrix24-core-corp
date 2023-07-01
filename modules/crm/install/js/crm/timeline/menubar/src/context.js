declare type ContextParams = {
	entityTypeId: Number,
	entityId: Number,
	isReadonly: Boolean,
	menuBarContainer: HTMLElement,
}

export default class Context
{
	#entityTypeId: Number = null;
	#entityId: Number = null;
	#isReadonly: Boolean = false;
	#menuBarContainer: HTMLElement = null;

	constructor(params: ContextParams)
	{
		this.#entityTypeId = params.entityTypeId;
		this.#entityId = params.entityId;
		this.#isReadonly = params.isReadonly;
		this.#menuBarContainer = params.menuBarContainer;
	}

	getEntityTypeId(): Number
	{
		return this.#entityTypeId;
	}

	getEntityId(): Number
	{
		return this.#entityId;
	}

	isReadonly(): Boolean
	{
		return this.#isReadonly;
	}

	getMenuBarContainer(): HTMLElement
	{
		return this.#menuBarContainer;
	}
}
