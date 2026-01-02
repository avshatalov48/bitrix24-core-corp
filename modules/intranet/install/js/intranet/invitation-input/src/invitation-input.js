import { Cache, Dom, Tag, Validation, Loc, Extension, Type } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { TagSelector } from 'ui.entity-selector';
import './style.css';
import type { InvitationProvider } from './provider/invitation-provider';
import { InvitationToGroup } from './provider/invitation-to-group';
import { InvitationToPortal } from './provider/invitation-to-portal';

export type InvitationInputOptionType = {
	placeholder: ?string,
	minHeight: ?number,
	showErrorBeforeSubmit: ?boolean,
	showErrorAfterSubmit: ?boolean,
};

export const InvitationInputType = Object.freeze({
	PHONE: 'phone',
	EMAIL: 'email',
	ALL: 'all',
});

export class InvitationInput extends EventEmitter
{
	#cache: MemoryCache = new Cache.MemoryCache();
	#invalidPhoneNumbersTagIds: Array<string> = [];
	#isReadySendInvitation: boolean = false;
	#placeholder: string;
	#inputType: InvitationInputType;
	#isPhoneEnabled: boolean;
	#isEmailEnabled: boolean;
	#userLang: string = null;
	#minHeight: ?number = null;
	#showErrorBeforeSubmit: boolean;
	#showErrorAfterSubmit: boolean;

