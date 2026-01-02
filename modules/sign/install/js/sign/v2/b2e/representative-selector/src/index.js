import { Loc, Tag, Type, Dom, Text as TextFormat, Text } from 'main.core';
import { Menu, Popup } from 'main.popup';
import { UserSelector, UserSelectorEvent } from 'sign.v2.b2e.user-selector';
import type { ItemOptions } from 'ui.entity-selector';
import { Helpdesk } from 'sign.v2.helper';
import { EntityType } from 'sign.type';

import './style.css';

const defaultAvatarLink = '/bitrix/js/socialnetwork/entity-selector/src/images/default-user.svg';
const HelpdeskCodes = Object.freeze({
	WhoCanBeRepresentative: '19740734',
});
const representativeSelectorMenuButtonId = 'sign-document-b2e-representative_selector_menu_button';

type UserInfo = {
	id: ?Number,
	name: ?String,
	position: ?String,
	avatarLink: ?String,
};

export type RepresentativeSelectorOptions = {
	excludedEntityList: ?[],
	isDescriptionVisible: boolean;
	isMenuButtonVisible: boolean;
	onDelete: () => void;
	onSelect: () => void;
	onHide: () => void;
	cacheable?: boolean,
	userId?: Number;
	description?: string;
	roleEnabled?: boolean;
	context?: string;
}

export class RepresentativeSelector
{
	#userSelector: UserSelector = null;
	#description: ?string;
	#isDescriptionVisible: boolean = true;
	#isMenuButtonVisible: boolean = false;
	#isMenuVisible: boolean = false;
	#uuid: ?string;
	#itemType: ?string;
	#onDelete: () => void;
	#onSelect: () => void;
	#onHide: () => void;

