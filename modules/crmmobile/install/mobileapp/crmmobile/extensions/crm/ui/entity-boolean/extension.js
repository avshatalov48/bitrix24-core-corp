/**
 * @module crm/ui/entity-boolean
 */
jn.define('crm/ui/entity-boolean', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { BooleanField } = require('layout/ui/fields/boolean');
	const { TypeId } = require('crm/type');
	const { EntitySvg } = require('crm/assets/entity');

	const ENTITY_ICONS = {
		[TypeId.Deal]: EntitySvg.dealInverted,
		[TypeId.Contact]: EntitySvg.contactInverted,
		[TypeId.Company]: EntitySvg.companyInverted,
		[TypeId.Quote]: EntitySvg.quoteInverted,
		[TypeId.SmartInvoice]: EntitySvg.smartInvoiceInverted,
	};

	const ENTITY_COLORS = {
		[TypeId.Deal]: AppTheme.colors.accentExtraPurple,
		[TypeId.Contact]: AppTheme.colors.accentMainSuccess,
		[TypeId.Company]: AppTheme.colors.accentMainWarning,
		[TypeId.Quote]: AppTheme.colors.accentExtraAqua,
		[TypeId.SmartInvoice]: AppTheme.colors.accentMainLinks,
	};

	const ENTITY_BACKGROUND_COLORS = {
		[TypeId.Deal]: AppTheme.colors.accentSoftRed2,
		[TypeId.Company]: AppTheme.colors.accentSoftOrange2,
		[TypeId.Contact]: AppTheme.colors.accentSoftGreen2,
		[TypeId.Quote]: AppTheme.colors.accentSoftBlue1,
		[TypeId.SmartInvoice]: AppTheme.colors.accentSoftBlue2,
	};

	const DISABLED_COLOR = {
		color: AppTheme.colors.base5,
		backgroundColor: AppTheme.colors.bgContentTertiary,
	};

	/**
	 * @class EntityBoolean
	 */
	class EntityBoolean extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.handleOnChange = this.handleOnChange.bind(this);
		}

		getBooleanFieldsProps()
		{
			const { enable, entityTypeId, simple } = this.props;

			const styles = simple ? {} : {
				activeToggleColor: ENTITY_COLORS[entityTypeId],
			};

			return {
				id: entityTypeId,
				testId: `CrmEntityBooleanField-${entityTypeId}-${enable}`,
				value: enable,
				config: {
					description: View(
						{
							style: {
								marginLeft: 8,
							},
						},
						this.renderText(),
					),
					styles,
				},
				showTitle: false,
				readOnly: false,
				onChange: this.handleOnChange,
			};
		}

		handleOnChange()
		{
			const { enable, entityTypeId, onChange } = this.props;

			onChange(entityTypeId, !enable);
		}

		renderText()
		{
			const { enable, text, disabledText } = this.props;
			const color = enable ? AppTheme.colors.base1 : AppTheme.colors.base5;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				this.renderImage(enable),
				Text({
					style: {
						color,
						fontSize: 16,
						flexShrink: 2,
					},
					text: enable ? text : disabledText || text,
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			);
		}

		renderImage(enable)
		{
			const { entityTypeId } = this.props;
			const content = this.getEntitySvg(entityTypeId, enable);

			if (!content)
			{
				return null;
			}

			const size = entityTypeId ? 30 : 24;

			return Image({
				resizeMode: 'cover',
				style: {
					width: size,
					height: size,
					marginRight: 8,
				},
				svg: { content },
			});
		}

		getEntitySvg(entityTypeId, enable)
		{
			const icon = ENTITY_ICONS[entityTypeId];
			const color = enable ? undefined : DISABLED_COLOR.color;

			return icon && icon(color);
		}

		renderEntityBlock(booleanField)
		{
			const { enable, entityTypeId } = this.props;

			return View(
				{
					style: {
						paddingTop: 4,
						paddingHorizontal: 16,
						backgroundColor: enable ? ENTITY_BACKGROUND_COLORS[entityTypeId] : DISABLED_COLOR.backgroundColor,
						borderRadius: 8,
						...this.getStyles('block'),
					},
				},
				booleanField,
			);
		}

		getStyles(type)
		{
			const { styles = {} } = this.props;

			return styles[type] || {};
		}

		render()
		{
			const { simple } = this.props;
			const booleanField = BooleanField(this.getBooleanFieldsProps());

			return View(
				{
					style: {
						width: '100%',
					},
				},
				simple ? booleanField : this.renderEntityBlock(booleanField),
			);
		}
	}

	module.exports = {
		EntityBoolean: (props) => new EntityBoolean(props),
	};
});
