import '../css/role-master-progress.css';

export const RoleMasterProgress = {
	props: {
		current: Number,
	},
	computed: {
		total(): number {
			return 2;
		},
	},
	methods: {
		getProgressStepClassname(isActive: boolean): Object {
			return {
				'ai__role-master_progress-step': true,
				'--active': isActive,
			};
		},
	},
	template: `
		<div class="ai__role-master_progress">
			<span
				v-for="index in total"
				:class="getProgressStepClassname(index <= current)"
			>
			</span>
		</div>
	`,
};
