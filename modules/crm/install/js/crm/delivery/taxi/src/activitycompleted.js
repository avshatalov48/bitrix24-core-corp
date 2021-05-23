import {Vue} from 'ui.vue';
import UseActivity from './mixins/useactivity';
import UseLocalize from './mixins/uselocalize';
import AuthorComponent from './components/author';
import LogoComponent from './components/logo';
import InfoComponent from './components/info';
import RouteComponent from './components/route';
import PerformerComponent from './components/performer';
import CarComponent from './components/car';

export default Vue.extend({
	components: {
		'author': AuthorComponent,
		'logo': LogoComponent,
		'info': InfoComponent,
		'route': RouteComponent,
		'performer': PerformerComponent,
		'car': CarComponent,
	},
	mixins: [UseActivity, UseLocalize],
	computed: {
		isExpectedPriceReceived()
		{
			return this.fields.hasOwnProperty('EXPECTED_PRICE_DELIVERY');
		},
	},
	template: `
		<div class="crm-entity-stream-section crm-entity-stream-section-new">
			<div class="crm-entity-stream-section-icon crm-entity-stream-section-icon-new crm-entity-stream-section-icon-taxi"></div>
			<div class="crm-entity-stream-section-content">
				<div class="crm-entity-stream-content-event crm-entity-stream-content-event--delivery">
					<div class="crm-entity-stream-content-header">
						<span class="crm-entity-stream-content-event-title">
							{{localize.TIMELINE_DELIVERY_TAXI_SERVICE}}
						</span>
						<span v-if="statusName" :class="statusClass">
							{{statusName}}
						</span>
						<span class="crm-entity-stream-content-event-time">{{this.createdAt}}</span>
					</div>
					<div class="crm-entity-stream-content-detail crm-entity-stream-content-delivery">
						<div class="crm-entity-stream-content-delivery-row crm-entity-stream-content-delivery-row--flex">
							<div class="crm-entity-stream-content-delivery-title">
								<div class="crm-entity-stream-content-delivery-icon crm-entity-stream-content-delivery-icon--car"></div>
								<div class="crm-entity-stream-content-delivery-title-contnet">
									<logo v-if="fields.DELIVERY_SYSTEM_LOGO" :logo="fields.DELIVERY_SYSTEM_LOGO"></logo>
									<info
										v-if="fields.DELIVERY_SYSTEM_NAME || fields.DELIVERY_METHOD"
										:name="fields.DELIVERY_SYSTEM_NAME"
										:method="fields.DELIVERY_METHOD"
									></info>
								</div>
							</div>
						</div>
						<div class="crm-entity-stream-content-delivery-row">
							<table class="crm-entity-stream-content-delivery-order">
								<tr v-if="fields.ADDRESS_FROM && fields.ADDRESS_TO">
									<td colspan="2">
										<route
											:from="fields.ADDRESS_FROM"
											:to="fields.ADDRESS_TO"
										></route>
									</td>
								</tr>
								<tr>
									<td>
										<div class="crm-entity-stream-content-delivery-order-item">
											<div class="crm-entity-stream-content-delivery-order-label">
												{{localize.TIMELINE_DELIVERY_TAXI_CLIENT_DELIVERY_PRICE}}
											</div>
											<div class="crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm">
												<span v-html="fields.DELIVERY_PRICE"></span>
											</div>
										</div>
									</td>
									<td>
										<div class="crm-entity-stream-content-delivery-order-item">
											<div class="crm-entity-stream-content-delivery-order-label">
												{{localize.TIMELINE_DELIVERY_TAXI_EXPECTED_DELIVERY_PRICE}}
											</div>
											<div class="crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm">
												<span v-if="isExpectedPriceReceived">
													<span v-html="fields.EXPECTED_PRICE_DELIVERY"></span></span>
												<span v-else>
													{{localize.TIMELINE_DELIVERY_TAXI_EXPECTED_PRICE_NOT_RECEIVED}}
												</span>
											</div>
										</div>
									</td>
								</tr>
								<tr v-if="this.fields.PERFORMER_NAME">
									<td colspan="2">
										<performer
											:name="fields.PERFORMER_NAME"
											:phone="fields.PERFORMER_PHONE"
											:phoneExt="fields.PERFORMER_PHONE_EXT"
										></performer>
									</td>
								</tr>
								<tr v-if="fields.PERFORMER_CAR">
									<td colspan="2">
										<car :car="fields.PERFORMER_CAR"></car>
									</td>
								</tr>
							</table>
						</div>
					</div>
					<author v-if="author" :author="author"></author>
				</div>
			</div>
		</div>
	`
});
