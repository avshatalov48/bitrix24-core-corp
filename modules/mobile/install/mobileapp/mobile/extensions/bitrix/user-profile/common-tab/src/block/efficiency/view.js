/**
 * @module user-profile/common-tab/src/block/efficiency/view
 */
jn.define('user-profile/common-tab/src/block/efficiency/view', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Circle, Line } = require('utils/skeleton');
	const { H2, Text6 } = require('ui-system/typography');
	const { createTestIdGenerator } = require('utils/test');
	const { PropTypes } = require('utils/validation');

	const CIRCLE_SIZE = 40;
	const MAX_EFFICIENCY_PERCENTAGE = 100;

	class Efficiency extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				prefix: 'efficiency',
				context: this,
			});
		}

		render()
		{
			const { efficiency } = this.props;

			if (!efficiency)
			{
				return this.#renderShimmer();
			}

			return this.#renderContent();
		}

		#renderContent()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				this.#renderSvgCircle(),
				this.#renderPercentages(),
			);
		}

		#renderSvgCircle()
		{
			return Image({
				testId: this.getTestId('svg-circle'),
				svg: {
					content: this.#createSvgCircle(),
				},
				style: {
					alignSelf: 'center',
					width: 40,
					height: 40,
				},
			});
		}

		#renderShimmer()
		{
			return View(
				{
					testId: this.getTestId('shimmer'),
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				Circle(CIRCLE_SIZE),
				View(
					{
						style: {
							marginLeft: Indent.M.toNumber(),
						},
					},
					Line(51, 25, 0, 0, 20),
				),
			);
		}

		#renderPercentages()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				H2({
					testId: this.getTestId('percentages'),
					text: String(this.#calculateEfficiencyPercentage()),
					accent: true,
					color: Color.base1,
					style: {
						marginLeft: Indent.M.toNumber(),
						marginRight: Indent.XS2.toNumber(),
					},
				}),
				Text6({
					text: '%',
					color: Color.base4,
				}),
			);
		}

		#calculateEfficiencyPercentage()
		{
			const { inProgress = 0, violations = 0 } = this.#getEfficiency();

			return Math.round(inProgress > 0
				? MAX_EFFICIENCY_PERCENTAGE - this.#calculatePercentage(violations)
				: MAX_EFFICIENCY_PERCENTAGE);
		}

		#createSvgCircle = () => {
			const efficiency = this.#getEfficiency();

			const calculatedValue = (value) => (Number(value) === 0
				? MAX_EFFICIENCY_PERCENTAGE
				: this.#calculatePercentage(value));

			const violationsCircular = {
				value: calculatedValue(efficiency.violations),
				backgroundColor: Color.accentMainAlert.toHex(),
				fillColor: Color.accentSoftRed2.toHex(),
				radius: 8.5,
			};
			const completedCircular = {
				value: calculatedValue(efficiency.completed),
				backgroundColor: Color.accentMainSuccess.toHex(),
				fillColor: Color.accentSoftGreen2.toHex(),
				radius: 13.5,
			};
			const progressCircular = {
				value: this.#calculateEfficiencyPercentage(),
				backgroundColor: Color.accentMainPrimaryalt.toHex(),
				fillColor: Color.accentSoftBlue2.toHex(),
				radius: 18.5,
			};

			return `
				<svg width="41" height="40" viewBox="0 0 41 40" fill="none" xmlns="http://www.w3.org/2000/svg">
				${this.#generateSvgCircular(violationsCircular)}
				${this.#generateSvgCircular(completedCircular)}
				${this.#generateSvgCircular(progressCircular)}
				</svg>
			`;
		};

		#calculatePercentage(value)
		{
			const efficiency = this.#getEfficiency();

			return (value / efficiency.inProgress) * MAX_EFFICIENCY_PERCENTAGE;
		}

		#generateSvgCircular({ value, backgroundColor, fillColor, radius })
		{
			const centerX = 20.5;
			const centerY = 20;
			const clampedValue = Math.min(Math.max(value, 0), MAX_EFFICIENCY_PERCENTAGE);
			const adjustedValue = clampedValue >= 70 ? clampedValue - 1 : clampedValue;
			const angle = (adjustedValue / MAX_EFFICIENCY_PERCENTAGE) * 360;
			const endX = centerX + radius * Math.cos(angle * (Math.PI / 180));
			const endY = centerY + radius * Math.sin(angle * (Math.PI / 180));
			const largeArcFlag = adjustedValue > 50 ? 1 : 0;
			const circle = `<circle cx="${centerX}" cy="${centerY}" r="${radius}" stroke="${fillColor}" stroke-width="3" fill="none" />`;

			if (value === 0)
			{
				return circle;
			}

			return `
				${circle}
				<path d="M${centerX + radius} ${centerY} 
				 A${radius} ${radius} 0 ${largeArcFlag} 1 ${endX} ${endY}"
				 stroke="${backgroundColor}" stroke-width="3" stroke-linecap="round" fill="none"
				/>
			`;
		}

		openEfficiency = async () => {
			const { Entry } = await requireLazy('tasks:entry').catch(console.error);

			void Entry.openEfficiency({ userId: this.#getUserId() });
		};

		#getUserId()
		{
			const { ownerId } = this.props;

			return ownerId;
		}

		#getEfficiency()
		{
			const { efficiency } = this.props;

			return {
				inProgress: efficiency?.inProgress || 0,
				completed: efficiency?.completed || 0,
				violations: efficiency?.violations || 0,
			};
		}
	}

	Efficiency.propTypes = {
		ownerId: PropTypes.number.isRequired,
		efficiency: PropTypes.shape({
			inProgress: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
			completed: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
			violations: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
		}),
	};

	module.exports = {
		Efficiency: (props) => new Efficiency(props),
	};
});
