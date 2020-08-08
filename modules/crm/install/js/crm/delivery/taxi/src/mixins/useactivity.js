import UseLocalize from './uselocalize';
import UseExternalLangMessages from './useexternallangmessages';

export default {
	props: {
		self: {
			required: true,
			type: Object
		},
	},
	mixins: [UseLocalize, UseExternalLangMessages],
	computed: {
		data()
		{
			return this.self._data;
		},
		fields()
		{
			return this.data.ASSOCIATED_ENTITY.SETTINGS.FIELDS;
		},
		author()
		{
			return this.data.AUTHOR;
		},
		statusName()
		{
			if (this.fields.STATUS === 'initial' || this.fields.STATUS === 'searching')
			{
				return '';
			}
			else if (this.fields.STATUS === 'on_its_way')
			{
				return this.localize.TIMELINE_DELIVERY_TAXI_DELIVERY_STATUS_ON_ITS_WAY;
			}
			else if (this.fields.STATUS === 'success')
			{
				return this.localize.TIMELINE_DELIVERY_TAXI_DELIVERY_STATUS_SUCCESS;
			}
			else if (this.fields.STATUS === 'unknown')
			{
				return this.localize.TIMELINE_DELIVERY_TAXI_DELIVERY_STATUS_UNKNOWN;
			}
		},
		statusClass()
		{
			let isUnknownStatus = (this.fields.STATUS === 'unknown');

			return {
				'crm-entity-stream-content-event-process': isUnknownStatus,
				'crm-entity-stream-content-event-done': !isUnknownStatus,
			};
		}
	},
};
