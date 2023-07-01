export const Section = {
	props: {
		id: {
			type: String,
			required: true,
		},
		toggleTitle: {
			type: String,
			default: '',
		},
		toggleEnabled: {
			type: Boolean,
			default: true,
		},
		toggleVisible: {
			type: Boolean,
			default: true,
		},
	},

	watch: {
		enabled() {
			this.$emit('onToggle', {
				id: this.id,
				isActive: this.enabled,
			});
		},
	},

	data() {
		return {
			enabled: this.toggleEnabled,
		}
	},

	template: `
		<section>
			<div class="ui-form-row" v-if="toggleVisible">
				<label for class="ui-ctl ui-ctl-checkbox" @click="enabled = !enabled">
					<input type="checkbox" class="ui-ctl-element" v-model="enabled">
					<div class="ui-ctl-label-text">{{ toggleTitle }}</div>
				</label>
			</div>
			<div class="ui-form-row" v-else>
				<label>
					<div class="ui-ctl-label-text">{{ toggleTitle }}</div>
				</label>
			</div>
			<slot v-if="enabled"></slot>
		</section>
	`
};
