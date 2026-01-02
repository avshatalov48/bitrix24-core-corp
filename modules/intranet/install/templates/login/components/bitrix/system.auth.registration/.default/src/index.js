import { Validation, Type } from 'main.core';
import { BitrixVue } from 'ui.vue3';

export type SystemAuthRegistrationParamsType = {
	containerNode: HTMLElement,
	isEmailRequired: 'Y' | 'N',
	isConfirmRegistrationBlockVisible: 'Y' | 'N',
	isCaptchaAvailable: 'Y' | 'N',
	userName: string | '',
	userLastName: string | '',
	userEmail: string | '',
	userLogin: string | '',
}

export class SystemAuthRegistration
{
	#application;
	#rootNode: HTMLElement;
	#isEmailRequired: boolean;
	#isConfirmRegistrationBlockVisible: boolean;
	#isCaptchaAvailable: boolean;
	#userName: string;
	#userLastName: string;
	#userEmail: string;
	#userLogin: string;

	constructor(params: SystemAuthRegistrationParamsType): void
	{
		this.#rootNode = params.containerNode;
		this.#isEmailRequired = params.isEmailRequired === 'Y';
		this.#isConfirmRegistrationBlockVisible = params.isConfirmRegistrationBlockVisible === 'Y';
		this.#isCaptchaAvailable = params.isCaptchaAvailable === 'Y';
		this.#userName = params.userName ?? '';
		this.#userLastName = params.userLastName ?? '';
		this.#userEmail = params.userEmail ?? '';
		this.#userLogin = params.userLogin ?? '';
		this.#initVueApp();
		BX.UI.Hint.init(this.#rootNode);
	}

	#initVueApp(): void
	{
		const context = this;

		this.#application = BitrixVue.createApp({
			name: 'SystemAuthRegistration',
			data()
			{
				return {
					isNameBlockVisible: true,
					isPasswordBlockVisible: false,
					isCaptchaBlockVisible: false,
					isBackButtonVisible: false,
					userName: this.getApplication().#userName,
					userLastName:  this.getApplication().#userLastName,
					userEmail: this.getApplication().#userEmail,
					userLogin: this.getApplication().#userLogin,
					isEmailRequired: this.getApplication().#isEmailRequired,
					isConfirmRegistrationBlockVisible: this.getApplication().#isConfirmRegistrationBlockVisible,
					isCaptchaAvailable: this.getApplication().#isCaptchaAvailable,
					isWaiting: false,
					inputPasswordType: 'password',
					inputConfirmPasswordType: 'password',
				};
			},
			beforeCreate(): void
			{
				this.$bitrix.Application.set(context);
			},
			computed: {
				fullName(): string
				{
					return this.toFullName();
				},
				email(): string
				{
					return this.userEmail;
				},
				activeOrDisabledButtonClass(): string
				{
					return this.isEnteredValidLoginAndEmail() ? 'ui-btn-success' : 'ui-btn-disabled';
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
				onButtonClick(event): void
				{
					if (this.isNameBlockVisible)
					{
						event.preventDefault();

						if (this.isEnteredValidLoginAndEmail())
						{
							this.showPasswordBlock();
							this.isBackButtonVisible = true;
						}
					}
					else if (this.isPasswordBlockVisible && this.isCaptchaAvailable)
					{
						event.preventDefault();
						this.showCaptchaBlock();
						this.isBackButtonVisible = true;
					}
					else
					{
						this.isWaiting = true;
					}
				},

				onBackButtonClick(): void
				{
					if (this.isPasswordBlockVisible)
					{
						this.showNameBlock();
						this.isBackButtonVisible = false;
					}
					else if (this.isCaptchaAvailable && this.isCaptchaBlockVisible)
					{
						this.showPasswordBlock();
					}
				},

				showNameBlock(): void
				{
					this.isNameBlockVisible = true;
					this.isPasswordBlockVisible = false;
					this.isCaptchaBlockVisible = false;
				},

				showPasswordBlock(): void
				{
					this.isNameBlockVisible = false;
					this.isPasswordBlockVisible = true;
					this.isCaptchaBlockVisible = false;
				},

				showCaptchaBlock(): void
				{
					this.isNameBlockVisible = false;
					this.isPasswordBlockVisible = false;
					this.isCaptchaBlockVisible = true;
				},

				toFullName(): string
				{
					const nameSplitter = this.userName !== '' && this.userLastName !== ''
						? ' '
						: ''
					;

					return `${this.userName}${nameSplitter}${this.userLastName}`;
				},

				onEnterUserEmail(value: string): void
				{
					this.userEmail = value;
				},

				isEmailEnteredCorrectly(): boolean
				{
					return Validation.isEmail(this.userEmail);
				},

				onEnterUserLogin(value: string): void
				{
					this.userLogin = value;
				},

				isLoginEnteredCorrectly(): boolean
				{
					return (this.userLogin.length >= 3);
				},

				onEnterUserName(value: string): void
				{
					this.userName = value;
				},

				onEnterUserLastName(value: string): void
				{
					this.userLastName = value;
				},

				isEnteredValidLoginAndEmail(): boolean
				{
					return (
						this.isLoginEnteredCorrectly()
						&& (
							!this.isEmailRequired ||
							this.isEmailRequired && this.isEmailEnteredCorrectly()
						)
					);
				},

				onEyeMouseDown(inputElementName): void
				{
					if (inputElementName === 'PASSWORD')
					{
						this.inputPasswordType = 'text';
					}
					else
					{
						this.inputConfirmPasswordType = 'text';
					}
				},

				onEyeMouseUp(inputElementName): void
				{
					if (inputElementName === 'PASSWORD')
					{
						this.inputPasswordType = 'password';
					}
					else
					{
						this.inputConfirmPasswordType = 'password';
					}
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