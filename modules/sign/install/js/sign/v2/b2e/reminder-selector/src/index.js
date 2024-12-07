import { Loc, Tag, Type } from 'main.core';
import { Button } from 'ui.buttons';
import { Api } from 'sign.v2.api';

import './style.css';
import type { MenuItemOptions } from 'main.popup';

export const ReminderType: $ReadOnly<{ [key: ReminderTypeId]: ReminderTypeId }> = Object.freeze({
	none: 'none',
	oncePerDay: 'oncePerDay',
	twicePerDay: 'twicePerDay',
	threeTimesPerDay: 'threeTimesPerDay',
});

export type ReminderTypeId = 'none' | 'oncePerDay' | 'twicePerDay' | 'threeTimesPerDay';
export type Options = { preSelectedType?: ReminderTypeId };

export class ReminderSelector
{
	#api: Api;
	#button: Button;
	#options: Options;
	#chosenTypeId: ReminderTypeId = ReminderType.none;

	constructor(options: Options = {})
	{
		this.#options = options;
		this.#button = this.#getButton();
		this.#api = new Api();
		if (!Type.isUndefined(this.#options.preSelectedType))
		{
			this.#chooseTypeById(this.#options.preSelectedType);
		}
	}

	getLayout(): HTMLElement
	{
		return Tag.render`
				<div class="sign-reminder-selector">
				<span class="sign-reminder-selector__label">
					${Loc.getMessage('SIGN_V2_REMINDER_SELECTOR_TITLE')}
				</span>
				${this.#button.getContainer()}
			</div>
		`;
	}

	save(documentUid: string, memberRole: string): Promise<void>
	{
		return this.#api.modifyReminderTypeForMemberRole(documentUid, memberRole, this.#chosenTypeId);
	}

	#getItems(): Array<{ text: string; onclick: Function }>
	{
		return this.#getAvailableOptions().map((reminderType): MenuItemOptions => {
			return {
				text: reminderType.name,
				onclick: () => this.#chooseTypeById(reminderType.id),
			};
		});
	}

	#getButton(): Button
	{
		return new Button({
			text: this.#getOptionById(ReminderType.none).name,
			dropdown: true,
			closeByEsc: true,
			autoHide: true,
			autoClose: true,
			color: BX.UI.Button.Color.LIGHT,
			size: BX.UI.Button.Size.SMALL,
			menu: {
				items: this.#getItems(),
			},
			className: 'sign-reminder-selector__button',
		});
	}

	#chooseTypeById(reminderTypeId: ReminderTypeId): void
	{
		this.#button.menuWindow.close();

		const option = this.#getOptionById(reminderTypeId);
		if (!option)
		{
			return;
		}

		this.#button.setText(option.name);
		this.#chosenTypeId = option.id;
	}

	#getOptionById(reminderTypeId: ReminderTypeId): { id: string, name: string } | null
	{
		return this.#getAvailableOptions()
			.find((option) => option.id === reminderTypeId) ?? null
		;
	}

	#getAvailableOptions(): Array<{ id: ReminderTypeId, name: string }>
	{
		return [
			{
				id: ReminderType.none,
				name: Loc.getMessage('SIGN_V2_REMINDER_SELECTOR_OPTION_NONE'),
			},
			{
				id: ReminderType.oncePerDay,
				name: Loc.getMessage('SIGN_V2_REMINDER_SELECTOR_OPTION_ONCE_PER_DAY'),
			},
			{
				id: ReminderType.twicePerDay,
				name: Loc.getMessage('SIGN_V2_REMINDER_SELECTOR_OPTION_TWICE_PER_DAY'),
			},
			{
				id: ReminderType.threeTimesPerDay,
				name: Loc.getMessage('SIGN_V2_REMINDER_SELECTOR_OPTION_THREE_TIMES_PER_DAY'),
			},
		];
	}
}
