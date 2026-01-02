import './style.css';

// @vue/component
export const DepartmentConnector = {
	name: 'DepartmentConnector',
	props: {
		topBindElement: {
			type: HTMLElement,
			required: true,
		},
		bottomBindElement: {
			type: HTMLElement,
			required: true,
		},
		offsetLeft: {
			type: Number,
			default: 11,
		},
	},
	computed: {
		top(): string
		{
			const { height } = this.topBindElement.getBoundingClientRect();
			const value = this.topBindElement.offsetTop + height;

			return `${value}px`;
		},
		left(): string
		{
			const value = this.topBindElement.offsetLeft + this.offsetLeft;

			return `${value}px`;
		},
		height(): number
		{
			const topElementBottom = this.topBindElement.offsetTop + this.topBindElement.offsetHeight;
			const bottomElementCenter = this.bottomBindElement.offsetTop + this.bottomBindElement.offsetHeight / 2;

			return Math.round(bottomElementCenter - topElementBottom);
		},
		pathD(): string
		{
			const height = this.height;

			const d = [
				'M 1 0',
				`V ${height - 5}`,
				`C 1 ${height - 3}.2091 2.7909 ${height - 1} 5 ${height - 1}`,
				'H 9',
			];

			return d.join('');
		},
		viewBox(): string
		{
			return `0 0 9 ${this.height}`;
		},
	},
	template: `
		<div class="intranet-user-mini-profile__structure-view-connector" 
			 :style="{ 'top': top, 'left': left }"
		>
			<svg width="9" :height="height" :viewBox="viewBox" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path :d="pathD" stroke="#F0F0F0"/>
			</svg>
		</div>
	`,
};
