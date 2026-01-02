import { ajax, Tag, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { EventType } from 'sale.payment-pay.const';
import { BitrixVue } from 'ui.vue';

BitrixVue.component('salescenter-payment_pay-components-payment_system-user_consent', {
	props: {
		items: {
			type: Array,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		submitEventName: {
			type: String,
			required: true,
		},
		autoSave: {
			type: Boolean,
			default: false,
			required: false,
		},
		originatorId: {
			type: String,
			default: '',
			required: false,
		},
		originId: {
			type: String,
			default: '',
			required: false,
		},
	},
	mounted() {
		this.loadBlockHtml();
	},
	methods: {
		loadBlockHtml() {
			if (this.items.length === 0)
			{
				return;
			}

			const data = {
				fields: {
					items: this.items,
					id: this.id,
					title: this.title,
					isChecked: this.checked ? 'Y' : 'N',
					submitEventName: this.submitEventName,
					autoSave: this.autoSave ? 'Y' : 'N',
					originatorId: this.originatorId,
					originId: this.originId,
				},
			};

			ajax.runComponentAction(
				'bitrix:salescenter.payment.pay',
				'userConsentRequest',
				{
					mode: 'ajax',
					data,
				},
			).then((response) => {
				if (!Type.isPlainObject(response.data) || !Type.isStringFilled(response.data.html) || !BX.UserConsent)
				{
					return;
				}

				const html = response.data.html;
				const wrapper = this.$refs.consentDiv;
				wrapper.appendChild(Tag.render`<div>${html}</div>`);
				BX.UserConsent.loadAll(wrapper);
				const controls = BX.UserConsent.getItems();
				controls.forEach((control) => {
					EventEmitter.subscribe(control, BX.UserConsent.events.afterAccepted, (event) => {
						EventEmitter.emit(EventType.consent.accepted, event);
					});
					EventEmitter.subscribe(control, BX.UserConsent.events.refused, (event) => {
						EventEmitter.emit(EventType.consent.refused, event);
					});
				});
			});
		},
	},
	// language=Vue
	template: `
		<div class="salescenter-user-consent-list">
			<div ref="consentDiv"/>
		</div>
	`,
});
