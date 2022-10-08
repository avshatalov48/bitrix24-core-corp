import {Vue} from 'ui.vue';
import {Loc} from 'main.core';
import HistoryItemMixin from '../mixins/history-item';
import Author from '../components/author';
import DeliveryServiceInfo from '../components/delivery/delivery-service-info';

export default Vue.extend({
	mixins: [HistoryItemMixin],
	components: {
		'author': Author,
		'delivery-service-info': DeliveryServiceInfo,
	},
	computed: {
		deliveryService()
		{
			if (!this.data.FIELDS.hasOwnProperty('DELIVERY_SERVICE'))
			{
				return null;
			}

			return this.data.FIELDS.DELIVERY_SERVICE;
		},
		messageData()
		{
			if (!this.data.FIELDS.hasOwnProperty('MESSAGE_DATA'))
			{
				return null;
			}

			return this.data.FIELDS.MESSAGE_DATA;
		},
		messageTitle()
		{
			if (!this.messageData)
			{
				return null;
			}

			return this.messageData['TITLE'];
		},
		messageDescription()
		{
			if (!this.messageData)
			{
				return null;
			}

			return this.messageData['DESCRIPTION'];
		},
		messageStatus()
		{
			if (!this.messageData)
			{
				return null;
			}

			return this.messageData['STATUS'];
		},
		messageStatusSemantics()
		{
			if (!this.messageData)
			{
				return null;
			}

			return this.messageData['STATUS_SEMANTIC'];
		},
		messageStatusSemanticsClass()
		{
			return {
				'crm-entity-stream-content-event-process': this.messageStatusSemantics === 'process',
				'crm-entity-stream-content-event-missing': this.messageStatusSemantics === 'error',
				'crm-entity-stream-content-event-done': this.messageStatusSemantics === 'success',
			};
		},
		addressFrom()
		{
			if (!this.data.FIELDS.hasOwnProperty('ADDRESS_FROM_FORMATTED'))
			{
				return null;
			}

			return this.data.FIELDS.ADDRESS_FROM_FORMATTED;
		},
		addressTo()
		{
			if (!this.data.FIELDS.hasOwnProperty('ADDRESS_TO_FORMATTED'))
			{
				return null;
			}

			return this.data.FIELDS.ADDRESS_TO_FORMATTED;
		},
	},
	// language=Vue
	template: `
		<div class="crm-entity-stream-section crm-entity-stream-section-new">
			<div class="crm-entity-stream-section-icon crm-entity-stream-section-icon-new crm-entity-stream-section-icon-taxi"></div>
			
			<div class="crm-entity-stream-section-content">
				<div class="crm-entity-stream-content-event">
					<div class="crm-entity-stream-content-header">
						<span
							v-if="messageTitle"
							class="crm-entity-stream-content-event-title"
						>
							{{messageTitle}}
						</span>
						<span
							v-if="messageStatus && messageStatusSemantics"
							:class="messageStatusSemanticsClass"
						>
							{{messageStatus}}
						</span>
						<span class="crm-entity-stream-content-event-time">
							<span v-html="createdAt">
							</span>
						</span>
					</div>
					<div class="crm-entity-stream-content-detail">
						<div
							v-html="messageDescription"
							class="crm-entity-stream-content-detail-description crm-delivery-taxi-caption"
						>
						</div>
						<div class="crm-entity-stream-content-detail-description">
							<div v-if="addressFrom" class="crm-entity-stream-content-delivery-order-box">
								<div class="crm-entity-stream-content-delivery-order-box-label">
									${Loc.getMessage('TIMELINE_DELIVERY_TAXI_ADDRESS_FROM')}
								</div>
								<span>{{addressFrom}}</span>
							</div>
							<div v-if="addressTo" class="crm-entity-stream-content-delivery-order-box">
								<div class="crm-entity-stream-content-delivery-order-box-label">
									${Loc.getMessage('TIMELINE_DELIVERY_TAXI_ADDRESS_TO')}
								</div>
								<span>{{addressTo}}</span>
							</div>
						</div>
					</div>
					<author v-if="author" :author="author">
					</author>
				</div>
			</div>
		</div>
	`
});
