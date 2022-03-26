import {ClientRegistrationOptions} from "./types";
import {ajax as Ajax, Tag, Loc, Extension} from "main.core";
import {Popup} from "main.popup";
import {SaveButton} from "ui.buttons";

const DEFAULT_LANGUAGE_ID = 'en';

export default class ClientRegistration
{

	options: ClientRegistrationOptions;
	popupContainerId = 'content-register-modal';

	constructor(options: ClientRegistrationOptions)
	{
		this.options = options;

		this.bindEvents();
	}

	bindEvents()
	{}

	start()
	{
		const allowedServerPromise = Ajax.runAction('disk.api.integration.b24documents.listAllowedServers')
			.then(response => {
				return response.data.servers;
			}
		);

		const warning = Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_REGISTRATION_WARNING', {'#NAME#' : this.#getServiceName()});

		const popupContent = Tag.render`
			<div class="ui-form" id="${this.popupContainerId}" style="padding-top: 20px">
				<div class="ui-form-row" style="display: none">
					<div class="ui-alert ui-alert-danger">
						<span class="ui-alert-message"></span>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-label">
						<div class="ui-ctl-label-text">${Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_REGISTRATION_SELECT_SERVER_LABEL')}</div>
					</div>
					<div class="ui-form-content">
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select class="ui-ctl-element"></select>
						</div>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-label">
						<div class="ui-ctl-label-text">${warning}</div>
					</div>
				</div>
			</div>
		`;

		const popup = new Popup({
			overlay: true,
			height: 280,
			width: 350,
			content: popupContent,
			closeIcon: true,
			events: {
				onAfterClose: () => popup.destroy(),
			},
			buttons: [
				new SaveButton({
					text: Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_REGISTRATION_BUTTON'),
					onclick: this.handleClickRegister.bind(this),
				}),
			]
		});
		popup.show();

		allowedServerPromise.then(servers => {
			const select = popupContent.querySelector('select');
			servers.forEach(server => {
				const regionSuffix = server.region ? ` (${server.region})` : '';
				select.add(Tag.render`<option value="${server.proxy}">${server.proxy}${regionSuffix}</option>`);
			})
		})
	}

	#getSelectedServer(): string
	{
		const selectNode = document.querySelector(`#${this.popupContainerId} select`);
		if (!selectNode)
		{
			return '';
		}

		return selectNode.value;
	}

	#getLanguageId(): string
	{
		return Loc.hasMessage('LANGUAGE_ID') ? Loc.getMessage('LANGUAGE_ID') : DEFAULT_LANGUAGE_ID;
	}

	#getServiceName(): string
	{
		return Extension.getSettings('disk.b24-documents-client-registration').get('serviceName');
	}

	#showOnlyErrorRowInPopup(message: string): void
	{
		const rows = document.querySelectorAll(`#${this.popupContainerId} .ui-form-row`);
		rows.forEach(row => {
			row.style.display = 'none';
		});

		rows[0].style.display = '';
		rows[0].querySelector('.ui-alert-message').textContent = message;
	}

	handleClickRegister(button: SaveButton, event: MouseEvent)
	{
		button.setDisabled();

		const loader = new BX.Loader({
			target: button.getContainer(),
			size: 45,
		});
		loader.show();

		Ajax.runAction('disk.api.integration.b24documents.registerCloudClient', {
			data: {
				serviceUrl: this.#getSelectedServer(),
				languageId: this.#getLanguageId(),
			}
		}).then(() => {
			document.location.reload();
		}).catch((response) => {
			console.warn('Registration error', response);

			loader.hide();
			this.#showOnlyErrorRowInPopup(
				this.#buildUsefulErrorText(response.errors || [])
			);
		});
	}

	#buildUsefulErrorText(errors: Array): string
	{
		for (const error of errors)
		{
			if (error.code === 'tariff_restriction')
			{
				return Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_REGISTRATION_ERROR_AFTER_REG', {'#NAME#' : this.#getServiceName()});
			}
			if (error.code === 'should_show_in_ui')
			{
				return error.message;
			}
			if (error.code === 'domain_verification')
			{
				return Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_REGISTRATION_ERROR_DOMAIN_VERIFICATION', {
					'#NAME#' : this.#getServiceName(),
					'#DOMAIN#' : error.customData.domain,
				});
			}
		}

		return Loc.getMessage('JS_B24_DOCUMENTS_CLIENT_REGISTRATION_ERROR_COMMON');
	}
};