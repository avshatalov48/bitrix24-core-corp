/**
 * @module intranet/department-structure/src/company
 */
jn.define('intranet/department-structure/src/company', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { Color, Indent } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { selectById } = require('intranet/statemanager/redux/slices/department');
	const { getState } = require('statemanager/redux/store');
	const { connect } = require('statemanager/redux/connect');
	const { createTestIdGenerator } = require('utils/test');

	/**
	 * @typedef {Object} CompanyProps
	 * @property {string} testId
	 * @property {number} id
	 * @property {string} name
	 * @property {boolean} [chevron = false]

	 * @class Company
	 */
	class Company extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				context: this,
			});
		}

		render()
		{
			const { name, chevron = false } = this.props;

			return View(
				{
					testId: this.getTestId('company'),
					style: {
						marginBottom: Indent.XL.toNumber(),
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
						},
					},
					Avatar({
						testId: this.getTestId('company-avatar'),
						icon: IconView({
							testId: this.getTestId('company-avatar-icon'),
							size: 28,
							color: Color.baseWhiteFixed,
							icon: Icon.COMPANY,
						}),
						backgroundColor: Color.accentMainSuccess,
						style: {
							marginRight: Indent.L.toNumber(),
						},
					}),
					Text4({
						text: name,
						color: Color.base2,
						numberOfLines: 2,
						ellipsize: 'end',
						accent: true,
						style: {
							flexShrink: 1,
						},
					}),
					chevron && this.#renderChevron(),
				),
			);
		}

		#renderChevron()
		{
			return IconView({
				testId: this.getTestId('chevron-icon'),
				size: 20,
				color: Color.base2,
				icon: Icon.CHEVRON_TO_THE_RIGHT,
			});
		}
	}

	/**
	 * @typedef {Object} ReduxCompanyProps
	 * @property {number} departmentId
	 * @property {boolean} chevron
	 * @property {string} testId
	 *
	 * @param {object} state
	 * @param {ReduxCompanyProps} props
	 * @returns {CompanyProps}
	 */
	const mapStateToProps = (state, {
		departmentId,
		chevron,
		testId,
	}) => {
		const department = selectById(getState(), departmentId);
		if (!department)
		{
			console.error(`Department with id ${departmentId} not found`);
		}

		const { name } = department;

		return {
			id: departmentId,
			chevron,
			testId,
			name,
		};
	};

	module.exports = {
		/**
		 * @param {ReduxCompanyProps} props
		 * @returns {Company}
		 */
		Company: connect(mapStateToProps)(Company),
	};
});
