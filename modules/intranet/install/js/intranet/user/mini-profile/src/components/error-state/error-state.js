import { ErrorStateDict, ErrorStateSettingByType } from './const';

// eslint-disable-next-line no-unused-vars
import type { ErrorStateType, ErrorStateSetting } from './type';

import './style.css';

// @vue/component
export const ErrorState = {
	name: 'ErrorState',
	props: {
		type: {
			/** @type ErrorStateType */
			type: String,
			default: ErrorStateDict.Default,
			validator: (value) => {
				return Object.values(ErrorStateDict).includes(value);
			},
		},
	},
	computed: {
		setting(): ?ErrorStateSetting
		{
			return ErrorStateSettingByType[this.type] ?? null;
		},
	},
	template: `
		<div 
			class="intranet-user-mini-profile__error-state"
			:class="setting?.class"
		>
			<div class="intranet-user-mini-profile__error-state-content">
				<div class="intranet-user-mini-profile__error-state__icon"></div>
				<div class="intranet-user-mini-profile__error-state__title">
					{{ setting?.title }}
				</div>
				<div class="intranet-user-mini-profile__error-state__description">
					{{ setting?.description }}
				</div>
			</div>
		</div>
	`,
};
