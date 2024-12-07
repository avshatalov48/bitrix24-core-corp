import { Text } from 'main.core';

export const RestAppLayoutBlocks = {
	props: {
		itemTypeId: {
			type: Number,
		},
		itemId: {
			type: Number,
		},
		restAppInfo: {
			title: String,
			clientId: String,
		},
		contentBlocks: {
			type: Object,
		},
	},
	computed: {
		restAppTitle(): string
		{
			return Text.encode(this.restAppInfo.title);
		},
		clientId(): string
		{
			return Text.encode(this.restAppInfo.clientId);
		},
	},
	template: `
		<div class="crm_timeline__rest_app_layout_blocks" :data-app-name="restAppTitle" :data-rest-client-id="clientId">
			<div class="crm-timeline__card-container_block" v-for="contentBlock in contentBlocks">
				<component :is="contentBlock.rendererName" v-bind="contentBlock.properties" ref="contentBlocks" />
			</div>
		</div>
	`,
};
