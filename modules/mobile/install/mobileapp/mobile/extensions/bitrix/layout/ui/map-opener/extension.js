/**
 * @module layout/ui/map-opener
 */
jn.define('layout/ui/map-opener', (require, exports, module) => {

	const { GeoPoint } = require('layout/ui/map-opener/geo-point');
	const { getServices } = require('layout/ui/map-opener/services');

	/**
	 * @class MapOpener
	 */
	class MapOpener
	{
		constructor(parentWidget = null)
		{
			this.parentWidget = parentWidget || PageManager;

			this.services = getServices();
		}

		/**
		 * @public
		 * @param {?{lat: String, lng: String}} coords
		 * @param {?String} address
		 * @returns {Promise<never>|void}
		 */
		open({ address = null, coords = null })
		{
			const geoPoint = new GeoPoint({ address, coords });

			if (geoPoint.hasAddress() || geoPoint.hasCoords())
			{
				return this.openMenu(geoPoint);
			}

			console.warn('AddressOpener: no address or coordinates');

			return Promise.reject();
		}

		/**
		 * @public
		 * @param {String} address
		 * @returns {Promise | Promise<unknown>}
		 */
		openAddress(address)
		{
			const geoPoint = new GeoPoint({ address });
			if (!geoPoint.hasAddress())
			{
				console.warn('AddressOpener: wrong address.', address);

				return null;
			}

			return this.openMenu(geoPoint);
		}

		/**
		 * @public
		 * @param {{lat: String, lng: String}} coords
		 * @returns {Promise | Promise<unknown>}
		 */
		openCoords(coords)
		{
			const geoPoint = new GeoPoint({ coords });
			if (!geoPoint.hasCoords())
			{
				console.warn('AddressOpener: wrong coords.', coords);

				return null;
			}

			return this.openMenu(geoPoint);
		}

		openMenu(geoPoint)
		{
			return this.getMenu(geoPoint).show(this.parentWidget);
		}

		getMenu(geoPoint)
		{
			return new ContextMenu({
				actions: this.getActions(geoPoint),
				params: {
					title: BX.message('UI_MAP_OPENER_BACKDROP_TITLE'),
					showCancelButton: true,
					isCustomIconColor: true,
				},
			});
		}

		getActions(geoPoint)
		{
			return (
				this.services
					.map((service) => {
						const url = service.getUrl(geoPoint);
						if (url === null)
						{
							return null;
						}

						return {
							id: service.id,
							title: service.title,
							data: {
								imgUri: service.imgUri,
								svgIcon: service.svgIcon,
							},
							onClickCallback: () => {
								PageManager.openPage({ url });

								return Promise.resolve();
							},
						};
					})
					.filter((service) => service)
			);
		}
	}

	module.exports = { MapOpener };

});
