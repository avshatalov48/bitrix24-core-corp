import { Loc } from 'main.core';

import { Hint } from 'ui.vue3.components.hint';
import { hint } from 'ui.vue3.directives.hint';

import '../css/prompt-master-prompt-types.css';

export const promptTypes: PromptTypesInfo = [
	{
		id: 'DEFAULT',
		title: Loc.getMessage('PROMPT_MASTER_TYPE_FIRST_TITLE'),
		description: Loc.getMessage('PROMPT_MASTER_TYPE_FIRST_DESCRIPTION'),
		example: Loc.getMessage('PROMPT_MASTER_TYPE_FIRST_EXAMPLE'),
		active: false,
		icon: 'stars',
	},
	{
		id: 'SIMPLE_TEMPLATE',
		title: Loc.getMessage('PROMPT_MASTER_TYPE_SECOND_TITLE'),
		description: Loc.getMessage('PROMPT_MASTER_TYPE_SECOND_DESCRIPTION'),
		example: Loc.getMessage('PROMPT_MASTER_TYPE_SECOND_EXAMPLE', {
			'#accent#': '<strong>',
			'#/accent#': '</strong>',
		}),
		active: false,
		icon: 'stars-question',
	},
];

type PromptTypesInfo = PromptTypeInfo[];

type PromptTypeInfo = {
	id: string;
	title: string;
	description: string;
	example: string;
	active: boolean;
	icon: string;
};

export const PromptMasterPromptTypes = {
	components: {
		Hint,
	},
	directives: {
		hint,
	},
	props: {
		activePromptType: {
			type: String,
			required: false,
			default: '',
		},
	},
	computed: {
		promptTypes(): PromptTypesInfo {
			return promptTypes.map((type) => {
				const isActive = type.id === this.activePromptType;

				return {
					...type,
					active: isActive,
				};
			});
		},
	},
	methods: {
		getPromptTypeClass(isActive: boolean): Object {
			return {
				'ai__prompt-master_prompt-type': true,
				'--active': isActive,
			};
		},
		getPromptTypeIconClassname(iconName: string): string[] {
			return ['ai__prompt-master_prompt-type_icon', `--icon-${iconName}`];
		},
		selectPromptType(type: string): void {
			this.$emit('select', type);
		},
	},
	template: `
		<div class="ai__prompt-master_prompt-types-step">
			<ul class="ai__prompt-master_prompt-types">
				<li
					v-for="promptType in promptTypes"
					class="ai__prompt-master_prompt-types_type"
					@click="selectPromptType(promptType.id)"
				>
					<div :class="getPromptTypeClass(promptType.active)">
						<div :class="getPromptTypeIconClassname(promptType.icon)"></div>
						<div class="ai__prompt-master_prompt-type_title">
							{{ promptType.title }}
						</div>
						<p class="ai__prompt-master_prompt-type_description">
							{{ promptType.description }}
						</p>
						<i class="ai__prompt-master_prompt-type_example" v-html="promptType.example"></i>
					</div>
				</li>
			</ul>
		</div>
	`,
};
