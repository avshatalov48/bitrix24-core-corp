import { mapWritableState } from 'ui.vue3.pinia';

import '../css/roles-dialog-empty-group-stub.css';
import { type RolesDialogGroupDataEmptyStub } from '../roles-dialog';

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
					<div class="ai__roles-dialog_empty-group-stub-text">
						{{ description }}
					</div>
				</div>
			</div>
		`,
	};
};
