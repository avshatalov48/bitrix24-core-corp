import { Type } from 'main.core';
import { BitrixVue } from 'ui.vue3';

export type SystemAuthOtpParamsType = {
	containerNode: HTMLElement,
}

export class SystemAuthOtp
{
	#application;
	#rootNode: HTMLElement;

	constructor(params: SystemAuthOtpParamsType): void
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
			name: 'SystemAuthOtp',
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
				onSubmitForm(): void
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