import './styles/empty-list-item.css';

export const EmptyListItem = {
	name: 'tabEmptyListItem',

	props: {
		title: {
			type: String,
			required: true,
		},
		imageClass: {
			type: String,
			required: true,
		},
		withAddPermission: {
			type: Boolean,
			required: false,
			default: true,
		},
	},

	template: `
		<div :class="['hr-department-detail-content__tab-empty-list_item-wrapper', { '--with-add': withAddPermission }]">
			<div class="hr-department-detail-content__tab-empty-list_item-image" :class="imageClass"/>
			<div class="hr-department-detail-content__tab-empty-list_item-content">
				<div class="hr-department-detail-content__tab-empty-list_item-title">
					{{ title }}
				</div>
			</div>
		</div>
	`,
};