	#ui = {
		container: HTMLDivElement = null,
		info: {
			container: HTMLDivElement = null,
			avatar: HTMLImageElement = null,
			title: {
				container: HTMLDivElement = null,
				name: HTMLDivElement = null,
				position: HTMLDivElement = null,
			},
		},
		changeBtn: {
			container: HTMLDivElement = null,
			element: HTMLDivElement = null,
		},
		menuBtn: {
			container: HTMLDivElement = null,
			element: HTMLSpanElement = null,
		},
		select: {
			container: HTMLDivElement = null,
			text: HTMLSpanElement = null,
			button: HTMLButtonElement = null,
		},
		description: HTMLParagraphElement = null,
	};

	#data: UserInfo = {
		id: null,
		name: null,
		position: null,
		avatarLink: null,
	};

	constructor(options: RepresentativeSelectorOptions = {})
	{
		this.#data.id = Type.isInteger(options.userId) ? options.userId : null;
		this.#description = options.description;
		this.#isDescriptionVisible = options.isDescriptionVisible ?? true;
		this.#isMenuButtonVisible = options.isMenuButtonVisible ?? false;
		this.#userSelector = new UserSelector({
			cacheable: options.cacheable,
			multiple: false,
			roleEnabled: options.roleEnabled ?? false,
			context: options.context ?? 'sign_b2e_representative_selector',
			excludedEntityList: options.excludedEntityList ?? [],
		});

		const defaultCallback = () => null;
		this.#onDelete = options.onDelete ?? defaultCallback;
		this.#onSelect = options.onSelect ?? defaultCallback;
		this.#onHide = options.onHide ?? defaultCallback;

		this.#ui.container = this.getLayout();
	}

	setExcludedEntityList(excludedEntityList: Array): void
	{
		if (!this.#userSelector)
		{
			return;
		}

		this.#userSelector.setExcludedEntityList(excludedEntityList);
	}

	getContainerId(): string
	{
		if (!this.#uuid)
		{
			this.#uuid = Text.getRandom();
		}

		return `sign_b2e_representative_selector_${this.#uuid}`;
	}

	getLayout(): HTMLDivElement
	{
		if (this.#ui.container)
		{
			return this.#ui.container;
		}

		this.#ui.info.title.name = Tag.render`
			<div class="sign-document-b2e-representative-info-user-name"></div>
		`;
		this.#ui.info.title.position = Tag.render`
			<div class="sign-document-b2e-representative-info-user-pos"></div>
		`;
		this.#ui.info.avatar = Tag.render`
			<img src="${defaultAvatarLink}">
		`;

		this.#ui.info.title.container = Tag.render`
			<div class="sign-document-b2e-representative-info-user-title">
				${this.#ui.info.title.name}
				${this.#ui.info.title.position}
			</div>
		`;

		this.#ui.select.text = Tag.render`
			<span class="sign-document-b2e-representative-select-text">
				${Loc.getMessage('SIGN_PARTIES_REPRESENTATIVE_SELECT_TEXT')}
			</span>
		`;
		this.#ui.select.button = Tag.render`
			<button class="ui-btn ui-btn-success ui-btn-xs ui-btn-round">
				${Loc.getMessage('SIGN_PARTIES_REPRESENTATIVE_SELECT_BUTTON')}
			</button>
		`;
		this.#ui.select.container = Tag.render`
			<div class="sign-document-b2e-representative-select">
				${this.#ui.select.text}
				${this.#ui.select.button}
			</div>
		`;

		this.#ui.changeBtn.element = Tag.render`
			<span class="sign-document-b2e-representative-change-btn"></span>
		`;
		this.#ui.changeBtn.container = Tag.render`
			<div class="sign-document-b2e-representative-change">
				${this.#ui.changeBtn.element}
			</div>
		`;

		this.#ui.menuBtn.container = Tag.render`
			<div id="${representativeSelectorMenuButtonId}" class="sign-document-b2e-company-info-edit" onclick="${(): void => this.#showMenu()}"></div>
		`;

		this.#ui.info.container = Tag.render`
			<div class="sign-document-b2e-representative-info">
				<div class="sign-document-b2e-representative-info-user-photo">
					${this.#ui.info.avatar}
				</div>
				${this.#ui.info.title.container}
			</div>
		`;

		let description = '';

		if (this.#isDescriptionVisible)
		{
			description = this.#description
				? Tag.render`<p class="sign-document-b2e-representative__info_paragraph">${this.#description}</p>`
				: Tag.render`
					<span>
						${Helpdesk.replaceLink(
							Loc.getMessage('SIGN_PARTIES_REPRESENTATIVE_INFO'),
							HelpdeskCodes.WhoCanBeRepresentative,
						)}
					</span>
				`
			;
		}

		this.#ui.description = Tag.render`
			<div class="sign-document-b2e-representative__info">
				${description}
			</div>
		`;

		const menuBtn = this.#isMenuButtonVisible ? this.#ui.menuBtn.container : Tag.render``;

		this.#ui.container = Tag.render`
			<div id="${this.getContainerId()}">
				<div class="sign-document-b2e-representative__selector">
					${this.#ui.select.container}
					${this.#ui.info.container}
					${this.#ui.changeBtn.container}
					${menuBtn}
				</div>
				${this.#ui.description}
			</div>
		`;

		this.#setEmptyState();
		this.#bindEvents();

		return this.#ui.container;
	}

	#showMenu(): void
	{
		const representativeSelectorElement = document.getElementById(this.getContainerId());
		if (representativeSelectorElement === null)
		{
			return;
		}

		const menuButtonElement = representativeSelectorElement.querySelector(`#${representativeSelectorMenuButtonId}`);
		if (menuButtonElement === null)
		{
			return;
		}

		const menu = new Menu({
			bindElement: menuButtonElement,
			cacheable: false,
			events: {
				onPopupClose: (popup: Popup) => {
					this.#isMenuVisible = false;
				},
			},
		});

		if (this.#isMenuVisible)
		{
			menu.close();

			return;
		}

		this.#isMenuVisible = true;

		menu.addMenuItem({
			text: Loc.getMessage('SIGN_B2E_REPRESENTATIVE_SELECTOR_DELETE_BUTTON_TITLE'),
			onclick: () => {
				representativeSelectorElement.remove();
				this.#onDelete(this.getContainerId());
				menu.close();
			},
		});
		menu.show();
	}

	formatSelectButton(className: string)
	{
		this.#ui.select.button.className = `ui-btn ${className}`;
	}

	#setInfoState()
	{
		this.#ui.info.container.style.display = 'flex';
		BX.show(this.#ui.changeBtn.container);
		BX.show(this.#ui.description);
		BX.hide(this.#ui.select.container);
	}

	#setEmptyState()
	{
		BX.hide(this.#ui.info.container);
		BX.hide(this.#ui.changeBtn.container);
		BX.hide(this.#ui.description);
		BX.show(this.#ui.select.container);
	}

	format(id: string, className: string)
	{
		this.#ui[id].className = className;
	}

	validate(): boolean
	{
		const isValid = Type.isInteger(this.getRepresentativeId()) && this.getRepresentativeId() > 0;
		if (isValid)
		{
			Dom.removeClass(this.#ui.container.firstElementChild, '--invalid');
		}
		else
		{
			Dom.addClass(this.#ui.container.firstElementChild, '--invalid');
		}

		return isValid;
	}

	load(representativeId: number, entityType: string = EntityType.USER): void
	{
		const dialog = this.#userSelector.getDialog();
		dialog.subscribeOnce('onLoad', () => {
			const userItems = dialog.items.get(entityType);
			const userItem = userItems.get(`${representativeId}`);
			userItem.select();
			this.#showItem(userItem);
		});
		dialog.load();
		this.#data.id = representativeId;
		this.#itemType = entityType;
	}

	loadFistRepresentative(): void
	{
		const dialog = this.#userSelector.getDialog();
		dialog.subscribeOnce('onLoad', () => {
			const userItems = dialog.items.get('user');

			if (userItems.size === 0)
			{
				return;
			}

			const firstUserItem = userItems.values().next().value;
			firstUserItem.select();
			this.#showItem(firstUserItem);
		});
		dialog.load();
	}

	getRepresentativeId(): ?number
	{
		return this.#data.id;
	}

	getRepresentativeItemType(): ?string
	{
		return this.#itemType;
	}

	#bindEvents()
	{
		BX.bind(this.#ui.changeBtn.element, 'click', () => this.#onChangeButtonClickHandler());
		BX.bind(this.#ui.select.button, 'click', () => this.#onChangeButtonClickHandler());
		this.#userSelector.subscribe(
			UserSelectorEvent.onItemSelect,
			(event) => this.#onSelectorItemSelectedHandler(event),
		);
		this.#userSelector.subscribe(
			UserSelectorEvent.onItemDeselect,
			(event) => this.onSelectorItemDeselectedHandler(event),
		);
		this.#userSelector.subscribe(
			UserSelectorEvent.onHide,
			(event) => this.#onSelectorDialogHide(event),
		);
	}

	#onChangeButtonClickHandler(): void
	{
		this.#userSelector.getDialog().setTargetNode(this.#ui.container.firstElementChild);
		this.#userSelector.toggle();
	}

	#onSelectorDialogHide(event): void
	{
		this.#onHide(this.getContainerId());
	}

	#onSelectorItemSelectedHandler(event): void
	{
		this.#onSelect(this.getContainerId());
		if (!event?.data?.items || Number(event?.data?.items?.length) === 0)
		{
			this.#data.id = null;
			this.#setEmptyState();
			this.#userSelector.setPreselectedEntityList([]);

			return;
		}

		const item = event.data.items[0];
		this.#userSelector.setPreselectedEntityList([
			{
				id: item.id,
				type: item.entityId,
			},
		]);

		this.#showItem(item);
	}

	#showItem(item: ItemOptions): void
	{
		this.#itemType = item.entityId;

		if (item.entityId === EntityType.USER)
		{
			this.#showUserItem(item);
		}
		else if (item.entityId === EntityType.STRUCTURE_NODE_ROLE)
		{
			this.#showRoleItem(item);
		}
	}

	#showUserItem(item: ItemOptions): void
	{
		this.#data.id = item.id;
		if (!Type.isInteger(this.#data.id) || this.#data.id <= 0)
		{
			return;
		}

		const name = item.customData?.get('name') ?? '';
		const lastName = item.customData?.get('lastName') ?? '';
		this.#data.name = Type.isStringFilled(name) ? name : '';
		if (Type.isStringFilled(lastName))
		{
			if (Type.isStringFilled(name))
			{
				this.#data.name += ' ';
			}
			this.#data.name += lastName;
		}

		if (!Type.isStringFilled(this.#data.name))
		{
			this.#data.name = item.customData?.get('login') ?? '';
		}

		this.#data.position = item.customData?.get('position') || '';
		this.#data.avatarLink = (item?.avatar || defaultAvatarLink);

		this.#refreshView();
	}

	#showRoleItem(item: ItemOptions): void
	{
		this.#data.id = item.id;
		if (!Type.isInteger(this.#data.id) || this.#data.id <= 0)
		{
			return;
		}

		this.#data.name = item.title;
		this.#data.avatarLink = defaultAvatarLink;
		this.#data.position = '';

		this.#refreshView();
	}

	onSelectorItemDeselectedHandler(event): void
	{
		this.#data.id = null;
		this.#onSelectorItemSelectedHandler(event);
		this.#userSelector.setPreselectedEntityList([]);
	}

	#refreshView(): void
	{
		this.#ui.info.title.name.innerText = TextFormat.encode(this.#data?.name);
		this.#ui.info.title.position.innerText = TextFormat.encode(this.#data?.position);
		this.#ui.info.avatar.src = this.#data?.avatarLink;
		this.#setInfoState();
	}
}
