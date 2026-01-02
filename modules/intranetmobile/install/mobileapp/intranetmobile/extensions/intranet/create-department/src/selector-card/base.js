/**
 * @module intranet/create-department/src/selector-card/base
 */

jn.define('intranet/create-department/src/selector-card/base', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Color, Indent } = require('tokens');
	const { Text4, Text5 } = require('ui-system/typography/text');
	const { Icon, IconView } = require('ui-system/blocks/icon');

	/**
	 * @typedef {Object} SelectorCardProps
	 * @property {string} testId
	 * @property {string} [props.title]
	 * @property {string} [props.text]
	 * @property {number} [props.selectedId]
	 * @property {boolean} [props.showBottomBorder = false]
	 * @property {boolean} [props.editable = true]
	 * @property {Function} [props.onSelected]
	 * @property {Function} [props.onViewHidden]

	 * @abstract
	 * @class SelectorCard
	 */
	class BaseSelectorCard extends PureComponent
	{
		#getTestId = (suffix) => {
			const prefix = this.props.testId;

			return suffix ? `${prefix}-${suffix}` : prefix;
		};

		render()
		{
			const { editable = true, showBottomBorder = false } = this.props;

			return View(
				{
					style: {
						width: '100%',
						marginTop: Indent.XL2.toNumber(),
						borderBottomWidth: showBottomBorder ? 1 : 0,
						borderBottomColor: Color.bgSeparatorSecondary.toHex(),
					},
					onClick: () => {
						if (editable)
						{
							this.onClick();
						}
					},
				},
				this.renderTitle(),
				this.renderDropDown(),
			);
		}

		renderTitle()
		{
			const { title } = this.props;

			return Text5({
				testId: this.#getTestId('selector-card-title-text'),
				text: title ?? '',
				color: Color.base3,
				numberOfLines: 1,
				ellipsize: 'end',
			});
		}

		renderDropDown()
		{
			const { text, editable = true } = this.props;

			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						alignItems: 'center',
						paddingVertical: Indent.XS2.toNumber(),
						marginBottom: Indent.XL.toNumber(),
					},
				},
				Text4({
					testId: this.#getTestId('selector-card-dropdown-text'),
					text: text ?? '',
					color: Color.base1,
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						flex: 1,
					},
				}),
				editable && IconView({
					icon: Icon.CHEVRON_DOWN,
					size: 20,
					color: Color.base2,
				}),
			);
		}

		/**
		 * @abstract
		 */
		onClick = () => {
			// need to implement in the child class
		};
	}

	module.exports = {
		/**
		 * @param {SelectorCardProps} props
		 */
		BaseSelectorCard,
	};
});
