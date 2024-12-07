export type BasePayloadMarkers = {
	[key: string]: string
}

export class Base
{
	payload: any = null;
	markers: {[key: string]: string} = {};

	constructor(payload: any)
	{
		this.payload = payload;
	}

	setMarkers(markers: BasePayloadMarkers): this
	{
		this.markers = markers;

		return this;
	}

	getMarkers(): BasePayloadMarkers
	{
		return this.markers;
	}

	/**
	 * Returns data in pretty style.
	 *
	 * @return {*}
	 */
	getPrettifiedData(): any
	{
		return this.payload;
	}

	/**
	 * Returns data in raw style.
	 *
	 * @return {*}
	 */
	getRawData(): any
	{
		return this.payload;
	}
}
