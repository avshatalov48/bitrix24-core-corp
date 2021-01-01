import {Vue} from 'ui.vue';
import {ajax} from 'main.core';
import {Manager} from 'salescenter.manager';
import StringControl from './properties/string';
import AddressControl from './properties/address';
import CheckboxService from './services/checkbox';
import customServiceRegistry from './services/customregistry';
import 'currency';

import './css/deliveryselector.css';

export default {
	components: {
		/**
		 * Properties Control Types
		 */
		'ADDRESS-control': AddressControl,
		'STRING-control': StringControl,
		/**
		 * Extra Services Control Types
		 */
		'checkbox-service': CheckboxService,
	},
	props: {
		initDeliveryServiceId: {required: false},
		initRelatedServicesValues: {required: false},
		initRelatedPropsValues: {required: false},
		initRelatedPropsOptions: {required: false},
		initResponsibleId: {default: null, required: false},
		initEstimatedDeliveryPrice: {required: false},
		initEnteredDeliveryPrice: {required: false},
		initIsCalculated: {required: false},
		personTypeId: {required: true},
		action: {type: String, required: true },
		actionData: {type: Object, required: true },
		externalSum: {required: true},
		externalSumLabel: {type: String, required: true},
		currency: {type: String, required: true},
		currencySymbol: {type: String, required: true},
		editable: {type: Boolean, required: true},
	},
	data()
	{
		return {
			/**
			 * Selected Service
			 */
			selectedDeliveryService: null,
			deliveryServices: [],

			/**
			 * Props
			 */
			relatedProps: [],
			relatedPropsOfAddressType: [],
			relatedPropsOfOtherTypes: [],
			relatedPropsValues: {},

			/**
			 * Extra Services
			 */
			relatedServices: [],
			relatedServicesOfCheckboxType: [],
			customServices: [],
			relatedServicesValues: {},

			/**
			 * Prices
			 */
			estimatedDeliveryPrice: null,
			enteredDeliveryPrice: null,
			/**
			 * Responsible User
			 */
			responsibleUser: null,

			/**
			 * Processing Indicators
			 */
			isCalculated: false,
			isCalculating: false,

			calculateErrors: [],
		};
	},
	methods: {
		initialize()
		{
			ajax.runAction(
				'salescenter.deliveryselector.getinitializationdata',
				{data: {personTypeId: this.personTypeId, responsibleId: this.initResponsibleId}}
			).then((result) => {
				/**
				 * Delivery services
				 */

				this.deliveryServices = result.data.services;

				if (this.deliveryServices.length > 0)
				{
					let initDeliveryServiceId = this.selectedDeliveryService
						? this.selectedDeliveryService.id
						: (this.initDeliveryServiceId ? this.initDeliveryServiceId : null);

					if (initDeliveryServiceId)
					{
						for (let deliveryService of this.deliveryServices)
						{
							if (deliveryService.id == initDeliveryServiceId)
							{
								this.selectedDeliveryService = deliveryService;
								break;
							}
						}
					}

					if (!this.selectedDeliveryService)
					{
						this.selectedDeliveryService = this.deliveryServices[0];
					}
				}

				/**
				 * Related props
				 */
				let relatedProps = result.data.properties;

				/**
				 * Setting default values to related props
				 */
				for (let relatedProp of relatedProps)
				{
					let initValue = null;

					if (this.initRelatedPropsValues
						&& this.initRelatedPropsValues.hasOwnProperty(relatedProp.id)
					)
					{
						initValue = this.initRelatedPropsValues[relatedProp.id];
					}
					else if (relatedProp.initValue)
					{
						initValue = relatedProp.initValue;
					}

					if (initValue !== null)
					{
						initValue = (typeof initValue === 'object') ? JSON.stringify(initValue) : initValue;

						Vue.set(this.relatedPropsValues, relatedProp.id, initValue);
					}
				}

				this.relatedProps = relatedProps;
				this.relatedPropsOfAddressType = this.relatedProps.filter((item) => item.type === 'ADDRESS');
				this.relatedPropsOfOtherTypes = this.relatedProps.filter((item) => item.type !== 'ADDRESS');

				/**
				 * Related services
				 */
				let relatedServices = result.data.extraServices;

				for (let relatedService of relatedServices)
				{
					let initValue = null;

					if (this.initRelatedServicesValues
						&& this.initRelatedServicesValues.hasOwnProperty(relatedService.id)
					)
					{
						initValue = this.initRelatedServicesValues[relatedService.id];
					}
					else if (relatedService.initValue)
					{
						initValue = relatedService.initValue;
					}

					if (initValue !== null)
					{
						Vue.set(this.relatedServicesValues, relatedService.id, initValue);
					}
				}
				this.relatedServices = relatedServices;
				this.relatedServicesOfCheckboxType = this.relatedServices.filter((item) => item.type === 'checkbox');

				/**
				 * Custom extra services
				 */
				for (let component in customServiceRegistry)
				{
					this.$options.components[component] = customServiceRegistry[component];
				}

				this.customServices = [];
				let registeredComponents = Object.keys(this.$options.components).filter(item => item.startsWith('SERVICE_'));
				for (let relatedService of this.relatedServices)
				{
					let componentName = 'SERVICE_' + relatedService.deliveryServiceCode + '_' + relatedService.code;

					if (registeredComponents.includes(componentName))
					{
						this.customServices.push({
							name: componentName,
							service: relatedService
						});
					}
				}

				/**
				 * Responsible
				 */
				this.responsibleUser = result.data.responsible;

				/**
				 * Misc
				 */
				this._userPageTemplate = result.data.userPageTemplate;

				this._deliverySettingsUrl = result.data.deliverySettingsUrl;

				/**
				 *
				 */
				if (this.initEstimatedDeliveryPrice !== null)
				{
					this.estimatedDeliveryPrice = this.initEstimatedDeliveryPrice;
				}
				if (this.initEnteredDeliveryPrice !== null)
				{
					this.enteredDeliveryPrice = this.initEnteredDeliveryPrice;
				}

				if (this.initIsCalculated !== null)
				{
					this.isCalculated = this.initIsCalculated;
				}

				this.emitChange();
			});
		},
		calculate()
		{
			if (!this.isCalculatingAllowed)
			{
				return;
			}

			this.isCalculating = true;

			let calculationFinallyCallback = (status, payload) =>
			{
				this.isCalculating = false;
				this.isCalculated = true;
				this.emitChange();
			};

			let actionData = Object.assign(
				{},
				this.actionData,
				{
					deliveryServiceId: this.selectedDeliveryServiceId,
					deliveryRelatedPropValues: this.currentRelatedPropsValues,
					deliveryRelatedServiceValues: this.currentRelatedServicesValues,
					deliveryResponsibleId: this.responsibleUser ? this.responsibleUser.id : null,
				}
			);

			ajax.runAction(this.action, {data: actionData}).then((result) => {
				let deliveryPrice = result.data.deliveryCalculationResult.price;

				this.estimatedDeliveryPrice = deliveryPrice;
				this.enteredDeliveryPrice = deliveryPrice;

				this.calculateErrors = [];
				calculationFinallyCallback();
			}).catch((result) => {
				this.estimatedDeliveryPrice = null;
				this.enteredDeliveryPrice = 0.00;

				this.calculateErrors = result.errors.map((item) => item.message);
				calculationFinallyCallback();
			});
		},
		openChangeResponsibleDialog(event)
		{
			let self = this;

			if (typeof(this._userEditor) === 'undefined')
			{
				this._userEditor = new BX.Crm.EntityEditorUserSelector();

				this._userEditor.initialize(
					'deliverySelectorUserEditor',
					{
						callback(item, type, search, bUndeleted)
						{
							self.responsibleUser = {
								id: type.entityId,
								name: type.name,
								photo: type.avatar,
							};
							self.emitChange();
						}
					}
				);
			}

			this._userEditor.open(
				event.target.parentElement
			);
		},
		onDeliveryServiceChanged(deliveryService)
		{
			if (!this.editable)
			{
				return;
			}

			this.selectedDeliveryService = deliveryService;
			this.emitChange();
		},
		onPropValueChanged(event, relatedProp)
		{
			Vue.set(this.relatedPropsValues, relatedProp.id, event);
			this.emitChange();
		},
		onServiceValueChanged(event, relatedService)
		{
			Vue.set(this.relatedServicesValues, relatedService.id, event);
			this.emitChange();
		},
		responsibleUserClicked()
		{
			Manager.openSlider(this.responsibleUserLink);
		},
		emitChange()
		{
			this.$emit('change', this.state);
		},
		formatMoney(value)
		{
			return BX.Currency.currencyFormat(value, this.currency, false);
		},
		isNumber: function(event) {
			event = (event) ? event : window.event;
			var charCode = (event.which) ? event.which : event.keyCode;
			if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
				event.preventDefault();
			} else {
				return true;
			}
			return false;
		},
		getPropValue(relatedProp)
		{
			if (!this.relatedPropsValues)
			{
				return null;
			}
			return this.relatedPropsValues.hasOwnProperty(relatedProp.id) ? this.relatedPropsValues[relatedProp.id] : null;
		},
		getPropOptions(relatedProp)
		{
			if (!this.initRelatedPropsOptions)
			{
				return null;
			}
			return this.initRelatedPropsOptions.hasOwnProperty(relatedProp.id) ? this.initRelatedPropsOptions[relatedProp.id] : null;
		},
		getServiceValue(relatedService)
		{
			if (!this.relatedServicesValues)
			{
				return null;
			}
			return this.relatedServicesValues.hasOwnProperty(relatedService.id) ? this.relatedServicesValues[relatedService.id] : null;
		},
		getCustomServiceValue(customService)
		{
			if (!this.relatedServicesValues)
			{
				return null;
			}
			return this.relatedServicesValues.hasOwnProperty(customService.service.id) ? this.relatedServicesValues[customService.service.id] : null;
		},
		onAddMoreClicked()
		{
			if (!this.editable)
			{
				return;
			}
			
			Manager.openSlider(this._deliverySettingsUrl).then(() => {
				this.initialize();
				this.$emit('settings-changed');
			});
		}
	},
	created()
	{
		this.initialize();
	},
	watch: {
		enteredDeliveryPrice(value)
		{
			this.emitChange();
		}
	},
	computed: {
		state()
		{
			return {
				deliveryServiceId: this.selectedDeliveryServiceId,
				deliveryPrice: this.deliveryPrice,
				estimatedDeliveryPrice: this.estimatedDeliveryPrice,
				relatedPropsValues: this.currentRelatedPropsValues,
				relatedServicesValues: this.currentRelatedServicesValues,
				responsibleUser: this.responsibleUser,
			};
		},
		selectedDeliveryServiceId()
		{
			return this.selectedDeliveryService ? this.selectedDeliveryService.id : null;
		},
		selectedNoDelivery()
		{
			return this.selectedDeliveryService && this.selectedDeliveryService['code'] === 'NO_DELIVERY';
		},
		isCalculatingAllowed()
		{
			return this.selectedDeliveryServiceId
				&& this.arePropValuesReady
				&& !this.isCalculating
				&& this.editable;
		},
		currentRelatedPropsValues()
		{
			let result = [];

			if (!this.selectedDeliveryServiceId)
			{
				return result;
			}

			for (let relatedProp of this.relatedProps)
			{
				if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId))
				{
					continue;
				}

				if (this.relatedPropsValues.hasOwnProperty(relatedProp.id))
				{
					result.push(
						{
							id: relatedProp.id,
							value: this.relatedPropsValues[relatedProp.id]
						}
					);
				}
			}

			return result;
		},
		isResponsibleUserSectionVisible()
		{
			return this.responsibleUser && !this.selectedNoDelivery;
		},
		responsibleUserLink()
		{
			if (!this.responsibleUser)
			{
				return '';
			}

			return this._userPageTemplate.replace('#user_id#', this.responsibleUser.id);
		},
		arePropValuesReady()
		{
			if (!this.selectedDeliveryServiceId)
			{
				return false;
			}

			for (let relatedProp of this.relatedProps)
			{
				if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId))
				{
					continue;
				}

				if (relatedProp.required && !this.relatedPropsValues[relatedProp.id])
				{
					return false;
				}
			}

			return true;
		},
		currentRelatedServicesValues()
		{
			let result = [];

			if (!this.selectedDeliveryServiceId)
			{
				return result;
			}

			for (let relatedService of this.relatedServices)
			{
				if (!relatedService.deliveryServiceIds.includes(this.selectedDeliveryServiceId))
				{
					continue;
				}

				if (this.relatedServicesValues.hasOwnProperty(relatedService.id))
				{
					result.push(
						{
							id: relatedService.id,
							value: this.relatedServicesValues[relatedService.id]
						}
					);
				}
			}

			return result;
		},
		totalPrice()
		{
			let result = this.externalSum;

			if (this.deliveryPrice !== null)
			{
				result += this.deliveryPrice;
			}
			return result;
		},
		totalPriceFormatted()
		{
			return this.formatMoney(this.totalPrice);
		},
		deliveryPrice()
		{
			if (!this.selectedDeliveryServiceId)
			{
				return null;
			}

			if (this.selectedNoDelivery)
			{
				return 0;
			}

			if (this.enteredDeliveryPrice)
			{
				return +this.enteredDeliveryPrice;
			}

			return null;
		},
		deliveryPriceFormatted()
		{
			return this.formatMoney(this.deliveryPrice);
		},
		estimatedDeliveryPriceFormatted()
		{
			return this.formatMoney(this.estimatedDeliveryPrice);
		},
		externalSumFormatted()
		{
			return this.formatMoney(this.externalSum);
		},
		calculateDeliveryPriceButtonClass()
		{
			return {
				'ui-btn': true,
				'ui-btn-light-border': true,
				'salescenter-delivery-bottom-update-icon': true,
				'ui-btn-disabled': !this.isCalculatingAllowed,
			};
		},
		localize()
		{
			return Vue.getFilteredPhrases('SALE_DELIVERY_SERVICE_SELECTOR_');
		},

		extraServiceCheckboxesCount()
		{
			let result = 0;

			for (let relatedService of this.relatedServicesOfCheckboxType)
			{
				if (!relatedService.deliveryServiceIds.includes(this.selectedDeliveryServiceId))
				{
					continue;
				}

				result++;
			}

			return result;
		},
		relatedPropsOfAddressTypeCount()
		{
			let result = 0;

			for (let relatedProp of this.relatedPropsOfAddressType)
			{
				if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId))
				{
					continue;
				}

				result++;
			}

			return result;
		},
		relatedPropsOfOtherTypeCount()
		{
			let result = 0;

			for (let relatedProp of this.relatedPropsOfOtherTypes)
			{
				if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId))
				{
					continue;
				}

				result++;
			}

			return result;
		}
	},
	template: `
		<div class="salescenter-delivery">
			<div class="salescenter-delivery-header">
				<span class="salescenter-delivery-header-method">
					{{localize.SALE_DELIVERY_SERVICE_SELECTOR_DELIVERY_SERVICE}}
				</span>
				
				<div
					v-for="deliveryService in deliveryServices"
					@click="onDeliveryServiceChanged(deliveryService)"
					:class="{'salescenter-delivery-method-item': true, 'salescenter-delivery-method-item--selected': (selectedDeliveryService && deliveryService.id == selectedDeliveryService.id) ? true : false}"
				>
					<div class="salescenter-delivery-method-image">
						<img :src="deliveryService.logo">
					</div>
					<div class="salescenter-delivery-method-info">
						<div v-if="deliveryService.title" class="salescenter-delivery-method-title">{{deliveryService.title}}</div>
						<div v-else="deliveryService.title" class="salescenter-delivery-method-title"></div>
						<div class="salescenter-delivery-method-name">{{deliveryService.name}}</div>
					</div>
				</div>
				<div @click="onAddMoreClicked" class="salescenter-delivery-method-item salescenter-delivery-method-item--add">
					<div class="salescenter-delivery-method-image-more"></div>
					<div class="salescenter-delivery-method-info">
						<div class="salescenter-delivery-method-name">
							{{localize.SALE_DELIVERY_SERVICE_SELECTOR_ADD_MORE}}
						</div>
					</div>
				</div>
			</div>
			
			<component
				v-for="customService in customServices"
				v-show="customService.service.deliveryServiceIds.includes(selectedDeliveryServiceId)"
				:is="customService.name"
				:key="customService.service.id"
				:name="customService.service.name"
				:initValue="getCustomServiceValue(customService)"
				:options="customService.service.options"
				:editable="editable"
				@change="onServiceValueChanged($event, customService.service)"
			>
			</component>
			
			<div v-show="extraServiceCheckboxesCount > 0" class="salescenter-delivery-additionally">
				<div class="salescenter-delivery-additionally-options">
					<component
						v-for="relatedService in relatedServicesOfCheckboxType"
						v-show="relatedService.deliveryServiceIds.includes(selectedDeliveryServiceId)"
						:is="'checkbox-service'"
						:key="relatedService.id"
						:name="relatedService.name"
						:initValue="getServiceValue(relatedService)"						
						:options="relatedService.options"
						:editable="editable"
						@change="onServiceValueChanged($event, relatedService)"
					>
					</component>
				</div>
			</div>
			<div v-show="relatedPropsOfAddressTypeCount > 0" class="salescenter-delivery-path">
				<div
					v-for="(relatedProp, index) in relatedPropsOfAddressType"
					v-show="relatedProp.deliveryServiceIds.includes(selectedDeliveryServiceId)"
					class="salescenter-delivery-path-item"
				>
					<div class="salescenter-delivery-path-title">{{relatedProp.name}}</div>
					<div class="salescenter-delivery-path-control">
						<div :class="{'salescenter-delivery-path-icon': true, 'salescenter-delivery-path-icon--green': index > 0}"></div>
						<component
							:is="'ADDRESS-control'"
							:key="relatedProp.id"
							:name="'PROPS_' + relatedProp.id"							
							:initValue="getPropValue(relatedProp)"
							:editable="editable"
							:options="getPropOptions(relatedProp)"
							@change="onPropValueChanged($event, relatedProp)"
						></component>
					</div>
				</div>
			</div>
			
			<div v-show="relatedPropsOfOtherTypeCount > 0" class="salescenter-delivery-path">
				<div
					v-for="(relatedProp, index) in relatedPropsOfOtherTypes"
					v-show="relatedProp.deliveryServiceIds.includes(selectedDeliveryServiceId)"
					class="salescenter-delivery-path-item"
				>
					<div class="salescenter-delivery-path-title-ordinary">{{relatedProp.name}}</div>
					<div class="salescenter-delivery-path-control">
						<component
							:is="relatedProp.type + '-control'"
							:key="relatedProp.id"
							:name="'PROPS_' + relatedProp.id"
							:editable="editable"
							:initValue="getPropValue(relatedProp)"
							:settings="relatedProp.settings"
							:options="getPropOptions(relatedProp)"
							@change="onPropValueChanged($event, relatedProp)"
						></component>
					</div>
				</div>
			</div>
			<div v-if="isResponsibleUserSectionVisible" class="salescenter-delivery-manager-wrapper">
				<div class="ui-ctl-label-text">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_RESPONSIBLE_MANAGER}}</div>
				<div class="salescenter-delivery-manager">
					<div class="salescenter-delivery-manager-avatar" :style="responsibleUser.photo ? {'background-image': 'url(' + responsibleUser.photo + ')'} : {}"></div>
					<div class="salescenter-delivery-manager-content">
						<div @click="responsibleUserClicked" class="salescenter-delivery-manager-name">{{responsibleUser.name}}</div>
					</div>
					<div v-if="editable" @click="openChangeResponsibleDialog" class="salescenter-delivery-manager-edit">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CHANGE_RESPONSIBLE}}</div>
				</div>
			</div>
					
			<div v-show="!selectedNoDelivery">
				<template v-if="calculateErrors">
					<div v-for="(error, index) in calculateErrors" class="ui-alert ui-alert-danger ui-alert-icon-danger salescenter-delivery-errors-container-alert">
						<span  class="ui-alert-message">
							<span v-html="error"></span>
						</span>
					</div>
				</template>
				<div class="salescenter-delivery-bottom">
					<div v-if="editable" class="salescenter-delivery-bottom-row">					
						<div class="salescenter-delivery-bottom-col">
							<span v-show="!isCalculating" @click="calculate" :class="calculateDeliveryPriceButtonClass">{{isCalculated ? localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATE_UPDATE : localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATE}}</span>
							
							<span v-show="isCalculating" class="salescenter-delivery-waiter">
								<span class="salescenter-delivery-waiter-alert">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATING_LABEL}}</span>
								<span class="salescenter-delivery-waiter-text">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATING_REQUEST_SENT}} {{selectedDeliveryService ? selectedDeliveryService.name : ''}}</span>
							</span>
						</div>
					</div>
					<div v-show="isCalculated" class="salescenter-delivery-bottom-row">
						<div class="salescenter-delivery-bottom-col"></div>
						<div class="salescenter-delivery-bottom-col">
							<table class="salescenter-delivery-table-total">
								<tr>
									<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_EXPECTED_DELIVERY_PRICE}}:</td>
									<td>
										<span v-html="estimatedDeliveryPriceFormatted"></span>&nbsp;<span v-html="currencySymbol"></span>
									</td>
								</tr>
								<tr>
									<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CLIENT_DELIVERY_PRICE}}:</td>
									<td>
										<div class="ui-ctl ui-ctl-md ui-ctl-wa salescenter-delivery-bottom-input-symbol">
											<input :disabled="!editable" v-model="enteredDeliveryPrice" @keypress="isNumber($event)" type="text" class="ui-ctl-element ui-ctl-textbox">
											<span v-html="currencySymbol"></span>
										</div>
									</td>
								</tr>
								<tr>
									<td>{{externalSumLabel}}:</td>
									<td>
										<span v-html="externalSumFormatted"></span><span class="salescenter-delivery-table-total-symbol" v-html="currencySymbol"></span>
									</td>
								</tr>
								<tr>
									<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_DELIVERY_DELIVERY}}:</td>
									<td>
										<span v-show="deliveryPrice > 0">
											<span v-html="deliveryPriceFormatted"></span>&nbsp;<span v-html="currencySymbol"></span>
										</span>
										<span v-show="!deliveryPrice" class="salescenter-delivery-status salescenter-delivery-status--success">
											{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CLIENT_DELIVERY_PRICE_FREE}}
										</span>
									</td>
								</tr>
								
								<tr class="salescenter-delivery-table-total-result">
									<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_TOTAL}}:</td>
									<td>
										<span v-html="totalPriceFormatted"></span>
										<span class="salescenter-delivery-table-total-symbol" v-html="currencySymbol"></span>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
};
