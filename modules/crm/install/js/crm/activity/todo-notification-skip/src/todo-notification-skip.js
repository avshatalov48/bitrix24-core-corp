import {BaseEvent, EventEmitter} from "main.core.events";
import {ajax as Ajax, Loc, Type} from "main.core";
import {UI} from "ui.notification";

declare type TodoNotificationSkipParams = {
	entityTypeId: number,
	onSkippedPeriodChange: ?function,
};

export class TodoNotificationSkip
{
	#entityTypeId: number = null;
	#onSkippedPeriodChange: ?function = null;

	constructor(params: TodoNotificationSkipParams)
	{
		this.#entityTypeId = params.entityTypeId;
		this.#onSkippedPeriodChange = params.onSkippedPeriodChange;

		this.#bindEvents();
	}

	#bindEvents(): void
	{
		EventEmitter.subscribe('onLocalStorageSet', this.#onExternalEvent.bind(this));
		EventEmitter.subscribe('BX.Crm.Activity.TodoNotification:SetSkipPeriod', this.#onSetSkipPeriod.bind(this));
	}

	#onExternalEvent(event: BaseEvent): void
	{
		const [data] = event.getData();

		if (data.key === 'BX.Crm.onCrmEntityTodoNotificationSkip')
		{
			const eventParams = data.value;
			if (eventParams.entityTypeId === this.#entityTypeId)
			{
				this.#onSkippedPeriodChangeCallback(eventParams.period);
			}
		}
	}

	#onSetSkipPeriod(event: BaseEvent): void
	{
		this.#onSkippedPeriodChangeCallback(event.getData().period);
	}

	#onSkippedPeriodChangeCallback(period: string)
	{
		if (Type.isFunction(this.#onSkippedPeriodChange))
		{
			this.#onSkippedPeriodChange(period);
		}
	}

	saveSkippedPeriod(skippedPeriod: string): Promise
	{
		BX.localStorage.set(
			'BX.Crm.onCrmEntityTodoNotificationSkip',
			{
				entityTypeId: this.#entityTypeId,
				period: skippedPeriod
			},
			5
		);

		EventEmitter.emit('BX.Crm.Activity.TodoNotification:SetSkipPeriod', {
			entityTypeId: this.#entityTypeId,
			period: skippedPeriod,
		});

		return Ajax.runAction('crm.activity.todo.skipEntityDetailsNotification', {
			data: {
				entityTypeId: this.#entityTypeId,
				period: skippedPeriod,
			}
		}).then(() => {
			return skippedPeriod;
		}).catch((response) => {
			UI.Notification.Center.notify({
				content: response.errors.map(item => item.message).join(', '),
				autoHideDelay: 5000,
			});
		});
	}

	showCancelPeriodNotification()
	{
		const self = this;
		UI.Notification.Center.notify({
			content: Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_CANCELED_TEXT'),
			autoHideDelay: 3000,
			actions: [{
				title: Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_CANCELED_BUTTON'),
				events: {
					click: function(event, balloon, action) {
						balloon.close();
						self.saveSkippedPeriod('');
					}
				}
			}]
		});
	}
}
