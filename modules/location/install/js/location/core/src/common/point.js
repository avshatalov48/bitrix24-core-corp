/**
 * Base class for the working with latitude and longitude
 */
export default class Point
{
	/** {String} */
	#latitude;
	/** {String} */
	#longitude;

	constructor(latitude: string, longitude: string)
	{
		this.#latitude = latitude;
		this.#longitude = longitude;
	}

	get latitude(): string
	{
		return this.#latitude;
	}

	get longitude(): string
	{
		return this.#longitude;
	}

	toArray()
	{
		return [this.latitude, this.longitude];
	}
}