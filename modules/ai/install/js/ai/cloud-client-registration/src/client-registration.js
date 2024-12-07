import { ClientRegistrationOptions } from './types';
import { ajax as Ajax, Dom, Tag, Loc } from 'main.core';
import { Popup } from 'main.popup';
import { SaveButton } from 'ui.buttons';
import 'ui.forms';
import 'ui.alerts';
import 'ui.layout-form';

import './css/client-registration-popup.css';

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
		const allowedServerPromise = Ajax.runAction('ai.integration.b24cloudai.listAllowedServers')
			.then((response) => {
				return response.data.servers;
			});

		const warning = Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_REGISTRATION_WARNING', { '#NAME#': this.#getServiceName() });

		const popupContent = Tag.render`
			<div class="ui-form ai__cloud-client-registration-form" id="${this.popupContainerId}">
				<div class="ui-form-row" style="display: none">
					<div class="ui-alert ui-alert-icon-danger ui-alert-xs ui-alert-danger">
						<span class="ui-alert-message"></span>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-label">
						<div class="ui-ctl-label-text">${Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_REGISTRATION_SELECT_SERVER_LABEL')}</div>
					</div>
					<div class="ui-form-content">
						<div
							ref="selectWrapper"
							class="ui-ctl ui-ctl-w100 --loading ui-ctl-after-icon ui-ctl-dropdown ai__cloud-client-registration-form_servers-select-wrapper"
						>
							<div class="ui-ctl-after ui-ctl-icon-loader"></div>
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select ref="select" class="ui-ctl-element"></select>
						</div>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-label">
					<div class="ui-alert ui-alert-icon-info ui-alert-xs">
						<span class="ui-alert-message">
							${warning}
						</span>
				</div>
			</div>
		`;

		const popup = new Popup({
			overlay: true,
			minHeight: 280,
			width: 400,
			content: popupContent.root,
			closeIcon: true,
			cacheable: false,
			buttons: [
				new SaveButton({
					id: 'save-button',
					text: Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_REGISTRATION_BUTTON'),
					onclick: this.handleClickRegister.bind(this),
					state: SaveButton.State.DISABLED,
				}),
			],
		});
		popup.show();

		allowedServerPromise.then((servers) => {
			const select = popupContent.select;

			servers.forEach((server) => {
				const regionSuffix = server.region ? ` (${server.region})` : '';
				select.add(Tag.render`<option value="${server.proxy}">${server.proxy}${regionSuffix}</option>`);
			});

			const btn = popup.getButton('save-button');
			btn.setState(null);
			Dom.removeClass(popupContent.selectWrapper, '--loading');
		}).catch((response) => {
			console.error('Error fetching allowed servers', response);

			this.#showOnlyErrorRowInPopup(Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_REGISTRATION_ERROR_ALLOWED_SERVERS'));

			Dom.removeClass(popupContent.selectWrapper, '--loading');
			const btn = popup.getButton('save-button');
			btn.setState(SaveButton.State.DISABLED);
		});
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
		return 'AiProxy';
	}

	#showOnlyErrorRowInPopup(message: string): void
	{
		const rows = document.querySelectorAll(`#${this.popupContainerId} .ui-form-row`);
		rows.forEach((row) => {
			Dom.style(row, 'display', 'none');
		});

		Dom.style(rows[0], 'display', '');
		rows[0].querySelector('.ui-alert-message').textContent = message;
	}

	handleClickRegister(button: SaveButton)
	{
		button.setDisabled();
		button.setState(SaveButton.State.WAITING);

		Ajax.runAction('ai.integration.b24cloudai.register', {
			data: {
				serviceUrl: this.#getSelectedServer(),
				languageId: this.#getLanguageId(),
			},
		}).then(() => {
			document.location.reload();
		}).catch((response) => {
			console.error('Registration error', response);

			button.setState(SaveButton.State.DISABLED);
			this.#showOnlyErrorRowInPopup(
				this.#buildUsefulErrorText(response.errors || []),
			);
		});
	}

	#buildUsefulErrorText(errors: Array): string
	{
		for (const error of errors)
		{
			if (error.code === 'tariff_restriction')
			{
				return Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_REGISTRATION_ERROR_AFTER_REG', { '#NAME#': this.#getServiceName() });
			}

			if (error.code === 'should_show_in_ui')
			{
				return error.message;
			}

			if (error.code === 'domain_verification')
			{
				return Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_REGISTRATION_ERROR_DOMAIN_VERIFICATION', {
					'#NAME#': this.#getServiceName(),
					'#DOMAIN#': error.customData.domain,
				});
			}
		}

		return Loc.getMessage('AI_COPILOT_CLOUD_CLIENT_REGISTRATION_ERROR_COMMON');
	}
}
