import { Runtime } from 'main.core';

class Limit
{
	show(featureId: string = 'booking'): Promise
	{
		return new Promise((resolve, reject) => {
			Runtime.loadExtension('ui.info-helper')
				.then(({ FeaturePromotersRegistry }) => {
					FeaturePromotersRegistry.getPromoter({ featureId }).show();

					resolve();
				})
				.catch((error) => {
					reject(error);
				})
			;
		});
	}
}

export const limit = new Limit();
