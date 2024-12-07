import { Dom, Loc, Tag, Text as TextFormat } from 'main.core';
import type { Loader } from 'main.loader';
import { Helpdesk } from 'sign.v2.helper';
import { Dialog, TagSelector } from 'ui.entity-selector';
import type { UserPartyOptions } from './type';
import type { CardItem } from './types/card-item';
import { UserPartyCounters } from 'sign.v2.b2e.user-party-counters';
import 'ui.icon-set.main';

import './style.css';

export type UserPartyConfig = {
	region: string,
	b2eSignersLimitCount: number,
}
const Mode = Object.freeze({
	view: 'view',
	edit: 'edit',
});

const defaultAvatarLink = '/bitrix/js/socialnetwork/entity-selector/src/images/default-user.svg';
const HelpdeskCodes = Object.freeze({
	SignEdmWithEmployees: '19740792',
});

export class UserParty
{
	#ui = {
		container: HTMLDivElement = null,
		itemContainer: HTMLDivElement = null,
		header: HTMLSpanElement = null,
		description: HTMLParagraphElement = null,
		userPartyCounterContainer: HTMLDivElement = null,
	};

	#items: Map<number, CardItem> = new Map();
	#preselectedUserIds: Array<number> = [];

	#viewMode = Mode.edit;

	#tagSelector: ?TagSelector = null;
	#loader: ?Loader = null;
	#userPartyCounters: UserPartyCounters = null;

	constructor(options: UserPartyOptions)
	{
		this.#viewMode = options.mode;
		this.#init(options);
	}

	#init(options: UserPartyOptions): void
	{
		if (this.#viewMode === Mode.view)
		{
			this.#ui.container = this.getLayout(options.region);

			return;
		}

		const { b2eSignersLimitCount, region } = options;
		this.#userPartyCounters = new UserPartyCounters({
			userCountersLimit: b2eSignersLimitCount,
		});
		this.#ui.container = this.getLayout(region);
		this.#tagSelector = new TagSelector({
			events: {
				onTagRemove: (event) => {
					const { tag } = event.getData();
					this.#removeItem(tag.id);
				},
				onTagAdd: (event) => {
					const { tag } = event.getData();
					this.#addItem(tag);
				},
			},
			dialogOptions: {
				width: 425,
				height: 363,
				multiple: true,
				targetNode: this.#ui.itemContainer,
				entities: [
					{
						id: 'user',
						options: { intranetUsersOnly: true },
					},
				],
				dropdownMode: true,
				hideOnDeselect: true,
			},
		});
		this.#tagSelector.renderTo(this.#ui.itemContainer);
	}

	getLayout(region: string): HTMLElement
	{
		if (this.#ui.container)
		{
			return this.#ui.container;
		}

		this.#ui.itemContainer = Tag.render`
			<div class="sign-document-b2e-user-party__item-list"></div>
		`;
		if (this.#viewMode !== Mode.edit)
		{
			Dom.addClass(this.#ui.itemContainer, '--view');

			return this.#ui.itemContainer;
		}

		const descriptionMessage = region === 'ru'
			? Helpdesk.replaceLink(Loc.getMessage('SIGN_USER_PARTY_DESCRIPTION'), HelpdeskCodes.SignEdmWithEmployees)
			: Loc.getMessage('SIGN_CMP_MASTER_TPL_TOUR_STEP_CHOOSE_MEMBER_USER_PARTY_DESCRIPTION')
		;
		this.#ui.description = Tag.render`
			<p class="sign-document-b2e-user-party__description">
				${descriptionMessage}
			</p>
		`;

		return Tag.render`
			<div>
				<div class="sign-b2e-settings__header-wrapper">
					<h1 class="sign-b2e-settings__header">${Loc.getMessage('SIGN_USER_PARTY_HEADER')}</h1>
					${this.#userPartyCounters.getLayout()}
				</div>
				<div class="sign-b2e-settings__item">
					<p class="sign-b2e-settings__item_title">
						${Loc.getMessage('SIGN_USER_PARTY_ITEM_TITLE')}
					</p>
					${this.#ui.itemContainer}
					${this.#ui.description}
				</div>
			</div>
		`;
	}

	async load(ids: number[]): Promise<void>
	{
		const { dialog } = this.#tagSelector;
		dialog.preselectedItems = ids.map((userId) => ['user', userId]);
		const promise = new Promise((resolve) => {
			dialog.subscribeOnce('onLoad', resolve);
		});
		dialog.load();
		await promise;
	}

	setUsersIds(usersData: [number]): void
	{
		this.#clean();
		this.#preselectedUserIds = usersData;
		this.#loadPreselectedUsersData();
	}

	async #loadPreselectedUsersData(): Promise<void>
	{
		this.#showLoader();
		await new Promise((resolve) => {
			const dialog = new Dialog({
				entities: [
					{ id: 'user' },
				],
				events: {
					onLoad: () => {
						dialog.getSelectedItems().forEach((item) => {
							this.#addItem(item);
						});
						resolve();
					},
				},
				preselectedItems: this.#preselectedUserIds.map((userId) => {
					return ['user', userId];
				}),
			});
			dialog.load();
		});
		this.#hideLoader();
	}

	#showLoader(): void
	{
		this.#ui.itemContainer.style.display = 'none';
		this.#getLoader().show();
	}

	#hideLoader(): void
	{
		this.#ui.itemContainer.style.display = 'flex';
		this.#getLoader().hide();
	}

	#getLoader(): Loader
	{
		if (this.#loader)
		{
			return this.#loader;
		}

		this.#loader = new BX.Loader({
			target: this.#ui.container,
			mode: 'inline',
			size: 40,
		});

		return this.#loader;
	}

	#removeItem(userId: string)
	{
		const item = this.#items.get(userId);
		if (item?.container)
		{
			Dom.remove(item.container);
		}

		this.#items.delete(userId);
		this.#userPartyCounters?.update(this.#items.size);
	}

	#addItem(tag): void
	{
		const item = {
			id: tag.id,
			name: tag.customData?.get('name'),
			lastName: tag.customData?.get('lastName'),
			avatar: tag?.avatar,
		};

		const container = this.#viewMode === Mode.view
			? this.#createItemLayout(item) : null;
		if (container)
		{
			Dom.append(container, this.#ui.itemContainer);
		}

		item.container = container;
		this.#items.set(item.id, item);
		this.#userPartyCounters?.update(this.#items.size);
	}

	validate(): boolean
	{
		const isValid = this.#items.size > 0;
		const tagSelectorContainer = this.#tagSelector.getOuterContainer();
		if (isValid)
		{
			Dom.removeClass(tagSelectorContainer, '--invalid');
		}
		else
		{
			Dom.addClass(tagSelectorContainer, '--invalid');
		}

		return isValid;
	}

	getUserIds(): Array<number>
	{
		return [...this.#items.keys()];
	}

	#createItemLayout(item: CardItem): HTMLElement
	{
		const name = TextFormat.encode(item.name) ?? '';
		const lastName = TextFormat.encode(item.lastName) ?? '';

		return Tag.render`
			<img
				class="sign-document-b2e-user-party__item-list_item-avatar"
				title="${name} ${lastName}" src='${item.avatar || defaultAvatarLink}'
			/>
		`;
	}

	#clean(): void
	{
		[...this.#items.values()].forEach((item) => Dom.remove(item.container));
		this.#items.clear();
		this.#userPartyCounters?.update(this.#items.size);
	}
}
