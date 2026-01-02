import { Extension, Loc, Tag, Dom, Text } from 'main.core';
import { UI } from 'ui.notification';
import { EventEmitter } from 'main.core.events';
import { DateTimeFormat, Timezone } from 'main.date';
import { Api } from 'sign.v2.api';
import './style.css';
import 'ui.design-tokens';

export class DatetimeLimitSelector extends EventEmitter
{
	#api: Api;
	#documentUids: Array<string>;
	#documentDateField: HTMLElement;
	#selectedDate: Date | null = null;
	#signingMinMinutes: number;
	#signingMaxMonth: number;

	constructor()
	{
		super();
		this.setEventNamespace('BX.Sign.V2.DatetimeLimitSelector');

		this.#documentUids = [];
		this.#api = new Api();

		this.#documentDateField = this.#getDateField();

		const settings = Extension.getSettings('sign.v2.datetime-limit-selector');

		const defaultSignUntilDate = settings.get('defaultSignUntilDate', null);
		this.setDate(defaultSignUntilDate ? new Date(defaultSignUntilDate) : new Date());

		this.#signingMinMinutes = settings.get('signingMinMinutes', 5);
		this.#signingMaxMonth = settings.get('signingMaxMonth', 3);
	}

	getLayout(): HTMLElement
	{
		return Tag.render`
			<div class="sign-datetime-limit-selector">
				<span class="sign-datetime-limit-selector__label">
					${Loc.getMessage('SIGN_BLANK_DATETIME_SELECTOR_LABEL')}
				</span>
				${this.#documentDateField}
			</div>
		`;
	}

	#getDateField(): HTMLElement
	{
		return Tag.render`
			<div
				class="sign-datetime-limit-selector_field"
				onclick="${() => {
					BX.calendar({
						node: this.#documentDateField,
						field: this.#documentDateField,
						currentTime: this.#selectedDate.getTime() / 1000,
						value: DateTimeFormat.format(DateTimeFormat.getFormat('FORMAT_DATETIME'), this.#selectedDate.getTime() / 1000),
						bTime: true,
						bHideTime: false,
						callback: (date) => {
							this.emit('beforeDateModify');

							if (!this.#isDatePassMinValidation(date))
							{
								const validationErrorMessage = Loc.getMessagePlural('PERIOD_TOO_SHORT', this.#signingMinMinutes, {
									'#MIN_PERIOD#': this.#signingMinMinutes,
								});

								UI.Notification.Center.notify({
									content: Text.encode(validationErrorMessage),
									autoHideDelay: 4000,
								});

								return false;
							}

							if (!this.#isDatePassMaxValidation(date))
							{
								const validationErrorMessage = Loc.getMessagePlural('PERIOD_TOO_LONG', this.#signingMaxMonth, {
									'#MONTH#': this.#signingMaxMonth,
								});

								UI.Notification.Center.notify({
									content: Text.encode(validationErrorMessage),
									autoHideDelay: 4000,
								});

								return false;
							}
							this.emit('afterDateModify');

							return true;
						},
						callback_after: (date) => {
							this.setDate(date);
						},
					});
				}}"
			>
				<span class="sign-datetime-limit-selector_field-text"></span>
			</div>
		`;
	}

	getSelectedDate(): Date | null
	{
		return this.#selectedDate;
	}

	setDate(date: Date): void
	{
		this.#selectedDate = date;

		const dateFormatted = DateTimeFormat.format(DateTimeFormat.getFormat('MEDIUM_DATE_FORMAT'), date);
		const timeFormatted = DateTimeFormat.format(DateTimeFormat.getFormat('SHORT_TIME_FORMAT'), date);
		const formattedDate = Loc.getMessage('SIGN_BLANK_DATETIME_SELECTOR_DATE', {
			'#MEDIUM_DATE#': dateFormatted,
			'#SHORT_TIME#': timeFormatted,
		});

		const dateTextNode = this.#documentDateField.firstElementChild;
		dateTextNode.textContent = formattedDate;
	}

	isValid(): boolean
	{
		return !Dom.hasClass(this.#documentDateField, '--invalid');
	}

	#isDatePassMinValidation(date: Date): boolean
	{
		const minValidDateTime = Timezone.UserTime.getDate();
		minValidDateTime.setMinutes(minValidDateTime.getMinutes() + this.#signingMinMinutes);

		return date.getTime() > minValidDateTime.getTime();
	}

	#isDatePassMaxValidation(date: Date): boolean
	{
		const maxValidDateTime = Timezone.UserTime.getDate();
		maxValidDateTime.setMonth(maxValidDateTime.getMonth() + this.#signingMaxMonth);

		return date.getTime() < maxValidDateTime.getTime();
	}

	setValidClass(): void
	{
		Dom.removeClass(this.#documentDateField, '--invalid');
	}

	setInvalidClass(): void
	{
		Dom.addClass(this.#documentDateField, '--invalid');
	}

	async saveSelectedDateForUids(): Promise<void>
	{
		const timestamp = Timezone.UserTime.toUTCTimestamp(this.#selectedDate);

		try
		{
			await Promise.all(this.#documentUids.map((uid) => this.#api.modifyDateSignUntil(uid, timestamp)));
			this.setValidClass();
		}
		catch (error)
		{
			this.setInvalidClass();
			throw error;
		}
	}

	setDocumentUids(uids: Array<string>): void
	{
		this.#documentUids = uids;
	}
}
