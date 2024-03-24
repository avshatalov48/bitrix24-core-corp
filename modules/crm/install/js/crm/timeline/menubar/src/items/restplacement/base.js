import Item from '../../item';
import { TourManager } from 'crm.tour-manager';
import { Tour } from './tour.js';

export default class Base extends Item
{
	showTour(): void
	{
		const tour = new Tour({
			id: this.getSetting('id'),
			title: this.getSetting('newUserNotificationTitle'),
			text: this.getSetting('newUserNotificationText'),
			isCanShowTour: (this.getSetting('isCanShowTour') && !BX.Crm.EntityEditor.getDefault().isNew()),
			appContext: {
				applicationId: this.getSetting('appId', ''),
				placementOptions: {
					entityTypeId: this.getEntityTypeId(),
					entityId: this.getEntityId(),
				},
			},
		});

		TourManager.getInstance().registerWithLaunch(tour);
	}
}
