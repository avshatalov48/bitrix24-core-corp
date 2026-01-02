import { Type, Dom, Event } from 'main.core';

export type SystemAuthAuthorizeParamsType = {
	authContainerNode: HTMLElement,
	isQrAvailable: 'Y'|'N',
	qrText: string,
	qrConfig: string,
}

export class SystemAuthAuthorize
{
	#authContainerNode: HTMLElement;
	#isQrAvailable: boolean;
	#qrText: string;
	#qrConfig: string;

	constructor(params: SystemAuthAuthorizeParamsType)
	{
		this.#authContainerNode = params.authContainerNode;
		this.#isQrAvailable = params.isQrAvailable === 'Y';
		this.#qrText = params.qrText || '';
		this.#qrConfig = params.qrConfig || '';

		if (!Type.isDomNode(this.#authContainerNode))
		{
			return;
		}

		this.buttonNode = this.#authContainerNode.querySelector("[data-role='auth-form-button']");

		this.init();

		if (this.#isQrAvailable)
		{
			this.initQrCode();
		}
	}

	init(): void
	{
		BX.UI.Hint.init(this.#authContainerNode);

		if (Type.isDomNode(this.buttonNode))
		{
			Event.bind(this.buttonNode, 'click', () => {
				const spinnerNode = this.buttonNode.querySelector('.intranet-text-btn__spinner');

				if (Type.isDomNode(spinnerNode))
				{
					Dom.style(spinnerNode, 'display', 'block');
				}
			});
		}
	}

	initQrCode(): void
	{
		new QRCode('bx_auth_qr_code', {
			text: this.#qrText,
			width: 220,
			height: 220,
			colorDark: '#000000',
			colorLight: '#ffffff',
		});

		if (!this.#qrConfig)
		{
			return;
		}

		this.qrCodeSuccessIcon = this.#authContainerNode.querySelector('.b24net-qr-scan-form__code-overlay');

		const Pull = new BX.PullClient();
		Pull.subscribe({
			moduleId: 'main',
			command: 'qrAuthorize',
			callback: (params) => {
				if (params.token)
				{
					this.showQrCodeSuccessIcon();

					BX.ajax.runAction(
						'main.qrcodeauth.authenticate',
						{
							data: {
								token: params.token,
								remember: (BX('USER_REMEMBER_QR') && BX('USER_REMEMBER_QR').checked ? 1 : 0),
							}
						}
					).then(
						(response) => {
							this.hideQrCodeSuccessIcon();

							if (response.status === 'success')
							{
								window.location = (params.redirectUrl !== '' ? params.redirectUrl : window.location);
							}
						}
					);
				}
			}
		});
		Pull.start(this.#qrConfig);
	}

	showQrCodeSuccessIcon(): void
	{
		if (!Type.isDomNode(this.qrCodeSuccessIcon))
		{
			return;
		}

		Dom.addClass(this.qrCodeSuccessIcon, 'b24net-qr-scan-form__code-overlay--active');
	}

	hideQrCodeSuccessIcon(): void
	{
		if (!Type.isDomNode(this.qrCodeSuccessIcon))
		{
			return;
		}

		Dom.removeClass(this.qrCodeSuccessIcon, 'b24net-qr-scan-form__code-overlay--active');
	}
}
