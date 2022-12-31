/**
 * @module layout/ui/map-opener/geo-point
 */
jn.define('layout/ui/map-opener/geo-point', (require, exports, module) => {

	class GeoPoint
	{
		constructor({ coords, address })
		{
			this.address = this.prepareAddress(address);
			this.coords = this.prepareCoords(coords);
		}

		prepareCoords(coords)
		{
			if (!coords)
			{
				return null;
			}

			if (Array.isArray(coords))
			{
				coords = {
					lat: coords[0],
					lng: coords[1],
				};
			}

			if (!BX.type.isPlainObject(coords) || !coords.lat || !coords.lng)
			{
				console.warn('GeoPoint: invalid coords', coords);

				return null;
			}

			return coords;
		}

		prepareAddress(address)
		{
			if (!address)
			{
				return null;
			}

			if (!BX.type.isString(address))
			{
				console.warn('GeoPoint: invalid address', address);

				return null;
			}

			return address;
		}

		hasCoords()
		{
			return Boolean(this.coords);
		}

		getCoords()
		{
			return this.coords;
		}

		hasAddress()
		{
			return Boolean(this.address);
		}

		getAddress()
		{
			return this.address;
		}
	}

	module.exports = { GeoPoint };

});
