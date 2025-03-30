import { Api } from 'sign.v2.api';
import { Dom, Tag, Loc, Event, Text } from 'main.core';
import { EventEmitter } from 'main.core.events';
import './style.css';

import './vacancy-choose.css';
import type { HcmLinkMultipleVacancyEmployee } from 'sign.v2.api';
import { HcmLinkVacancyChooser } from './vacancy-choose';

const maxPreviewUserAvatarCount = 6;

export class HcmLinkEmployeeSelector extends EventEmitter
{
	#api: Api;

	#documentGroupUids: Array<string> = [];
	#enabled: boolean = false;

	#employees: Map<number, HcmLinkMultipleVacancyEmployee> = new Map();
	#companyTitle: string | null = null;

	#container: HTMLDivElement;
	#usersPreviewContainer: HTMLElement | null = null;

	constructor(options: { api: Api })
	{
		super();
		this.#api = options.api;

		this.setEventNamespace('BX.Sign.V2.B2e.HcmLinkEmployeeSelector');
	}

	async check(): Promise<boolean>
	{
		if (!this.#enabled)
		{
			return true;
		}

		this.#employees = await this.#loadEmployees();
		if (this.#employees.size > 0)
		{
			this.#updateUsersPreview();
		}

		return this.#employees.size === 0;
	}

	setDocumentGroupUids(uids: Array<string>): void
	{
		this.#documentGroupUids = uids;
	}

	setEnabled(value: boolean): void
	{
		this.#enabled = value;
	}

	render(): HTMLDivElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		const { root, chooseButton } = Tag.render`
			<div class="sign-b2e-hcm-link-party-checker-container">
				<div class="sign-b2e-hcm-link-party-checker-wrapper">
					<div class="sign-b2e-hcm-link-party-checker-wrapper-part --left">
						<div class="sign-b2e-hcm-link-party-checker-title">
							${Loc.getMessage('SIGN_V2_B2E_HCM_LINK_EMPLOYEE_SELECTOR_WIDGET_TITLE')}
						</div>
						<div class="sign-b2e-hcm-link-party-checker-description">
							${Loc.getMessage('SIGN_V2_B2E_HCM_LINK_EMPLOYEE_SELECTOR_WIDGET_DESCRIPTION')}
						</div>
					</div>
					<div class="sign-b2e-hcm-link-party-checker-wrapper-part --right">
						${this.#getUsersPreviewContainer()}
						<div class="sign-b2e-hcm-link-party-checker__action-button" ref="chooseButton">
							${Loc.getMessage('SIGN_V2_B2E_HCM_LINK_EMPLOYEE_SELECTOR_WIDGET_OPEN_BUTTON')}
						</div>
					</div>
				</div>
			</div>
		`;

		Event.bind(chooseButton, 'click', (): void => this.#openVacancyChooser());

		this.#container = root;

		this.hide();

		return this.#container;
	}

	hide(): void
	{
		Dom.hide(this.#container);
	}

	show(): void
	{
		Dom.show(this.#container);
	}

	async #loadEmployees(): Promise<Map>
	{
		this.#employees.clear();

		const { employees, company } = await this.#api.getMultipleVacancyMemberHrIntegration(
			this.#getLastDocumentUid()
		);
		this.#companyTitle = company.title;

		employees.forEach((employee) => this.#employees.set(employee.userId, employee));

		return new Map([...this.#employees.entries()]);
	}

	#getLastDocumentUid(): string | undefined
	{
		return this.#documentGroupUids.at(-1);
	}

	#openVacancyChooser(): void
	{
		HcmLinkVacancyChooser.openSlider({
			api: this.#api,
			documentGroupUids: this.#documentGroupUids,
			employees: this.#employees,
			companyTitle: this.#companyTitle,
		}, {
			onCloseHandler: () => this.emit('update')
		});
	}

	#updateUsersPreview(): void
	{
		Dom.clean(this.#getUsersPreviewContainer());

		const usersCount = this.#employees.size;

		const userIds = Array.from(this.#employees.keys()).slice(0, maxPreviewUserAvatarCount);
		const userAvatarContainer = Tag.render`
			<div class="sign-b2e-hcm-link-party-checker-users-avatar-container"></div>
		`;

		userIds.forEach((userId: number): void => {
			const employee = this.#employees.get(userId);

			const previewElement = Tag.render`
				<div class="sign-b2e-hcm-link-party-checker-user-preview" 
					title="${Text.encode(employee.fullName)}"
				>
					<img src="${employee.avatarLink}">
				</div>
			`;

			Dom.append(previewElement, userAvatarContainer);
		});

		Dom.append(userAvatarContainer, this.#getUsersPreviewContainer());

		const additionalUserCount = usersCount - maxPreviewUserAvatarCount;
		if (additionalUserCount > 0)
		{
			const counterElement = Tag.render`
				<div class="sign-b2e-hcm-link-party-checker-users-preview-counter">
					${Loc.getMessage('SIGN_V2_B2E_HCM_LINK_EMPLOYEE_USERS_COUNT_PLUS', { '#COUNT#' : additionalUserCount })}
				</div>
			`;
			Dom.append(counterElement, this.#getUsersPreviewContainer());
		}
	}

	#getUsersPreviewContainer(): HTMLElement
	{
		if (!this.#usersPreviewContainer)
		{
			this.#usersPreviewContainer = Tag.render`
				<div class="sign-b2e-hcm-link-party-checker-users-preview-container"></div>
			`;
		}

		return this.#usersPreviewContainer;
	}
}
