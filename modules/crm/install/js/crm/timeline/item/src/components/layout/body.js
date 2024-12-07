import { Type } from 'main.core';
import { Logo } from './body/logo';
import { LogoCalendar } from './body/logo-calendar';

export const Body = {
	components: {
		Logo,
		LogoCalendar,
	},
	props: {
		logo: Object,
		blocks: Object,
	},
	data()
	{
		return {
			blockRefs: {},
		}
	},
	mounted() {
		const blocks = this.$refs.blocks;
		if (!blocks || !this.visibleBlocks)
		{
			return;
		}
		this.visibleBlocks.forEach((block, index) => {
			if (Type.isDomNode(blocks[index].$el))
			{
				blocks[index].$el.setAttribute('data-id', block.id);
			}
			else
			{
				throw new Error('Vue component "' + block.rendererName + '" was not found');
			}
		});
	},
	beforeUpdate() {
		this.blockRefs = {};
	},
	computed: {
		visibleBlocks(): Array
		{
			if (!Type.isPlainObject(this.blocks))
			{
				return [];
			}

			return Object.keys(this.blocks)
				.map((id) => ({id, ...this.blocks[id]}))
				.filter((item) => (item.scope !== 'mobile'))
				.sort((a, b) => {
					let aSort = a.sort === undefined ? 0 : a.sort
					let bSort = b.sort === undefined ? 0 : b.sort

					if (aSort < bSort) {
						return -1;
					}
					if (aSort > bSort) {
						return  1;
					}

					return 0
				})
			;
		},
		contentContainerClassname(): Array {
			return [
				'crm-timeline__card-container', {
				'--without-logo': !this.logo,
				}
			];
		}
	},
	methods: {
		getContentBlockById(blockId: string): ?Object
		{
			return this.blockRefs[blockId] ?? null;
		},
		getLogo(): ?Object
		{
			return this.$refs.logo;
		},
		saveRef(ref: Object, id: string): void
		{
			this.blockRefs[id] = ref;
		}
	},
	template: `
		<div class="crm-timeline__card-body">
			<div v-if="logo" class="crm-timeline__card-logo_container">
				<LogoCalendar v-if="logo.icon === 'calendar'" v-bind="logo"></LogoCalendar>
				<Logo v-else v-bind="logo" ref="logo"></Logo>
			</div>
			<div :class="contentContainerClassname">
				<div
					v-for="block in visibleBlocks"
					:key="block.id"
					class="crm-timeline__card-container_block"
				>
					<component
						:is="block.rendererName"
						v-bind="block.properties"
						:ref="(el) => this.saveRef(el, block.id)"
					/>
				</div>
			</div>
		</div>
	`
};
