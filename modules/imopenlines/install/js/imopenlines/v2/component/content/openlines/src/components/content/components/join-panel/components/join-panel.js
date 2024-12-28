import { Messenger } from 'im.public';
import { Button as ButtonPanel, ButtonColor, ButtonSize } from 'im.v2.component.elements';
import { Layout } from 'im.v2.const';
import { LayoutManager } from 'im.v2.lib.layout';
import { JoinService, StartService } from 'imopenlines.v2.provider.service';

// @vue/component
export const JoinPanel = {
	name: 'JoinPanel',
	components: { ButtonPanel },
	props:
	{
		dialogId: {
			type: String,
			required: true,
		},
		isNewSession: {
			type: Boolean,
			required: true,
		},
		isClosed: {
			type: Boolean,
			required: true,
		},
	},
	computed:
	{
		ButtonSize: () => ButtonSize,
		ButtonColor: () => ButtonColor,
		textStartJoinButtons(): string
		{
			return this.isClosed
				? this.loc('IMOL_CONTENT_TEXTAREA_JOIN_PANEL_START')
				: this.loc('IMOL_CONTENT_TEXTAREA_JOIN_PANEL_JOIN_BUTTON');
		},
	},
	methods:
	{
		handleDialogAccess(): Promise
		{
			if (this.isClosed)
			{
				return this.getStartService().startDialog(this.dialogId);
			}

			return this.getJoinService().joinToDialog(this.dialogId);
		},
		closeDialog()
		{
			void Messenger.openLines();
			LayoutManager.getInstance().setLastOpenedElement(Layout.openlinesV2.name, '');
		},
		getStartService(): StartService
		{
			if (!this.startService)
			{
				this.startService = new StartService();
			}

			return this.startService;
		},
		getJoinService(): JoinService
		{
			if (!this.joinService)
			{
				this.joinService = new JoinService();
			}

			return this.joinService;
		},
		loc(phraseCode: string): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode);
		},
	},
	template: `
		<ul class="bx-imol-textarea_join-panel-list-button">
			<li v-if="!isNewSession" class="bx-imol-textarea_join-panel-item-button">
				<ButtonPanel
					:size="ButtonSize.L"
					:color="ButtonColor.Success"
					:text=textStartJoinButtons
					@click="handleDialogAccess"
				/>
			</li>
			<li class="bx-imol-textarea_join-panel-item-button">
				<ButtonPanel
					:size="ButtonSize.L"
					:color="ButtonColor.Danger"
					:text="loc('IMOL_CONTENT_TEXTAREA_JOIN_PANEL_CLOSE')"
					@click="closeDialog"
				/>
			</li>
		</ul>
	`,
};
