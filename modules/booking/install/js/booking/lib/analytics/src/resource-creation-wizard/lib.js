import { Type } from 'main.core';
import { Core } from 'booking.core';
import { Model } from 'booking.const';

export { getCSection } from '../lib';

export function isExistingResource(): boolean
{
	const $store = Core.getStore();
	const resourceId = $store.state[Model.ResourceCreationWizard].resourceId || null;

	return Type.isNumber(resourceId);
}
