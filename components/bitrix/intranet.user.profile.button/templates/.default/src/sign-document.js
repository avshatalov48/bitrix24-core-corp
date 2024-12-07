import { Tag, Loc, Runtime } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { BaseCache, MemoryCache } from 'main.core.cache';

import 'ui.hint';

export class SignDocument
{
	static events: Record<string, string> = {
		onDocumentCreateSidePanelOpen: 'onDocumentCreateSidePanelOpen',
	};

	static B2EEmployeeSignSettingsClass: any;

	static #cache: BaseCache<any> = new MemoryCache();

	static async getPromise(): Promise<HTMLElement>
	{
		const { B2EEmployeeSignSettings } = await Runtime.loadExtension('sign.v2.b2e.sign-settings-employee');
		SignDocument.B2EEmployeeSignSettingsClass = B2EEmployeeSignSettings;

		return SignDocument.#getLayout();
	}

	static #getLayout(): HTMLElement
	{
		return this.#cache.remember('layout', () => {
			const layout = Tag.render`
				<div class="system-auth-form__scope system-auth-form__sign">
					<div class="system-auth-form__item-container --flex --border" style="flex-direction:row;">
						<div class="system-auth-form__item-logo">
							<div class="system-auth-form__item-logo--image --sign">
								<i></i>
							</div>
						</div>
						<div class="system-auth-form__item-title">
							<span>${Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_TITLE')}</span>
							<span data-hint-center data-hint="${Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_TITLE_HINT')}"></span>
						</div>
						<div class="system-auth-form__btn--sign ui-popupcomponentmaker__btn --medium --border" onclick="${() => SignDocument.#onCreateDocumentBtnClick()}">
							${Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_CREATE_DOCUMENT')}
						</div>
					</div>
					<div class="system-auth-form__item-block --flex --center">
						<div class="system-auth-form__item-title --link-dotted">${Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_TRACK_SIGNING')}</div>
						<span data-hint-center data-hint="${Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_TRACK_SIGNING_HINT')}"></span>
					</div>
				</div>
			`;
			BX.UI.Hint.init(layout);

			return layout;
		});
	}

	static #onCreateDocumentBtnClick(): void
	{
		EventEmitter.emit(SignDocument, SignDocument.events.onDocumentCreateSidePanelOpen);
		const container = Tag.render`<div id="sign-b2e-employee-settings-container"></div>`;

		BX.SidePanel.Instance.open('sign-b2e-settings-init-by-employee', {
			width: 750,
			cacheable: false,
			contentCallback: () => {
				container.innerHTML = '';

				return container;
			},
			events: {
				onLoad: () => {
					const employeeSignSettings = new SignDocument.B2EEmployeeSignSettingsClass(container.id);
					employeeSignSettings.render();
				},
			},
		});
	}
}
