(() =>
{
	class SelectorTestComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				product: null,
				sections: [],
				contractor: null,
				store: null,
				multipleSections: {
					value: [],
					entityList: []
				},
				singleUser: {
					value: 1,
					entityList: [
						{
							id: 1,
							title: 'admin'
						}
					]
				},
			};
		}

		render()
		{
			const selector1 = FieldFactory.create('entity-selector', {
				title: 'multiple SELECTOR',
				value: this.state.multipleSections.value,
				readOnly: false,
				multiple: true,
				config: {
					selectorType: 'section',
					enableCreation: true,
					provider: {
						options: result['section']
					},
					entityList: this.state.multipleSections.entityList,
				},
				onChange: (value, entityList) => this.setState({multipleSections: {value, entityList}})
			});

			const selector2 = FieldFactory.create('entity-selector', {
				title: 'single SELECTOR',
				value: this.state.singleUser.value,
				readOnly: false,
				multiple: false,
				config: {
					selectorType: 'section',
					enableCreation: true,
					provider: {
						options: result['section']
					},
					entityList: this.state.singleUser.entityList,
				},
				onChange: (value, entityList) => this.setState({singleUser: {value, entityList}})
			});

			return ScrollView(
				{
					style: {
						flex: 1,
					}
				},
				View(
					{
						style: {
							padding: 20,
						}
					},
					selector1,
					selector2,
					/**
					 * Product
					 */
					View(
						{
							style: {
								height: 100,
								backgroundColor: '#f0ebdf',
								justifyContent: 'center',
							},
							onClick: () => {
								const selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.PRODUCT, {
									provider: {
										options: result.product,
									},
									allowMultipleSelection: false,
									events: {
										onClose: (products) => {
											if (products && products.length > 0)
											{
												const product = products[0];
												this.setState({product});
											}
										}
									},
									widgetParams: {
										backdrop: {
											mediumPositionPercent: 70,
										}
									}
								});
								selector.show();
							}
						},
						Text({
							style: {
								fontSize: 18,
								textAlign: 'center'
							},
							text: BX.message('SELECTOR_COMPONENT_PICK_PRODUCT') + ': ' + JSON.stringify(this.state.product)
						})
					),
					/**
					 * Sections
					 */
					View(
						{
							style: {
								marginTop: 30,
								height: 100,
								backgroundColor: '#d8e1e3',
								justifyContent: 'center',
							},
							onClick: () => {
								const selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.SECTION, {
									provider: {
										options: result.section,
									},
									createOptions: {
										enableCreation: true,
									},
									initSelectedIds: [16, 19],
									allowMultipleSelection: true,
									events: {
										onClose: sections => this.setState({sections}),
									},
									widgetParams: {
										backdrop: {
											mediumPositionPercent: 70,
										}
									}
								});
								selector.show();
							}
						},
						Text({
							style: {
								fontSize: 20,
								textAlign: 'center'
							},
							text: BX.message('SELECTOR_COMPONENT_PICK_SECTION') + ': ' + JSON.stringify(this.state.sections)
						})
					),
					/**
					 * Contractor
					 */
					View(
						{
							style: {
								marginTop: 30,
								height: 100,
								backgroundColor: '#d8e1e3',
								justifyContent: 'center',
							},
							onClick: () => {
								const selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.CONTRACTOR,{
									allowMultipleSelection: false,
									events: {
										onClose: (contractors) => {
											if (contractors && contractors.length > 0)
											{
												const contractor = contractors[0];
												this.setState({contractor});
											}
										}
									},
									widgetParams: {
										backdrop: {
											mediumPositionPercent: 70,
										}
									}
								});
								selector.show();
							}
						},
						Text({
							style: {
								fontSize: 20,
								textAlign: 'center'
							},
							text: BX.message('SELECTOR_COMPONENT_PICK_CONTRACTOR') + ': ' + JSON.stringify(this.state.contractor)
						})
					),
					/**
					 * Store
					 */
					View(
						{
							style: {
								marginTop: 30,
								height: 100,
								backgroundColor: '#d8e1e3',
								justifyContent: 'center',
							},
							onClick: () => {
								const selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.STORE, {
									allowMultipleSelection: false,
									events: {
										onClose: (stores) => {
											if (stores && stores.length > 0)
											{
												const store = stores[0];
												this.setState({store});
											}
										}
									},
									widgetParams: {
										backdrop: {
											mediumPositionPercent: 70,
										}
									}
								});
								selector.show();
							}
						},
						Text({
							style: {
								fontSize: 20,
								textAlign: 'center'
							},
							text: BX.message('SELECTOR_COMPONENT_PICK_STORE') + ': ' + JSON.stringify(this.state.store)
						})
					),
				)
			);
		}
	}

	BX.onViewLoaded(() =>
	{
		layout.showComponent(new SelectorTestComponent())
	});
})();
