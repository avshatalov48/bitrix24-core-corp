import { Loc, Runtime, Tag } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { BaseCache, MemoryCache } from 'main.core.cache';
import { type AnalyticsOptions } from 'sign.v2.analytics';
import type { B2EEmployeeSignSettings } from 'sign.v2.b2e.sign-settings-employee';

import 'ui.hint';
import { HcmLinkSalaryVacation } from './hcmlink-salary-vacation';

const analyticsContext: Partial<AnalyticsOptions> = {
	category: 'documents',
	c_section: 'ava_menu',
	type: 'from_employee',
};

export class SignDocument
{
	static events: Record<string, string> = {
		onDocumentCreateBtnClick: 'onDocumentCreateBtnClick',
	};

	static #b2eEmployeeSignSettings: B2EEmployeeSignSettings;

	static #container = Tag.render`<div id="sign-b2e-employee-settings-container"></div>`;
	static #cache: BaseCache<any> = new MemoryCache();

	static async getPromise(isLocked: boolean): Promise<HTMLElement>
	{
		const { B2EEmployeeSignSettings } = await Runtime.loadExtension('sign.v2.b2e.sign-settings-employee');
		SignDocument.#b2eEmployeeSignSettings = new B2EEmployeeSignSettings(SignDocument.#container.id, analyticsContext);

		try
		{
			await HcmLinkSalaryVacation.load();
		}
		catch (e) {}

		return SignDocument.#getLayout(isLocked);
	}

	static #getLayout(isLocked: boolean): HTMLElement
	{
		const lockedClass = isLocked ? ' --lock' : '';

		return this.#cache.remember('layout', () => {
			const layout = Tag.render`
				<div>
					<div class="system-auth-form__scope system-auth-form__sign">
						<div class="system-auth-form__item-container --flex" style="flex-direction:row;">
							<div class="system-auth-form__item-logo">
								<div class="system-auth-form__item-logo--image --sign">
									<i></i>
								</div>
							</div>
							<div class="system-auth-form__item-title">
								<span>${Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_TITLE')}</span>
								<span class="system-auth-form__item-title --link-light --margin-s">
									${Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_TITLE_HINT')}
								</span>
							</div>
							<div class="system-auth-form__btn--sign ui-popupcomponentmaker__btn --medium --border${lockedClass}" onclick="${() => SignDocument.#onCreateDocumentBtnClick(isLocked)}">
								${Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_CREATE_DOCUMENT')}
							</div>
						</div>
					</div>
					${HcmLinkSalaryVacation.getLayout()}
				</div>
			`;

			if (BX.UI.Hint)
			{
				BX.UI.Hint.init(layout);
			}

			return layout;
		});
	}

	static #onCreateDocumentBtnClick(isLocked: boolean): void
	{
		EventEmitter.emit(SignDocument, SignDocument.events.onDocumentCreateBtnClick);
		if (isLocked)
		{
			top.BX.UI.InfoHelper.show('limit_office_e_signature');

			return;
		}
		const container = SignDocument.#container;

		BX.SidePanel.Instance.open('sign-b2e-settings-init-by-employee', {
			width: 750,
			cacheable: false,
			contentCallback: () => {
				container.innerHTML = '';

				return container;
			},
			events: {
				onLoad: () => {
					SignDocument.#b2eEmployeeSignSettings.clearCache();
					SignDocument.#b2eEmployeeSignSettings.render();
				},
			},
		});
	}
}
