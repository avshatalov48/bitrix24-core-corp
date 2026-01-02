import { Type, Validation } from 'main.core';
import { BitrixVue } from 'ui.vue3';

export type SystemAuthForgotPasswordParamsType = {
	containerNode: HTMLElement,
	isFormVisible: boolean,
}

export class SystemAuthForgotPassword
{
	#application;
	#rootNode: HTMLElement;
	#isFormVisible: boolean;

	constructor(params: SystemAuthForgotPasswordParamsType): void
	{
		this.#rootNode = params.containerNode;
		this.#isFormVisible = params.isFormVisible === 'Y';

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
			name: 'SystemAuthForgotPassword',
			data()
			{
				return {
					isWaiting: false,
					isFormVisible: this.getApplication().#isFormVisible,
					isEmailEntered: false,
				};
			},
			beforeCreate(): void
			{
				this.$bitrix.Application.set(context);
			},
			computed: {
				loginOrEmail(): string
				{
					return (this.isEmailEntered) ? 'USER_EMAIL' : 'USER_LOGIN';
				},
			},
			mounted(): void
			{
				if (this.$refs && Type.isDomNode(this.$refs.modalInput))
				{
					this.$refs.modalInput.focus();
				}
			},
			methods: {
				onEnterLoginOrEmail(value: string): void
				{
					this.isEmailEntered = Validation.isEmail(value);
				},

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