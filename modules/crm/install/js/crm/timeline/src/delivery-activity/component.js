import {Vue} from 'ui.vue';
import {ajax, Loc} from 'main.core';
import Author from '../components/author';
import DeliveryServiceInfo from '../components/delivery/delivery-service-info';
import {PULL} from 'pull.client';
import HistoryItemMixin from '../mixins/history-item';

export default Vue.extend({
	mixins: [HistoryItemMixin],
	components: {
		'author': Author,
		'delivery-service-info': DeliveryServiceInfo,
	},
	props: {
		mode: {
			required: true,
			type: String
		},
	},
	data()
	{
		return {
			entityData: null,
			deliveryInfo: null,
			isRefreshing: false,
			isCreatingRequest: false,
			isCancellingRequest: false,
		};
	},
	methods: {
		// region common activity methods
		completeActivity()
		{
			if(this.self.canComplete())
			{
				this.self.setAsDone(!this.self.isDone());
			}
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
		// endregion
		// region delivery request methods
		createDeliveryRequest()
		{
			if (this.isLocked)
			{
				return;
			}

			this.isCreatingRequest = true;
			BX.ajax.runAction(
				'sale.deliveryrequest.create',
				{
					analyticsLabel: 'saleDeliveryTaxiCall',
					data: {
						shipmentIds: this.shipmentIds,
						additional: {
							ACTIVITY_ID: this.activityId,
						}
					}
				}
			).then((result) => {
				this.refresh(() => {
					this.isCreatingRequest = false;
				});
			}).catch((result) => {
				this.isCreatingRequest = false;
				this.showError(result.errors.map((item) => item.message).join());
			});
		},
		cancelDeliveryRequest()
		{
			if (this.isLocked || !this.deliveryRequest)
			{
				return;
			}

			this.isCancellingRequest = true;
			BX.ajax.runAction(
				'sale.deliveryrequest.execute',
				{
					data: {
						requestId: this.deliveryRequest['ID'],
						actionType: this.deliveryService['CANCEL_ACTION_CODE'],
					}
				})
				.then((result) => {
					const data = result.data;

					BX.ajax.runAction(
						'crm.timeline.deliveryactivity.createcanceldeliveryrequestmessage',
						{data: {
							requestId: this.deliveryRequest['ID'],
							message: data.message
						}}
					).then((result) => {
						BX.ajax.runAction(
							'sale.deliveryrequest.delete',
							{
								data: {
									requestId: this.deliveryRequest['ID'],
								}
							})
							.then((result) => {
								this.refresh(() => {
									this.isCancellingRequest = false;
								});
							}).catch((result) => {
							this.isCancellingRequest = false;
							this.showError(result.errors.map((item) => item.message).join());
						});
					});
				}).catch((result) => {
					this.isCancellingRequest = false;
					this.showError(result.errors.map((item) => item.message).join());
				});
		},
		checkRequestStatus()
		{
			BX.ajax.runAction('crm.timeline.deliveryactivity.checkrequeststatus');
		},
		startCheckingRequestStatus()
		{
			clearTimeout(this._checkRequestStatusTimeoutId);
			this._checkRequestStatusTimeoutId = setInterval(
				() => this.checkRequestStatus(), 30 * 1000
			);
		},
		stopCheckingRequestStatus()
		{
			clearTimeout(this._checkRequestStatusTimeoutId);
		},
		// endregion
		// region refresh methods
		setDeliveryInfo(deliveryInfo)
		{
			this.deliveryInfo = deliveryInfo;
		},
		refresh(callback = null)
		{
			if (this.isRefreshing)
			{
				return;
			}
			this.isRefreshing = true;

			const finallyCallback = () => {
				this.isRefreshing = false;
				if (callback)
				{
					callback();
				}
			};
			ajax.runAction(
				'crm.timeline.deliveryactivity.getdeliveryinfo',
				{
					data: {
						activityId: this.activityId
					}
				}
			).then((result) => {
				this.setDeliveryInfo(result.data);
				finallyCallback();
			}).catch((result) => {
				finallyCallback();
			});
		},
		subscribePullEvents()
		{
			if (this._isPullSubscribed)
			{
				return;
			}

			PULL.subscribe({
				moduleId: 'crm',
				command: 'onOrderShipmentSave',
				callback: (params) => {
					if (this.shipmentIds.some(id => id == params.FIELDS.ID))
					{
						this.refresh();
					}
				}
			});
			PULL.subscribe({
				moduleId: 'sale',
				command: 'onDeliveryServiceSave',
				callback: (params) => {
					if (this.deliveryServiceIds.some(id => id == params.ID))
					{
						this.refresh();
					}
				}
			});
			PULL.subscribe({
				moduleId: 'sale',
				command: 'onDeliveryRequestUpdate',
				callback: (params) => {
					if (this.deliveryRequestId == params.ID)
					{
						this.refresh();
					}
				}
			});
			PULL.subscribe({
				moduleId: 'sale',
				command: 'onDeliveryRequestDelete',
				callback: (params) => {
					if (this.deliveryRequestId == params.ID)
					{
						this.refresh();
					}
				}
			});
			PULL.extendWatch('SALE_DELIVERY_SERVICE');
			PULL.extendWatch('CRM_ENTITY_ORDER_SHIPMENT');
			PULL.extendWatch('SALE_DELIVERY_REQUEST');

			this._isPullSubscribed = true;
		},
		//endregion
		// region miscellaneous
		callPhone(phone)
		{
			if (this.canUseTelephony && typeof(top.BXIM)!=='undefined')
			{
				top.BXIM.phoneTo(phone);
			}
			else
			{
				window.location.href='tel:' + phone;
			}
		},
		isPhone(property)
		{
			return (
				property.hasOwnProperty('TAGS')
				&& Array.isArray(property['TAGS'])
				&& property['TAGS'].includes('phone')
			);
		},
		showError(message)
		{
			BX.loadExt('ui.notification').then(() => { BX.UI.Notification.Center.notify({content: message}); });
		},
		// endregion
	},
	created()
	{
		this.entityData = this.self.getAssociatedEntityData();
		if (this.entityData['DELIVERY_INFO'])
		{
			this.setDeliveryInfo(this.entityData['DELIVERY_INFO']);
		}

		this.subscribePullEvents();

		this._checkRequestStatusTimeoutId = null;
		if (this.needCheckRequestStatus)
		{
			this.startCheckingRequestStatus();
		}
	},
	computed: {
		activityId()
		{
			return this.data.ASSOCIATED_ENTITY.ID;
		},
		// region shipments
		shipments()
		{
			if (
				this.deliveryInfo
				&& this.deliveryInfo.hasOwnProperty('SHIPMENTS')
				&& Array.isArray(this.deliveryInfo['SHIPMENTS'])
			)
			{
				return this.deliveryInfo['SHIPMENTS'];
			}

			return null;
		},
		shipmentIds()
		{
			return this.shipments ? this.shipments.map((shipment) => shipment['ID']) : [];
		},
		shipment()
		{
			if (
				this.shipments
				&& Array.isArray(this.shipments)
				&& this.shipments.length > 0
			)
			{
				return this.shipments[0];
			}

			return null;
		},
		expectedDeliveryPriceFormatted()
		{
			return (this.shipment && this.shipment.hasOwnProperty('BASE_PRICE_DELIVERY'))
				? this.shipment['BASE_PRICE_DELIVERY_FORMATTED']
				: this.shipment['PRICE_DELIVERY_FORMATTED'];
		},
		// endregion
		// region delivery service
		deliveryService()
		{
			if (
				this.deliveryInfo
				&& this.deliveryInfo.hasOwnProperty('DELIVERY_SERVICE')
				&& typeof this.deliveryInfo['DELIVERY_SERVICE'] === 'object'
				&& this.deliveryInfo['DELIVERY_SERVICE'] !== null
			)
			{
				return this.deliveryInfo['DELIVERY_SERVICE'];
			}

			return null;
		},
		deliveryServiceIds()
		{
			// @TODO
			if (!this.deliveryService)
			{
				return null;
			}

			return this.deliveryService.IDS;
		},
		// endregion
		// region delivery request
		deliveryRequest()
		{
			if (
				this.deliveryInfo
				&& this.deliveryInfo.hasOwnProperty('DELIVERY_REQUEST')
				&& typeof this.deliveryInfo['DELIVERY_REQUEST'] === 'object'
				&& this.deliveryInfo['DELIVERY_REQUEST'] !== null
			)
			{
				return this.deliveryInfo['DELIVERY_REQUEST'];
			}

			return null;
		},
		deliveryRequestId()
		{
			if (this.deliveryRequest && this.deliveryRequest.hasOwnProperty('ID'))
			{
				return this.deliveryRequest['ID'];
			}

			return null;
		},
		deliveryRequestProperties()
		{
			if (
				this.deliveryRequest
				&& this.deliveryRequest.hasOwnProperty('EXTERNAL_PROPERTIES')
				&& typeof this.deliveryRequest['EXTERNAL_PROPERTIES'] === 'object'
				&& this.deliveryRequest['EXTERNAL_PROPERTIES'] !== null
			)
			{
				return this.deliveryRequest['EXTERNAL_PROPERTIES'];
			}

			return null;
		},
		deliveryRequestStatus()
		{
			if (!this.deliveryRequest)
			{
				return null;
			}

			return this.deliveryRequest['EXTERNAL_STATUS'];
		},
		deliveryRequestStatusSemantic()
		{
			if (!this.deliveryRequest)
			{
				return null;
			}

			return this.deliveryRequest['EXTERNAL_STATUS_SEMANTIC'];
		},
		isConnectedWithDeliveryRequest()
		{
			return !!this.deliveryRequest;
		},
		needCheckRequestStatus()
		{
			return this.isConnectedWithDeliveryRequest && this.mode === 'schedule';
		},
		isSendRequestButtonVisible()
		{
			return (!this.isCreatingRequest && !this.isConnectedWithDeliveryRequest);
		},
		// endregion
		//region miscellaneous
		miscellaneous()
		{
			if (
				this.deliveryInfo
				&& this.deliveryInfo.hasOwnProperty('MISCELLANEOUS')
			)
			{
				return this.deliveryInfo['MISCELLANEOUS'];
			}

			return null;
		},
		canUseTelephony()
		{
			return (
				this.miscellaneous
				&& this.miscellaneous.hasOwnProperty('CAN_USE_TELEPHONY')
				&& this.miscellaneous['CAN_USE_TELEPHONY']
			);
		},
		template()
		{
			if (!this.miscellaneous || !this.miscellaneous.hasOwnProperty('TEMPLATE'))
			{
				return null;
			}

			return this.miscellaneous['TEMPLATE'];
		},
		// endregion
		// region classes
		cancelRequestButtonStyle()
		{
			return {
				'ui-btn': true,
				'ui-btn-sm': true,
				'ui-btn-light-border': true,
				'ui-btn-wait': this.isCancellingRequest
			};
		},
		statusClass()
		{
			return {
				'crm-entity-stream-content-event-process': this.deliveryRequestStatusSemantic === 'process',
				'crm-entity-stream-content-event-missing': this.deliveryRequestStatusSemantic === 'error',
				'crm-entity-stream-content-event-done': this.deliveryRequestStatusSemantic === 'success',
			};
		},
		wrapperContainerClass()
		{
			return {
				'crm-entity-stream-section-planned': this.mode === 'schedule'
			};
		},
		innerWrapperContainerClass()
		{
			return {
				'crm-entity-stream-content-event--delivery': this.mode !== 'schedule'
			};
		},
		// endregion
		isLocked()
		{
			return this.isRefreshing || this.isCreatingRequest || this.isCancellingRequest;
		},
	},
	watch: {
		needCheckRequestStatus: function (value)
		{
			if (value)
			{
				this.startCheckingRequestStatus();
			}
			else
			{
				this.stopCheckingRequestStatus();
			}
		},
	},
	// language=Vue
	template: `
		<div
			class="crm-entity-stream-section crm-entity-stream-section-new"
			:class="wrapperContainerClass"
		>
			<div class="crm-entity-stream-section-icon crm-entity-stream-section-icon-new crm-entity-stream-section-icon-taxi"></div>
			<div
				v-if="mode === 'schedule'"
				@click="showContextMenu"
				class="crm-entity-stream-section-context-menu"
			></div>
			<div class="crm-entity-stream-section-content">
				<div
					class="crm-entity-stream-content-event"
					:class="innerWrapperContainerClass"
				>
					<div class="crm-entity-stream-content-header">
						<span class="crm-entity-stream-content-event-title">
							${Loc.getMessage('TIMELINE_DELIVERY_TAXI_SERVICE')}
						</span>
						<span
							v-if="deliveryRequestStatus && deliveryRequestStatusSemantic"
							:class="statusClass"
						>
							{{deliveryRequestStatus}}
						</span>
						<span class="crm-entity-stream-content-event-time">{{createdAt}}</span>
					</div>
					<div class="crm-entity-stream-content-detail crm-entity-stream-content-delivery">
						<div class="crm-entity-stream-content-delivery-row crm-entity-stream-content-delivery-row--flex">
							<template v-if="mode === 'schedule'">
								<span
									v-if="isSendRequestButtonVisible"
									@click="createDeliveryRequest"
									class="ui-btn ui-btn-sm ui-btn-primary"
								>
									${Loc.getMessage('TIMELINE_DELIVERY_CREATE_DELIVERY_REQUEST')}
								</span>
								<span v-if="isCreatingRequest" class="crm-entity-stream-content-delivery-status">
									${Loc.getMessage('TIMELINE_DELIVERY_CREATING_REQUEST')}
								</span>
								<span
									v-if="isConnectedWithDeliveryRequest && deliveryService && deliveryService.IS_CANCELLABLE"
									@click="cancelDeliveryRequest"
									:class="cancelRequestButtonStyle"
								>								
									{{deliveryService.CANCEL_ACTION_NAME}}
								</span>					
							</template>
							<delivery-service-info
								v-if="deliveryService"
								:deliveryService="deliveryService"
							>
							</delivery-service-info>
						</div>
						<div class="crm-entity-stream-content-delivery-row">
							<table class="crm-entity-stream-content-delivery-order">
								<tr v-if="shipment && shipment.ADDRESS_FROM_FORMATTED && shipment.ADDRESS_TO_FORMATTED">
									<td colspan="2">
										<div class="crm-entity-stream-content-delivery-order-item">
											<div class="crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm">
												<div class="crm-entity-stream-content-delivery-order-box">
													<div class="crm-entity-stream-content-delivery-order-box-label">
														${Loc.getMessage('TIMELINE_DELIVERY_TAXI_ADDRESS_FROM')}
													</div>
													<span v-html="shipment.ADDRESS_FROM_FORMATTED">
													</span>
												</div>
												<div class="crm-entity-stream-content-delivery-order-box">
													<div class="crm-entity-stream-content-delivery-order-box-label">
														${Loc.getMessage('TIMELINE_DELIVERY_TAXI_ADDRESS_TO')}
													</div>
													<span v-html="shipment.ADDRESS_TO_FORMATTED">
													</span>
												</div>
											</div>
										</div>
									</td>
								</tr>
								<tr v-if="shipment">
									<td>
										<div class="crm-entity-stream-content-delivery-order-item">
											<div class="crm-entity-stream-content-delivery-order-label">
												${Loc.getMessage('TIMELINE_DELIVERY_TAXI_CLIENT_DELIVERY_PRICE')}
											</div>
											<div class="crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm">
												<span
													v-html="shipment.PRICE_DELIVERY_FORMATTED"
													 style="font-size: 14px; color: #333;"
												>
												</span>
											</div>
										</div>
									</td>
									<td>
										<div class="crm-entity-stream-content-delivery-order-item">
											<div class="crm-entity-stream-content-delivery-order-label">
												${Loc.getMessage('TIMELINE_DELIVERY_TAXI_EXPECTED_DELIVERY_PRICE')}
											</div>
											<div class="crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm">
												<span style="font-size: 14px; color: #333; opacity: .5;">
													<span
														v-html="expectedDeliveryPriceFormatted"
													>
													</span>
												</span>
												<span v-else>
													${Loc.getMessage('TIMELINE_DELIVERY_TAXI_EXPECTED_PRICE_NOT_RECEIVED')}
												</span>
											</div>
										</div>
									</td>
								</tr>
								<!-- Properties --->
								<tr v-for="property in deliveryRequestProperties">
									<td colspan="2">
										<div class="crm-entity-stream-content-delivery-order-item">
											<div class="crm-entity-stream-content-delivery-order-label">
												{{property.NAME}}
											</div>
											<div class="crm-entity-stream-content-delivery-order-value crm-entity-stream-content-delivery-order-value--sm">
												<span
													v-if="isPhone(property)"
													@click="callPhone(property.VALUE)"
													class="crm-entity-stream-content-delivery-link"
												>
													{{property.VALUE}}
												</span>
												<span v-else>
													{{property.VALUE}}
												</span>
											</div>
										</div>
									</td>
								</tr>
								<!-- end Properties --->
							</table>
						</div>
					</div>
					<div v-if="mode === 'schedule'" class="crm-entity-stream-content-detail-planned-action">
						<input @click="completeActivity" type="checkbox" class="crm-entity-stream-planned-apply-btn">
					</div>
					<author v-if="author" :author="author">
					</author>
				</div>
			</div>
		</div>
	`
});
