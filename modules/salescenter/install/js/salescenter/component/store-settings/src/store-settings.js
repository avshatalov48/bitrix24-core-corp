import {Vue} from 'ui.vue';
import {rest as Rest} from 'rest.client';
import {Manager} from 'salescenter.manager';
import {PopupMenuWindow} from 'main.popup';
import {Text} from 'main.core';

import {PreviewBlock} from 'salescenter.component.store-preview';
import {RequisiteBlock} from 'salescenter.component.mycompany-requisite-settings';

import 'ui.design-tokens';
import 'ui.fonts.opensans';
import './store-settings.css';


export class StoreSettings
{
	constructor(containerId, parameters)
	{
		parameters = parameters || {};
		let data = {};
		data.companyId = BX.prop.get(parameters, "companyId", 0);
		data.companyTitle = BX.prop.get(parameters, "companyTitle", '');
		data.previewLang = BX.prop.get(parameters, "previewLang", '');
		data.companyPhone = '';
		data.phoneIdSelected = BX.prop.get(parameters, "phoneIdSelected", 0);
		data.companyPhoneList = BX.prop.get(parameters, "companyPhoneList", '');
		data.phoneValueSelected = BX.prop.get(parameters, "phoneValueSelected", '');
		data.originPhoneIdSelected = BX.prop.get(parameters, "phoneIdSelected", 0);

		this.componentName = 'bitrix:salescenter.company.contacts';
		this.slider = BX.SidePanel.Instance.getTopSlider();

		const context = this;

		Vue.create({
			el: '#' + containerId,
			data: data,
			components: {
				'my-company-requisite-block': RequisiteBlock,
				'store-preview-block': PreviewBlock,
			},
			computed:
				{
					loc() {
						return Vue.getFilteredPhrases('SC_STORE_SETTINGS_')
					},
					isNew()
					{
						return (this.companyId === undefined || parseInt(this.companyId)<=0);
					},
					getPhonesList()
					{
						return BX.type.isArray(this.companyPhoneList) ? this.companyPhoneList:[];
					},
					getSelectedPhoneId()
					{
						return parseInt(this.phoneIdSelected)>0 ? this.phoneIdSelected:0;
					},
					getSelectedPhoneValue()
					{
						return this.phoneValueSelected === '' ? this.loc.SC_STORE_SETTINGS_COMPANY_PHONE_DEFAULT:this.phoneValueSelected;
					},
					getSelectedPhoneNumber()
					{
						let phones = this.getPhonesList;
						let number = '';
						if (phones.length > 0)
						{
							phones.forEach(item => {
								if(item.id === this.getSelectedPhoneId)
								{
									number = item.value;
								}
							})
						}
						return number
					},
					getOriginSelectedPhoneId()
					{
						let phones = this.getPhonesList;
						let id = 0;
						if (phones.length > 0)
						{
							phones.forEach(item => {
								if(item.id === this.originPhoneIdSelected)
								{
									id = item.id;
								}
							})
						}
						return id;
					},
				},
			created()
			{
				this.$app = context;
			},
			mounted()
			{
				BX.UI.Hint.init(BX('salescenter-company-contacts-wrapper'));
			},
			methods:
				{
					requisiteOpenSlider(e)
					{
						let url = '/crm/company/details/'+ this.companyId +'/?init_mode=edit';
						Manager.openSlider(url).then(() => this.refresh());
					},
					showPopupMenu(e)
					{
						let phoneItems = [];
						let setItem = (ev, data) => {
							this.phoneIdSelected = data.id;
							this.popupMenu.close()};

						let phones = this.getPhonesList;

						if (phones.length > 0)
						{
							phoneItems.push(
								{
									text: this.loc.SC_STORE_SETTINGS_COMPANY_PHONE_DEFAULT,
									id: '0',
									onclick: setItem.bind(this)
								}
							);
							phoneItems.push(
								{
									text: this.loc.SC_STORE_SETTINGS_COMPANY_PHONE_DELIMETER,
									id: '-1',
									delimiter: true,
								}
							);

							phones.forEach(item => {
								phoneItems.push(
									{
										text: Text.encode(item.value),
										id: item.id,
										onclick: setItem.bind(this)
									}
								)
							})
						}

						this.popupMenu = new PopupMenuWindow({
							bindElement: e.target,
							minWidth: e.target.offsetWidth,
							items: phoneItems
						});

						this.popupMenu.show();
					},
					reset()
					{
						this.companyPhoneList = [];
						this.phoneIdSelected = 0;
						this.companyTitle = '';
					},
					refresh()
					{
						Rest.callMethod('crm.company.get',{
							id:this.companyId
						})
							.then((result) => {
								let answer = result.data();

								this.reset();

								this.companyTitle = BX.prop.get(answer, "TITLE", '');

								let phones = BX.prop.get(answer, "PHONE", []);

								if (BX.type.isObject(phones) && Object.values(phones).length > 0)
								{
									Object.values(phones).forEach(item => {
										this.companyPhoneList.push({
											id: item.ID,
											value: item.VALUE
										})
									});
								}

								this.phoneIdSelected = this.getOriginSelectedPhoneId;
							});
					},
					save()
					{
						if(this.isNew)
						{
							this.addCompany();
						}
						else
						{
							this.updateCompany();
						}
					},
					updateCompany()
					{
						this.$app.query(
							'updateCompanyContacts',
							{
								id: this.companyId,
								fields:{
									title: this.companyTitle,
									phoneIdSelected: this.phoneIdSelected
								}
							},
							'salescenterContactsCompanyUpdate'
						)
							.then((response) => {
								this.$app.closeApplication();
							})
							.catch((result) => {
								let errors = BX.prop.getArray(result, "errors", []);
								if (BX.type.isArray(errors) && errors.length > 0)
								{
									let error = BX.prop.get(errors[0], 'message','');
									Manager.showNotification(error);
								}
							});
					},
					addCompany()
					{
						this.$app.query(
							'saveCompanyContacts',
							{
								fields:{
									title: this.companyTitle,
									phone: this.companyPhone,
								}
							},
							'salescenterContactsCompanyAdd'
						)
							.then((response) => {
								this.$app.closeApplication();
							})
							.catch((result) => {
								let errors = BX.prop.getArray(result, "errors", []);
								if (BX.type.isArray(errors) && errors.length > 0)
								{
									let error = BX.prop.get(errors[0], 'message','');
									Manager.showNotification(error);
								}
							});
					},
					close()
					{
						this.$app.closeApplication();
					},
				},
			watch:
				{
					phoneIdSelected()
					{
						this.phoneValueSelected = this.getSelectedPhoneNumber;
					}
				},
			template: `   
				<div class="salescenter-company-contacts-wrapper" id="salescenter-company-contacts-wrapper">
					<div class="salescenter-company-contacts-item">
						<div class="salescenter-company-contacts-area">
							<div class="salescenter-company-contacts-area-item">
								<div class="ui-ctl-label-text">
									{{loc.SC_STORE_SETTINGS_COMPANY_NAME}}
									<span class="ui-hint" :data-hint="loc.SC_STORE_SETTINGS_COMPANY_NAME_HINT"></span>
								</div>
								<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
									<input type="text" class="ui-ctl-element" name="name" v-model="companyTitle">
								</div>
							</div>
							
							<div class="salescenter-company-contacts-area-item">
								<div class="ui-ctl-label-text">{{loc.SC_STORE_SETTINGS_COMPANY_PHONE_NUMBER}}</div>
								<template v-if="isNew">
									<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
										<input type="text" class="ui-ctl-element" name="phone" v-model="companyPhone">
									</div>								
								</template>
								<template v-else>
									<template v-if="getPhonesList.length === 0">
										<div class="salescenter-company-contacts-text">{{loc.SC_STORE_SETTINGS_COMPANY_PHONE_EMPTY}}<a href="javascript:void(0)" @click="requisiteOpenSlider()">{{loc.SC_STORE_SETTINGS_COMPANY_PHONE_ADD}}</a></div>
									</template>
									<template v-else>
										<div class="ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-dropdown salescenter-company-contacts-area-item-input" @click="showPopupMenu($event)">
											<div class="ui-ctl-element">{{this.getSelectedPhoneValue}}</div>
											<div class="ui-ctl-after ui-ctl-icon-angle"></div>
										</div>
										<div class="salescenter-company-contacts-controls">
											<div class="salescenter-company-contacts-link" @click="requisiteOpenSlider()">{{loc.SC_STORE_SETTINGS_COMPANY_PHONE_ADD_SMALL}}</div>
										</div>
									</template>
								</template>
							</div>
						</div>
						<my-company-requisite-block 
							:isNewCompany="isNew"
							:companyId="companyId"
							v-on:on-mycompany-requisite-settings="refresh"/>
						<div class="salescenter-company-contacts-panel">
							<button class="ui-btn ui-btn-md ui-btn-success" @click="save">{{loc.SC_STORE_SETTINGS_SAVE}}</button>
							<button class="ui-btn ui-btn-md ui-btn-link" @click="close">{{loc.SC_STORE_SETTINGS_CANCEL}}</button> 
						</div>
					</div>
					<store-preview-block :options="{lang: this.previewLang}"/>
			</div>`,
		});
	}

	query(action, data, analyticsLabel)
	{
		let result;
		result = BX.ajax.runComponentAction(this.componentName, action, {
			mode: 'class',
			data: data,
			analyticsLabel: analyticsLabel
		});


		return result;
	}

	closeApplication()
	{
		if(this.slider)
		{
			this.slider.close();
		}
	}

	static showNotification(message)
	{
		if(!message)
		{
			return;
		}
		BX.loadExt('ui.notification').then(() =>
		{
			BX.UI.Notification.Center.notify({
				content: message
			});
		});
	}

}