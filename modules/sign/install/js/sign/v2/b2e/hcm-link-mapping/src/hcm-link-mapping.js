import { Dom, Event, Loc, Tag, Text, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Mapper } from 'humanresources.hcmlink.data-mapper';
import type { Api } from 'signproxy.signing.api';
import type { HrmLinkOptions } from './type';

import './style.css';
import { Dialog } from 'ui.entity-selector';
import { EntityTypes } from '../../document-send/src/item';

const maxPreviewUserAvatarCount = 6;
const defaultAvatarLink = '/bitrix/js/socialnetwork/entity-selector/src/images/default-user.svg';

export class HcmLinkMapping extends EventEmitter
{
	#api: Api;

	#documentUid: string | null = null;
	#integrationId: number | null = null;
	#employeeIds: Array<number> = [];
	#participantsIds: Array<number> = [];

	#container: HTMLElement | null = null;
	#usersPreviewContainer: HTMLElement | null = null;

	#enabled: boolean = false;

	constructor(options: HrmLinkOptions)
	{
		super();
		this.#api = options.api;

		this.setEventNamespace('BX.Sign.V2.B2e.HcmLinkMapping');

		this.#container = this.render();
	}

	render(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		const { root, syncButton } = Tag.render`
			<div class="sign-b2e-hcm-link-party-checker-container --orange">
				<div class="sign-b2e-hcm-link-party-checker-wrapper">
					<div class="sign-b2e-hcm-link-party-checker-wrapper-part --left">
						<div class="sign-b2e-hcm-link-party-checker-title">
							${Loc.getMessage('SIGN_V2_B2E_HCM_LINK_MAPPING_TITLE')}
						</div>
						<div class="sign-b2e-hcm-link-party-checker-description">
							${Loc.getMessage('SIGN_V2_B2E_HCM_LINK_MAPPING_TEXT')}
						</div>
					</div>
					<div class="sign-b2e-hcm-link-party-checker-wrapper-part --right">
						${this.#getUsersPreviewContainer()}
						<div class="sign-b2e-hcm-link-party-checker__action-button" ref="syncButton">
							${Loc.getMessage('SIGN_V2_B2E_HCM_LINK_MAPPING_SYNC_BUTTON')}
						</div>
					</div>
				</div>
			</div>
		`;

		Event.bind(syncButton, 'click', (): void => this.#openMapper());

		this.#container = root;
		this.hide();

		return this.#container;
	}

	setEnabled(value: boolean): void
	{
		this.#enabled = value;
	}

	setDocumentUid(uid: string): void
	{
		this.#documentUid = uid;
	}

	async check(): Promise<boolean>
	{
		if (!Type.isStringFilled(this.#documentUid))
		{
			return true;
		}

		const { integrationId, userIds, allUserIds } = await this.#api.checkNotMappedMembersHrIntegration(this.#documentUid);

		this.#participantsIds = allUserIds;
		this.#employeeIds = userIds;
		this.#integrationId = integrationId;

		if (this.#employeeIds.length > 0)
		{
			await this.#updateUsersPreview();
		}

		return !Type.isArrayFilled(this.#employeeIds);
	}

	#openMapper(): void
	{
		Mapper.openSlider({
			companyId: this.#integrationId,
			userIds: new Set(this.#participantsIds),
			mode: Mapper.MODE_DIRECT,
		}, {
			onCloseHandler: (): void => {
				this.emit('update');
			},
		});
	}

	hide(): void
	{
		Dom.hide(this.#container);
	}

	show(): void
	{
		Dom.show(this.#container);
	}

	#loadUsersAvatarMap(userIds: Array<number>): Promise<Map<number, string>>
	{
		return new Promise((resolve) => {
			const dialog = new Dialog({
				entities: [
					{ id: EntityTypes.User }
				],
				events: {
					'onLoad': (event) => {
						const users = dialog.getSelectedItems();
						const avatarByUserMap = new Map();

						users.forEach((item) => {
							avatarByUserMap.set(Number(item.id), item.avatar);
						})

						resolve(avatarByUserMap);
					},
				},
				preselectedItems: userIds.map((userId: number): [string, number] => ['user', userId])
			});

			dialog.load();
		});
	}

	async #updateUsersPreview(): void
	{
		const usersCount = this.#employeeIds.length;
		const userIds = this.#employeeIds.slice(0, maxPreviewUserAvatarCount);
		const usersAvatarMap =  await this.#loadUsersAvatarMap(userIds);

		Dom.clean(this.#getUsersPreviewContainer());

		const userAvatarContainer = Tag.render`
			<div class="sign-b2e-hcm-link-party-checker-users-avatar-container"></div>
		`;

		userIds.forEach((userId: number): void => {
			const avatarLink = usersAvatarMap.get(userId) ?? defaultAvatarLink;

			const previewElement = Tag.render`
				<div class="sign-b2e-hcm-link-party-checker-user-preview --orange">
					<img src="${avatarLink}">
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
