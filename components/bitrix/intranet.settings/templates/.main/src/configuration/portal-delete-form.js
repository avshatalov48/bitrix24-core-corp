import { BaseEvent, EventEmitter } from 'main.core.events';
import { Loc, Tag } from 'main.core';

export default class PortalDeleteFormTypes {
	static WARNING = '--warning';
	static DANGER = '--danger';
}

export class PortalDeleteForm extends EventEmitter
{
	#container: ?HTMLElement;
	confirmButton;
	bodyClass: string;
	#verificationOptions: Object;

	constructor(verificationOptions: Object) {
		super();
		this.#verificationOptions = verificationOptions;
		this.setEventNamespace('BX.Intranet.Settings:PortalDeleteForm');
	}

	getDescription(): HTMLElement
	{
		const moreDetails = `
			<a class="ui-section__link" onclick="top.BX.Helper.show('redirect=detail&code=19566456')">
				${Loc.getMessage('INTRANET_SETTINGS_CANCEL_MORE')}
			</a>
		`;

		return Tag.render`
			${Loc.getMessage('INTRANET_SETTINGS_SECTION_CONFIGURATION_DESCRIPTION_DELETE_PORTAL_MSGVER_1', {
				'#MORE_DETAILS#': moreDetails,
			})}
		`;
	}

	getBodyClass(): string
	{
		return PortalDeleteFormTypes.WARNING;
	}

	getConfirmButtonText(): ?string
	{
		return Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_DELETE_PORTAL');
	}

	getInputContainer() {}

	getContainer(): HTMLElement
	{
		if (!this.#container)
		{
			this.#container = Tag.render`
				<div class="intranet-settings__portal-delete-form_wrapper ${this.getBodyClass()}">
					<div class="intranet-settings__portal-delete-form_body">
						<div class="intranet-settings__portal-delete-icon-wrapper">
							<div class="ui-icon-set --warning"></div>
						</div>
						<div class="intranet-settings__portal-delete-form_description-wrapper">
							<span class="intranet-settings__portal-delete-form_description">
								${this.getDescription()}
							</span>
							${this.getInputContainer()}
						</div>
					</div>
					${this.getButtonContainer()}
				</div>
			`;
		}

		return this.#container;
	}

	onConfirmEventHandler(): void
	{
		this.getConfirmButton().setWaiting(true);
		top.BX.Runtime.loadExtension('bitrix24.portal-delete')
			.then((exports) => {
				const { PortalDelete } = exports;
				const portalDelete = new PortalDelete(this.#verificationOptions);
				portalDelete.showCheckwordPopup();
				this.getConfirmButton().setWaiting(false);
			});
	}

	getButtonContainer(): ?HTMLElement
	{
		return Tag.render`
			<span class="intranet-settings__portal-delete-form_buttons-wrapper">
				${this.getConfirmButton().getContainer()}
			</span>
		`;
	}

	getConfirmButton(): BX.UI.Button
	{
		if (!this.confirmButton)
		{
			this.confirmButton = new BX.UI.Button({
				text: this.getConfirmButtonText() ?? '',
				noCaps: true,
				round: true,
				className: '--confirm',
				events: {
					click: () => {
						this.onConfirmEventHandler();
					}
				},
				props: {
					'data-bx-role': 'delete-portal-confirm',
				},
			});
		}

		return this.confirmButton;
	}

	sendChangeFormEvent(type: ?string): void
	{
		this.emit(
			'updateForm',
			new BaseEvent({data: { type: type ?? null } })
		);
	}
}
