import { EventEmitter } from 'main.core.events';
import type { BaseEvent } from 'main.core.events';

import {
	DateFilterField,
	DashboardDateFilterField,
	KeyInfoField,
	UserNotificationField,
	DashboardOwnerField,
	DashboardParamsField,
	DeleteSupersetField,
	ClearCacheField,
} from './entities/index';

export class FieldFactory
{
	constructor(entityEditorControlFactory: string = 'BX.UI.EntityEditorControlFactory')
	{
		EventEmitter.subscribe(`${entityEditorControlFactory}:onInitialize`, (event: BaseEvent) => {
			const [, eventArgs] = event.getCompatData();
			eventArgs.methods.dashboardSettings = this.factory.bind(this);
		});
	}

	factory(type, controlId, settings): ?BX.UI.EntityEditorField
	{
		switch (type)
		{
			case 'timePeriod':
				return DateFilterField.create(controlId, settings);
			case 'dashboardTimePeriod':
				return DashboardDateFilterField.create(controlId, settings);
			case 'keyInfo':
				return KeyInfoField.create(controlId, settings);
			case 'userNotificationSelector':
				return UserNotificationField.create(controlId, settings);
			case 'ownerSelector':
				return DashboardOwnerField.create(controlId, settings);
			case 'dashboardParametersSelector':
				return DashboardParamsField.create(controlId, settings);
			case 'deleteSuperset':
				return DeleteSupersetField.create(controlId, settings);
			case 'clearCache':
				return ClearCacheField.create(controlId, settings);
			default:
				return null;
		}
	}
}
