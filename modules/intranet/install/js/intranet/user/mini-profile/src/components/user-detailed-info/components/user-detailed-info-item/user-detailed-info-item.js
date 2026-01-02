import './style.css';

// @vue/component
export const UserDetailedInfoItem = {
	name: 'UserDetailedInfoItem',
	props: {
		title: {
			type: String,
			required: true,
		},
		type: {
			type: String,
			required: false,
			default: 'general',
		},
	},
	template: `
		<div class="intranet-user-mini-profile__detailed-info-item" :data-test-id="'usermp_detailed-info-' + type">
			<div class="intranet-user-mini-profile__detailed-info-item__title" :data-test-id="'usermp_detailed-info-' + type + '-title'">
				{{ title }}
			</div>
			<div class="intranet-user-mini-profile__detailed-info-item__value" :data-test-id="'usermp_detailed-info-' + type + '-value'">
				<slot></slot>
			</div>
		</div>
	`,
};
