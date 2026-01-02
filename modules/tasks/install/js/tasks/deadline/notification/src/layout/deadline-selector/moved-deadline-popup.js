import { Loc } from 'main.core';
import { Popup, PopupOptions } from 'main.popup';

export class MovedDeadlinePopup extends Popup
{
	constructor(options: PopupOptions)
	{
		const content = `
			<div class="tasks-deadline-notification-form__content_deadline-selector_moved-toolbar">
				${Loc.getMessage('TASKS_DEADLINE_NOTIFICATION_MOVED_DEADLINE_TOOLTIP')}
			</div>
		`;

		const popupOptions = {
			id: 'deadline-notification-moved-deadline-popup',
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
