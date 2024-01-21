import { Loc, Tag } from 'main.core';
import type { LandingOptions }  from './landing-button';
import { Button } from 'ui.buttons';

export class LandingCard
{
	#landing: LandingOptions;
	#copyBtn: ?Button;
	#landingCardElement: ?HTMLElement;

	constructor(landingOptions: LandingOptions)
	{
		this.#landing = landingOptions;
	}

	qrRender()
	{
		let qrContainer = Tag.render`<div class="intranet-settings__qr_image-container"></div>`;
		new QRCode(qrContainer, {
			text: this.#landing.public_url,
			width: 106,
			height: 106,
		});

		return qrContainer;
	}

	render(): HTMLElement
	{
		if (this.#landingCardElement)
		{
			return this.#landingCardElement;
		}

		const onclickOpenEdit = () => {
			window.open(this.#landing.edit_url, '_blank').focus();
		};

		this.#landingCardElement = Tag.render`
		<div class="intranet-settings__req-info-container">
			<div class="intranet-settings__req-info-inner">
				<div class="intranet-settings__qr_container">${this.qrRender()}</div>
				<div class="intranet-settings__qr_description-block">
					<div class="intranet-settings__qr_help-text">
						<h4 class="intranet-settings__qr_title">${Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_SITE')}</h4>
						<p class="intranet-settings__qr_text">
							${Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_HELP_TEXT', { '#SITE_URL#': this.#landing.public_url })}
						</p>
					</div>
					<div class="intranet-settings__qr_button">
						${this.getCopyButton().getContainer()}
					</div>
				</div>
			</div>
			<div class="intranet-settings__qr_editor_box" onclick="${onclickOpenEdit}">
				<div class="intranet-settings__qr_editor_icon">
					<div class="ui-icon-set --paint-1"></div>
				</div>
				<div class="intranet-settings__qr_editor_name">${Loc.getMessage('INTRANET_SETTINGS_BUTTON_EDIT_LANDING')}</div>
				<div class="ui-icon-set --expand intranet-settings__qr_editor_btn"></div>
			</div>
		</div>`;

		return this.#landingCardElement;
	}

	getCopyButton():Button
	{
		if (this.#copyBtn)
		{
			return this.#copyBtn;
		}

		this.#copyBtn = new Button({
			text: Loc.getMessage('INTRANET_SETTINGS_BUTTON_REQ_COPY_LINK'),
			round: true,
			noCaps: true,
			className: 'landing-copy-button',
			size: BX.UI.Button.Size.EXTRA_SMALL,
			color: BX.UI.Button.Color.SUCCESS,
			events: {
				click: () => {
					if (BX.clipboard.copy(this.#landing.public_url))
					{
						top.BX.UI.Notification.Center.notify({
							content: Loc.getMessage('INTRANET_SETTINGS_BUTTON_LINK_WAS_COPIED'),
							autoHide: true,
						});
					}
				},
			},
		});

		return this.#copyBtn;
	}
}