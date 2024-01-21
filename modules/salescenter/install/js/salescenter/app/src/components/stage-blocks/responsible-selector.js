import { ajax as Ajax, Loc, Text, Event } from 'main.core';
import { TagSelector } from 'ui.entity-selector';
import { Block } from 'salescenter.component.stage-block';
import { StageMixin } from './stage-mixin';

const ResponsibleSelector = {
	props: {
		status: {
			type: String,
			required: true,
		},
		counter: {
			type: Number,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		selectedUser: {
			type: Number,
			required: false,
		},
		responsible: {
			type: Object,
			required: false,
		},
		isMobileInstalledForResponsible: {
			type: Boolean,
			required: false,
		},
		editable: {
			type: Boolean,
			required: true,
		},
		contact: {
			type: Object,
			required: true,
		},
		hintTitle: {
			type: String,
			required: false,
		},
	},

	mixins: [StageMixin],

	components: {
		'stage-block-item':	Block,
	},

	computed: {
		isCreateMode()
		{
			return this.$root.$app.options.templateMode === 'create';
		},
		configForBlock()
		{
			return {
				counter: this.counter,
				titleItems: [],
				installed: true,
				collapsible: false,
				checked: this.counterCheckedMixin,
				showHint: true,
			};
		},
		contactInfo()
		{
			if (this.contact.name || this.contact.phone)
			{
				return Loc.getMessage(
					'SALESCENTER_PAYMENT_RESPONSIBLE_SELECTOR_BLOCK_CONTACT_INFO_TEMPLATE',
					{
						'[span]': '<span class="salescenter-responsible-block-contact">',
						'[/span]': '</span>',
						'#CONTACT_NAME#': Text.encode(this.contact.name),
						'#CONTACT_PHONE#': Text.encode(this.contact.phone),
					},
				);
			}

			return '';
		},
		addEmployee()
		{
			return Loc.getMessage(
				'SALESCENTER_PAYMENT_RESPONSIBLE_SELECTOR_BLOCK_ADD_EMPLOYEE',
				{
					'[link]': '<span id="add-employee-link" class="salescenter-responsible-block-add-employee-link">',
					'[/link]': '</span>',
				},
			);
		},
		responsibleHeaderText()
		{
			return this.isCreateMode ? Loc.getMessage('SALESCENTER_PAYMENT_RESPONSIBLE_SELECTOR_BLOCK_RESPONSIBLE_CREATE') : Loc.getMessage('SALESCENTER_PAYMENT_RESPONSIBLE_SELECTOR_BLOCK_RESPONSIBLE_VIEW');
		},
		avatarStyle()
		{
			const url = this.responsible.photo ? { 'background-image': `url(${this.responsible.photo})` } : null;

			return [url];
		},
	},

	mounted()
	{
		this.$store.commit('orderCreation/setMobileInstalledForResponsible', this.isMobileInstalledForResponsible ?? true);

		const addEmployeeLink = document.getElementById('add-employee-link');
		if (addEmployeeLink)
		{
			Event.bind(addEmployeeLink, 'click', (event) => {
				this.$root.$app.openMobileAppPopup();
			});
		}

		if (!this.editable)
		{
			return;
		}

		const selectorRoot = document.getElementById('salescenterResponsibleSelector');

		const dialogOptions = {
			context: 'salescenter_responsible',
			entities: [
				{
					id: 'user',
				},
			],
			events: {
				'Item:onSelect': (event) => {
					this.onResponsibleChanged(event);
				},
			},
		};

		if (this.selectedUser)
		{
			dialogOptions.preselectedItems = [
				['user', this.selectedUser],
			];
			dialogOptions.undeselectedItems = [
				['user', this.selectedUser],
			];
		}

		const tagSelector = new TagSelector({
			multiple: false,
			dialogOptions,
			deselectable: false,
		});

		tagSelector.renderTo(selectorRoot);
	},

	methods: {
		onResponsibleChanged(event)
		{
			const { item } = event.getData();

			item.setDeselectable(false);
			event.target?.tagSelector?.updateTags();
			this.$store.commit('orderCreation/setPaymentResponsibleId', item.getId());

			const isSubmitEnabled = this.$store.getters['orderCreation/isAllowedSubmit'];
			this.$store.commit('orderCreation/disableSubmit');
			Ajax.runAction('salescenter.terminalResponsible.getUserMobileInfo', {
				data: {
					userId: item.getId(),
				},
			}).then((result) => {
				this.$store.commit('orderCreation/setMobileInstalledForResponsible', result.data.isMobileInstalled);
				if (!result.data.isMobileInstalled)
				{
					this.$store.commit('orderCreation/setResponsiblePhoneNumbers', result.data.phones);
				}

				if (isSubmitEnabled)
				{
					this.$store.commit('orderCreation/enableSubmit');
				}

				this.$emit('on-responsible-changed', event);
			});
		},
		onItemHint(event)
		{
			BX.Salescenter.Manager.openHowTerminalWorks();
		},
	},

	created()
	{
		if (this.selectedUser)
		{
			this.$store.commit('orderCreation/setPaymentResponsibleId', this.selectedUser);
		}
	},

	// language=Vue
	template: `
		<stage-block-item
			:class="statusClassMixin"
			:config="configForBlock"
			v-on:on-item-hint="onItemHint"
		>
			<template v-slot:block-title-title>{{ title }}</template>
			<template v-slot:block-hint-title>{{ hintTitle }}</template>
			<template v-slot:block-container>
				<div :class="containerClassMixin">
					<p class="salescenter-responsible-block-responsible-header">{{ responsibleHeaderText }}</p>
					<div id="salescenterResponsibleSelector" v-if="editable"></div>
					<div class="salescenter-responsible-block-responsible-wrapper" v-else>
						<div
							class="salescenter-app-payment-by-sms-item-container-sms-user-avatar salescenter-responsible-block-responsible-photo"
							:style="avatarStyle"></div>
						<div class="salescenter-responsible-block-responsible-name-wrapper">
							<a class="salescenter-responsible-block-responsible-name">{{ responsible.fullName }}</a>
							<div class="salescenter-responsible-block-responsible-position-wrapper">
								<span class="salescenter-responsible-block-responsible-position">{{ responsible.position }}</span>
							</div>
						</div>
					</div>
					<div v-html="contactInfo" class="salescenter-responsible-block-contact-info" v-if="!isCreateMode"></div>
					<div v-html="addEmployee" class="salescenter-responsible-block-add-employee" v-else></div>
				</div>
			</template>
		</stage-block-item>
	`,
};

export
{
	ResponsibleSelector,
};
