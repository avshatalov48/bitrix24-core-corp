import { Type } from 'main.core';

declare type ContextParams = {
	entityTypeId: Number,
	entityId: Number,
	entityCategoryId: ?Number,
	isReadonly: Boolean,
	menuBarContainer: HTMLElement,
}

export default class Context
{
	#entityTypeId: Number = null;
	#entityId: Number = null;
	#entityCategoryId: ?Number = null;
	#isReadonly: Boolean = false;
	#menuBarContainer: HTMLElement = null;

	constructor(params: ContextParams)
	{
		this.#entityTypeId = params.entityTypeId;
		this.#entityId = params.entityId;
		this.#entityCategoryId = Type.isNumber(params.entityCategoryId) ? params.entityCategoryId : null;
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

	getEntityCategoryId(): ?Number
	{
		return this.#entityCategoryId;
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
