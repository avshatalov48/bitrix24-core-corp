import './battery-icon.css';

const MIN_CHARGE = 0;
const MAX_CHARGE = 12;
const CHARGE_COLOR = 'var(--ui-color-primary-alt)';
const EMPTY_COLOR = 'var(--ui-color-background-secondary)';

export const BATTERY_ICON_HEIGHT = 14;
export const BATTERY_ICON_WIDTH = 27;

export const BatteryIcon = {
	name: 'BatteryIcon',
	props: {
		percent: {
			type: Number,
			default: 0,
		},
		dataId: {
			type: [String, Number],
			default: '',
		},
		height: {
			type: Number,
			default: BATTERY_ICON_HEIGHT,
		},
		width: {
			type: Number,
			default: BATTERY_ICON_WIDTH,
		},
	},
	mounted(): void {
		this.repaint();
	},
	methods: {
		getCharge(percent: number): number
		{
			if (percent <= 0)
			{
				return MIN_CHARGE;
			}
			if (percent >= 100)
			{
				return MAX_CHARGE;
			}

			return Math.round(percent * MAX_CHARGE * 0.01);
		},
		repaint(): void
		{
			const rects: SVGRectElement[] = this.$refs['icon-battery-charge']?.children || [];
			const charge: number = this.getCharge(this.percent);
			let index = 1;

			for (const rect: SVGRectElement of rects)
			{
				rect.setAttribute('fill', index > charge ? EMPTY_COLOR : CHARGE_COLOR);
				index++;
			}
		},
	},
	watch: {
		percent: {
			handler(): void
			{
				this.repaint();
			},
		},
	},
	template: `
		<div :data-id="dataId" :data-percent="percent" data-element="booking-resource-workload-percent">
			<svg id="booking--battery-icon" :width="width" :height="height" viewBox="0 0 27 14" fill="none"
				 xmlns="http://www.w3.org/2000/svg">
				<rect width="23.2875" height="13.8" rx="4" fill="white"/>
				<rect x="22.6871" y="0.6" width="12.6" height="22.0875" rx="3.4" transform="rotate(90 22.6871 0.6)" stroke="#C9CCD0" stroke-width="1.2"/>
				<g ref="icon-battery-charge" id="booking--battery-icon-charge" clip-path="url(#clip0_5003_187951)">
					<rect x="2.58789" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="4.09766" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="5.60547" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="7.11523" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="8.625" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="10.1328" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="11.6426" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="13.1523" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="14.6621" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="16.1699" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="17.6797" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
					<rect x="19.1895" y="2.5875" width="1.50917" height="10" fill="#EDEEF0"/>
				</g>
				<g clip-path="url(#clip1_5003_187951)">
					<ellipse cx="23.102" cy="6.89999" rx="2.9" ry="3.48" transform="rotate(90 23.102 6.89999)" fill="#C9CCD0"/>
				</g>
				<defs>
					<clipPath id="clip0_5003_187951">
						<rect x="2.58789" y="2.5875" width="18.1125" height="8.625" rx="1.5" fill="white"/>
					</clipPath>
					<clipPath id="clip1_5003_187951">
						<rect width="6.9" height="2.15625" fill="white" transform="translate(26.7383 3.45) rotate(90)"/>
					</clipPath>
				</defs>
			</svg>
		</div>
	`,
};
