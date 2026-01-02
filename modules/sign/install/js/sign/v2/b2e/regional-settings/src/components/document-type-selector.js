import { Hint } from 'ui.vue3.components.hint';
import { LocMixin, SignDropdownComponent } from 'sign.v2.b2e.vue-util';

// @vue/component
export const DocumentTypeSelector = {
	name: 'DocumentTypeSelector',
	components: {
		SignDropdownComponent,
		Hint,
	},
	mixins: [LocMixin],
	props: {
		typeList: {
			type: Array,
			required: true,
			default: () => [],
		},
		selectedId: {
			type: String,
			required: true,
		},
	},
	emits: ['onSelected'],

	computed: {
		dropdownItems(): Array<{ id: string, title: string, description: string }>
		{
			return this.typeList.map(({ code, description }) => ({
				id: code,
				title: code,
				caption: `(${description})`,
				entityId: 'b2e-document-code',
				tabId: 'b2e-document-codes',
			}));
		},
	},

	methods: {
		setType(item: { id: string }): void
		{
			this.$emit('onSelected', item.id);
		},
	},

	template: `
		<div class="sign-b2e-regional-settings__item">
			<p class="sign-b2e-regional-settings__item-text">
				<span>{{ loc('SIGN_DOCUMENT_SETUP_TYPE') }}</span>
			  	<Hint class="sign-b2e-regional-settings__hint" :text="loc('SIGN_DOCUMENT_SETUP_TYPE_HINT')"/>
			</p>
			<SignDropdownComponent 
				class="sign-b2e-regional-settings-dropdown"
				:entities="[{ id: 'b2e-document-code', searchFields: [{ name: 'caption', system: true }] }]"
				:tabs="[{ id: 'b2e-document-codes', title: ' ' }]"
				:items="dropdownItems"
				:isEnableSearch="true"
				:isWithCaption="true"
				:selectedId="selectedId"
				@onSelected="setType"
			/>
		</div>
	`,
};
