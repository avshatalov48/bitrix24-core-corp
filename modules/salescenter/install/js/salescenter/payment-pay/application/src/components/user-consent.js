import {ajax, Tag, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {EventType} from 'sale.payment-pay.const';

export default {
	props: {
		id: {
			type: Number,
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
		checked: {
			type: Boolean,
			default: false,
			required: false,
		},
	},
	methods: {
		loadBlockHtml() {
			let data = {
				fields: {
					id: this.id,
					title: this.title,
					isChecked: this.checked ? 'Y' : 'N',
					submitEventName: this.submitEventName,
				}
			};

			ajax.runComponentAction('bitrix:salescenter.payment.pay', 'userConsentRequest', {
				mode: 'ajax',
				data: data,
			}).then((response) => {

				if (!Type.isPlainObject(response.data) || !Type.isStringFilled(response.data.html) || !BX.UserConsent)
				{
					return;
				}

				let html, wrapper, control;

				html = response.data.html;
				wrapper = this.$refs.consentDiv;
				wrapper.appendChild(Tag.render`<div>${html}</div>`);
				control = BX.UserConsent.load(wrapper);

				EventEmitter.subscribe(control, BX.UserConsent.events.accepted, (event) => {
					EventEmitter.emit(EventType.consent.accepted);
				});
				EventEmitter.subscribe(control, BX.UserConsent.events.refused, (event) => {
					EventEmitter.emit(EventType.consent.refused);
				});

			});
		}
	},
	mounted() {
		this.loadBlockHtml();
	},
	// language=Vue
	template: `
		<div>
        	<div ref="consentDiv"/>
		</div>
	`,
};