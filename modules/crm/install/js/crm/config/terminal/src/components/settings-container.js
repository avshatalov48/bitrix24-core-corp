export const SettingsContainer = {
	props: {
		title: String,
		iconStyle: String,
		collapsed: Boolean,
	},
	methods: {
		onTitleClicked()
		{
			this.$emit('titleClick');
		},
	},

	template: `
	<div class="settings-container">
		<div
			class="ui-slider-heading-4 settings-container-title"
			v-bind:class="{ 'settings-container-title-collapsed': collapsed }"
			v-on:click="onTitleClicked"
		>
			<div :class="iconStyle"></div>
			{{ title }}
		</div>

		<div class="settings-section-list" v-bind:class="{ 'settings-section-list-collapsed': collapsed }">
			<slot></slot>
		</div>
	</div>
	`,
};