	constructor(options: InvitationInputOptionType)
	{
		super();
		this.#inputType = options?.inputType ?? InvitationInputType.ALL;
		this.setEventNamespace('BX.Intranet.InvitationInput');

		this.#minHeight = options?.minHeight;
		this.#showErrorBeforeSubmit = Type.isBoolean(options?.showErrorBeforeSubmit)
			? options.showErrorBeforeSubmit
			: false;
		this.#showErrorAfterSubmit = Type.isBoolean(options?.showErrorAfterSubmit)
			? options.showErrorAfterSubmit
			: true;

		const settings = Extension.getSettings('intranet.invitation-input');
		if (this.#inputType === InvitationInputType.PHONE && !settings?.isInvitationByPhoneAvailable)
		{
			throw new Error('Incorrect component operation parameters.');
		}

		this.#isPhoneEnabled = [InvitationInputType.ALL, InvitationInputType.PHONE].includes(this.#inputType)
			&& Boolean(settings?.isInvitationByPhoneAvailable);
		this.#isEmailEnabled = [InvitationInputType.ALL, InvitationInputType.EMAIL].includes(this.#inputType);
		this.#placeholder = Type.isStringFilled(options?.placeholder) ? options.placeholder : this.#getDefaultPlaceholder();
	}

	changeLanguage(lang: string): void
	{
		if (Type.isString(lang))
		{
			this.#userLang = lang;
		}
	}

	getTagSelector(): TagSelector
	{
		return this.#cache.remember('tagSelector', () => {
			return new TagSelector({
				id: `intranet-invitation-input-${Math.random(4)}`,
				showTextBox: true,
				showAddButton: false,
				placeholder: this.#placeholder,
				tagTextColor: '#1E8D36',
				tagBgColor: '#D4FDB0',
				tagMaxWidth: 200,
				events: {
					onAfterTagRemove: this.#onAfterTagRemove.bind(this),
					onBeforeTagAdd: this.#onBeforeTagAdd.bind(this),
					onEnter: this.#onEnter.bind(this),
					onBlur: this.#onBlur.bind(this),
					onInput: this.#onInput.bind(this),
					onContainerClick: this.#onContainerClick.bind(this),
				},
			});
		});
	}

	isReadySendInvitation(): boolean
	{
		return this.#isReadySendInvitation;
	}

	render(): HTMLElement
	{
		return this.#cache.remember('node', () => {
			this.getTagSelector().renderTo(this.getWrapper());
			this.getTagSelector().focusTextBox();

			if (Type.isNumber(this.#minHeight))
			{
				const itemsContainer = (this.getTagSelector().getItemsContainer());
				Dom.style(itemsContainer, 'min-height', `${this.#minHeight}px`);
			}

			return this.getWrapper();
		});
	}

	renderTo(node: HTMLElement): void
	{
		Dom.append(this.render(), node);
	}

	getWrapper(): HTMLDivElement
	{
		return this.#cache.remember('wrapper', () => {
			return Tag.render`
				<div class="intranet-invitation-wrapper"></div>
			`;
		});
	}

	inviteToGroup(groupId: number): Promise
	{
		const invitationProvider = new InvitationToGroup(
			groupId,
			this.#getPreparedUserList(),
		);

		return this.#invite(invitationProvider);
	}

	inviteToPortal(): Promise
	{
		const invitationProvider = new InvitationToPortal(this.#getPreparedUserList());

		return this.#invite(invitationProvider);
	}

	hasErrorTags(): boolean
	{
		return this.#getErrorTags().length > 0;
	}

	isEmptyTags(): boolean
	{
		return this.getTagSelector().getTags().length === 0;
	}

	#invite(invitationProvider: InvitationProvider): Promise
	{
		return new Promise((resolve, reject) => {
			this.#removeErrorBlock();

			if (this.#getErrorTags().length > 0)
			{
				this.#addErrorBlockByMessage(this.#getDefaultValidationMessage());
				reject(this.#getDefaultValidationMessage());
			}
			else if (this.getTagSelector().getTags().length === 0)
			{
				this.#addErrorBlockByMessage(this.#getEmptyValidationMessage());
				reject(this.#getEmptyValidationMessage());
			}
			else
			{
				invitationProvider.invite().then(() => {
					this.getTagSelector().removeTags();
					this.#setUnreadySaveState();
					resolve();
				}).catch((response) => {
					if (this.#showErrorAfterSubmit)
					{
						this.#addErrorBlockByMessage(response.errors[0].message);
					}
					else
					{
						this.getTagSelector().removeTags();
					}

					reject(response.errors[0].message);
				});
			}
		});
	}

	#getPreparedUserList(): Array
	{
		this.#removeErrorBlock();
		const selector = this.getTagSelector();
		const tags = selector.getTags();
		const users = [];

		tags.forEach((tag) => {
			if (this.#isEmailEnabled && tag.getEntityType() === 'email')
			{
				users.push({
					email: tag.getTitle(),
					languageId: this.#userLang,
				});
			}

			if (
				tag.getEntityType() === 'phone'
				&& this.#isPhoneEnabled
				&& !this.#invalidPhoneNumbersTagIds.includes(tag.getId())
			)
			{
				users.push({
					phone: tag.getTitle(),
				});
			}
		});

		return users;
	}

	#getErrorTags(): Array
	{
		const selector = this.getTagSelector();
		const tags = selector.getTags();
		const errorTags = [];

		tags.forEach((tag) => {
			if (tag.getEntityType() === 'error')
			{
				errorTags.push(tag);
			}

			if (
				tag.getEntityType() === 'phone'
				&& this.#isPhoneEnabled
				&& this.#invalidPhoneNumbersTagIds.includes(tag.getId())
			)
			{
				errorTags.push(tag);
			}
		});

		return errorTags;
	}

	#setReadySaveState(): void
	{
		this.emit('onReadySave');
		this.#isReadySendInvitation = true;
	}

	#setUnreadySaveState(): void
	{
		this.emit('onUnreadySave');
		this.#isReadySendInvitation = false;
	}

	#getPhoneParser(): BX.PhoneNumberParser
	{
		return BX.PhoneNumberParser.getInstance();
	}

	#getDefaultPlaceholder(): string
	{
		if (this.#isPhoneEnabled && !this.#isEmailEnabled)
		{
			return Loc.getMessage('INTRANET_INVITATION_INPUT_PLACEHOLDER_PHONE');
		}
		else if (!this.#isPhoneEnabled && this.#isEmailEnabled)
		{
			return Loc.getMessage('INTRANET_INVITATION_INPUT_PLACEHOLDER');
		}

		return Loc.getMessage('INTRANET_INVITATION_INPUT_PLACEHOLDER_WITH_PHONE');
	}

	#getDefaultValidationMessage(): string
	{
		if (this.#isPhoneEnabled && !this.#isEmailEnabled)
		{
			return Loc.getMessage('INTRANET_INVITATION_INPUT_VALIDATION_MESSAGE_PHONE');
		}
		else if (!this.#isPhoneEnabled && this.#isEmailEnabled)
		{
			return Loc.getMessage('INTRANET_INVITATION_INPUT_VALIDATION_MESSAGE');
		}

		return Loc.getMessage('INTRANET_INVITATION_INPUT_VALIDATION_MESSAGE_WITH_PHONE');
	}

	#getEmptyValidationMessage(): string
	{
		if (this.#isPhoneEnabled && !this.#isEmailEnabled)
		{
			return Loc.getMessage('INTRANET_INVITATION_INPUT_VALIDATION_MESSAGE_PHONE');
		}
		else if (!this.#isPhoneEnabled && this.#isEmailEnabled)
		{
			return Loc.getMessage('INTRANET_INVITATION_INPUT_EMPTY_MESSAGE');
		}

		return Loc.getMessage('INTRANET_INVITATION_INPUT_EMPTY_MESSAGE_WITH_PHONE');
	}

	#onAfterTagRemove(event: BaseEvent): void
	{
		const selector = event.getTarget();

		const tags = selector.getTags();
		const errorTags = tags.filter((item) => item.getEntityType() === 'error');

		if (errorTags.length === 0)
		{
			this.#removeErrorBlock();
		}

		if (tags.length === 0)
		{
			this.#setUnreadySaveState();
		}
		else if (errorTags.length === 0)
		{
			this.#setReadySaveState();

			if (this.#showErrorBeforeSubmit)
			{
				this.#removeErrorBlock();
			}
		}
	}

	#onBeforeTagAdd(event: BaseEvent): void
	{
		const { tag } = event.getData();
		const textBox = event.target.getTextBox();
		textBox.placeholder = '';
		this.#setReadySaveState();

		if (tag.getEntityType() === 'error')
		{
			this.#setErrorStateForTag(tag);

			if (this.#showErrorBeforeSubmit)
			{
				this.#addErrorBlockByMessage(this.#getDefaultValidationMessage());
			}
		}

		if (tag.getEntityType() === 'phone')
		{
			this.#getPhoneParser().parse(tag.getTitle()).then((result) => {
				if (result.valid)
				{
					tag.setTitle(result.rawNumber);
				}
				else
				{
					this.#setErrorStateForTag(tag);
					this.#invalidPhoneNumbersTagIds.push(tag.getId());
				}
				tag.render();
			}).catch(() => {});
		}
	}

	#setErrorStateForTag(tag): void
	{
		tag.setTextColor('#E92F2A');
		tag.setBgColor('#FFDCDB');
	}

	#onEnter(event: BaseEvent): void
	{
		const selector = event.getTarget();
		const value = selector.getTextBoxValue();

		if (value && !/^\s*$/.test(value))
		{
			this.#addTagByValue(value);
		}
	}

	#onBlur(event: BaseEvent): void
	{
		this.#onEnter(event);
	}

	#onInput(event: BaseEvent): void
	{
		this.#setReadySaveState();
		const selector = event.target;
		const inputEvent = event.getData().event;
		const specialSymbols = [' ', ','];
		let value = selector.getTextBoxValue();

		const index = specialSymbols.indexOf(inputEvent.data);

		if (index === -1)
		{
			return;
		}

		const symbol = specialSymbols[index];

		if (value.endsWith(symbol))
		{
			value = value.slice(0, -1);
		}

		if (value)
		{
			this.#addTagByValue(value);
		}
	}

	#onContainerClick(): void
	{
		this.getTagSelector().getTextBox().focus();
	}

	#getEntityTypeByValue(value: string): string
	{
		const isPhone = this.#isPhoneEnabled ? this.#isPhone(value) : false;
		const isEmail = this.#isEmailEnabled ? (Validation.isEmail(value) && /^[^@]+@[^@]+\.[^@]+$/.test(value)) : false;

		if (isEmail)
		{
			return 'email';
		}

		if (isPhone)
		{
			return 'phone';
		}

		return 'error';
	}

	#addTagByValue(value: string): void
	{
		const selector = this.getTagSelector();
		const parsedValues = this.#parseValue(value);

		parsedValues.forEach((part) => {
			selector.addTag({
				id: part,
				title: part,
				entityId: 'invitation-tag',
				entityType: this.#getEntityTypeByValue(part),
			});
		});
		selector.clearTextBox();
	}

	#parseValue(value: string): Array<string>
	{
		const parts = value.split(/[\s,]+/);

		return parts.filter((part) => part.length > 0);
	}

	#isPhone(value: string): boolean
	{
		return BX.PhoneNumber.getValidNumberRegex().test(value);
	}

	#addErrorBlockByMessage(message: string): void
	{
		if (this.getWrapper().querySelector('.intranet-invitation-input-error__wrapper'))
		{
			return;
		}

		const errorBlock = this.#createErrorBlockByMessage(message);
		this.getWrapper().append(errorBlock);
		this.#setUnreadySaveState();

		if (!Dom.hasClass(this.getTagSelector().getOuterContainer(), '--error'))
		{
			Dom.addClass(this.getTagSelector().getOuterContainer(), '--error');
		}
	}

	#removeErrorBlock(): void
	{
		const errorBlock = this.getWrapper()
			.querySelector('.intranet-invitation-input-error__wrapper');

		if (errorBlock)
		{
			errorBlock.remove();
		}

		if (Dom.hasClass(this.getTagSelector().getOuterContainer(), '--error'))
		{
			Dom.removeClass(this.getTagSelector().getOuterContainer(), '--error');
		}
	}

	#createErrorBlockByMessage(message: string): HTMLElement
	{
		return Tag.render`
			<div class="intranet-invitation-input-error__wrapper">
				<span class="ui-icon-set --warning"></span>
				<span class="intranet-invitation-input-error__text">${message}</span>
			</div>
		`;
	}
}
