/**
 * @module calendar/event-edit-form/layout/section-info
 */
jn.define('calendar/event-edit-form/layout/section-info', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color, Indent, Corner } = require('tokens');

	const { Link4 } = require('ui-system/blocks/link');
	const { Text4 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	const { State, observeState } = require('calendar/event-edit-form/state');
	const { SectionManager } = require('calendar/data-managers/section-manager');
	const { SettingsManager } = require('calendar/data-managers/settings-manager');
	const { Selector } = require('calendar/event-edit-form/selector');
	const { CalendarType } = require('calendar/enums');

	class SectionInfo extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.componentLoaded = false;
			this.pendingSectionId = null;
		}

		componentDidMount()
		{
			this.componentLoaded = true;

			if (this.pendingSectionId !== null)
			{
				setTimeout(() => {
					State.setSectionId(Number(this.pendingSectionId));
					this.pendingSectionId = null;
				}, 0);
			}
		}

		get sectionId()
		{
			return this.props.sectionId;
		}

		get collabId()
		{
			return this.props.collabId;
		}

		get editAttendeesMode()
		{
			return this.props.editAttendeesMode;
		}

		render()
		{
			const section = this.getAvailableSection();

			if (!section)
			{
				return null;
			}

			return View(
				{
					style: {
						marginTop: Indent.XS.toNumber() * 2,
						alignItems: 'center',
						flexDirection: 'row',
					},
					onClick: this.isSelectorInViewMode(section) ? null : this.openSectionSelector,
				},
				this.renderColor(section),
				this.renderTitle(),
				this.renderName(section),
			);
		}

		renderTitle()
		{
			return Text4({
				text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_SECTION'),
				color: Color.base4,
				ellipsize: 'end',
				numberOfLines: 1,
				style: {
					marginRight: Indent.XS.toNumber(),
				},
			});
		}

		renderColor(section)
		{
			return View(
				{
					style: {
						alignItems: 'center',
						justifyContent: 'center',
						marginRight: Indent.S.toNumber(),
						backgroundColor: section.getColor(),
						width: 22,
						height: 22,
						borderRadius: Corner.XS.toNumber(),
					},
				},
				IconView({
					icon: Icon.CALENDAR,
					size: 20,
					color: Color.baseWhiteFixed,
				}),
			);
		}

		renderName(section)
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				this.isSelectorInViewMode(section)
					? this.renderNameReadOnly(section)
					: this.renderNameLink(section)
				,
			);
		}

		renderNameLink(section)
		{
			return Link4({
				testId: 'calendar-event-edit-form-section-selector',
				onClick: this.openSectionSelector,
				useInAppLink: false,
				numberOfLines: 1,
				text: section.getName(),
				color: Color.base3,
				rightIcon: Icon.CHEVRON_DOWN,
				style: {
					marginRight: Indent.XS.toNumber(),
				},
			});
		}

		renderNameReadOnly(section)
		{
			return Text4({
				testId: 'calendar-event-edit-form-section-selector-readonly',
				numberOfLines: 1,
				text: section.getName(),
				color: Color.base3,
				style: {
					marginRight: Indent.XS.toNumber(),
				},
			});
		}

		openSectionSelector = () => {
			const { sections: items, categories } = this.getSelectorData();
			const { id: selectedId, categoryId: selectedCategoryId } = this.getAvailableSection();

			Selector.open({
				parentLayout: this.props.layout,
				selectedId,
				onItemClick: this.onSectionSelected,
				items,
				categories,
				selectedCategoryId,
				selectorIcon: Icon.CALENDAR,
				isSection: true,
			});
		};

		onSectionSelected = (sectionId) => {
			State.setSectionId(Number(sectionId));
		};

		getSections()
		{
			return this.isCollabContext() || this.collabId > 0
				? SectionManager.getCollabSectionsForEdit()
				: SectionManager.getActiveSectionsForEdit()
			;
		}

		getSelectorData()
		{
			const defaultSections = this.getSections();

			if (env.isCollaber || this.collabId > 0)
			{
				return {
					sections: defaultSections,
					categories: [],
				};
			}

			const sections = [
				...this.getSectionsWithCategory(defaultSections, this.baseCategoryFilterFunc, categoryId.base),
			];
			const categories = [this.getBaseCategory()];

			const sectionsByType = {
				personal: this.getSectionsWithCategory(defaultSections, this.personalCategoryFilterFunc, categoryId.personal),
				company: this.getSectionsWithCategory(defaultSections, this.companyCategoryFilterFunc, categoryId.company),
				group: this.getSectionsWithCategory(defaultSections, this.groupCategoryFilterFunc, categoryId.group),
				collab: this.getSectionsWithCategory(defaultSections, this.collabCategoryFilterFunc, categoryId.collab),
			};

			let hasAdditionalSections = false;
			Object.entries(sectionsByType).forEach(([categoryType, value]) => {
				if (Type.isArrayFilled(value))
				{
					hasAdditionalSections = true;
					sections.push(...value);
					categories.push(this.getCategoryByType(categoryType));
				}
			});

			const { userSections, userCategories } = this.getUserSections(defaultSections);
			if (Type.isArrayFilled(userSections))
			{
				hasAdditionalSections = true;
				sections.push(...userSections);
				categories.push(...userCategories);
			}

			if (hasAdditionalSections)
			{
				return {
					sections,
					categories,
				};
			}

			return {
				sections,
				categories: [],
			};
		}

		getBaseCategory()
		{
			return {
				name: this.getBaseCategoryName(),
				id: categoryId.base,
			};
		}

		getBaseCategoryName()
		{
			if (State.calType === CalendarType.USER)
			{
				return Number(env.userId) === State.ownerId
					? Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_SECTION_CATEGORY_PERSONAL')
					: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_SECTION_CATEGORY_USER')
				;
			}

			if (State.calType === CalendarType.COMPANY_CALENDAR)
			{
				return Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_SECTION_CATEGORY_COMPANY');
			}

			return SettingsManager.isCollabCalendarContext()
				? Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_SECTION_CATEGORY_THIS_COLLAB')
				: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_SECTION_CATEGORY_THIS_GROUP')
			;
		}

		getCategoryByType(type)
		{
			return {
				id: categoryId[type],
				name: Loc.getMessage(`M_CALENDAR_EVENT_EDIT_FORM_SECTION_CATEGORY_${type.toUpperCase()}`),
			};
		}

		getCategoryByUser(user)
		{
			const userId = Number(user.ID);

			return {
				id: this.getUserCategoryId(userId),
				name: user.FORMATTED_NAME,
			};
		}

		getSectionsWithCategory(sections, filterFunc, categoryId)
		{
			return sections
				.filter((section) => filterFunc(section))
				.map((section) => {
					section.setCategoryId(categoryId);

					return section;
				})
			;
		}

		getUserSections(defaultSections)
		{
			const result = {
				userSections: [],
				userCategories: [],
			};

			const trackingUserList = SectionManager.getTrackingUserList();

			if (!Type.isArrayFilled(trackingUserList))
			{
				return result;
			}

			for (const user of trackingUserList)
			{
				const userId = Number(user.ID);
				if (userId === Number(env.userId))
				{
					continue;
				}

				const sections = this.getUsersSectionsWithCategory(defaultSections, userId);

				if (Type.isArrayFilled(sections))
				{
					result.userSections.push(...sections);
					result.userCategories.push(this.getCategoryByUser(user));
				}
			}

			return result;
		}

		getUsersSectionsWithCategory(sections, userId)
		{
			const categoryId = this.getUserCategoryId(userId);

			return sections
				.filter((section) => this.userCategoryFilterFunc(section, userId))
				.map((section) => {
					section.setCategoryId(categoryId);

					return section;
				})
			;
		}

		getUserCategoryId(userId)
		{
			return `user_${userId}`;
		}

		baseCategoryFilterFunc = (section) => {
			return this.belongsToView(section);
		};

		personalCategoryFilterFunc = (section) => {
			return !this.belongsToView(section) && section.isUserCalendar(Number(env.userId));
		};

		companyCategoryFilterFunc = (section) => {
			return !this.belongsToView(section) && section.isCompanyCalendar();
		};

		groupCategoryFilterFunc = (section) => {
			return !this.belongsToView(section) && section.isGroupCalendar() && !section.isCollab();
		};

		collabCategoryFilterFunc = (section) => {
			return !this.belongsToView(section) && section.isGroupCalendar() && section.isCollab();
		};

		userCategoryFilterFunc = (section, userId) => {
			return !this.belongsToView(section) && section.isUserCalendar(userId);
		};

		getAvailableSection()
		{
			let result = State.isEditForm()
				? this.findSectionForEditEvent()
				: this.findSectionForCreateEvent()
			;

			if (result?.id)
			{
				if (Number(result.id) !== Number(this.sectionId))
				{
					this.handleDifferentSectionSelected(result.id);
				}

				return result;
			}

			result = this.getSections().find((section) => this.belongsToView(section));

			if (result?.id)
			{
				this.handleDifferentSectionSelected(result.id);

				return result;
			}

			return null;
		}

		findSectionForEditEvent()
		{
			let result = null;

			if (!this.isCollabContext() && this.collabId > 0)
			{
				result = SectionManager.getCollabSection(this.sectionId);

				if (!result?.id)
				{
					result = SectionManager.getCollabSectionByCollabId(this.collabId);
				}
			}
			else
			{
				result = this.isCollabContext()
					? SectionManager.getCollabSection(this.sectionId)
					: SectionManager.getSection(this.sectionId)
				;
			}

			return result;
		}

		findSectionForCreateEvent()
		{
			const sections = this.getSections();

			if (this.isCollabContext())
			{
				return sections.find((section) => {
					return section.getId() === Number(this.sectionId);
				}) ?? sections[0];
			}

			if (this.componentLoaded)
			{
				return sections.find((section) => {
					return section.getId() === Number(this.sectionId);
				});
			}

			return sections.find((section) => {
				return section.getId() === Number(this.sectionId) && this.belongsToView(section);
			});
		}

		handleDifferentSectionSelected(sectionId)
		{
			if (this.componentLoaded)
			{
				State.setSectionId(Number(sectionId));
			}
			else
			{
				this.pendingSectionId = sectionId;
			}
		}

		isCollabContext()
		{
			return State.calType === CalendarType.USER && env.isCollaber;
		}

		isSelectorInViewMode(section)
		{
			return (State.isEditForm() && section.isSyncSection()) || this.editAttendeesMode;
		}

		belongsToView(section)
		{
			return SectionManager.belongsToView(section, State.ownerId, State.calType);
		}
	}

	const categoryId = {
		base: 1,
		personal: 2,
		company: 3,
		group: 4,
		collab: 5,
	};

	const mapStateToProps = (state) => ({
		sectionId: state.sectionId,
		collabId: state.collabId,
		editAttendeesMode: state.editAttendeesMode,
	});

	module.exports = { SectionInfo: observeState(SectionInfo, mapStateToProps) };
});
