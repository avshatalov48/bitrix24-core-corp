import { Loc, Tag, Type } from 'main.core';
import { Button } from 'ui.buttons';
import { Api } from 'sign.v2.api';
import { Reminder, type ReminderType } from 'sign.type';

import './style.css';
import type { MenuItemOptions } from 'main.popup';

export type Options = { preSelectedType?: ReminderType };

export class ReminderSelector
{
	#api: Api;
	#button: Button;
	#options: Options;
	#chosenTypeId: ReminderType = Reminder.none;

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
			text: this.#getOptionById(Reminder.none).name,
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

	#chooseTypeById(reminderTypeId: ReminderType): void
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

	#getOptionById(reminderTypeId: ReminderType): { id: string, name: string } | null
	{
		return this.#getAvailableOptions()
			.find((option) => option.id === reminderTypeId) ?? null
		;
	}

	#getAvailableOptions(): Array<{ id: ReminderType, name: string }>
	{
		return [
			{
				id: Reminder.none,
				name: Loc.getMessage('SIGN_V2_REMINDER_SELECTOR_OPTION_NONE'),
			},
			{
				id: Reminder.oncePerDay,
				name: Loc.getMessage('SIGN_V2_REMINDER_SELECTOR_OPTION_ONCE_PER_DAY'),
			},
			{
				id: Reminder.twicePerDay,
				name: Loc.getMessage('SIGN_V2_REMINDER_SELECTOR_OPTION_TWICE_PER_DAY'),
			},
			{
				id: Reminder.threeTimesPerDay,
				name: Loc.getMessage('SIGN_V2_REMINDER_SELECTOR_OPTION_THREE_TIMES_PER_DAY'),
			},
		];
	}
}
