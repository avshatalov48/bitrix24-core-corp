import {Action} from "../../../action";
import {Label} from 'ui.label';
import {TagType} from '../../enums/tag-type';

export const Tag = {
	props: {
		title: {
			type: String,
			required: false,
			default: '',
		},
		action: {
			type: Object,
			required: false,
			default: null,
		},
		type: {
			type: String,
			required: false,
			default: TagType.SECONDARY,
		},
		state: String,
	},
	computed: {
		className(): Object {
			return {
				'crm-timeline__card-status': true,
				'--clickable': !!this.action,
			}
		},

		tagTypeToLabelColorDict() {
			return {
				[TagType.PRIMARY]: Label.Color.LIGHT_BLUE,
				[TagType.SECONDARY]: Label.Color.LIGHT,
				[TagType.SUCCESS]: Label.Color.LIGHT_GREEN,
				[TagType.WARNING]: Label.Color.LIGHT_YELLOW,
				[TagType.FAILURE]: Label.Color.LIGHT_RED,
			};
		},

		tagContainerRef() {
			return this.$refs.tag;
		}
	},
	methods: {

		getLabelColorFromTagType(tagType) {
			const lowerCaseTagType = tagType ? tagType.toLowerCase() : '';
			const labelColor = this.tagTypeToLabelColorDict[lowerCaseTagType];
			return labelColor ? labelColor : Label.Color.LIGHT;
		},

		renderTag(tagOptions): HTMLElement | null {
			if (!tagOptions || !this.tagContainerRef) {
				return null;
			}


			const {title, type} = tagOptions;

			const uppercaseTitle = title && typeof title === 'string' ? title.toUpperCase() : '';
			const label = new Label({
				text: uppercaseTitle,
				color: this.getLabelColorFromTagType(type),
				fill: true,
			});

			this.tagContainerRef.appendChild(label.render());
		},

		executeAction() {
			if (!this.action) {
				return;
			}

			const action = new Action(this.action);
			action.execute(this);
		},
	},
	mounted() {
		this.renderTag({title: this.title, type: this.type});
	},
	template: `
		<div ref="tag" :class="className" @click="executeAction"></div>
	`
};
