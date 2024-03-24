import { BitrixVue } from 'ui.vue3';
import BlockType from '../../enums/block-type';
import SectionImageSize from '../../enums/section-image-size';
import SectionType from '../../enums/section-type';
import BaseBlocksCollection from './base-blocks-collection';

export const Section = BitrixVue.cloneComponent(BaseBlocksCollection, {
	props: {
		type: {
			type: String,
			required: false,
			default: SectionType.default,
		},
		imageSrc: {
			type: String,
			required: false,
			default: '',
		},
		imageSize: {
			type: String,
			required: false,
			default: SectionImageSize.LG,
		},
	},
	computed: {
		allowedTypes(): Array
		{
			return Object.values(BlockType).filter((item) => (item !== BlockType.section));
		},
		className(): Array
		{
			return [
				'crm-timeline-block-section',
				this.typeClassname,
			];
		},
		imageClassName(): Array
		{
			return [
				'crm-timeline-block-section-img',
				this.imageSizeClassname,
			];
		},
		typeClassname(): string
		{
			const type = SectionType[this.type] ?? SectionType.default;

			return type ? `--type-${type}` : '';
		},
		imageSizeClassname(): string
		{
			const size = SectionImageSize[this.imageSize.toUpperCase()] ?? SectionImageSize.LG;

			return size ? `--size-${size}` : '';
		},
		imageUri(): ?string
		{
			if (!this.imageSrc)
			{
				return null;
			}
			const regex = /^(http|https):\/\//;
			if (!regex.test(this.imageSrc))
			{
				return null;
			}

			return this.imageSrc;
		},
	},
	// language=Vue
	template: `
		<div :class="className">
			<div v-if="imageUri" :class="imageClassName">
				<img :src="imageUri" />
			</div>
		<BlocksCollection
			ref="blocks"
			containerCssClass="crm-timeline-block-section-blocks"
			itemCssClass="crm-timeline__restapp-container_block"
			:blocks="blocks ?? {}"
			:allowedTypes="allowedTypes"
		></BlocksCollection>
		</div>`,
});
