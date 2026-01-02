import { RichLoc } from 'ui.vue3.components.rich-loc';

// @vue/component
export const MessageTemplate = {
	components: {
		RichLoc,
	},
	props: {
		text: {
			type: String,
			required: true,
		},
	},
	computed: {
		preparedTemplate(): string
		{
			return this.text
				.replaceAll('\n', '[br/]')
				.replaceAll(/#(.+?)#/g, '[accent][[bold]$1[/bold]][/accent]')
			;
		},
	},
	template: `
		<RichLoc
			class="resource-creation-wizard__form-notification-info-template"
			:placeholder="['[accent]', '[br/]']"
			:text="preparedTemplate"
		>
			<template #accent="{ text: accentText }">
				<RichLoc tag="span" class="--accent" placeholder="[bold]" :text="accentText">
					<template #bold="{ text: boldText }">
						<span class="--bold">{{ boldText }}</span>
					</template>
				</RichLoc>
			</template>
			<template #br><br/></template>
		</RichLoc>
	`,
};
