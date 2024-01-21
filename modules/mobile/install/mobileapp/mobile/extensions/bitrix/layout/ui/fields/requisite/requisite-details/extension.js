/**
 * @module layout/ui/fields/requisite/requisite-details
 */
jn.define('layout/ui/fields/requisite/requisite-details', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Container, Island, Title, FormGroup } = require('layout/ui/islands');
	const { StringField } = require('layout/ui/fields/string');
	const { Loc } = require('loc');
	const { get } = require('utils/object');
	const { stringify } = require('utils/string');
	const { EmptyScreen } = require('layout/ui/empty-screen');

	/**
	 * @class RequisiteDetails
	 */
	class RequisiteDetails extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layout = props.layout || layout;
		}

		componentDidMount()
		{
			this.layout.setRightButtons([
				{
					name: Loc.getMessage('PRODUCT_GRID_PRODUCT_DETAILS_CLOSE'),
					type: 'text',
					color: AppTheme.colors.accentMainLinks,
					callback: () => this.layout.close(),
				},
			]);
			this.layout.enableNavigationBarBorder(false);
		}

		get items()
		{
			return Array.isArray(this.props.items) ? this.props.items : [];
		}

		getRequisiteTitle()
		{
			return stringify(get(this.getSelectedRequisite(), ['requisiteData', 'fields', 'NAME'], ''));
		}

		renderRequisiteFields()
		{
			return (
				this
					.getSelectedRequisiteFields()
					.map((field) => StringField({
						title: field.title,
						value: field.textValue,
						readOnly: true,
						required: false,
					}))
			);
		}

		getSelectedRequisite()
		{
			return this.items.find((requisite) => requisite.selected);
		}

		getSelectedRequisiteFields()
		{
			return get(this.getSelectedRequisite(), ['requisiteData', 'viewData', 'fields'], []);
		}

		render()
		{
			if (this.getSelectedRequisiteFields().length === 0)
			{
				return new EmptyScreen({
					image: {
						uri: EmptyScreen.makeLibraryImagePath('empty-list.svg'),
						style: {
							width: 95,
							height: 95,
						},
					},
					title: () => Text({
						style: {
							fontWeight: '400',
							color: AppTheme.colors.base3,
							fontSize: 17,
							textAlign: 'center',
						},
						text: BX.message('MCRM_REQUISITE_DETAILS_EMPTY_LIST'),
					}),
				});
			}

			return Container(
				Island(
					this.getRequisiteTitle() && Title(this.getRequisiteTitle()),
					FormGroup(...this.renderRequisiteFields()),
				),
			);
		}
	}

	module.exports = { RequisiteDetails };
});
