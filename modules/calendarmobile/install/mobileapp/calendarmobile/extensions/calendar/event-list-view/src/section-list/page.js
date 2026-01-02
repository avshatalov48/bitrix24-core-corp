/**
 * @module calendar/event-list-view/section-list/page
 */
jn.define('calendar/event-list-view/section-list/page', (require, exports, module) => {
	const { BottomSheet } = require('bottom-sheet');
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { Type } = require('type');

	const { Box } = require('ui-system/layout/box');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Area } = require('ui-system/layout/area');

	const { SectionManager } = require('calendar/data-managers/section-manager');
	const { SectionListManager } = require('calendar/event-list-view/section-list/manager');
	const { SectionListHeader } = require('calendar/event-list-view/section-list/header');
	const { SectionListItem } = require('calendar/event-list-view/section-list/item');
	const { State } = require('calendar/event-list-view/state');

	/**
	 * @class SectionList
	 */
	class SectionListPage extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = null;
			this.hasChanges = false;

			this.state = {
				hiddenSections: State.hiddenSections,
			};

			this.sectionListManager = new SectionListManager({
				ownerId: State.ownerId,
				calType: State.calType,
			});

			this.sections = this.sectionListManager.getData();
		}

		componentDidMount()
		{
			this.layoutWidget.preventBottomSheetDismiss(true);
			this.layoutWidget.on('preventDismiss', this.onPreventDismiss);
		}

		onPreventDismiss = () => {
			if (this.hasChanges)
			{
				this.saveState();
			}

			this.layoutWidget.close();
		};

		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				this.renderContent(),
			);
		}

		renderContent()
		{
			return Box(
				{
					withPaddingHorizontal: true,
					style: {
						flex: 1,
					},
				},
				View(
					{
						style: {
							flex: 1,
							backgroundColor: Color.bgContentPrimary.toHex(),
						},
					},
					this.renderList(),
				),
			);
		}

		renderList()
		{
			return AreaList(
				{
					withScroll: true,
					divided: true,
					showsVerticalScrollIndicator: false,
					flex: 1,
				},
				...this.renderListItems(),
			);
		}

		/**
		 * @param header {string}
		 * @param sections {array}
		 * @returns {Area}
		 */
		renderSectionArea(header, sections)
		{
			return Area(
				{
					isFirst: true,
					excludePaddingSide: {
						horizontal: true,
					},
				},
				SectionListHeader(header),
				...sections.map((section) => this.renderSection(section)),
			);
		}

		renderSection(section)
		{
			const sectionId = section.id;

			return View(
				{
					clickable: true,
					onClick: () => this.onSectionClick(sectionId),
					testId: `calendar-section-list-item-${sectionId}`,
				},
				SectionListItem(section, this.isSectionShown(sectionId)),
			);
		}

		renderListItems()
		{
			return [
				this.renderLocalSections(),
				...this.renderUserSections(),
				...this.renderAdditionalSections(),
				...this.renderSyncSections(),
			];
		}

		renderLocalSections()
		{
			const { header, sections } = this.sections.local;

			if (!Type.isArrayFilled(sections))
			{
				return null;
			}

			return this.renderSectionArea(header, sections);
		}

		renderMultipleSectionAreas(sectionAreas)
		{
			const result = [];

			if (Type.isArrayFilled(sectionAreas))
			{
				for (const sectionArea of sectionAreas)
				{
					const { header, sections } = sectionArea;

					result.push(this.renderSectionArea(header, sections));
				}
			}

			return result;
		}

		renderUserSections()
		{
			return this.renderMultipleSectionAreas(this.sections.users);
		}

		renderAdditionalSections()
		{
			return this.renderMultipleSectionAreas(this.sections.additional);
		}

		renderSyncSections()
		{
			return this.renderMultipleSectionAreas(this.sections.sync);
		}

		isSectionShown(sectionId)
		{
			return !this.state.hiddenSections.includes(sectionId);
		}

		onSectionClick(sectionId)
		{
			this.hasChanges = true;

			const { hiddenSections } = this.state;

			if (this.isSectionShown(sectionId))
			{
				hiddenSections.push(sectionId);
			}
			else
			{
				const sectionIndex = hiddenSections.indexOf(sectionId);
				hiddenSections.splice(sectionIndex, 1);
			}

			this.setState({ hiddenSections });
		}

		saveState()
		{
			SectionManager.saveHiddenSections(State.ownerId, State.calType, this.state.hiddenSections);
			State.setHiddenSections(this.state.hiddenSections);
		}

		show(parentLayout = PageManager)
		{
			void new BottomSheet({ component: this })
				.setParentWidget(parentLayout)
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setTitleParams({
					text: Loc.getMessage('M_CALENDAR_EVENT_LIST_SECTION_LIST_TITLE'),
					type: 'wizard',
				})
				.setMediumPositionPercent(85)
				.open()
				.then((widget) => {
					this.layoutWidget = widget;
				})
			;
		}
	}

	module.exports = { SectionListPage };
});
