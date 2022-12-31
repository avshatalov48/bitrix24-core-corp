/**
 * @module layout/ui/map-opener/services
 */
jn.define('layout/ui/map-opener/services', (require, exports, module) => {

	const { Loc } = require('loc');

	const getServices = () => services;

	const filterServicesForUserRegion = (services) => services.filter((service) => {
		if (!service)
		{
			return false;
		}

		if (Array.isArray(service.regionWhiteList))
		{
			return service.regionWhiteList.includes(Application.getLang());
		}

		if (Array.isArray(service.regionBlackList))
		{
			return !service.regionBlackList.includes(Application.getLang());
		}

		return true;
	});

	const fillLocalizationTitles = (services) => {
		services.forEach((service) => service.title = Loc.getMessage(`UI_MAP_OPENER_${service.id.toUpperCase()}_TITLE`));
	};

	const fillUrlCallback = (services) => {
		const getUrl = (handler, geoPoint) => {
			const handlerSupportsAddress = Boolean(handler.addressParam);
			const geoPointHasAddress = geoPoint.hasAddress();

			if (handlerSupportsAddress && geoPointHasAddress)
			{
				const addressString = handler.addressParam.replace('#address#', geoPoint.getAddress());

				return encodeURI(handler.url + addressString);
			}

			const handlerSupportsCoords = Boolean(handler.coordsParam);
			const hasCoords = geoPoint.hasCoords();

			if (handlerSupportsCoords && hasCoords)
			{
				const { lat, lng } = geoPoint.getCoords();
				const coordsString = handler.coordsParam.replace('#lat#', lat).replace('#lng#', lng);

				return encodeURI(handler.url + coordsString);
			}

			return null;
		};

		services.forEach((handler) => {
			if (!handler.getUrl)
			{
				handler.getUrl = (geoPoint) => getUrl(handler, geoPoint);
			}
		});
	};

	const pathToExtension = currentDomain + '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/map-opener/icons/';

	let services = [
		{
			id: 'yandex',
			regionWhiteList: ['ru', 'kz', 'by'],
			imgUri: `${pathToExtension}yandex.png`,
			getUrl: (geoPoint) => {
				if (!geoPoint.hasCoords() && !geoPoint.hasAddress())
				{
					return null;
				}

				const baseUrl = 'https://yandex.ru/maps/?z=17&';

				if (geoPoint.hasCoords() && geoPoint.hasAddress())
				{
					const { lng, lat } = geoPoint.getCoords();

					return encodeURI(`${baseUrl}ll=${lng},${lat}&text=${geoPoint.getAddress()}`);
				}

				if (geoPoint.hasCoords())
				{
					const { lng, lat } = geoPoint.getCoords();

					return encodeURI(`${baseUrl}pt=${lng},${lat}`);
				}

				return encodeURI(`${baseUrl}text=${geoPoint.getAddress()}`);
			},
		},
		{
			id: 'google',
			imgUri: `${pathToExtension}google.png`,
			getUrl: (geoPoint) => {
				if (!geoPoint.hasCoords() && !geoPoint.hasAddress())
				{
					return null;
				}

				const baseUrl = 'https://www.google.com/maps/search/?api=1&';

				if (geoPoint.hasAddress())
				{
					return encodeURI(`${baseUrl}query=${geoPoint.getAddress()}`);
				}

				const { lng, lat } = geoPoint.getCoords();

				return encodeURI(`${baseUrl}query=${lat},${lng}`);
			},
		},
		Application.getPlatform() === 'ios' && {
			id: 'apple',
			regionBlackList: ['ru', 'kz', 'by'],
			imgUri: `${pathToExtension}apple.png`,
			getUrl: (geoPoint) => {
				if (!geoPoint.hasCoords() && !geoPoint.hasAddress())
				{
					return null;
				}

				const baseUrl = 'https://maps.apple.com/?';

				if (geoPoint.hasCoords() && geoPoint.hasAddress())
				{
					const { lng, lat } = geoPoint.getCoords();

					return encodeURI(`${baseUrl}ll=${lat},${lng}&q=${geoPoint.getAddress()}`);
				}

				if (geoPoint.hasCoords())
				{
					const { lng, lat } = geoPoint.getCoords();

					return encodeURI(`${baseUrl}ll=${lat},${lng}`);
				}

				return encodeURI(`${baseUrl}q=${geoPoint.getAddress()}`);
			},
		},
		{
			id: '2gis',
			regionWhiteList: ['ru', 'kz', 'by'],
			imgUri: `${pathToExtension}2gis.png`,
			getUrl: (geoPoint) => {
				if (!geoPoint.hasCoords() && !geoPoint.hasAddress())
				{
					return null;
				}

				const baseUrl = 'https://2gis.ru/';

				if (geoPoint.hasCoords())
				{
					const { lng, lat } = geoPoint.getCoords();

					return encodeURI(`${baseUrl}geo/${lng},${lat}`);
				}

				return encodeURI(`${baseUrl}search/${geoPoint.getAddress()}`);
			},
		},
		Application.getPlatform() === 'android' && {
			id: 'geo',
			regionBlackList: ['ru', 'kz', 'by'],
			imgUri: `${pathToExtension}geo.png`,
			getUrl: (geoPoint) => {
				if (!geoPoint.hasCoords())
				{
					return null;
				}

				const { lat, lng } = geoPoint.getCoords();
				let url = `geo:${lat},${lng}`;

				if (geoPoint.hasAddress())
				{
					url = `${url}?q=${geoPoint.getAddress()}`;
				}

				return encodeURI(url);
			},
		},
	];

	services = services.filter((service) => Boolean(service));
	services = filterServicesForUserRegion(services);
	fillLocalizationTitles(services);
	fillUrlCallback(services);

	module.exports = { getServices };

});
