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
	data()
	{
		return {
			isMakingRequest: false,
			isCancelling: false,
		};
	},
	methods: {
		completeActivity()
		{
			if(this.self.canComplete())
			{
				this.self.setAsDone(!this.self.isDone());
			}
		},
		makeRequest()
		{
			this.isMakingRequest = true;

			BX.ajax.runAction(
				'sale.taxidelivery.sendrequest',
				{
					analyticsLabel: 'saleDeliveryTaxiCall',
					data: {
						shipmentId: this.fields.SHIPMENT_ID
					}
				}
			).then((result) => {
			}).catch((result) => {
				this.isMakingRequest = false;
				this.showError(result.errors.map((item) => item.message).join());
			});
		},
		cancelRequest()
		{
			if (this.isCancelling)
			{
				return;
			}

			this.isCancelling = true;

			BX.ajax.runAction(
				'sale.taxidelivery.cancelrequest',
				{
					data: {
						shipmentId: this.fields.SHIPMENT_ID,
						requestId: this.fields.REQUEST_ID,
					}
				})
				.then((result) => {
					this.isCancelling = false;
				}).catch((result) => {
				this.isCancelling = false;
				this.showError(result.errors.map((item) => item.message).join());
			});
		},
		checkRequestStatus()
		{
			BX.ajax.runAction('sale.taxidelivery.checkrequeststatus');
		},
		startCheckingRequestStatus()
		{
			clearTimeout(this._checkRequestStatusTimeoutId);
			this._checkRequestStatusTimeoutId = setInterval(
				() => this.checkRequestStatus(), 10 * 1000
			);
		},
		stopCheckingRequestStatus()
		{
			clearTimeout(this._checkRequestStatusTimeoutId);
		},
		showError(message)
		{
			BX.loadExt('ui.notification').then(() => { BX.UI.Notification.Center.notify({content: message}); });
		},
		showContextMenu(event)
		{
			let popup = BX.PopupMenu.create(
				'taxi_activity_context_menu_' + this.self.getId(),
				event.target,
				[
					{
						id: 'delete',
						text: this.getLangMessage('menuDelete'),
						onclick: () => {
							popup.close();

							let deletionDlgId = 'entity_timeline_deletion_' + this.self.getId() + '_confirm';
							let dlg = BX.Crm.ConfirmationDialog.get(deletionDlgId);

							if (!dlg)
							{
								dlg = BX.Crm.ConfirmationDialog.create(
									deletionDlgId,
									{
										title: this.getLangMessage('removeConfirmTitle'),
										content: this.getLangMessage('deliveryRemove')
									}
								);
							}

							dlg.open().then(
								(result) => {
									if (result.cancel)
									{
										return;
									}

									this.self.remove();
								},
								(result) => {},
							);
						}
					}
				],
				{
					autoHide: true,
					offsetTop: 0,
					offsetLeft: 16,
					angle: { position: "top", offset: 0 },
					events: {
						onPopupShow: () =>  BX.addClass(event.target, 'active'),
						onPopupClose: () => BX.removeClass(event.target, 'active'),
					}
				}
			);

			popup.show();
		},
	},
	created()
	{
		this._checkRequestStatusTimeoutId = null;
		if (this.isSearchingCar)
		{
			this.startCheckingRequestStatus();
		}
	},
	computed: {
		isExpectedPriceReceived()
		{
			return this.fields.hasOwnProperty('EXPECTED_PRICE_DELIVERY');
		},
		isSendRequestButtonVisible()
		{
			if (this.isMakingRequest)
			{
				return false;
			}

			if (!this.fields.STATUS)
			{
				return false;
			}

			if (this.fields.STATUS)
			{
				if (this.fields.STATUS === 'initial')
				{
					return true;
				}
			}

			return false;
		},
		isSearchingCar()
		{
			return this.isMakingRequest || (this.fields.STATUS && this.fields.STATUS === 'searching');
		},
		isRequestCancellationLinkVisible()
		{
			return this.fields && this.fields.REQUEST_CANCELLATION_AVAILABLE;
		},
		cancelRequestButtonStyle()
		{
			return {
				'ui-btn': true,
				'ui-btn-sm': true,
				'ui-btn-light-border': true,
				'ui-btn-wait': this.isCancelling
			};
		},
	},
	watch: {
		isSearchingCar: function (value)
		{
			if (value)
			{
				this.startCheckingRequestStatus();
			}
			else
			{
				this.stopCheckingRequestStatus();
			}
		}
	},
	template: `
		<div class="crm-entity-stream-section crm-entity-stream-section-new crm-entity-stream-section-planned">
			<div class="crm-entity-stream-section-icon crm-entity-stream-section-icon-new crm-entity-stream-section-icon-taxi"></div>
			<div @click="showContextMenu" class="crm-entity-stream-section-context-menu"></div>
			<div class="crm-entity-stream-section-content">
				<div class="crm-entity-stream-content-event">
					<div class="crm-entity-stream-content-header">
						<span class="crm-entity-stream-content-event-title">
							{{localize.TIMELINE_DELIVERY_TAXI_SERVICE}}
						</span>
						<span v-if="statusName":class="statusClass">
							{{statusName}}
						</span>
						<span class="crm-entity-stream-content-event-time">{{this.createdAt}}</span>
					</div>
					<div class="crm-entity-stream-content-detail crm-entity-stream-content-delivery">
						<div class="crm-entity-stream-content-delivery-row crm-entity-stream-content-delivery-row--flex">
							<span v-if="isSendRequestButtonVisible" @click="makeRequest" class="ui-btn ui-btn-sm ui-btn-primary">
								{{localize.TIMELINE_DELIVERY_TAXI_SEND_REQUEST_2}}
							</span>
							<span v-if="isSearchingCar" class="crm-entity-stream-content-delivery-status">
								{{localize.TIMELINE_DELIVERY_TAXI_SEARCHING_CAR}}
							</span>
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
												<span style="font-size: 14px; color: #333;" v-html="fields.DELIVERY_PRICE"></span>
											</div>
										</div>
									</td>
									<td>
										<div class="crm-entity-stream-content-delivery-order-item">
											<div class="crm-entity-stream-content-delivery-order-label">
												{{localize.TIMELINE_DELIVERY_TAXI_EXPECTED_DELIVERY_PRICE}}
											</div>
											<div class="crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm">												
												<span style="font-size: 14px; color: #333; opacity: .5;" v-if="isExpectedPriceReceived">
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
											:canUseTelephony="fields.CAN_USE_TELEPHONY"
										></performer>
									</td>
								</tr>
								<tr v-if="fields.PERFORMER_CAR">
									<td colspan="2">
										<car :car="fields.PERFORMER_CAR"></car>
									</td>
								</tr>
								<tr v-if="isRequestCancellationLinkVisible">
									<td colspan="2">
										<div class="crm-entity-stream-content-delivery-order-item">
											<span @click="cancelRequest" :class="cancelRequestButtonStyle">
												{{localize.TIMELINE_DELIVERY_TAXI_DELIVERY_CANCEL_REQUEST}}
											</span>
										</div>
									</td>
								</tr>
							</table>
						</div>
					</div>
					<div class="crm-entity-stream-content-detail-planned-action">
						<input @click="completeActivity" type="checkbox" class="crm-entity-stream-planned-apply-btn">
					</div>
					<author v-if="author" :author="author"></author>
				</div>
			</div>
		</div>
	`
});
