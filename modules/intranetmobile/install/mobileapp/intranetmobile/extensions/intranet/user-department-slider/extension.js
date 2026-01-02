/**
 * @module intranet/user-department-slider
 */
jn.define('intranet/user-department-slider', (require, exports, module) => {
	const { SwipeSlider, SliderNavigationMode } = require('ui-system/layout/swipe-slider');
	const { createTestIdGenerator } = require('utils/test');
	const { Indent, Component } = require('tokens');
	const { ReduxDepartmentStructure } = require('intranet/department-structure');
	const { Card } = require('ui-system/layout/card');

	/**
	 * @typedef {Object} UserDepartmentSliderProps
	 * @property {number[][]} [hierarchies]
	 * @property {number} userId
	 * @property {number} sliderWidth
	 * @property {bool} chevron
	 * @property {function} onClick

	 * @class UserDepartmentSlider
	 */
	class UserDepartmentSlider extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				context: this,
			});
			this.state = {
				heights: [],
			};

			this.heights = {};
			this.renderedStructuresCount = 0;
		}

		render()
		{
			const { sliderWidth } = this.props;
			const isIOS = this.#isIOS();
			const renderedStructures = this.#renderDepartmentStructures();
			if (renderedStructures.length === 1)
			{
				return renderedStructures[0];
			}

			return SwipeSlider({
				testId: this.getTestId('user-departments'),
				width: sliderWidth,
				children: [
					...renderedStructures,
				],
				style: {
					marginBottom: isIOS ? 0 : Component.cardPaddingB.toNumber(),
				},
				navigationMode: this.#isIOS() ? SliderNavigationMode.SWIPE : SliderNavigationMode.BUTTON,
				...this.#getSliderHeightProps(),
			});
		}

		#isIOS = () => {
			return Application.getPlatform() === 'ios';
		};

		#getSliderHeightProps = () => {
			const { heights } = this.state;

			return heights.length > 0 ? { pageHeights: heights } : { contentHeight: 520 };
		};

		onStructureLayout = (index, { height }) => {
			const { heights } = this.state;
			const { hierarchies } = this.props;
			if (heights.length === 0)
			{
				this.heights[index] = height + Indent.XL.toNumber();
				this.renderedStructuresCount++;

				if (this.renderedStructuresCount === hierarchies.length)
				{
					const heightsArray = [];
					for (let i = 0; i < hierarchies.length; i++)
					{
						heightsArray.push(this.heights[i]);
					}
					this.setState({
						heights: heightsArray,
					});
				}
			}
		};

		#renderDepartmentStructures = () => {
			const { hierarchies } = this.props;

			return hierarchies.map((departmentIds, index) => this.#renderDepartmentStructure(departmentIds, index));
		};

		#renderDepartmentStructure = (departmentIds, index) => {
			const preparedDepartmentIds = this.#prepareDepartmentIds(departmentIds);
			const { userId, sliderWidth, chevron, onClick } = this.props;
			const cardStyle = sliderWidth ? { width: sliderWidth } : {};

			return Card(
				{
					style: cardStyle,
					onClick,
				},
				ReduxDepartmentStructure({
					testId: this.getTestId(`structure-${departmentIds?.[0]}`),
					departmentIds: preparedDepartmentIds,
					onLayout: this.onStructureLayout.bind(this, index),
					userId,
					chevron,
				}),
			);
		};

		#prepareDepartmentIds = (departmentIds) => {
			if (departmentIds.length < 4)
			{
				return [departmentIds[0], ...departmentIds];
			}

			return departmentIds;
		};
	}

	module.exports = {
		/**
		 * @param {UserDepartmentSliderProps} props
		 * @returns {UserDepartmentSlider}
		 */
		UserDepartmentSlider: (props) => new UserDepartmentSlider(props),
	};
});
