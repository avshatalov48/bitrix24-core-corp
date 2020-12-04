import {config} from './config';
import {Vue} from 'ui.vue';
import {Vuex} from 'ui.vue.vuex';
import {Manager} from 'salescenter.manager';
import {Dom, Loc, Tag, Text, ajax as Ajax} from 'main.core';
import {Popup} from 'main.popup';
import {PopupMenuWindow, PopupWindow, PopupWindowButton} from 'main.popup';
import 'marketplace';
import 'applayout';

import "./bx-salescenter-app-add-payment-product";
import {SmsConfigureBlock} from "./components/sms-configure";
import {SmsAlertBlock} from "./components/sms-alert";
import {SmsSenderListBlock} from "./components/sms-sender-list";
import DeliverySelector from "./components/delivery-selector";
import {MixinTemplatesType} from "./components/templates-type-mixin";

let classModule = 'salescenter-app-payment-by-sms-item';

Vue.component(config.templateAddPaymentBySmsItem,
{
	data()
	{
		return {
			type: null,
			title: null,
			stage: null,
			itemData: null,
			set: null,
			infoHover: false,
			smsEditMessageMode: false,
			smsSenders: null,
			layout: {
				paymentInfo: null
			},
		}
	},

		props: ['data', 'index'],
		mixins:[MixinTemplatesType],
		components: {
			'sms-configure-block': SmsConfigureBlock,
			'sms-alert-block': SmsAlertBlock,
			'sms-sender-list-block': SmsSenderListBlock,
			'delivery-selector': DeliverySelector,
		},

	mounted()
	{
		this.layout.paymentInfo = this.paymentInfo;
		this.loadData();
	},

	computed:
	{
		getSmsSenderConfig()
		{
			return {
				url: this.$root.$app.urlSettingsSmsSenders,
				phone: this.$root.$app.options.contactPhone,
				sender: {
					code: this.$root.$app.sendingMethodDesc.provider
				},
			};
		},
		deliverySelectorConfig()
		{
			let deliveryServiceId = null;
			if (this.$root.$app.options.hasOwnProperty('shipmentData')
				&& this.$root.$app.options.shipmentData.hasOwnProperty('deliveryServiceId')
			)
			{
				deliveryServiceId = this.$root.$app.options.shipmentData.deliveryServiceId;
			}

			let responsibleId = null;
			if (this.$root.$app.options.hasOwnProperty('shipmentData')
				&& this.$root.$app.options.shipmentData.hasOwnProperty('responsibleId')
			)
			{
				responsibleId = this.$root.$app.options.shipmentData.responsibleId;
			}
			else
			{
				responsibleId = this.$root.$app.options.assignedById;
			}

			let deliveryPrice = null;
			if (this.$root.$app.options.hasOwnProperty('shipmentData')
				&& this.$root.$app.options.shipmentData.hasOwnProperty('deliveryPrice')
			)
			{
				deliveryPrice = this.$root.$app.options.shipmentData.deliveryPrice;
			}

			let expectedDeliveryPrice = null;
			if (this.$root.$app.options.hasOwnProperty('shipmentData')
				&& this.$root.$app.options.shipmentData.hasOwnProperty('deliveryPrice')
			)
			{
				expectedDeliveryPrice = this.$root.$app.options.shipmentData.expectedDeliveryPrice;
			}

			let relatedPropsValues = {};
			if (this.$root.$app.options.hasOwnProperty('orderPropertyValues')
				&& !Array.isArray(this.$root.$app.options.orderPropertyValues)
			)
			{
				relatedPropsValues = this.$root.$app.options.orderPropertyValues;
			}

			let relatedServicesValues = {};
			if (this.$root.$app.options.hasOwnProperty('shipmentData')
				&& this.$root.$app.options.shipmentData.hasOwnProperty('extraServicesValues')
				&& !Array.isArray(this.$root.$app.options.shipmentData.extraServicesValues)
			)
			{
				relatedServicesValues = this.$root.$app.options.shipmentData.extraServicesValues;
			}

			let isExistingItem = parseInt(this.$root.$app.options.associatedEntityId) > 0;

			return {
				isExistingItem,
				personTypeId: this.$root.$app.options.personTypeId,
				basket: this.order.basket,
				currencySymbol: this.$root.$app.options.currencySymbol,
				currency: this.order.currency,
				ownerTypeId: this.$root.$app.options.ownerTypeId,
				ownerId: this.$root.$app.options.ownerId,
				sessionId: this.$root.$app.options.sessionId,
				relatedPropsValues,
				relatedServicesValues,
				deliveryServiceId,
				responsibleId,
				deliveryPrice,
				expectedDeliveryPrice,
				editable: this.editable,
			};
		},
		countItems()
		{
			return this.order.basket.length;
		},

		listeners()
		{
			return {
				blur: this.adjustUpdateMessage,
				keydown: this.pressKey
			};
		},

		...Vuex.mapState({
			order: state => state.orderCreation
		})
	},

	methods:
	{
		pressKey(event)
		{
			if(event.code === "Enter")
			{
				this.adjustUpdateMessage();
				this.smsEditMessageMode = false;
			}
		},

		isHasLink()
		{
			return this.$root.$app.sendingMethodDesc.text.match(/#LINK#/)
		},

		getRawSmsMessage()
		{
			let text = this.$root.$app.sendingMethodDesc.text;
			
			return Text.encode(text);
		},

		getSmsMessage()
		{
			
			let link = `<span class="${classModule}-container-sms-content-message-link">${this.$root.$app.orderPublicUrl}</span><sapn class="${classModule}-container-sms-content-message-link-ref">xxxxx</sapn>` + ` `;
			let text = this.$root.$app.sendingMethodDesc.text;
			
			return Text.encode(text).replace(/#LINK#/g, link);
		},

		updateMessage()
		{
			this.$root.$app.sendingMethodDesc.text = this.$refs.smsMessageNode.innerText;
		},

		saveSmsTemplate(smsText)
		{
			BX.ajax.runComponentAction(
				"bitrix:salescenter.app",
				"saveSmsTemplate",
				{
					mode: "class",
					data: {
						smsTemplate: smsText,
					},
					analyticsLabel: 'salescenterSmsTemplateChange'
				}
			);
		},

		adjustUpdateMessage(event)
		{
			this.updateMessage();

			if(!this.isHasLink())
			{
				this.showPopupHint(this.$refs.smsMessageNode, Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_TEMPLATE_ERROR'), 2000);
			}
			else
			{
				this.saveSmsTemplate(this.$root.$app.sendingMethodDesc.text);
			}

			if(event && event.type === 'blur')
			{
				this.smsEditMessageMode = false;
			}
		},

		loadData()
		{
			this.type = this.data.type;
			this.title = this.data.title;
			this.stage = this.data.stage;
			this.set = this.data.set;
			this.itemData = this.data.itemData || null;

			if (this.isTemplateBeSendSms())
			{
				this.smsSenders = this.data.itemData.smsSenders;
				if (this.smsSenders)
				{
					this.setProviderDefault();
				}
			}
		},

		setProviderDefault()
		{
			this.$root.$app.sendingMethodDesc.provider = this.smsSenders.length !== 0 ? this.smsSenders[0].id : null;
		},

		isTemplateBeSendSms()
		{
			return this.type === 'BEE_SEND_SMS';
		},

		isTemplateSelectProduct()
		{
			return this.type === 'SELECT_PRODUCTS';
		},

		isTemplatePaySystem()
		{
			return this.type === 'PAY_SYSTEM';
		},

		isTemplateCashBox()
		{
			return this.type === 'CASHBOX';
		},

		isTemplateAutomationBox()
		{
			return this.type === 'AUTOMATION';
		},

		isTemplateDeliveryBox()
		{
			return this.type === 'DELIVERY';
		},

		isItemsSet()
		{
			return this.set;
		},

		refreshBasket()
		{
			this.$store.dispatch('orderCreation/refreshBasket');
		},

		changeBasketItem(item)
		{
			this.$store.dispatch('orderCreation/changeBasketItem', {
				index: item.index,
				fields: item.fields
			});
		},

		removeItem(item)
		{
			this.$store.dispatch('orderCreation/removeItem', {
				index: item.index
			});
			this.refreshBasket();
		},

		showItem(item, sliderOptions = {})
		{
			if (item.hasOwnProperty('width'))
			{
				sliderOptions.width = Number(item.width);
			}

			if (item.hasOwnProperty('type') && item.type === 'marketplace')
			{
				this.showRestApplication(item, sliderOptions);
			}
			else
			{
				sliderOptions['width'] = 835;
				Manager.openSlider(item.link, sliderOptions).then(() => this.getAjaxData());
			}
		},

		showRestApplication(item, sliderOptions = {})
		{
			if (item.hasOwnProperty('installedApp') && item.installedApp)
			{
				this.openRestAppLayout(item.id, item.code);
			}
			else
			{
				this.openMarketPlacePage(item.code, sliderOptions);
			}
		},

		openMarketPlacePage(code, sliderOptions = {})
		{
			let applicationUrlTemplate = "/marketplace/detail/#app#/";
			let url = applicationUrlTemplate.replace("#app#", encodeURIComponent(code));
			Manager.openSlider(url, sliderOptions).then(() => this.getAjaxData());
		},

		openRestAppLayout(applicationId, appCode)
		{
			BX.ajax.runComponentAction(
				"bitrix:salescenter.app",
				"getRestApp",
				{
					data: {
						code: appCode
					}
				}
			).then(function(response)
			{
				let app = response.data;
				if(app.TYPE === "A")
				{
					this.showRestApplication(appCode);
				}
				else
				{
					BX.rest.AppLayout.openApplication(applicationId);
				}
			}.bind(this)).catch(function(response)
			{
				this.restAppErrorPopup(" ", response.errors.pop().message);
			}.bind(this));
		},

		restAppErrorPopup(title, text)
		{
			let popup = new PopupWindow('rest-app-error-alert', null, {
				closeIcon: true,
				closeByEsc: true,
				autoHide: false,
				titleBar: title,
				content: text,
				zIndex: 16000,
				overlay: {
					color: 'gray',
					opacity: 30
				},
				buttons: [
					new PopupWindowButton({
						'id': 'close',
						'text': Loc.getMessage('SALESCENTER_JS_POPUP_CLOSE'),
						'events': {
							'click': function(){
								popup.close();
							}
						}
					})
				],
				events: {
					onPopupClose: function() {
						this.destroy();
					},
					onPopupDestroy: function() {
						popup = null;
					}
				}
			});
			popup.show();
		},

		isAddItemClass(item)
		{
			if (!item.hasOwnProperty('type'))
			{
				return true;
			}

			return !['paysystem', 'marketplace', 'delivery'].includes(item.type);
		},

		getAjaxData()
		{
			BX.ajax.runComponentAction(
				"bitrix:salescenter.app",
				"getAjaxData",
				{
					mode: "class",
					data: {
						type: this.type,
					}
				}
			).then(function(response) {
				if (response.data)
				{
					this.updateTemplate(response.data);
				}
			}.bind(this));
		},

		updateTemplate(data)
		{
			this.data.itemData = data;
			this.data.type = this.type;

			if (typeof this.data.itemData.isSet !== "undefined")
			{
				this.data.set = this.data.itemData.isSet;
				this.data.stage = this.data.itemData.isSet ? 'complete' : 'disabled';
			}

			if (this.isTemplatePaySystem())
			{
				this.data.title = this.data.itemData.isSet
					? Loc.getMessage('SALESCENTER_PAYSYSTEM_SET_BLOCK_TITLE')
					: Loc.getMessage('SALESCENTER_PAYSYSTEM_BLOCK_TITLE');
			}
			else if (this.isTemplateCashBox())
			{
				this.data.title = this.data.itemData.isSet
					? Loc.getMessage('SALESCENTER_CASHBOX_SET_BLOCK_TITLE')
					: Loc.getMessage('SALESCENTER_CASHBOX_BLOCK_TITLE');
			}

			this.loadData();
		},

		showPaySystemSettingsHint()
		{
			return !this.isItemsSet()
				&& this.isTemplatePaySystem();
		},

		showCashBoxSettingsHint()
		{
			return !this.isItemsSet()
				&& this.isTemplateCashBox();
		},

		showPopupHint(target, message, timer)
		{
			if(this.popup)
			{
				this.popup.destroy();
				this.popup = null;
			}

			if(!target && !message)
			{
				return;
			}

			this.popup = new Popup(null, target, {
				events: {
					onPopupClose: () => {
						this.popup.destroy();
						this.popup = null;
					}
				},
				darkMode: true,
				content: message,
				offsetLeft: target.offsetWidth,
			});

			if(timer)
			{
				setTimeout(() => {
					this.popup.destroy();
					this.popup = null;
				}, timer);
			}

			this.popup.show();
		},

		showSmsMessagePopupHint(target)
		{
			this.showPopupHint(target, Loc.getMessage('SALESCENTER_SMS_MESSAGE_HINT'))
		},

		showSelectPopup(target, options, type)
		{
			if(!target)
			{
				return;
			}

			this.selectPopup = new Popup(null, target, {
				closeByEsc: true,
				autoHide: true,
				width: 250,
				offsetTop: 5,
				events: {
					onPopupClose: () => { this.selectPopup.destroy() }
				},
				content: this.getSelectPopupContent(options, type)
			});

			this.selectPopup.show();
		},

		getSelectPopupContent(options, type)
		{
			if (!this.selectPopupContent)
			{
				this.selectPopupContent = Tag.render`<div class="salescenter-app-payment-by-sms-select-popup"></div>`;

				const onClickOptionHandler = (event) => {
					this.onChooseSelectOption(event, type);
				};

				for (let i = 0; i < options.length; i++)
				{
					const option = Tag.render`
						<div data-item-value="${options[i].id}" class="salescenter-app-payment-by-sms-select-popup-option" style="background-color:${options[i].color ? options[i].color : ''};" onclick="${onClickOptionHandler.bind(this)}">
							${options[i].name}
						</div>
					`;

					if (options[i].colorText === 'light') {
						option.style.color = '#fff';
					}

					Dom.append(option, this.selectPopupContent);
				}
			}


			return this.selectPopupContent;
		},

		onChooseSelectOption(event, type)
		{
			const currentOption = document.getElementById(type);
			currentOption.textContent = event.currentTarget.textContent;
			currentOption.style.color = event.currentTarget.style.color;
			currentOption.nextElementSibling.style.borderColor = event.currentTarget.style.color;
			currentOption.parentNode.style.background = event.currentTarget.style.backgroundColor;

			if (type === 'stageOnOrderPaid')
			{
				this.$root.$app.stageOnOrderPaid = event.currentTarget.getAttribute('data-item-value')
			}
			else if (type === 'delivery')
			{
				this.$root.$app.delivery = event.currentTarget.getAttribute('data-item-value')
			}

			this.selectPopup.destroy();
		},

		hidePopupHint()
		{
			if(this.popup)
			{
				this.popup.destroy();
			}
		},

		adjustSmsEditMessageMode()
		{
			this.smsEditMessageMode ?
				this.smsEditMessageMode = false :
				this.smsEditMessageMode = true;
		},

		isSmsEditMessageMode()
		{
			return this.smsEditMessageMode;
		},

		smsSenderConfigure()
		{
			Ajax.runComponentAction("bitrix:salescenter.app", "getSmsSenderList", {
						mode: "class"
			})
				.then((resolve)=>{
					if (BX.type.isObject(resolve.data) && Object.values(resolve.data).length > 0)
					{
						this.smsSenders = [];
						Object.values(resolve.data).forEach(
							item => this.smsSenders
								.push({
									name: item.name,
									id: item.id
								}));

						this.setProviderDefault();
					}
				});
		},

		smsSenderSelected(value)
		{
			this.$root.$app.sendingMethodDesc.provider = value;
		},

		hasContactPhone()
		{
			return !(this.getSmsSenderConfig.phone === '');
		},
		showCompanyContacts(e)
		{
			this.$root.$emit("on-show-company-contacts", e);
		},
	},

	template: `
	<div class="${classModule}" 
		:class="{ 
		'salescenter-app-payment-by-sms-item-current': stage === 'current', 
		'salescenter-app-payment-by-sms-item-disabled': stage === 'disabled',
		'salescenter-app-payment-by-sms-item-disabled-bg': !isItemsSet() && (isTemplatePaySystem() || isTemplateCashBox())
		}">
		<div class="${classModule}-counter">
			<div class="${classModule}-counter-rounder"></div>
			<div class="${classModule}-counter-line"></div>
			<div class="${classModule}-counter-number">
				<div v-if="stage === 'complete'" class="${classModule}-counter-number-checker"></div>
				<div class="${classModule}-counter-number-text">{{ index }}</div>
			</div>
		</div>
		<div class="${classModule}-title">
			<div class="${classModule}-title-text">{{ title }}</div>
			<div v-if="showPaySystemSettingsHint()" v-on:click="BX.Salescenter.Manager.openHowToConfigPaySystem(event)" class="${classModule}-title-info">${Loc.getMessage('SALESCENTER_PAYSYSTEM_BLOCK_SETTINGS_TITLE')}</div>
			<div v-if="showCashBoxSettingsHint()" v-on:click="BX.Salescenter.Manager.openHowToConfigCashBox(event)" class="${classModule}-title-info">${Loc.getMessage('SALESCENTER_CASHBOX_BLOCK_SETTINGS_TITLE')}</div>
			<div v-if="isTemplateBeSendSms()" v-on:click="showCompanyContacts(event)" class="${classModule}-title-info">${Loc.getMessage('SALESCENTER_LEFT_PAYMENT_COMPANY_CONTACTS')}</div>
		</div>
		<div class="${classModule}-container" v-bind:class="{ 'salescenter-app-payment-by-sms-item-container-offtop': isTemplateBeSendSms() }">
			<!--BEE_SEND_SMS-->
			<template v-if="isTemplateBeSendSms() && smsSenders.length === 0">
				<component :is="'sms-configure-block'"
					:config="getSmsSenderConfig"
					v-on:on-configure="smsSenderConfigure"
				>
					<template v-slot:sms-configure-text-alert>${Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_NOT_CONFIGURED')}</template>
					<template v-slot:sms-configure-text-setting>${Loc.getMessage('SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE')}</template>
				</component>
			</template>
			<template v-if="isTemplateBeSendSms() && !hasContactPhone() && smsSenders.length !== 0">
				<component :is="'sms-alert-block'">
					<template v-slot:sms-alert-text>${Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_ALERT_PHONE_EMPTY')}</template>
				</component>
			</template>
			<div v-if="isTemplateBeSendSms()" class="${classModule}-container-sms">
				<div class="${classModule}-container-sms-user">
					<div class="${classModule}-container-sms-user-avatar" v-bind:style="[ itemData.manager.photo ? { 'background-image': 'url(' + itemData.manager.photo + ')'} : null ]"></div>
					<div class="${classModule}-container-sms-user-name">{{itemData.manager.name}}</div>
				</div>
				<div class="${classModule}-container-sms-content">
					<div class="${classModule}-container-sms-content-message">
						<div 	v-if="smsEditMessageMode"
								contenteditable="true" 
								class="${classModule}-container-sms-content-message-text ${classModule}-container-sms-content-message-text-edit"
								v-on="listeners"
								v-html="getRawSmsMessage()"
								ref="smsMessageNode">
						</div>
						<div v-else contenteditable="false" class="${classModule}-container-sms-content-message-text" v-html="getSmsMessage()" v-on:mouseenter="showSmsMessagePopupHint($event.target)" v-on:mouseleave="hidePopupHint()">
						</div>
						<div class="${classModule}-container-sms-content-edit" v-bind:class="{ 'salescenter-app-payment-by-sms-item-container-sms-content-save': isSmsEditMessageMode() }" @click="adjustSmsEditMessageMode"></div>
					</div>				
					
					<component :is="'sms-sender-list-block'"
						:list="smsSenders"
						:config="getSmsSenderConfig"
						v-on:on-configure="smsSenderConfigure"
						v-on:on-selected="smsSenderSelected" 
					>
						<template v-slot:sms-sender-list-text-send-from>${Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER')}</template>
					</component>
					
				</div>
			</div>
			
			<!--SELECT_PRODUCTS-->
			<div v-if="isTemplateSelectProduct()" class="${classModule}-container-payment">
				<${config.templateAddPaymentName}/>
			</div>
			
			<!--PAY_SYSTEM-->
			<div v-if="isTemplatePaySystem()" class="${classModule}-container-payment">
				<template v-if="isItemsSet()">
					<div class="${classModule}-container-payment-inline">
						<div v-for="item in itemData.items" v-on:click="showItem(item, {width: 1000})" class="${classModule}-container-payment-item-text">{{item.name}}</div>
						<br><div v-on:click="showItem(itemData.paysystemPanel)" class="${classModule}-container-payment-item-text-add">{{itemData.paysystemPanel.name}}</div>
						<div v-if="itemData.paysystemForm" v-on:click="showItem(itemData.paysystemForm)" class="${classModule}-container-payment-item-text-add">{{itemData.paysystemForm.name}}</div>
					</div>
				</template>
				<template v-else>
					<div v-for="item in itemData.items" v-on:click="showItem(item)" v-bind:class="[isAddItemClass(item) ? '${classModule}-container-payment-item-added' : '', '${classModule}-container-payment-item']">
						<div class="${classModule}-container-payment-item-contet">
							<template v-if="item.img">
								<div class="${classModule}-container-payment-item-info" v-on:mouseenter="showPopupHint($event.target, item.info)" v-on:mouseleave="hidePopupHint()"></div>
								<img class="${classModule}-container-payment-item-img" :src="item.img">
							</template>
							<span v-else class="${classModule}-container-payment-item-added-text">{{ item.name }}</span>
						</div>
					</div>
				</template>
			</div>
			
			<!--CASHBOX-->
			<div v-if="isTemplateCashBox()" class="${classModule}-container-payment">
				<template v-if="isItemsSet()">
					<div class="${classModule}-container-payment-inline">
						<div v-for="item in itemData.items" v-on:click="showItem(item, {width: 1000})" class="${classModule}-container-payment-item-text">{{item.name}}</div>
						<br><div v-on:click="showItem(itemData.cashboxPanel)" class="${classModule}-container-payment-item-text-add">{{itemData.cashboxPanel.name}}</div>
						<div v-if="itemData.cashboxForm" v-on:click="showItem(itemData.cashboxForm)" class="${classModule}-container-payment-item-text-add">{{itemData.cashboxForm.name}}</div>
					</div>
				</template>
				<template v-else>
					<div v-for="item in itemData.items" v-on:click="showItem(item)" v-bind:class="[item.type !== 'cashbox' ? '${classModule}-container-payment-item-added' : '', '${classModule}-container-payment-item']">
						<div class="${classModule}-container-payment-item-contet">
							 <template v-if="item.type === 'cashbox'" >
								<div class="${classModule}-container-payment-item-info" v-on:mouseenter="showPopupHint($event.target, item.info)" v-on:mouseleave="hidePopupHint()"></div>
								<img class="${classModule}-container-payment-item-img" :src="item.img">
								<div v-if="item.showTitle" class="${classModule}-container-payment-item-title-text">{{ item.name }}</div>
							</template>
							<span v-else class="${classModule}-container-payment-item-added-text">{{ item.name }}</span>
						</div>
					</div>
				</template>
			</div>
			
			<!--AUTOMATION-->
			<div v-if="isTemplateAutomationBox()" class="${classModule}-container-payment">
				<div class="${classModule}-container-select">
					<div class="${classModule}-container-select-text">${Loc.getMessage('SALESCENTER_AUTOMATION_BLOCK_TEXT')}</div>
					<template v-for="item in itemData">
						<div v-if="item.selected" class="${classModule}-container-select-inner" v-bind:style="{background:item.color}" v-on:click="showSelectPopup($event.currentTarget, itemData, 'stageOnOrderPaid')">
							<div  class="${classModule}-container-select-item" id="stageOnOrderPaid">
								{{item.name}}
							</div>
							<span class="${classModule}-container-select-arrow"></span>
						</div>
					</template>
				</div>
			</div>
			
			<!--DELIVERY-->
			<div v-if="isTemplateDeliveryBox()" class="${classModule}-container-payment">
				<template v-if="itemData.isInstalled">
					<div class="${classModule}-container-select">
						<delivery-selector @delivery-settings-changed="getAjaxData" :config="deliverySelectorConfig"></delivery-selector>
					</div>
				</template>
				<template v-else>
					<div v-for="item in itemData.items" v-on:click="showItem(item)" v-bind:class="[isAddItemClass(item) ? '${classModule}-container-payment-item-added' : '', '${classModule}-container-payment-item']">
						<div class="${classModule}-container-payment-item-contet">
							<template v-if="item.img">
								<div class="${classModule}-container-payment-item-info" v-on:mouseenter="showPopupHint($event.target, item.info)" v-on:mouseleave="hidePopupHint()"></div>
								<div :style="{backgroundImage:'url('+encodeURI(item.img)+')'}" class="${classModule}-container-payment-item-img-del" :class="{ '${classModule}-container-payment-item-img-del-title' : item.showTitle}"></div>
								<div v-if="item.showTitle" class="${classModule}-container-payment-item-title-text">{{ item.name }}</div>
							</template>
							<span v-else class="${classModule}-container-payment-item-added-text">{{ item.name }}</span>
						</div>
					</div>
				</template>
			</div>
		</div>
	</div>
	`
});