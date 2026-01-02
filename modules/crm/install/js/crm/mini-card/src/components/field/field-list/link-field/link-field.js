import { BitrixVueComponentProps } from 'ui.vue3';
import { type MenuOptions } from 'main.popup';

import {
	Field,
	FieldTitle,
	FieldValue,
	FieldValueList,
	ValueEllipsis,
} from '../../layout/index';

import { ShowMoreMenu } from '../../show-more/show-more-menu';

declare type Link = {
	href: string,
	title: string,
	target: string,
};

const SHOWABLE_LINKS_LIMIT = 2;

import './link-field.css';

export const LinkField: BitrixVueComponentProps = {
	name: 'LinkField',
	components: {
		Field,
		FieldTitle,
		FieldValueList,
		FieldValue,
		ValueEllipsis,
		ShowMoreMenu,
	},
	props: {
		title: {
			type: String,
			required: true,
		},
		links: {
			type: Array,
			required: true,
		},
	},
	computed: {
		showableLinks(): Link[]
		{
			return this.links.slice(0, SHOWABLE_LINKS_LIMIT);
		},
		hiddenLinks(): Link[]
		{
			return this.links.slice(SHOWABLE_LINKS_LIMIT);
		},
		hiddenLinkItems(): MenuOptions[]
		{
			return this.hiddenLinks.map((link: Link) => {
				return {
					text: link.title,
					href: link.href,
					target: link.target,
				};
			});
		},
	},
	template: `
		<Field class="--link">
			<FieldTitle>{{ title }}</FieldTitle>
			<FieldValueList>
				<FieldValue v-for="link in showableLinks">
					<a
						class="--value-link"
						:href="link.href"
						:target="link.target"
					>
						<ValueEllipsis :title="link.title">{{ link.title }}</ValueEllipsis>
					</a>
				</FieldValue>
				<ShowMoreMenu
					v-if="hiddenLinks.length > 0"
					:items="hiddenLinkItems"
				/>
			</FieldValueList>
		</Field>
	`,
};
