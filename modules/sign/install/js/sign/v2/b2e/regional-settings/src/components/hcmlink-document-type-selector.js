import { Loc } from 'main.core';
import { LocMixin, SignSelector } from 'sign.v2.b2e.vue-util';

const NotSelectedItem = Object.freeze({
	id: null,
	title: Loc.getMessage('SIGN_V2_B2E_REGIONAL_SETTINGS_NOT_SELECTED_ITEM'),
});

// @vue/component
export const HcmLinkDocumentTypeSelector = {
	name: 'HcmLinkDocumentTypeSelector',
	components: {
		SignSelector,
	},
	mixins: [LocMixin],
	props: {
		typeList: {
			type: Array,
			required: true,
			default: () => [],
		},
		selectedId: {
			type: [Number, null],
			required: true,
		},
	},
	emits: ['onSelected'],

	computed: {
		dropdownItems(): Array<{ id: number, title: string, }>
		{
			const typeList = this.typeList.length > 0 ? [NotSelectedItem, ...this.typeList] : [NotSelectedItem];

			return typeList.map(({ id, title }) => ({
				id,
				title,
			}));
		},
	},

	methods: {
		onSelected(id: number | null): void
		{
			this.$emit('onSelected', id);
		},
	},

	template: `
		<div class="sign-b2e-regional-settings__item">
		  <p class="sign-b2e-regional-settings__item-text">
		    <span>{{ loc('SIGN_V2_B2E_REGIONAL_SETTINGS_DOCUMENT_HCMLINK_UID_TYPE') }}</span>
		  </p>
		  <SignSelector
		      :items="dropdownItems"
			  :selectedId="selectedId"
			  @onSelect="onSelected"
		  />
		</div>
	`,
};
