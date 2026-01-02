/**
 * @module ui-system/blocks/department-card/src/card
 */
jn.define('ui-system/blocks/department-card/src/card', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Text3, Text4, Text5, Text6 } = require('ui-system/typography/text');
	const { createTestIdGenerator } = require('utils/test');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Card } = require('ui-system/layout/card');
	const { Color, Indent, Corner } = require('tokens');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { DepartmentCardMode } = require('ui-system/blocks/department-card/src/mode-enum');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');

	const LEFT_PADDING_STEP = 20;

	/**
	 * @typedef {Object} DepartmentCardProps
	 * @property {string} testId
	 * @property {DepartmentCardMode} [mode = DepartmentCardMode.UPPER]
	 * @property {string} departmentName
	 * @property {string} [employeesCountText = null]
	 * @property {boolean} [chevron = false]
	 * @property {string} employeeName
	 * @property {string} employeePosition
	 * @property {string} employeeCounterValue
	 * @property {AvatarBaseProps} employeeAvatarProps
	 * @property {string} managerName
	 * @property {string} managerTitle
	 * @property {AvatarBaseProps} managerAvatarProps
	 * @property {string} managerCounterValue
	 * @property {number} [depth = 0]
	 * @property {boolean} [accent = false]
	 * @property {function} [onClick]

	 * @class DepartmentCard
	 */
	class DepartmentCard extends PureComponent
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
			const {
				depth = 0,
				accent = false,
				employeesCountText = null,
				employeeName = null,
				managerTitle = null,
				managerName = null,
				mode = DepartmentCardMode.UPPER,
				onClick,
			} = this.props;
			const shouldRenderLine = depth > 0;

			return View(
				{},
				shouldRenderLine && this.#renderShortLine(),
				View(
					{
						testId: this.getTestId('department-view'),
						style: {
							flexDirection: 'row',
						},
						onClick,
					},
					shouldRenderLine && this.#renderLine(),
					Card(
						{
							testId: this.getTestId('department-card'),
							border: true,
							style: {
								flex: 1,
								borderColor: accent
									? Color.accentSoftBorderBlue.toHex()
									: Color.bgSeparatorPrimary.toHex(),
							},
						},
						this.#renderDepartmentName(),
						Boolean(employeesCountText) && this.#renderEmployeesCount(),
						Boolean(employeeName) && this.#renderEmployee(),
						Boolean(mode === DepartmentCardMode.CURRENT && managerName && managerTitle) && this.#renderManagerTitle(),
						Boolean(mode === DepartmentCardMode.CURRENT && managerName) && this.#renderManager(),
					),
				),
			);
		}

		#renderEmployee()
		{
			const {
				employeeAvatarProps = {},
				employeeName,
				employeePosition,
				employeeCounterValue,
			} = this.props;

			return this.#renderUser({
				userName: employeeName,
				userPosition: employeePosition,
				userAvatarProps: {
					...employeeAvatarProps,
					testId: this.getTestId('employee-avatar'),
				},
				counter: employeeCounterValue,
			});
		}

		#renderManagerTitle()
		{
			const { managerTitle = '' } = this.props;

			return Text5({
				text: managerTitle,
				color: Color.base3,
				ellipsize: 'end',
				style: {
					marginTop: Indent.XS2.toNumber() + Indent.L.toNumber(),
					marginBottom: Indent.S.toNumber(),
				},
			});
		}

		#renderManager()
		{
			const { managerName, managerAvatarProps = {}, managerCounterValue } = this.props;

			return this.#renderUser({
				userName: managerName,
				containerStyle: {
					marginTop: 0,
				},
				userAvatarProps: {
					size: 22,
					...managerAvatarProps,
					testId: this.getTestId('manager-avatar'),
				},
				counter: managerCounterValue,
			});
		}

		#renderUser({
			userName = '',
			userPosition = null,
			userAvatarProps = {},
			containerStyle = {},
			counter = null,
			testId = this.getTestId('user-block'),
		})
		{
			return View(
				{
					testId,
					style: {
						flexDirection: 'row',
						marginTop: Indent.L.toNumber(),
						alignItems: 'center',
						...containerStyle,
					},
				},
				Avatar({
					testId: this.getTestId('user-avatar'),
					size: 30,
					style: {
						marginRight: Indent.S.toNumber(),
					},
					...userAvatarProps,
				}),
				View(
					{
						style: {
							flex: 1,
						},
					},
					Text4({
						testId: this.getTestId('user-name-text'),
						text: userName,
						color: Color.base1,
						numberOfLines: 1,
						ellipsize: 'end',
					}),
					Boolean(userPosition) && Text5({
						testId: this.getTestId('user-position-text'),
						text: userPosition,
						color: Color.base3,
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							marginTop: Indent.XS2.toNumber(),
						},
					}),
				),
				Boolean(counter) && this.#renderCounter(counter),
			);
		}

		#renderCounter(counter)
		{
			return BadgeCounter({
				testId: this.getTestId('badge-counter'),
				value: String(counter),
				design: BadgeCounterDesign.GREY,
				showRawValue: true,
			});
		}

		#renderDepartmentName = () => {
			const { chevron = false, departmentName } = this.props;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Text3({
					testId: this.getTestId('department-name-text'),
					text: departmentName,
					color: Color.base1,
					numberOfLines: 2,
					ellipsize: 'end',
					style: {
						flexShrink: 1,
					},
				}),
				chevron && this.#renderChevron(),
			);
		};

		#renderChevron()
		{
			return IconView({
				testId: this.getTestId('chevron-icon'),
				size: 20,
				color: Color.base2,
				icon: Icon.CHEVRON_TO_THE_RIGHT,
			});
		}

		#renderLine = () => {
			const { depth } = this.props;
			const marginLeft = (depth - 0.5) * LEFT_PADDING_STEP;
			const borderColor = Color.bgSeparatorSecondary.toHex();

			return View({
				style: {
					height: '50%',
					borderLeftWidth: 1,
					borderBottomWidth: 1,
					borderLeftColor: borderColor,
					borderBottomColor: borderColor,
					borderBottomLeftRadius: Corner.S.toNumber(),
					width: LEFT_PADDING_STEP / 2,
					marginLeft,
				},
			});
		};

		#renderShortLine = () => {
			const { depth } = this.props;
			const marginLeft = (depth - 0.5) * LEFT_PADDING_STEP;
			const borderColor = Color.bgSeparatorSecondary.toHex();

			return View({
				style: {
					height: Indent.XL.toNumber(),
					borderLeftWidth: 1,
					borderLeftColor: borderColor,
					width: LEFT_PADDING_STEP / 2,
					marginLeft,
				},
			});
		};

		#renderEmployeesCount()
		{
			const { employeesCountText } = this.props;

			return Text6({
				testId: this.getTestId('company-employees-count'),
				text: employeesCountText,
				color: Color.base4,
				ellipsize: 'end',
				style: {
					marginVertical: Indent.XS2.toNumber(),
				},
			});
		}
	}

	DepartmentCard.defaultProps = {
		depth: 0,
		mode: DepartmentCardMode.UPPER,
		accent: false,
		chevron: false,
	};

	DepartmentCard.propTypes = {
		testId: PropTypes.string.isRequired,
		depth: PropTypes.number,
		mode: PropTypes.oneOf(Object.values(DepartmentCardMode)),
		accent: PropTypes.bool,
		onClick: PropTypes.func,
		departmentName: PropTypes.string.isRequired,
		employeesCountText: PropTypes.string,
		chevron: PropTypes.bool,
		employeeName: PropTypes.string,
		employeePosition: PropTypes.string,
		managerTitle: PropTypes.string,
		managerName: PropTypes.string,
		managerCounterValue: PropTypes.string,
		employeeCounterValue: PropTypes.string,
		managerAvatarProps: PropTypes.object,
		employeeAvatarProps: PropTypes.object,
	};

	module.exports = {
		DepartmentCard,
	};
});
