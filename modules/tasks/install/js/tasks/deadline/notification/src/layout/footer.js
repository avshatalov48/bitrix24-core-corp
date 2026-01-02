import { Loc, Tag } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Button, ButtonColor, ButtonSize, ButtonState } from 'ui.buttons';
import { SkipButtonMenu } from './skip-button-menu';
import { sendData } from 'ui.analytics';

type FooterOptions = {
	events: {
		[eventName: string]: (event: BaseEvent) => void,
	},
}

export class Footer extends EventEmitter
{
	#EVENT_NAMESPACE: string = 'BX.Tasks.Deadline.Notification.Layout.Footer';

	#submitButton: Button;
	#cancelButton: Button;
	#skipButton: Button;
	#skipButtonMenu: SkipButtonMenu;

	constructor(options: FooterOptions = {})
	{
		super();

		this.setEventNamespace(this.#EVENT_NAMESPACE);
		this.subscribeFromOptions(options.events);

		this.#init();
	}

	render(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-deadline-notification-form__footer">
				<div class="tasks-deadline-notification-form__footer_buttons-container">
					${this.#submitButton.render()}
					${this.#cancelButton.render()}
				</div>
				${this.#skipButton.render()}
			</div>
		`;
	}

	getSkipPeriod(): string
	{
		return this.#skipButtonMenu.getPeriod();
	}

	updateSkipButtonText(text: string): void
	{
		const defaultText = Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_SKIP_BUTTON');

		const skipButtonText = (text === '') ? defaultText : `${defaultText}, ${text}`;
		this.#skipButton.setText(skipButtonText);
	}

	enableButtons(): void
	{
		this.enableSubmitButton();
		this.enableCancelButton();
		this.enableSkipButton();
	}

	disableButtons(): void
	{
		this.disableSubmitButton();
		this.disableCancelButton();
		this.disableSkipButton();
	}

	enableSubmitButton(): void
	{
		this.#submitButton.setDisabled(false);
		this.#submitButton.setColor(ButtonColor.PRIMARY);
	}

	disableSubmitButton(): void
	{
		this.#submitButton.setDisabled();
		this.#submitButton.setColor();
	}

	enableCancelButton(): void
	{
		this.#cancelButton.setDisabled(false);
	}

	disableCancelButton(): void
	{
		this.#cancelButton.setDisabled();
	}

	enableSkipButton(): void
	{
		this.#skipButton.setDisabled(false);
	}

	disableSkipButton(): void
	{
		this.#skipButton.setDisabled();
	}

	#init(): void
	{
		this.#submitButton = new Button({
			dataset: { id: 'tasks-deadline-notification-submit' },
			text: Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_SAVE_BUTTON'),
			size: ButtonSize.SMALL,
			state: ButtonState.DISABLED,
			noCaps: true,
			round: true,
			onclick: this.#onSubmitButtonClick.bind(this),
		});

		this.#cancelButton = new Button({
			dataset: { id: 'tasks-deadline-notification-cancel' },
			text: Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_CANCEL_BUTTON_MSGVER_1'),
			color: ButtonColor.LINK,
			size: ButtonSize.SMALL,
			state: ButtonState.ACTIVE,
			noCaps: true,
			round: true,
			onclick: this.#onCancelButtonClick.bind(this),
		});

		this.#skipButtonMenu = new SkipButtonMenu({
			events: {
				onMenuItemSelect: this.#onSkipMenuItemSelect.bind(this),
				onMenuItemDeselect: this.#onSkipMenuItemDeselect.bind(this),
			},
		});

		this.#skipButton = new Button({
			dataset: { id: 'tasks-deadline-notification-skip' },
			text: Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_SKIP_BUTTON'),
			className: 'tasks-deadline-notification-form__footer_skip-button',
			color: ButtonColor.LINK,
			noCaps: true,
			dropdown: true,
			menu: this.#skipButtonMenu.getMenu(),
			onclick: this.#onSkipButtonClick.bind(this),
		});

		this.#skipButtonMenu.setButton(this.#skipButton);
	}

	#onSubmitButtonClick(): void
	{
		if (this.#submitButton.isDisabled())
		{
			return;
		}

		this.emit('onSubmitButtonClick');

		sendData({
			tool: 'tasks',
			category: 'task_operations',
			event: 'click_save_button',
			type: 'popup',
			c_section: 'tasks',
			c_sub_section: 'deadline_field',
			c_element: 'save_button',
		});
	}

	#onCancelButtonClick(): void
	{
		if (this.#cancelButton.isDisabled())
		{
			return;
		}

		this.emit('onCancelButtonClick');

		sendData({
			tool: 'tasks',
			category: 'task_operations',
			event: 'click_no_deadline_button',
			type: 'popup',
			c_section: 'tasks',
			c_sub_section: 'deadline_popup',
			c_element: 'no_deadline_button',
		});
	}

	#onSkipButtonClick(): void
	{
		sendData({
			tool: 'tasks',
			category: 'task_operations',
			event: 'click_no_remind_button',
			type: 'popup',
			c_section: 'tasks',
			c_sub_section: 'deadline_popup',
			c_element: 'no_remind_button',
		});
	}

	#onSkipMenuItemSelect(event: BaseEvent): void
	{
		const { buttonMenu, text } = event.getData();

		buttonMenu.close();
		this.updateSkipButtonText(text.toLowerCase());

		this.emit('onSkipMenuItemSelect', event);
	}

	#onSkipMenuItemDeselect(event: BaseEvent): void
	{
		const { buttonMenu } = event.getData();

		buttonMenu.close();
		this.updateSkipButtonText('');

		this.emit('onSkipMenuItemDeselect', event);
	}
}
