import { Event, Loc, Tag, Type, ajax as Ajax, Dom } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { DateTimeFormat } from 'main.date';
import { Popup } from 'main.popup';
import { Checker } from 'ui.form-elements.view';
import { DateRounder } from 'tasks.date-rounder';
import { DeadlineSelector } from './layout/deadline-selector/deadline-selector';
import { Footer } from './layout/footer';
import { DefaultDeadlineSelector } from './layout/default-deadline-selector';
import { sendData } from 'ui.analytics';

import './style.css';

type NotificationOptions = {
	targetForm: HTMLFormElement;
	targetInput: HTMLInputElement;
	targetButton: ?HTMLButtonElement;
	matchWorkTime: boolean;
}

export class Notification extends EventEmitter
{
	#EVENT_NAMESPACE: string = 'BX.Tasks.Deadline.Notification';

	#popup: Popup;

	#targetForm: HTMLFormElement;
	#targetInput: HTMLInputElement;
	#targetButton: ?HTMLButtonElement;
	#matchWorkTime: boolean;

	#layout: {
		errorContainer: HTMLDivElement,
		deadlineSelector: DeadlineSelector,
		defaultDeadlineChecker: Checker,
		defaultDeadlineSelector: DefaultDeadlineSelector,
		footer: Footer,
	};

	constructor(options: NotificationOptions)
	{
		super();

		this.setEventNamespace(this.#EVENT_NAMESPACE);

		this.#init(options);
	}

	show(): void
	{
		this.#popup.show();

		sendData({
			tool: 'tasks',
			category: 'task_operations',
			event: 'deadline_popup_show',
			type: 'popup',
			c_section: 'tasks',
		});
	}

	close(): void
	{
		this.#popup.close();
	}

	#getPopupContent(): HTMLElement
	{
		const form = Tag.render`
			<form class="tasks-deadline__notification-form">
				${this.#getTitleContainer()}
				${this.#getErrorsContainer()}
				${this.#getDeadlineContainer()}
				${this.#layout.footer.render()}
			</form>
		`;

		Event.bind(form, 'submit', (e): void => {
			e.preventDefault();
		});

		return form;
	}

	#getTitleContainer(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-deadline-notification-form__title">
				<div class="tasks-deadline-notification-form__title_icon"></div>
				<div class="tasks-deadline-notification-form__title_text">
					${Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_TITLE')}
				</div>
			</div>
			<div class="tasks-deadline-notification-form__subtitle">
				${Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_DESCRIPTION')}
			</div>
		`;
	}

	#getErrorsContainer(): HTMLDivElement
	{
		this.#layout.errorContainer = Tag.render`
			<div class="tasks-deadline-notification-form__errors" hidden>
				<div class="ui-alert ui-alert-danger">
					<span class="ui-alert-message">${Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_ERROR_MESSAGE')}</span>
				</div>
			</div>
		`;

		return this.#layout.errorContainer;
	}

	#getDeadlineContainer(): HTMLElement
	{
		this.#layout.defaultDeadlineChecker = new Checker({
			id: 'tasks-deadline-notification-default-deadline-checker',
			inputName: 'defaultDeadlineChecker',
			checked: false,
			title: this.#getDefaultDeadlineCheckerTitle(),
			hideSeparator: true,
			size: 'extra-small',
		});

		const defaultDeadlineContainer = this.#layout.defaultDeadlineSelector.render();
		EventEmitter.subscribe(
			this.#layout.defaultDeadlineChecker,
			'change',
			this.#onDefaultDeadlineCheckerChange.bind(this, defaultDeadlineContainer),
		);

		return Tag.render`
			<div class="tasks-deadline-notification-form__content">
				<div class="tasks-deadline-notification-form__content_title">
					${Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_DEADLINE_TITLE')}
				</div>
				${this.#layout.deadlineSelector.render()}
				<div class="tasks-deadline-notification-form__content_default-deadline-checker">
					${this.#layout.defaultDeadlineChecker.render()}
				</div>
				${defaultDeadlineContainer}
			</div>
		`;
	}

	#getDefaultDeadlineCheckerTitle(): string
	{
		return `
			<div>
				${Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_DEFAULT_DEADLINE_CHECKER')}
			</div>
			<span
				data-id="defaultDeadlineHint"
				class="ui-hint"
				data-hint="${Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_DEFAULT_DEADLINE_HINT')}"
				data-hint-no-icon
			>
				<span class="ui-hint-icon">
				</span>
			</span>
		`;
	}

	#saveTargetButtonStateInForm(): void
	{
		if (!this.#targetButton || !this.#targetButton.name || !this.#targetButton.value)
		{
			return;
		}

		const targetButtonInput = Tag.render`
			<input name="${this.#targetButton.name}" value="${this.#targetButton.value}" hidden>
		`;

		Dom.append(targetButtonInput, this.#targetForm);
	}

	#onSubmitButtonClick(): void
	{
		this.#layout.footer.disableButtons();

		const defaultDeadline = this.#layout.defaultDeadlineSelector.getDeadlineInSeconds();
		const isExactDeadlineTime = this.#layout.defaultDeadlineSelector.isExactDeadlineTime();
		const skipPeriod = this.#layout.footer.getSkipPeriod();

		this.#setDefaultDeadline(defaultDeadline, isExactDeadlineTime)
			.then((): Promise => {
				return this.#setSkipNotificationPeriod(skipPeriod);
			})
			.then((): void => {
				this.#setTargetInputValue();
				this.#submit();
			})
			.catch((): void => this.#onPromiseError())
		;
	}

	#onCancelButtonClick(): void
	{
		this.#layout.footer.disableButtons();
		this.#submit();
	}

	#onSkipMenuItemSelect(): void
	{
		if (this.#layout.deadlineSelector.getInputValue())
		{
			return;
		}

		this.#layout.footer.disableButtons();
		const skipPeriod = this.#layout.footer.getSkipPeriod();

		this.#setSkipNotificationPeriod(skipPeriod)
			.then((): void => {
				this.#submit();
			})
			.catch((): void => this.#onPromiseError())
		;
	}

	#onDefaultDeadlineCheckerChange(defaultDeadlineContainer: HTMLElement, event: BaseEvent): void
	{
		const checked = event.getData();

		if (!checked)
		{
			Dom.hide(defaultDeadlineContainer);

			return;
		}

		Dom.show(defaultDeadlineContainer);

		if (!this.#layout.deadlineSelector.getInputValue())
		{
			this.#layout.defaultDeadlineSelector.clear();
			this.#layout.defaultDeadlineSelector.focus();

			return;
		}

		const now = this.#layout.deadlineSelector.getToday();
		const selectedDeadline = this.#layout.deadlineSelector.getDeadline();

		const offsetInSeconds = (selectedDeadline - now) / 1000;

		const defaultDeadline = DateRounder.roundToDays(offsetInSeconds);
		if (defaultDeadline <= 0)
		{
			this.#layout.defaultDeadlineSelector.clear();
			this.#layout.defaultDeadlineSelector.focus();

			return;
		}

		this.#layout.defaultDeadlineSelector.select(defaultDeadline);
	}

	#onDeadlineSelect(): void
	{
		const selectedDate = this.#layout.deadlineSelector.getDeadline();
		if (selectedDate === null)
		{
			this.#layout.footer.disableSubmitButton();

			return;
		}

		this.#layout.footer.enableSubmitButton();
	}

	#onPopupFirstShow(event: BaseEvent): void
	{
		BX.UI.Hint.init(event.getTarget().contentContainer);
	}

	#onPromiseError(): void
	{
		this.#layout.footer.enableCancelButton();
		this.#layout.footer.enableSkipButton();

		const selectedDate = this.#layout.deadlineSelector.getDeadline();
		if (!Type.isNull(selectedDate))
		{
			this.#layout.footer.enableSubmitButton();
		}

		Dom.show(this.#layout.errorContainer);
	}

	#setDefaultDeadline(deadline: number, isExactDeadlineTime: boolean): Promise
	{
		if (!this.#layout.defaultDeadlineChecker.isChecked())
		{
			return new Promise((resolve): void => {
				resolve();
			});
		}

		const deadlineData = {
			default: deadline,
			isExactTime: isExactDeadlineTime ? 'Y' : 'N',
		};

		return Ajax.runAction('tasks.deadline.Deadline.setDefault', {
			data: {
				deadlineData,
			},
		});
	}

	#setSkipNotificationPeriod(skipPeriod: string): Promise
	{
		if (!this.#layout.footer.getSkipPeriod())
		{
			return new Promise((resolve): void => {
				resolve();
			});
		}

		const notificationData = {
			skipPeriod,
		};

		return Ajax.runAction('tasks.deadline.Notification.skip', {
			data: {
				notificationData,
			},
		});
	}

	#submit(): void
	{
		this.close();

		this.#saveTargetButtonStateInForm();

		this.emit('onBeforeSubmit');

		this.#targetForm.submit();
	}

	#setTargetInputValue(): void
	{
		const dateFormat = DateTimeFormat.getFormat('FORMAT_DATETIME');

		this.#targetInput.value = this.#layout.deadlineSelector.getFormattedDeadline(dateFormat);
	}

	#init(options: NotificationOptions): void
	{
		this.#targetForm = options.targetForm;
		this.#targetInput = options.targetInput;
		this.#targetButton = options.targetButton;
		this.#matchWorkTime = options.matchWorkTime;

		this.#layout = {};

		this.#layout.deadlineSelector = new DeadlineSelector({
			matchWorkTime: this.#matchWorkTime,
			events: {
				onSelect: this.#onDeadlineSelect.bind(this),
			},
		});

		this.#layout.defaultDeadlineSelector = new DefaultDeadlineSelector();

		this.#layout.footer = new Footer({
			events: {
				onSubmitButtonClick: this.#onSubmitButtonClick.bind(this),
				onCancelButtonClick: this.#onCancelButtonClick.bind(this),
				onSkipMenuItemSelect: this.#onSkipMenuItemSelect.bind(this),
			},
		});

		this.#popup = new Popup({
			id: 'deadline-notification',
			content: this.#getPopupContent(),
			className: 'tasks__deadline-notification-popup',
			overlay: {
				opacity: 40,
			},
			width: 600,
			autoHide: false,
			closeIcon: false,
			events: {
				onFirstShow: this.#onPopupFirstShow.bind(this),
			},
		});
	}
}
