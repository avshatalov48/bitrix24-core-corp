/**
 * @module more-menu/block/header
 */
jn.define('more-menu/block/header', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { isModuleInstalled } = require('module');
	const { PureComponent } = require('layout/pure-component');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { PropTypes } = require('utils/validation');
	const { createTestIdGenerator } = require('utils/test');

	const { PersonInfo } = require('more-menu/block/header/person-info');
	const { WorkTime } = require('more-menu/block/header/worktime');
	const { CheckIn } = require('more-menu/block/header/check-in');

	/**
	 * @class MoreMenuHeader
	 */
	class MoreMenuHeader extends PureComponent
	{
		/**
		 * @param {object} props
		 * @param {string} props.testId
		 * @param {object} props.currentShift
		 * @param {object} props.workTime
		 * @param {boolean} props.canEditProfile
		 * @param {boolean} props.canUseTimeMan
		 * @param {boolean} props.canUseCheckIn
		 */
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				prefix: props.testId,
			});
		}

		render()
		{
			const {
				canEditProfile,
				canUseTimeMan,
				currentShift,
				workTime,
			} = this.props;

			return View(
				{
					style: {
						padding: Indent.XL3.toNumber(),
						paddingTop: Indent.M.toNumber(),
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				PersonInfo({
					testId: this.getTestId('person-info'),
					userId: Number(env.userId),
					canEditProfile,
				}),
				(this.shouldShowCheckIn() || canUseTimeMan) && Card(
					{
						testId: this.getTestId('day-info'),
						style: {
							marginTop: Indent.XL4.toNumber(),
							borderColor: Color.bgSeparatorSecondary.toHex(),
						},
						border: true,
						design: CardDesign.PRIMARY,
						excludePaddingSide: {
							top: true,
							bottom: true,
							left: true,
							right: true,
						},
					},
					canUseTimeMan && new WorkTime({
						testId: this.getTestId('work-time'),
						showBorder: this.shouldShowCheckIn(),
						workTime,
					}),
					this.shouldShowCheckIn() && new CheckIn({
						testId: this.getTestId('check-in'),
						currentShift,
					}),
				),
			);
		}

		shouldShowCheckIn()
		{
			const { canUseCheckIn } = this.props;

			return isModuleInstalled('stafftrack') && canUseCheckIn;
		}
	}

	MoreMenuHeader.propTypes = {
		testId: PropTypes.string.isRequired,
		currentShift: PropTypes.object,
		workTime: PropTypes.object,

		canEditProfile: PropTypes.bool,
		canUseTimeMan: PropTypes.bool,
		canUseCheckIn: PropTypes.bool,
	};

	module.exports = {
		MoreMenuHeader,
	};
});
