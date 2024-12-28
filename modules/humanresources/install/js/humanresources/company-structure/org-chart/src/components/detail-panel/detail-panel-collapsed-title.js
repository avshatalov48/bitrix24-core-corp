export const DetailPanelCollapsedTitle = {
	name: 'detailPanelCollapsedTitle',

	props:
	{
		title:
		{
			type: String,
			required: true,
		},
		avatars:
		{
			type: Array,
			required: true,
		},
	},

	computed:
	{
		maxVisibleAvatarsCount(): Number
		{
			return 2;
		},
		additionalCount(): Number
		{
			return this.avatars.length > this.maxVisibleAvatarsCount ? this.avatars.length - this.maxVisibleAvatarsCount : 0;
		},
	},

	template: `
		<div class="humanresources-detail-panel__collapsed-title">
			<template v-for="(avatar, index) in avatars">
				<img
					v-if="index < this.maxVisibleAvatarsCount"
					:key="index"
					:src="encodeURI(avatar)"
					class="humanresources-detail-panel__collapsed-title-avatar"
				/>
			</template>
			<div
				v-if="avatars.length > maxVisibleAvatarsCount"
				class="humanresources-detail-panel__collapsed-title-avatar --additional"
			>
			 +{{ additionalCount }}	
			</div>
			<div class="humanresources-detail-panel__title">{{ title }}</div>
		</div>
	`,
};
