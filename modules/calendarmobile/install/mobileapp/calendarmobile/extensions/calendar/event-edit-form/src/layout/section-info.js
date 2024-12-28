/**
 * @module calendar/event-edit-form/layout/section-info
 */
jn.define('calendar/event-edit-form/layout/section-info', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent, Corner } = require('tokens');

	const { Link4 } = require('ui-system/blocks/link');
	const { Text4 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	const { State, observeState } = require('calendar/event-edit-form/state');
	const { SectionManager } = require('calendar/data-managers/section-manager');
	const { Selector } = require('calendar/event-edit-form/selector');
	const { CalendarType } = require('calendar/enums');

	class SectionInfo extends LayoutComponent
	{
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
			Selector.open({
				parentLayout: this.props.layout,
				selectedId: this.getAvailableSection().id,
				onItemClick: this.onSectionSelected,
				items: this.getSections(),
				selectorIcon: Icon.CALENDAR,
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

		getAvailableSection()
		{
			const sections = this.getSections();
			const sectionId = parseInt(this.sectionId, 10);

			let result = null;
			if (State.isEditForm())
			{
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
			}
			else
			{
				result = sections.find((section) => section.id === sectionId);
			}

			if (result?.id)
			{
				return result;
			}

			result = sections.shift();

			if (result.id)
			{
				State.setSectionId(Number(result.id));

				return result;
			}

			return null;
		}

		isCollabContext()
		{
			return State.calType === CalendarType.USER && env.isCollaber;
		}

		isSelectorInViewMode(section)
		{
			return (State.isEditForm() && section.isSyncSection()) || this.editAttendeesMode;
		}
	}

	const mapStateToProps = (state) => ({
		sectionId: state.sectionId,
		collabId: state.collabId,
		editAttendeesMode: state.editAttendeesMode,
	});

	module.exports = { SectionInfo: observeState(SectionInfo, mapStateToProps) };
});
