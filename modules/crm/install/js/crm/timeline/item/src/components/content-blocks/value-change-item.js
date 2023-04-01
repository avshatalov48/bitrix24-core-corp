export default {
	props: {
		iconCode: String,
		text: String,
		pillText: String,
	},
	computed: {
		iconClassName(): Object
		{
			return [
				'crm-timeline__value-change-item_icon', {
				[`--${this.iconCode}`]: true,
				}
			];

		}
	},
	// language=Vue
	template: `
		<div class="crm-timeline__value-change-item">
			<span v-if="iconCode" :class="iconClassName"></span>
			<span class="crm-timeline__value-change-item_text" v-if="text">{{ text }}</span>
			<span class="crm-entity-stream-content-detain-info-status" v-if="pillText">{{ pillText }}</span>
		</div>
	`
};