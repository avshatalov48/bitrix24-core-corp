import { BitrixVue } from 'ui.vue3';
import { Type } from 'main.core';

export type SystemAuthChangePasswordParamsType = {
	containerNode: HTMLElement,
	isFormVisible: boolean,
	isCaptchaAvailable: boolean,
}

export class SystemAuthChangePassword
{
	#application;
	#rootNode: HTMLElement;
	#isFormVisible: boolean;
	#isCaptchaAvailable: boolean;

	constructor(params: SystemAuthChangePasswordParamsType): void
	{
		this.#rootNode = params.containerNode;
		this.#isFormVisible = params.isFormVisible === 'Y';
		this.#isCaptchaAvailable = params.isCaptchaAvailable === 'Y';

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
			name: 'SystemAuthChangePassword',
			data()
			{
				return {
					isWaiting: false,
					isFormVisible: this.getApplication().#isFormVisible,
					isCaptchaAvailable: this.getApplication().#isCaptchaAvailable,
					isPasswordBlockVisible: true,
					isCaptchaBlockVisible: false,
				};
			},
			beforeCreate(): void
			{
				this.$bitrix.Application.set(context);
			},
			methods: {
				onButtonClick(event): void
				{
					if (this.isCaptchaAvailable && !this.isCaptchaBlockVisible)
					{
						event.preventDefault();
						this.showCaptchaBlock();
					}
					else
					{
						this.isWaiting = true;
					}
				},

				showCaptchaBlock(): void
				{
					this.isPasswordBlockVisible = false;
					this.isCaptchaBlockVisible = true;
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