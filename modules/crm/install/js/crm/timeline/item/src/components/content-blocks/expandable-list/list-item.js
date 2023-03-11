import 'ui.design-tokens';
import Link from '../link';
import Text from '../text';
import ListItemButton from './list-item-button';

export default {
	props: {
		title: {
			type: String,
			required: true,
		},
		titleAction: Object,
		isSelected: {
			type: Boolean,
			required: false,
			default: false,
		},
		image: String,
		showDummyImage: {
			type: Boolean,
			required: false,
			default: true,
		},
		bottomBlock: Object,
		button: Object,
	},
	components: {
		Text,
		Link,
		ListItemButton,
	},
	computed: {
		imageStyle()
		{
			if (!this.image)
			{
				return {};
			}

			return {
				backgroundImage: 'url(' + this.image + ')'
			};
		},
	},
	// language=Vue
	template: `
		<li
			:class="{'crm-entity-stream-advice-list-item--active': isSelected}"
			class="crm-entity-stream-advice-list-item"
		>
			<div class="crm-entity-stream-advice-list-content">
				<div
					v-if="image || showDummyImage"
					:style="imageStyle"
					class="crm-entity-stream-advice-list-icon"
				>
				</div>
				<div class="crm-entity-stream-advice-list-inner">
					<Link v-if="titleAction" :action="titleAction" :text="title"></Link>
					<Text v-else :value="title"></Text>
					<div v-if="bottomBlock" class="crm-entity-stream-advice-list-desc-box">
						<LineOfTextBlocks v-bind="bottomBlock.properties"></LineOfTextBlocks>
					</div>
				</div>
			</div>
			<ListItemButton v-if="button" v-bind="button.properties"></ListItemButton>
		</li>
	`
}
