import { mapMutations } from 'ui.vue3.vuex';

import { HelpDesk, Model } from 'booking.const';
import type { ResourceCreationType } from 'booking.model.resource-creation-wizard';
import { helpDesk } from 'booking.lib.help-desk';
import { ResourceType } from './resource-type/resource-type';
import './resource-category-card.css';

export const ResourceCategoryCard = {
	name: 'ResourceCategoryCard',
	computed: {
		resourceTypes(): ResourceCreationType[]
		{
			return this.$store.state[Model.ResourceCreationWizard].advertisingResourceTypes;
		},
	},
	methods: {
		...mapMutations(
			Model.ResourceCreationWizard,
			['updateResource', 'updateFetching'],
		),
		loadResourceTypes()
		{
			this.updateFetching(true);

			setTimeout(() => {
				this.updateFetching(false);
			}, 1);
		},
		async selectResourceType({ relatedResourceTypeId }: ResourceCreationType): Promise<void>
		{
			this.updateResource({
				typeId: relatedResourceTypeId,
			});
			await this.$store.dispatch(`${Model.ResourceCreationWizard}/nextStep`);
		},
		showHelpDesk(): void
		{
			helpDesk.show(
				HelpDesk.ResourceType.code,
				HelpDesk.ResourceType.anchorCode,
			);
		},
	},
	beforeMount()
	{
		this.loadResourceTypes();
	},
	components: {
		ResourceType,
	},
	template: `
		<div class="resource-category-card">
			<div class="resource-category-card__header">
				<div class="resource-category-card__header__title">
					{{ loc('BRCW_CHOOSE_CATEGORY') }}
				</div>
				<div class="booking--resource-category-card__header__sub-title">
					{{ loc('BRCW_CHOOSE_CATEGORY_DESCRIPTION') }}
					<span
						class="booking--rcw--more"
						@click="showHelpDesk"
					>
						{{ loc('BRCW_CHOOSE_CATEGORY_DESCRIPTION_MORE') }}
					</span>
				</div>
			</div>
			<div class="resource-category-card__content resource-creation-wizard__form">
				<ResourceType
					v-for="resourceType in resourceTypes"
					:key="resourceType.code"
					:resource-type
					:data-id="'brcw-resource-type-list-' + resourceType.code"
					@selected="selectResourceType"
				/>
			</div>
		</div>
	`,
};
