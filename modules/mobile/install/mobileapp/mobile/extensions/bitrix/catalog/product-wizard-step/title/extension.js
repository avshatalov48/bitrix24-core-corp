(() =>
{
	class FooterComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				sections: []
			};
		}

		render()
		{
			return View({
					style: CatalogProductWizardStepStyles.footer.container,
				},
				Text({
					style: CatalogProductWizardStepStyles.footer.text,
					text: BX.message('WIZARD_STEP_FOOTER_TEXT_PRODUCT')
				}),
				this.renderSections()
			);
		}

		renderSections()
		{
			const sections = this.state.sections.map(section => section.title);

			return View({
					style: {
						paddingTop: 20,
					},
					onClick: this.showSectionSelector.bind(this),
				},
				Text({
					style: CatalogProductWizardStepStyles.footer.link,
					text: BX.message('WIZARD_STEP_FOOTER_BIND_TO_SECTION')
				}),
				sections.length ?
					Text({
						style: {
							paddingTop: 8,
							fontSize: 13,
							color: '#A8ADB4',

						},
						text: BX.message('WIZARD_STEP_FOOTER_SECTION_BINDINGS').replace('#SECTIONS#', sections.join(', ')),
					})
					: null,
			);
		}

		showSectionSelector()
		{
			const selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.SECTION, {
				provider: {
					options: {
						iblockId: this.props.iblockId,
					},
				},
				createOptions: {
					enableCreation: true,
				},
				initSelectedIds: this.state.sections.map(section => section.id),
				events: {
					onClose: (sections) => {
						this.setState({sections});
						this.props.onChangeSection(sections);
					}
				},
				widgetParams: {
					backdrop: {
						mediumPositionPercent: 70,
					},
				},
				allowMultipleSelection: false,
			});

			selector.show();
		}
	}

	class CatalogProductTitleStep extends CatalogProductWizardStep
	{
		constructor(props)
		{
			super(props);
		}

		prepareFields()
		{
			this.clearFields();

			this.addField(
				'NAME',
				FieldFactory.Type.STRING,
				BX.message('WIZARD_FIELD_PRODUCT_NAME'),
				this.entity.get('NAME', ''),
				{
					required: true
				}
			);
			this.addField(
				'BARCODE',
				FieldFactory.Type.BARCODE,
				BX.message('WIZARD_FIELD_PRODUCT_BARCODE'),
				this.entity.get('BARCODE', ''),
			);
		}

		onMoveToNextStep()
		{
			return super.onMoveToNextStep()
				.then(() => this.entity.save())
			;
		}

		renderFooter()
		{
			return new FooterComponent({
				onChangeSection: (sections) => {
					const sectionIds = sections.map(section => section.id);
					this.onChange('SECTION_ID', sectionIds.length ? sectionIds[0] : 0);
					this.onChange('SECTION', sections.length ? sections[0] : null);
				},
				iblockId: this.entity.getIblockId(),
			})
		}
	}

	this.CatalogProductTitleStep = CatalogProductTitleStep;
})();
