(() => {
	/**
	 * @class EntityEditorSection
	 */
	class EntityEditorSection extends EntityEditorBaseControl
	{
		constructor(props)
		{
			super(props);
			this.initialize(props.id, props.settings, props.type);

			/** @type {EntityEditorField[]} */
			this.fields = [];
			this.title = this.schemeElement.getTitle();

			this.openQrPopup = this.openQrPopup.bind(this);
		}

		/**
		 * @returns {EntityEditorField[]}
		 */
		getControls()
		{
			return this.fields;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						paddingBottom: 12,
						backgroundColor: '#eef2f4'
					}
				},
				View(
					{
						style: {
							paddingTop: 8,
							paddingBottom: 8,
							paddingLeft: 16,
							paddingRight: 16,
							borderRadius: 12,
							backgroundColor: '#ffffff'
						}
					},
					Text({
						style: {
							color: '#333333',
							fontWeight: 'bold',
							fontSize: 16,
							width: '100%',
							textAlign: 'left',
							paddingTop: 6,
							paddingBottom: 8
						},
						text: String(this.title)
					}),
					this.renderFields()
				),
				this.editor.canChangeScheme() ? this.renderSectionManaging() : null
			)
		}

		renderFieldsAndSaveRefs() {
			return this.renderFromModel((ref, index) => {
				this.fields[index] = ref;
			});
		}

		renderFields()
		{
			if (this.readOnly)
			{
				return View({}, ...this.renderFieldsAndSaveRefs());
			}

			return FieldsWrapper({fields: this.renderFieldsAndSaveRefs()});
		}

		renderSectionManaging()
		{
			return View(
				{
					style: {
						paddingTop: 6,
						paddingLeft: 16,
						paddingRight: 16,
						flexDirection: 'row',
						justifyContent: 'space-between'
					}
				},
				View(
					{
						style: {
							flexDirection: 'row',
							flex: 1,
						}
					},
					View(
						{
							style: {
								borderBottomWidth: 1,
								borderBottomColor: '#b8c0c9',

								borderStyle: 'dash',
								borderDashWidth: 1,
								borderDashGap: 2,

								marginRight: 21
							},
							onClick: this.openQrPopup
						},
						Text(
							{
								style: {
									color: '#b8bfc9',
									fontSize: 13
								},
								text: BX.message('ENTITY_EDITOR_SECTION_SELECT_FIELD')
							}
						)
					),
					View(
						{
							style: {
								borderBottomWidth: 1,
								borderBottomColor: '#b8c0c9',

								borderStyle: 'dash',
								borderDashWidth: 1,
								borderDashGap: 2,
							},
							onClick: this.openQrPopup
						},
						Text(
							{
								style: {
									color: '#b8bfc9',
									fontSize: 13
								},
								text: BX.message('ENTITY_EDITOR_SECTION_ADD_FIELD')
							}
						)
					)
				),

				View(
					{
						style: {
							borderBottomWidth: 1,
							borderBottomColor: '#b8c0c9',

							borderStyle: 'dash',
							borderDashWidth: 1,
							borderDashGap: 2,
						},
						onClick: this.openQrPopup
					},
					Text(
						{
							style: {
								color: '#b8bfc9',
								fontSize: 13
							},
							text: BX.message('ENTITY_EDITOR_SECTION_SETTINGS')
						}
					)
				)
			)
		}

		openQrPopup()
		{
			qrauth.open({
				redirectUrl: this.editor.getDesktopUrl()
			});
		}

		validate(result)
		{
			const validator = EntityAsyncValidator.create();

			this.fields.forEach((field) => {
				validator.addResult(field.validate(result));
			});

			return validator.validate();
		}
	}

	jnexport(EntityEditorSection);
})();