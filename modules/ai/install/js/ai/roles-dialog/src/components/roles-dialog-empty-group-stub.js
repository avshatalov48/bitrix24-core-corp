import { mapWritableState } from 'ui.vue3.pinia';

import '../css/roles-dialog-empty-group-stub.css';
import { type RolesDialogGroupDataEmptyStub } from '../roles-dialog';
import { Loc, Event, Runtime } from 'main.core';
import type { AnalyticsOptions } from 'ui.analytics';

const customDescription = Loc.getMessage('AI_COPILOT_ROLES_EMPTY_CUSTOM_GROUP', {
	'#LINK#': '<a @click.prevent="openRolesLibrary" href="#">',
	'#/LINK#': '</a>',
});

export const getRolesDialogEmptyGroupStubWithStates = (States) => {
	return {
		computed: {
			...mapWritableState(States.useGlobalState, {
				currentGroup: 'currentGroup',
			}),
			emptyStubData(): RolesDialogGroupDataEmptyStub {
				return this.currentGroup.customData.emptyStubData;
			},
			groupCode(): string {
				return this.currentGroup.id;
			},
			title(): string {
				return this.emptyStubData.title;
			},
			description(): string {
				return this.emptyStubData.description;
			},
		},
		methods: {
			async sendAnalytics(): void
			{
				try
				{
					const { sendData } = await Runtime.loadExtension('ui.analytics');

					const sendDataOptions: AnalyticsOptions = {
						event: 'open_list',
						status: 'success',
						tool: 'ai',
						category: 'roles_saving',
						c_section: 'roles_picker',
					};

					sendData(sendDataOptions);
				}
				catch (e)
				{
					console.error('AI: RolesDialog: Can\'t send analytics', e);
				}
			},
			openRolesLibrary() {
				if (BX.SidePanel)
				{
					this.sendAnalytics();
					BX.SidePanel.Instance.open(
						'/bitrix/components/bitrix/ai.role.library.grid/slider.php',
						{
							cacheable: false,
							events: {
								onCloseStart: () => {
									Event.EventEmitter.emit('update');
								},
							},
						},
					);
				}
				else
				{
					window.location.href = '/bitrix/components/bitrix/ai.prompt.library.grid/slider.php';
				}
			},
		},
		template: `
			<div class="ai__roles-dialog_empty-group-stub">
				<div class="ai__roles-dialog_empty-group-stub-content">
					<div
						class="ai__roles-dialog_empty-group-stub-image"
						:class="'--' + groupCode"
					></div>
					<h3 class="ai__roles-dialog_empty-group-stub-title">
						{{ title }}
					</h3>
					<div v-if="groupCode !== 'customs'" class="ai__roles-dialog_empty-group-stub-text">
						{{ description }}
					</div>
					<div v-else class="ai__roles-dialog_empty-group-stub-text">
						${customDescription}
					</div>
				</div>
			</div>
		`,
	};
};
