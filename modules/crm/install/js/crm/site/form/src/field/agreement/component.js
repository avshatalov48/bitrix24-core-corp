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
			<span v-if="field.isLink()" class="b24-form-control-desc"
				@click.capture="onLinkClick"
				v-html="link"
			></span>
			<span v-else class="b24-form-control-desc">
				<span class="b24-form-field-agreement-link">{{ field.label }}</span>
			</span>
			<span v-show="field.required" class="b24-form-control-required">*</span>
			<field-item-alert v-bind:field="field"></field-item-alert>	
		</label>
	`,
	computed: {
		link()
		{
			let url = this.field.options.content.url.trim();
			if (!/^http:|^https:/.test(url))
			{
				url = 'https://' + url;
			}

			const node = document.createElement('div');
			node.textContent = url;
			url = node.innerHTML;

			node.textContent = this.field.label;
			const label = node.innerHTML;

			return label
				.replace('%', `<a href="${url}" target="_blank" class="b24-form-field-agreement-link">`)
				.replace('%', '</a>');
		},
	},
	methods: {
		onLinkClick(e)
		{
			if (e.target.tagName.toUpperCase() === 'A')
			{
				return this.requestConsent(e);
			}
		},
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