import { Loc } from 'main.core';
import { Popup, PopupOptions } from 'main.popup';

export class OverdueDeadlinePopup extends Popup
{
	constructor(options: PopupOptions)
	{
		const content = `
			<div class="tasks-deadline-notification-form__content_deadline-selector_overdue-toolbar">
				${Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_OVERDUE_DEADLINE_TOOLTIP')}
			</div>
		`;

		const popupOptions = {
			id: 'deadline-notification-overdue-deadline-popup',
			offsetLeft: 30,
			offsetTop: 5,
			angle: true,
			autoHide: true,
			closeByEsc: false,
			closeIcon: true,
			content,
		};

		super({ ...options, ...popupOptions });
	}
}
