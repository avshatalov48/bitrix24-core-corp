import { Type } from 'main.core';

declare type ContextParams = {
	entityTypeId: Number,
	entityId: Number,
	entityCategoryId: ?Number,
	isReadonly: Boolean,
	menuBarContainer: HTMLElement,
	extras: Extras,
}

declare type Extras = {
	analytics: Analytics;
}

type Analytics = {
	c_section?: string;
	c_sub_section?: string;
}

export default class Context
{
	#entityTypeId: Number = null;
	#entityId: Number = null;
	#entityCategoryId: ?Number = null;
	#isReadonly: Boolean = false;
	#menuBarContainer: HTMLElement = null;
	#extras: Extras = {};

	constructor(params: ContextParams)
	{
		this.#entityTypeId = params.entityTypeId;
		this.#entityId = params.entityId;
		this.#entityCategoryId = Type.isNumber(params.entityCategoryId) ? params.entityCategoryId : null;
		this.#isReadonly = params.isReadonly;
		this.#menuBarContainer = params.menuBarContainer;
		this.#extras = params.extras ?? {};
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

	getExtras(): Extras
	{
		return this.#extras;
	}
}
