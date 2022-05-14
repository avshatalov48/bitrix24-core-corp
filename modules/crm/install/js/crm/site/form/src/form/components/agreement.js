import {Factory} from "../../field/factory";
import * as Window from "../../window/registry";

const AgreementBlock = {
	mixins: [],
	props: ['messages', 'view', 'fields', 'visible', 'title', 'html', 'field', 'formId'],
	components: Object.assign(
		Window.Components.Definition,
		{
			'field': Factory.getComponent(),
		}
	),
	data: function () {
		return {
			field: null,
			visible: false,
			title: '',
			html: '',
			maxWidth: 600,
		}
	},
	template: `
		<div>
			<component v-bind:is="'field'"
				v-for="field in fields"
				v-bind:key="field.id"
				v-bind:field="field"
			></component>

			<b24-popup
				:mountId="formId"
				:show="visible" 
				:title="title" 
				:maxWidth="maxWidth" 
				:zIndex="199999"
				:scrollDown="true"
				:scrollDownText="messages.get('consentReadAll')"
				@hide="reject"
			>
				<div style="padding: 0 12px 12px;">
					<div v-html="html"></div>
					
					<div class="b24-form-btn-container" style="padding: 12px 0 0;">
						<div class="b24-form-btn-block"
							@click.prevent="apply"						
						>
							<button type="button" class="b24-form-btn">
								{{ messages.get('consentAccept') }}
							</button>
						</div>
						<div class="b24-form-btn-block"
							@click.prevent="reject"						
						>
							<button type="button" class="b24-form-btn b24-form-btn-white b24-form-btn-border">
								{{ messages.get('consentReject') }}
							</button>
						</div>
					</div>
				</div>
			</b24-popup>
		</div>
	`,
	mounted() {
		this.$root.$on('consent:request', this.showPopup);
	},
	computed: {
		position()
		{
			return this.view.position;
		}
	},
	methods: {
		apply()
		{
			this.field.applyConsent();
			this.field  = null;
			this.hidePopup();
		},
		reject()
		{
			this.field.rejectConsent();
			this.field  = null;
			this.hidePopup();
		},
		hidePopup()
		{
			this.visible = false;
		},
		showPopup(field)
		{
			let text = field.options.content.text || '';
			let div =  document.createElement('div');
			div.textContent = text;
			text = div.innerHTML.replace(/[\n]/g, '<br>');

			this.field = field;
			this.title = field.options.content.title;
			this.html = text || field.options.content.html;

			this.visible = true;

			setTimeout(() => {
				this.$root.$emit('resize');
			}, 0);
		},
	}
};

export {
	AgreementBlock,
}