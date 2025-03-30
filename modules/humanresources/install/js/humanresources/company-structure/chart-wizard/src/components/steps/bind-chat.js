import { TagSelector } from 'ui.entity-selector';

export const BindChat = {
	name: 'bindChat',

	created(): void
	{
		this.hints = [
			this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_1'),
			this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_HINT_2'),
		];

		this.chatSelector = this.getChatSelector();
	},

	mounted(): void
	{
		this.chatSelector.renderTo(this.$refs['chat-selector']);
	},

	methods: {
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		getChatSelector(): TagSelector
		{
			const selector = new TagSelector({
				events: {},
				multiple: true,
				locked: true,
				dialogOptions: {
					height: 250,
					width: 380,
					dropdownMode: true,
					hideOnDeselect: true,
				},
			});
			selector.getDialog().freeze();

			return selector;
		},
	},
	template: `
		<div class="chart-wizard__bind-chat">
			<div class="chart-wizard__bind-chat__item">
				<div class="chart-wizard__bind-chat__item-hint">
					<div class="chart-wizard__bind-chat__item-hint__logo"></div>
					<div class="chart-wizard__bind-chat__item-hint__text">
						<div v-for="hint in hints"
							 class="chart-wizard__bind-chat__item-hint__text-item"
						>
							<div class="chart-wizard__bind-chat__item-hint__text-item__icon"></div>
							<span>{{ hint }}</span>
						</div>
					</div>
				</div>
				<div class="chart-wizard__bind-chat__item-options">
					<div class="chart-wizard__bind-chat__item-options__item-content__title">
						<div class="chart-wizard__bind-chat__item-options__item-content__title-text">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT') }}
						</div>
						<span class="chart-wizard__bind-chat__item-options__item-content__title-not-available">
							{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_OPTION_NOT_AVAILABLE') }}
						</span>
					</div>
					<div class="chart-wizard__chat_selector" ref="chat-selector" disabled="disabled"></div>
					<span class="chart-wizard__employee_item-description">
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_BINDCHAT_SELECT_CHAT_DESCRIPTION') }}
					</span>
				</div>
			</div>
		</div>
	`,
};
