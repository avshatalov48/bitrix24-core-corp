import { Runtime, Type, Loc } from 'main.core';
import { UI } from 'ui.notification';
import type { FilterFieldMutator } from './filter-field-mutator';

/**
 * if the filter contains any activity fast search fields and, if the "CREATED" field is not already present,
 * add it to the filter.
 */
export class ActivityFastSearchMutator implements FilterFieldMutator
{
	#notifyFn = Runtime.throttle(() => {
		UI.Notification.Center.notify({
			content: Loc.getMessage('CRM_ACTIVITY_FASTSEARCH_CREATED_ADDED'),
			autoHideDelay: 5000,
		});
	}, 4000);

	mutate(fields: {[k: string]: any}, oldFields: {[k: string]: any}): [{[k: string]: any}, boolean]
	{
		const isOldFilterHasCreatedField = Boolean(oldFields.ACTIVITY_FASTSEARCH_CREATED);
		let isFilterHasActivityFields = false;

		for (const fieldName of Object.keys(fields))
		{
			if (!Object.prototype.hasOwnProperty.call(fields, fieldName))
			{
				continue;
			}

			if (this.#checkActivityFields(fields, fieldName))
			{
				isFilterHasActivityFields = true;
				break;
			}
		}

		if (
			isFilterHasActivityFields
			&& !fields.ACTIVITY_FASTSEARCH_CREATED
		)
		{
			if (isOldFilterHasCreatedField)
			{
				this.#notifyFn();
			}

			return [{
				...fields,
				ACTIVITY_FASTSEARCH_CREATED: '365',
			}, true];
		}

		return [fields, false];
	}

	#checkActivityFields(fields: object, fieldName: string): boolean {
		if (fieldName.indexOf('ACTIVITY_FASTSEARCH_') !== 0)
		{
			return false;
		}

		if (!fields[fieldName])
		{
			return false;
		}

		if (fields[fieldName] === 'NONE')
		{
			return false;
		}

		return !(Type.isArray(fields[fieldName]) && fields[fieldName].length === 0);
	}
}
