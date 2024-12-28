/**
 * @module tasks/layout/fields/user-fields/view/field/base
 */
jn.define('tasks/layout/fields/user-fields/view/field/base', (require, exports, module) => {
	const { BaseField } = require('tasks/layout/fields/user-fields/field/base');
	const { IconView } = require('ui-system/blocks/icon');
	const { Text5 } = require('ui-system/typography');
	const { CollapsibleText } = require('layout/ui/collapsible-text');
	const { Indent, Color, Typography } = require('tokens');

	class ViewBaseField extends BaseField
	{
		render()
		{
			if (this.isEmpty)
			{
				return View({ style: { display: 'none' } });
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						paddingTop: Indent.S.toNumber() + (this.props.isFirst ? 0 : Indent.M.toNumber()),
					},
				},
				IconView({
					size: 24,
					icon: this.icon,
					color: Color.accentMainPrimary,
				}),
				View(
					{
						style: {
							flex: 1,
							marginLeft: Indent.M.toNumber(),
							paddingBottom: Indent.XL.toNumber(),
							borderBottomWidth: this.props.isLast ? 0 : 1,
							borderBottomColor: Color.bgSeparatorPrimary.toHex(),
						},
					},
					this.renderTitle(),
					this.renderValues(),
				),
			);
		}

		renderTitle()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				Text5({
					style: {
						flexShrink: 1,
					},
					color: Color.base3,
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.props.title,
					testId: `${this.testId}_TITLE`,
				}),
				this.isMandatory && Text5({
					style: {
						marginLeft: Indent.XS.toNumber(),
					},
					text: '*',
					color: Color.accentMainAlert,
					testId: `${this.testId}_MANDATORY`,
				}),
			);
		}

		renderValues()
		{
			return View(
				{},
				...(
					this.isMultiple
						? this.renderMultipleValues()
						: [this.renderSingleValue(this.prepareValue(this.state.value))]
				),
			);
		}

		renderMultipleValues()
		{
			return this.state.value.map((value) => this.renderSingleValue(this.prepareValue(value)));
		}

		renderSingleValue(value)
		{
			return new CollapsibleText({
				value,
				containerStyle: {
					paddingTop: Indent.S.toNumber(),
				},
				style: {
					...Typography.text4.getStyle(),
					color: Color.base1.toHex(),
				},
				bbCodeMode: false,
				canExpand: true,
				maxLettersCount: 100,
				testId: `${this.testId}_VALUE`,
				onClick: this.props.onClick,
			});
		}

		prepareValue(value)
		{
			return value;
		}
	}

	module.exports = { ViewBaseField };
});
