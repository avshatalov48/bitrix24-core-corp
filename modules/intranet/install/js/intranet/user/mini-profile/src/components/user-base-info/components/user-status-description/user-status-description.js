import { DateTimeFormat } from 'main.date';
import { Type } from 'main.core';

import { StatusService } from '../../../../classes/status-service';
import { LocMixin } from '../../../../mixins/loc-mixin';
import { StaticDescriptionByStatus } from './const';
import { UserStatus } from '../../../../type';

import type { UserStatusType } from '../../../../type';

import './style.css';

// @vue/component
export const UserStatusDescription = {
	name: 'UserStatusDescription',
	mixins: [LocMixin],
	props: {
		status: {
			/** @type UserStatusType */
			type: Object,
			required: true,
		},
	},
	computed: {
		text(): string
		{
			let { code } = this.status;
			if (!Type.isStringFilled(code))
			{
				return '';
			}

			if (!StatusService.isSupported(code))
			{
				code = StatusService.getFailoverStatus();
			}

			const staticText = StaticDescriptionByStatus[code] ?? null;
			if (staticText)
			{
				return staticText;
			}

			if (code === UserStatus.Offline)
			{
				return this.formatTextForOfflineStatus({
					...this.status,
					code,
				});
			}

			if (code === UserStatus.Vacation)
			{
				return this.formatTextForVacationStatus({
					...this.status,
					code,
				});
			}

			return '';
		},
	},
	methods: {
		formatTextForOfflineStatus(status: UserStatusType): string
		{
			if (!Type.isNumber(status.lastSeenTs) || status.lastSeenTs === 0)
			{
				return this.loc('INTRANET_USER_MINI_PROFILE_USER_STATUS_OFFLINE');
			}

			const dayMonthFormat = DateTimeFormat.getFormat('DAY_MONTH_FORMAT');
			const shortTimeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');

			return this.loc('INTRANET_USER_MINI_PROFILE_USER_STATUS_OFFLINE_LAST_SEEN_TEMPLATE', {
				'#DATE#': DateTimeFormat.format(dayMonthFormat, status.lastSeenTs),
				'#TIME#': DateTimeFormat.format(shortTimeFormat, status.lastSeenTs),
			});
		},
		formatTextForVacationStatus(status: UserStatusType): string
		{
			if (!Type.isNumber(status.vacationTs))
			{
				return this.loc('INTRANET_USER_MINI_PROFILE_USER_STATUS_VACATION');
			}

			const dayMonthFormat = DateTimeFormat.getFormat('DAY_MONTH_FORMAT');

			return this.loc('INTRANET_USER_MINI_PROFILE_USER_STATUS_VACATION_TEMPLATE', {
				'#DATE#': DateTimeFormat.format(dayMonthFormat, status.vacationTs),
			});
		},
	},
	template: `
		<span v-if="text"
			class="intranet-user-mini-profile__user-status-description"
			data-test-id="usermp_status-description-text"
		>
			{{ text }}
		</span>
	`,
};
