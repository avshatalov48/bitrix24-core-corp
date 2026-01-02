/**
 * @module calendar/booking/model
 */
jn.define('calendar/booking/model', (require, exports, module) => {
	const { Type } = require('type');

	/**
	 * @class BookingModel
	 */
	class BookingModel
	{
		constructor(props)
		{
			this.setFields(props);
		}

		setFields(props)
		{
			this.id = BX.prop.getNumber(props, 'id', 0);
			this.resources = this.#prepareResources(props?.resources);
			this.services = this.#prepareServices(props?.services);
			this.client = this.#prepareClient(BX.prop.getObject(props, 'client', {}));
			this.note = BX.prop.getString(props, 'note', '');
		}

		getId()
		{
			return this.id;
		}

		getResources()
		{
			return this.resources;
		}

		getServices()
		{
			return this.services;
		}

		getClient()
		{
			return this.client;
		}

		getNote()
		{
			return this.note;
		}

		getResourceById(resourceId)
		{
			const id = Number(resourceId);

			return this.resources.find((item) => item.id === id);
		}

		getServiceById(serviceId)
		{
			const id = Number(serviceId);

			return this.services.find((item) => item.id === id);
		}

		canReadResource(resourceId)
		{
			const resource = this.getResourceById(resourceId);

			return Boolean(resource?.permissions?.read);
		}

		canReadService(serviceId)
		{
			const service = this.getServiceById(serviceId);

			return Boolean(service?.permissions?.read);
		}

		canReadClient()
		{
			return Boolean(this.client?.permissions?.read);
		}

		hasClient()
		{
			return Type.isObject(this.client) && this.client?.id > 0;
		}

		getClientPhones()
		{
			return Type.isArrayFilled(this.client?.phones) ? this.client.phones : null;
		}

		#prepareResources(list)
		{
			if (!Type.isArrayFilled(list))
			{
				return [];
			}

			return list.map((item) => ({
				id: BX.prop.getNumber(item, 'id', 0),
				type: BX.prop.getString(item, 'type', ''),
				name: BX.prop.getString(item, 'name', ''),
				permissions: BX.prop.getObject(item, 'permissions', {}),
			}));
		}

		#prepareServices(list)
		{
			if (!Type.isArrayFilled(list))
			{
				return [];
			}

			return list.map((item) => ({
				id: BX.prop.getNumber(item, 'id', 0),
				name: BX.prop.getString(item, 'name', ''),
				permissions: BX.prop.getObject(item, 'permissions', {}),
			}));
		}

		#prepareClient(raw)
		{
			if (!Type.isObject(raw))
			{
				return {};
			}

			return {
				id: BX.prop.getNumber(raw, 'id', 0),
				name: BX.prop.getString(raw, 'name', ''),
				phones: Array.isArray(raw.phones) ? raw.phones : [],
				image: BX.prop.getString(raw, 'image', ''),
				type: BX.prop.getString(raw, 'type', ''),
				permissions: BX.prop.getObject(raw, 'permissions', {}),
			};
		}
	}

	module.exports = { BookingModel };
});
