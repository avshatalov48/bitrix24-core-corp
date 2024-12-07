import { Tag, Loc } from 'main.core';
import { DateTimeFormat } from 'main.date';

export class DateSelector
{
	#documentDateField: HTMLElement;
	#selectedDate: string;

	constructor()
	{
		this.#documentDateField = this.#getDateField();
		const date = new Date();
		this.#selectDate(date);
		this.setDateInCalendar(date);
	}

	#formatDate(date: Date, formatType: string): string
	{
		const template = DateTimeFormat.getFormat(formatType);

		return DateTimeFormat.format(template, date);
	}

	#getDateField(): HTMLElement
	{
		return Tag.render`
			<div
				class="sign-b2e-document-setup__date-selector_field"
				onclick="${() => {
					BX.calendar({
						node: this.#documentDateField,
						field: this.#documentDateField,
						bTime: false,
						callback_after: (date) => {
							this.#selectDate(date);
							this.setDateInCalendar(date);
						}
					});
				}}"
			>
				<span class="sign-b2e-document-setup__date-selector_field-text"></span>
			</div>
		`;
	}

	#selectDate(date: Date): void
	{
		const formattedDate = this.#formatDate(date, 'SHORT_DATE_FORMAT');
		this.#selectedDate = formattedDate;
	}

	setDateInCalendar(date: Date): void
	{
		const formattedDate = this.#formatDate(date, 'DAY_MONTH_FORMAT');
		const dateTextNode = this.#documentDateField.firstElementChild;
		dateTextNode.textContent = formattedDate;
		dateTextNode.title = formattedDate;
	}

	getLayout(): HTMLElement
	{
		return Tag.render`
			<div class="sign-b2e-document-setup__date-selector">
				<span class="sign-b2e-document-setup__date-selector_label">
					${Loc.getMessage('SIGN_DOCUMENT_SETUP_DATE_LABEL')}
				</span>
				${this.#documentDateField}
			</div>
		`;
	}

	getSelectedDate(): string
	{
		return this.#selectedDate;
	}
}
