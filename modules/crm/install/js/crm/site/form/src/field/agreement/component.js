import "./style.css"
import * as Mixins from "../base/components/mixins";

const FieldAgreement = {
	mixins: [Mixins.MixinField],
	template: `	
		<label class="b24-form-control-container">
			<input type="checkbox" 
				v-model="field.item().selected"
				@blur="$emit('input-blur', this)"
				@focus="$emit('input-focus', this)"
				@click.capture="requestConsent"
				onclick="this.blur()"
			>
			<span class="b24-form-control-desc">
				<a :href="href" :target="target"
					@click="requestConsent" 
				>{{ field.label }}</a>
			</span>
			<span v-show="field.required" class="b24-form-control-required">*</span>
			<field-item-alert v-bind:field="field"></field-item-alert>	
		</label>
	`,
	computed: {
		target()
		{
			return this.field.isLink() ? '_blank' : null;
		},
		href()
		{
			return this.field.isLink() ? this.field.options.content : null;
		},
	},
	methods: {
		requestConsent(e)
		{
			this.field.consentRequested = true;

			if (this.field.isLink())
			{
				this.field.applyConsent();
				return true;
			}

			e ? e.preventDefault() : null;
			e ? e.stopPropagation() : null;
			this.$root.$emit('consent:request', this.field);
			return false;
		},
	}
};

export {
	FieldAgreement,
}