import { Loc, Tag, Type, Dom, Text as TextFormat } from 'main.core';
import { UserSelector, UserSelectorEvent } from 'sign.v2.b2e.user-selector';
import type { ItemOptions } from 'ui.entity-selector';
import { Helpdesk } from 'sign.v2.helper';

import './style.css';

const defaultAvatarLink = '/bitrix/js/socialnetwork/entity-selector/src/images/default-user.svg';
const HelpdeskCodes = Object.freeze({
	WhoCanBeRepresentative: '19740734',
});

type UserInfo = {
	id: ?Number,
	name: ?String,
	position: ?String,
	avatarLink: ?String,
};

export type RepresentativeSelectorOptions = {
	userId?: Number;
	description?: string;
	context?: string;
}

export class RepresentativeSelector
{
	#userSelector: UserSelector = null;
	#description: ?string;

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
			element: HTMLSpanElement = null,
		},
		select: {
			container: HTMLDivElement = null,
			text: HTMLSpanElement = null,
			button: HTMLButtonElement = null,
		},
		description: HTMLParagraphElement = null
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
		this.#userSelector = new UserSelector({
			multiple: false,
			context: options.context ?? 'sign_b2e_representative_selector',
		});
		this.#ui.container = this.getLayout();
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
			<span class="sign-document-b2e-representative-select-text">${Loc.getMessage('SIGN_PARTIES_REPRESENTATIVE_SELECT_TEXT')}</span>
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

		this.#ui.info.container = Tag.render`
			<div class="sign-document-b2e-representative-info">
				<div class="sign-document-b2e-representative-info-user-photo">
					${this.#ui.info.avatar}
				</div>
				${this.#ui.info.title.container}
			</div>
		`;

		const description = this.#description
			? Tag.render`<p class="sign-document-b2e-representative__info_paragraph">${this.#description}</p>`
			: Tag.render`
				<span>
					${Helpdesk.replaceLink(Loc.getMessage('SIGN_PARTIES_REPRESENTATIVE_INFO'), HelpdeskCodes.WhoCanBeRepresentative)}
				</span>
			`
		;
		this.#ui.description = Tag.render`
			<div class="sign-document-b2e-representative__info">
				${description}
			</div>
		`;

		this.#ui.container = Tag.render`
			<div>
				<div class="sign-document-b2e-representative__selector">
					${this.#ui.select.container}
					${this.#ui.info.container}
					${this.#ui.changeBtn.container}
				</div>
				${this.#ui.description}
			</div>
		`;

		this.#setEmptyState();
		this.#bindEvents();

		return this.#ui.container;
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

	load(representativeId: number): void
	{
		const dialog = this.#userSelector.getDialog();
		dialog.subscribeOnce('onLoad', () => {
			const userItems = dialog.items.get('user');
			const userItem = userItems.get(`${representativeId}`);
			userItem.select();
			this.#showItem(userItem);
		});
		dialog.load();
	}

	getRepresentativeId(): ?number
	{
		return this.#data.id;
	}

	#bindEvents()
	{
		BX.bind(this.#ui.changeBtn.element, 'click', () => this.#onChangeButtonClickHandler());
		BX.bind(this.#ui.select.button, 'click', () => this.#onChangeButtonClickHandler());
		this.#userSelector.subscribe(UserSelectorEvent.onItemSelect, (event) => this.#onSelectorItemSelectedHandler(event))
		this.#userSelector.subscribe(UserSelectorEvent.onItemDeselect, (event) => this.onSelectorItemDeselectedHandler(event))
	}

	#onChangeButtonClickHandler(): void
	{
		this.#userSelector.getDialog().setTargetNode(this.#ui.container.firstElementChild);
		this.#userSelector.toggle();
	}

	#showItem(item: ItemOptions): void
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

	#onSelectorItemSelectedHandler(event): void
	{
		if (!event?.data?.items || event.data.items.length === 0)
		{
			this.#data.id = null;
			this.#setEmptyState();

			return;
		}

		const item = event.data.items[0];
		this.#showItem(item);
	}

	onSelectorItemDeselectedHandler(event): void
	{
		this.#data.id = null;
		this.#onSelectorItemSelectedHandler(event);
	}

	#refreshView(): void
	{
		this.#ui.info.title.name.innerText = TextFormat.encode(this.#data?.name);
		this.#ui.info.title.position.innerText = TextFormat.encode(this.#data?.position);
		this.#ui.info.avatar.src = this.#data?.avatarLink;
		this.#setInfoState();
	}
}
