import { UserStatus as UserStatusType } from 'im.v2.const';
import { Utils } from 'im.v2.lib.utils';
import { SettingsService } from 'im.v2.provider.service.settings';

import { UserStatus, UserStatusSize } from './user-status';

// @vue/component
export const UserStatusContent = {
	name: 'UserStatusContent',
	components: { UserStatus },
	emits: ['close'],
	computed:
	{
		UserStatusSize: () => UserStatusSize,
		statusList(): string[]
		{
			return [UserStatusType.online, UserStatusType.dnd];
		},
	},
	methods:
	{
		onStatusClick(statusName: string)
		{
			this.getSettingsService().changeStatus(statusName);
			this.$emit('close');
		},
		getSettingsService(): SettingsService
		{
			if (!this.settingsService)
			{
				this.settingsService = new SettingsService();
			}

			return this.settingsService;
		},
		getStatusText(status: string): string
		{
			return Utils.user.getStatusText(status);
		},
	},
	template:
	`
		<div class="bx-im-user-status-popup__scope bx-im-user-status-popup__container">
			<div
				v-for="status in statusList"
				:key="status"
				@click="onStatusClick(status)"
				class="bx-im-user-status-popup__item"
			>
				<UserStatus :status="status" :size="UserStatusSize.M" />
				<div class="bx-im-user-status-popup__text">{{ getStatusText(status) }}</div>
			</div>
		</div>
	`,
};
