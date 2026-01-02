import { Type } from 'main.core';
import { BitrixVue } from 'ui.vue3';

export type SystemAuthInitializeParamsType = {
	containerNode: HTMLElement,
}

export class SystemAuthInitialize
{
	#application;
	#rootNode: HTMLElement;

	constructor(params: SystemAuthInitializeParamsType): void
	{
		this.#rootNode = params.containerNode;

		if (!Type.isDomNode(this.#rootNode))
		{
			return;
		}

		this.#initVueApp();
	}

	#initVueApp(): void
	{
		const context = this;

		this.#application = BitrixVue.createApp({
			name: 'SystemAuthInitialize',
			data()
			{
				return {
					isWaiting: false,
				};
			},
			beforeCreate(): void
			{
				this.$bitrix.Application.set(context);
			},
			mounted(): void
			{
				if (this.$refs && Type.isDomNode(this.$refs.modalInput))
				{
					this.$refs.modalInput.focus();
				}
			},
			methods: {
				onButtonClick(): void
				{
					this.isWaiting = true;
				},

				getApplication()
				{
					return this.$bitrix.Application.get();
				},
			},
		});
		this.#application.mount(this.#rootNode);
	}
}