/**
 * @module crm/ui/entity-boolean
 */
jn.define('crm/ui/entity-boolean', (require, exports, module) => {
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
		[TypeId.Deal]: '#8c78ef',
		[TypeId.Contact]: '#9dcf00',
		[TypeId.Company]: '#e89b06',
		[TypeId.Quote]: '#00b4ac',
		[TypeId.SmartInvoice]: '#1e6ec2',
	};

	const ENTITY_BACKGROUND_COLORS = {
		[TypeId.Deal]: '#f2e9fe',
		[TypeId.Company]: '#fff1d6',
		[TypeId.Contact]: '#f1fbd0',
		[TypeId.Quote]: '#d3f9f7',
		[TypeId.SmartInvoice]: '#deeeff',
	};

	const DISABLED_COLOR = {
		color: '#bdc1c6',
		backgroundColor: '#f1f4f6',
	};

	/**
	 * @class WizardFields
	 */
	class EntityBoolean extends LayoutComponent
	{
		getBooleanFieldsProps()
		{
			const { enable, entityTypeId, onChange, simple } = this.props;

			const styles = simple ? {} : {
				activeToggleColor: ENTITY_COLORS[entityTypeId],
			};

			return {
				id: entityTypeId,
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
				onChange: () => {
					onChange(entityTypeId, !enable);
				},
			};
		}

		renderText()
		{
			const { enable, text, disabledText } = this.props;
			const color = enable ? '#333333' : '#bdc1c6';

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
