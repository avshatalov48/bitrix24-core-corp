import { bind, Loc, Reflection, Tag, ajax as Ajax } from 'main.core';
import { BannerDispatcher } from 'ui.banner-dispatcher';
import { AirButtonStyle, Button } from 'ui.buttons';
import { Dialog } from 'ui.system.dialog';

const namespaceCrmWhatsNew = Reflection.namespace('BX.Crm.WhatsNew');

class Vat22Popup
{
	#dialog: Dialog;
	#isNeedToUpdateVat22: boolean;

	constructor()
	{
		this.#dialog = null;
		this.#isNeedToUpdateVat22 = false;
	}

	#renderDialogContent(): HTMLElement
	{
		return Tag.render`
			<div class="crm-whats-new-vat-22_content-description">
				<p>${Loc.getMessage('CRM_WHATS_NEW_VAT_22_CONTENT')}</p>
				<p>${Loc.getMessage('CRM_WHATS_NEW_VAT_22_MARKETPLACE')}</p>
				<p>${this.#renderHelpDescLink()}</p>
			</div>
		`;
	}

	show(): void
	{
		if (!this.#dialog)
		{
			this.#dialog = new Dialog({
				title: Loc.getMessage('CRM_WHATS_NEW_VAT_22_TITLE'),
				content: this.#renderDialogContent(),
				leftButtons: [this.#renderUpdateVat22Button(), this.#renderUpdateLaterButton()],
				closeByClickOutside: false,
				width: 555,
				hasOverlay: true,
			});

			BannerDispatcher.high.toQueue((onDone) => {
				this.#dialog.subscribe('onAfterHide', () => {
					onDone();
					if (this.#isNeedToUpdateVat22)
					{
						this.updateVat22();
					}
					else
					{
						this.updateLater();
					}
				});
				this.#dialog.show();
			});
		}
	}

	updateLater()
	{
		Ajax.runAction('crm.settings.tourvat22.updateLater')
			.catch((errors) => {
				console.error(errors);
			});
	}

	updateVat22()
	{
		Ajax.runAction('crm.settings.tourvat22.updateVat22')
			.catch((errors) => {
				console.error(errors);
			});
	}

	#renderHelpDescLink(): HTMLElement
	{
		const Helper = Reflection.getClass('top.BX.Helper');
		const link = Tag.render`<a>${Loc.getMessage('CRM_WHATS_NEW_VAT_22_HELP_DESC')}</a>`;
		bind(link, 'click', () => {
			Helper?.show('redirect=detail&code=27490382');
		});

		return link;
	}

	#renderUpdateVat22Button(): Button
	{
		return new Button({
			style: AirButtonStyle.FILLED,
			text: Loc.getMessage('CRM_WHATS_NEW_VAT_22_UPDATE_AUTO'),
			round: true,
			useAirDesign: true,
			onclick: () => {
				this.#isNeedToUpdateVat22 = true;
				this.#dialog.hide();
			},
		});
	}

	#renderUpdateLaterButton(): Button
	{
		return new Button({
			style: AirButtonStyle.OUTLINE,
			text: Loc.getMessage('CRM_WHATS_NEW_VAT_22_UPDATE_LATER'),
			round: true,
			useAirDesign: true,
			onclick: () => {
				this.#dialog.hide();
			},
		});
	}
}

namespaceCrmWhatsNew.Vat22Popup = Vat22Popup;
