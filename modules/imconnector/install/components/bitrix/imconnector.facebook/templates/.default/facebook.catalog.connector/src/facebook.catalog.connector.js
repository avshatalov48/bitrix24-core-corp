import {Event, Loc, Reflection, Tag, Uri} from 'main.core';
import {Popup} from 'main.popup';
import {EventEmitter} from 'main.core.events';
import 'ui.design-tokens';
import './facebook.catalog.connector.css';

class FacebookCatalogConnector
{
	constructor()
	{
		EventEmitter.subscribe('seo-client-auth-result', (event) => {
			if (event.reload) {
				BX.Dom.addClass(document.getElementById('catalog-login-button'), 'ui-btn-wait');
			}
		});

		Event.bind(
			document.getElementById('catalog-logout-button'),
			'click',
			this.confirmLogout.bind(this)
		);
	}

	confirmLogout()
	{
		const confirmPopup = new Popup({
			content: this.getConfirmPopupHtml(),
			autoHide: true,
			cacheable: false,
			closeIcon: true,
			closeByEsc: true,
			overlay: {opacity: 20},
			buttons: [
				new BX.UI.Button({
					text: Loc.getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_DISCONNECT'),
					color: BX.UI.Button.Color.DANGER,
					onclick: () => {
						confirmPopup.close();
						this.logout();
					}
				}),
				new BX.UI.Button({
					text: Loc.getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_CANCEL'),
					color: BX.UI.Button.Color.LINK,
					onclick: () => {
						confirmPopup.close();
					}
				})
			]
		});

		confirmPopup.show();
	}

	getConfirmPopupHtml()
	{
		return Tag.render`<div class="imconnector-facebook-catalog-popup">
			<div class="imconnector-facebook-catalog-popup-text">
				${Loc.getMessage('IMCONNECTOR_COMPONENT_FACEBOOK_OPENLINES_CATALOG_REMOVE')}
			</div>
		</div>`;
	}

	logout()
	{
		BX.ajax.runComponentAction('bitrix:imconnector.facebook', 'logout', {'mode': 'ajax'})
			.then(() => {
				document.location.href = new Uri(document.location.href)
					.setQueryParams({MENU_TAB: 'catalog'})
				;
			});
	}
}

Reflection.namespace('BX.ImConnector').FacebookCatalogConnector = FacebookCatalogConnector;