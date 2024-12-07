import { ajax as Ajax, Type } from "main.core";

export class Backend
{
	saveVisit(tourId: string): Promise<null>
	{
		return Ajax.runAction('sign.api.tour.saveVisit', { data: { tourId } }).then(({ data }) => data);
	}

	getLastVisitDate(tourId: string): Promise<{ lastVisitDate: ?Date }>
	{
		return Ajax
			.runAction('sign.api.tour.getLastVisitDate', { data: { tourId } })
			.then(({ data }) => {
				if (!Type.isNil(data?.lastVisitDate))
				{
					data.lastVisitDate = new Date(data.lastVisitDate);
				}

				return data;
			});
	}

	isAllToursDisabled(): Promise<boolean>
	{
		return Ajax
			.runAction('sign.api.tour.isAllToursDisabled', {})
			.then(({ data }) => data)
	}
}