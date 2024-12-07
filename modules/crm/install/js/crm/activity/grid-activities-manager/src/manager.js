import { ajax, Dom, Reflection, Runtime } from 'main.core';

const Util = Reflection.namespace('BX.util');

interface Activity {
	ID: ?number;
	typeID: number;
	providerID: string;
	associatedEntityID: ?number;
	calendarEventId: ?number;
	customViewLink: ?string;
}

export class Manager
{
	static #activityTypes = {
		task: 3,
		provider: 6,
	};

	activityAddingPopup = {};

	async showAddPopup(
		bindElement: HTMLElement,
		gridManagerId: string,
		entityTypeId: number,
		entityId: number,
		currentUser: Object,
		settings: ?any,
	)
	{
		Dom.addClass(bindElement, '--active');
		const key = `${entityTypeId}_${entityId}`;

		const exports = await Runtime.loadExtension('crm.activity.adding-popup');

		if (!exports || !exports.AddingPopup)
		{
			return;
		}

		if (!Object.hasOwn(this.activityAddingPopup, key))
		{
			this.activityAddingPopup[key] = new exports.AddingPopup(
				entityTypeId,
				entityId,
				currentUser,
				settings,
				{
					events: {
						onClose() {
							BX.Dom.removeClass(bindElement, '--active');
						},
						onSave() {
							const grid = BX.Main.gridManager.getById(gridManagerId);
							if (grid)
							{
								grid.instance.reload();
							}
						},
					},
				},
			);
		}

		this.activityAddingPopup[key].show();
	}

	async viewActivity(gridId: string, activityId: number, allowEdit: boolean)
	{
		try
		{
			const activity = await this.#fetchActivity(activityId);

			if (!activity)
			{
				return;
			}

			if (activity.customViewLink)
			{
				BX.Crm.Page.open(activity.customViewLink);
			}
			else
			{
				this.openActivityDialog(activity, allowEdit);
			}
		}
		catch (err)
		{
			console.error(err);
		}
	}

	openActivityDialog(activity: Activity, allowEdit: boolean)
	{
		const typeId = parseInt(activity.typeID, 10);

		if (typeId === Manager.#activityTypes.provider && BX.CrmActivityProvider)
		{
			this.#showProviderSix(activity, allowEdit);
		}
		else if (typeId === Manager.#activityTypes.task)
		{
			this.#showTask(activity, allowEdit);
		}
	}

	#showProviderSix(activity: Activity, allowEdit: boolean)
	{
		const providerID = activity.providerID;

		switch (providerID)
		{
			case 'CRM_TASKS_TASK':
			case 'CRM_TASKS_TASK_COMMENT':
				this.#showTask(activity, allowEdit);
				break;
			case 'REST_APP':
			case 'CONFIGURABLE_REST_APP':
				this.#showRestApp(activity);

				break;
			case 'CRM_CALENDAR_SHARING':
				this.#showCalendarSharing(activity);
				break;
			default:
		}
	}

	#showRestApp(activity: Activity)
	{
		BX.rest.AppLayout.openApplication(
			activity.associatedEntityID || 0,
			{
				action: 'view_activity',
				activity_id: activity.ID || 0,
			},
		);
	}

	#showCalendarSharing(activity: Activity)
	{
		const calendarEventId = parseInt(activity.calendarEventId, 10);
		if (!calendarEventId)
		{
			return;
		}

		if ((window.top.BX || window.BX).Calendar.SliderLoader)
		{
			const sliderId = `crm-calendar-slider-${calendarEventId}-${Math.floor(Math.random() * 1000)}`;

			new (window.top.BX || window.BX).Calendar.SliderLoader(calendarEventId, {
				sliderId,
			}).show();
		}
	}

	#showTask(activity: Activity, allowEdit: boolean)
	{
		const taskId = parseInt(activity.associatedEntityID, 10);
		if (taskId <= 0)
		{
			return;
		}

		let taskOpenUrl = allowEdit
			? '/company/personal/user/#user_id#/tasks/task/edit/#task_id#/'
			: '/company/personal/user/#user_id#/tasks/task/view/#task_id#/'
		;

		taskOpenUrl = taskOpenUrl.replace('#user_id#', this.#getCurrentUserId());
		taskOpenUrl = taskOpenUrl.replace('#task_id#', taskId);

		if (BX.SidePanel)
		{
			BX.SidePanel.Instance.open(taskOpenUrl);
		}
		else
		{
			window.top.location.href = taskOpenUrl;
		}
	}

	#getCurrentUserId(): string
	{
		return BX.message('USER_ID');
	}

	async #fetchActivity(activityId: number): Promise<Activity>
	{
		return new Promise((resolve, reject) => {
			const serviceUrl = Util.add_url_param(
				'/bitrix/components/bitrix/crm.activity.editor/ajax.php',
				{
					id: activityId,
					action: 'get_activity',
					sessid: BX.bitrix_sessid(),
				},
			);

			ajax({
				url: serviceUrl,
				method: 'POST',
				dataType: 'json',
				data: {
					ACTION: 'GET_ACTIVITY',
					ID: activityId,
				},
				onsuccess: (data): void => {
					if (data.ERROR)
					{
						reject(data.ERROR);
					}
					else
					{
						resolve(data.ACTIVITY || null);
					}
				},
				onfailure: (errorData) => {
					reject(errorData);
				},
			});
		});
	}
}
