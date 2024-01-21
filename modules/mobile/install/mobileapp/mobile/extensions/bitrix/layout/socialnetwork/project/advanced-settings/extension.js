(() => {
	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');

	class AdvancedSettings extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = null;
			this.state = {
				description: props.description,
				dateStart: props.dateStart,
				dateFinish: props.dateFinish,
				subjects: props.subjects,
				subject: props.subject,
				initiatePerms: props.initiatePerms,
				ownerData: props.ownerData,
				moderatorsData: props.moderatorsData,
				tags: props.tags,
			};
		}

		render()
		{
			return View(
				{
					resizableByKeyboard: true,
					style: {
						flex: 1,
						backgroundColor: AppTheme.colors.bgPrimary,
					},
				},
				ScrollView(
					{
						style: {
							flex: 1,
							borderRadius: 12,
						},
						bounces: false,
						showsVerticalScrollIndicator: true,
					},
					View(
						{
							style: {
								backgroundColor: AppTheme.colors.bgContentPrimary,
								borderRadius: 12,
								paddingVertical: 5,
								paddingHorizontal: 15,
								paddingBottom: 15,
							},
						},
						FieldsWrapper({
							fields: [
								new ProjectDescriptionField({
									value: this.state.description,
									onChange: (text) => this.setState({ description: text }),
								}),
								(this.state.subjects.length > 1 && new ProjectSubjectField({
									readOnly: false,
									value: this.state.subject,
									subjects: this.state.subjects,
									parentWidget: this.layoutWidget,
									onChange: (id, title) => this.setState({ subject: id }),
								})),
								new ProjectOwnerField({
									readOnly: false,
									value: this.state.ownerData.id,
									ownerData: this.state.ownerData,
									parentWidget: this.layoutWidget,
									onChange: (ownerId, ownerData) => {
										if (ownerId)
										{
											this.setState({
												ownerData: {
													id: ownerId,
													title: ownerData.title,
													imageUrl: ownerData.imageUrl,
												},
											});
										}
									},
								}),
								new ProjectModeratorsField({
									readOnly: false,
									value: this.state.moderatorsData.map((item) => item.id),
									moderatorsData: this.state.moderatorsData,
									parentWidget: this.layoutWidget,
									onChange: (moderatorsIds, moderatorsData) => {
										if (moderatorsIds)
										{
											this.setState({ moderatorsData });
										}
									},
								}),
								new ProjectDateStartField({
									readOnly: false,
									value: this.state.dateStart,
									onChange: (date) => this.setState({ dateStart: date }),
								}),
								new ProjectDateFinishField({
									readOnly: false,
									value: this.state.dateFinish,
									onChange: (date) => this.setState({ dateFinish: date }),
								}),
								new ProjectTagsField({
									readOnly: false,
									value: this.state.tags,
									projectId: 0,
									parentWidget: this.layoutWidget,
									onChange: (tags) => this.setState({ tags }),
								}),
								new ProjectInitiatePermsField({
									value: this.state.initiatePerms,
									parentWidget: this.layoutWidget,
									onChange: (id, title) => this.setState({ initiatePerms: id }),
								}),
							],
						}),
					),
				),
			);
		}

		getSaveButton()
		{
			return {
				name: BX.message('MOBILE_LAYOUT_PROJECT_ADVANCED_SETTINGS_HEADER_BUTTON_NEXT'),
				callback: this.onSaveButtonClick.bind(this),
				color: AppTheme.colors.accentMainLinks,
			};
		}

		onSaveButtonClick()
		{
			this.layoutWidget.close(() => {
				this.props.onFieldsSave({
					description: this.state.description,
					dateStart: this.state.dateStart,
					dateFinish: this.state.dateFinish,
					subject: this.state.subject,
					initiatePerms: this.state.initiatePerms,
					ownerData: this.state.ownerData,
					moderatorsData: this.state.moderatorsData,
					tags: this.state.tags,
				});
			});
		}
	}

	class AdvancedSettingsManager
	{
		static open(data, parentWidget = null)
		{
			const advancedSettings = new AdvancedSettings(data);

			parentWidget = (parentWidget || PageManager);
			parentWidget.openWidget('layout', {
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					bounceEnable: true,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
					navigationBarColor: AppTheme.colors.bgSecondary,
				},
				title: BX.message('MOBILE_LAYOUT_PROJECT_ADVANCED_SETTINGS_HEADER'),
				onReady: (layoutWidget) => {
					layoutWidget.setRightButtons([advancedSettings.getSaveButton()]);
					layoutWidget.enableNavigationBarBorder(false);
					layoutWidget.showComponent(advancedSettings);

					advancedSettings.layoutWidget = layoutWidget;
				},
				onError: console.error,
			});
		}
	}

	this.AdvancedSettings = AdvancedSettings;
	this.AdvancedSettingsManager = AdvancedSettingsManager;
})();
