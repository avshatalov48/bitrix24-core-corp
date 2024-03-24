import { Tag, Reflection } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { Button } from 'ui.buttons';

type LimitPopupParams = {
	title: number,
	content: string,
	licenseButtonText: string,
	laterButtonText: string,
	licenseUrl: string,
	fullLock: 'Y' | 'N',
};

class LimitLockPopup
{
	#title: string = '';
	#content: string = '';
	#licenseButtonText: string = '';
	#laterButtonText: string = '';
	#licenseUrl: string = '';
	#popupClassName: string = 'biconnector-limit-lock';
	#fullLock: boolean = false;

	constructor(params: LimitPopupParams)
	{
		this.#init(params);
		this.#show();
	}

	#init(params: LimitPopupParams)
	{
		this.#title = params.title || '';
		this.#content = params.content || '';
		this.#licenseButtonText = params.licenseButtonText || '';
		this.#laterButtonText = params.laterButtonText || '';
		this.#licenseUrl = params.licenseUrl;
		this.#fullLock = params.fullLock === 'Y';
	}

	#show()
	{
		const popupButtons = [
			new Button({
				text: this.#licenseButtonText,
				color: Button.Color.SUCCESS,
				onclick: () => {
					top.location.href = this.#licenseUrl;
				},
			}),
			new Button({
				text: this.#laterButtonText,
				color: Button.Color.LINK,
				onclick: () => {
					popup.close();
				},
			}),
		];

		const popupContent = Tag.render`
			<div class="biconnector-limit-popup-wrap">
				<div class="biconnector-limit-popup">
					<div class="biconnector-limit-pic">
						<div class="biconnector-limit-pic-round"></div>
					</div>
					<div class="biconnector-limit-text">${this.#content}</div>
				</div>
			</div>
		`;

		const popup = new Popup({
			titleBar: this.#title,
			content: popupContent,
			overlay: true,
			className: this.#popupClassName,
			closeIcon: true,
			lightShadow: true,
			offsetLeft: 100,
			buttons: popupButtons,
		});

		if (this.#fullLock)
		{
			popup.subscribe('onClose', () => {
				if (BX.SidePanel.Instance.isOpen())
				{
					BX.SidePanel.Instance.close();
				}
				EventEmitter.emit('BiConnector:LimitPopup.Lock.onClose');
			});
		}
		else
		{
			popup.subscribe('onClose', () => {
				EventEmitter.emit('BiConnector:LimitPopup.Warning.onClose');
			});
		}

		popup.show();
	}
}

Reflection.namespace('BX.BIConnector').LimitLockPopup = LimitLockPopup;
