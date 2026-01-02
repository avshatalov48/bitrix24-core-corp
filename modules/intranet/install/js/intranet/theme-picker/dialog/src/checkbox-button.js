import { Tag, Loc } from 'main.core';
import { PopupWindowButton } from 'main.popup';
import { InfoHelper } from 'ui.info-helper';

export class CheckboxButton extends PopupWindowButton
{
	#themePicker: BX.Intranet.Bitrix24.ThemePicker;
	#checkbox: HTMLInputElement;

	constructor(themePicker: BX.Intranet.Bitrix24.ThemePicker)
	{
		super({ id: 'checkbox' });

		this.#themePicker = themePicker;
		this.#checkbox = Tag.render`
			<input
				id="theme-dialog-checkbox-input"
				class="theme-dialog-checkbox-input"
				type="checkbox"
				name="defaultTheme"
				value="Y"
				onclick="${this.#handleCheckboxClick.bind(this)}"
			>
		`;

		this.buttonNode = Tag.render`
			<div class="theme-dialog-checkbox-button">
				${this.#checkbox}
				<label for="theme-dialog-checkbox-input" class="theme-dialog-checkbox-label">
					${Loc.getMessage('BITRIX24_THEME_DEFAULT_THEME_FOR_ALL')}
				</label>
				${this.#themePicker.canSetDefaultTheme() ? '' : Tag.render`<span class="tariff-lock"></span>`}
			</div>
		`;
	}

	isChecked(): boolean
	{
		return this.#checkbox.checked;
	}

	check(): void
	{
		this.#checkbox.checked = true;
	}

	uncheck(): void
	{
		this.#checkbox.checked = false;
	}

	#handleCheckboxClick(): void
	{
		if (this.#themePicker.canSetDefaultTheme())
		{
			return;
		}

		InfoHelper.show('limit_office_background_to_all');

		this.uncheck();
	}
}
