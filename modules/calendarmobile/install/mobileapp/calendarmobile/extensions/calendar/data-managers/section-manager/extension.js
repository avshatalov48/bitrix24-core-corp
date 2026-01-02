/**
 * @module calendar/data-managers/section-manager
 */
jn.define('calendar/data-managers/section-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { SectionModel } = require('calendar/model/section');
	const { SectionPermissionActions } = require('calendar/enums');
	const { EventAjax } = require('calendar/ajax');
	const { PullCommand, CalendarType } = require('calendar/enums');

	/**
	 * @class SectionManager
	 */
	class SectionManager
	{
		constructor()
		{
			this.sections = [];
			this.collabSections = [];
			this.trackingUserList = [];
			this.hiddenSections = [];

			this.isRefreshing = false;
		}

		setSections(sectionInfo)
		{
			this.sections = [];
			this.addSections(sectionInfo);
		}

		addSections(sectionInfo)
		{
			sectionInfo.forEach((sectionRaw) => {
				const section = new SectionModel(sectionRaw);
				this.sections[section.getId()] = section;
			});
		}

		setCollabSections(collabSectionInfo)
		{
			this.collabSections = [];

			collabSectionInfo.forEach((collabSectionRaw) => {
				const section = new SectionModel(collabSectionRaw);
				this.collabSections[section.getId()] = section;
			});
		}

		setHiddenSections(hiddenSections)
		{
			this.hiddenSections = [];

			if (Type.isArrayFilled(hiddenSections))
			{
				hiddenSections.forEach((id) => {
					this.hiddenSections.push(id === 'tasks' ? id : parseInt(id, 10));
				});
			}
		}

		setTrackingUserList(trackingUserList)
		{
			this.trackingUserList = trackingUserList;
		}

		getSection(id)
		{
			return this.sections[id] || {};
		}

		getCollabSection(id)
		{
			return this.collabSections[id] || {};
		}

		getCollabSectionByCollabId(collabId)
		{
			return this.collabSections.filter((section) => section).find((section) => section.ownerId === collabId) || {};
		}

		getSectionName(id)
		{
			return this.getSection(id)?.name;
		}

		getSectionColor(id)
		{
			return this.getSection(id)?.color;
		}

		getActiveSections()
		{
			return this.sections.filter((section) => section.isActive());
		}

		getSortedSections()
		{
			return this.getActiveSections().sort((first, second) => this.compareByName(first, second));
		}

		getActiveSectionsForEdit()
		{
			return this.sections.filter((section) => section.isActive() && section.canDo(SectionPermissionActions.EDIT));
		}

		getActiveSectionsIds()
		{
			return this.getActiveSections().map((section) => section.getId());
		}

		getCollabSectionsForEdit()
		{
			return this.collabSections.filter((section) => section.canDo(SectionPermissionActions.EDIT));
		}

		getTrackingUserList()
		{
			return this.trackingUserList;
		}

		getHiddenSections()
		{
			return this.hiddenSections;
		}

		saveHiddenSections(ownerId, calType, sections)
		{
			this.setHiddenSections(sections);
			void EventAjax.saveHiddenSections({ ownerId, calType, sections });
		}

		handlePull(data)
		{
			const command = BX.prop.getString(data, 'command', '');
			const params = BX.prop.getObject(data, 'params', {});
			const fields = BX.prop.getObject(params, 'fields', {});

			if (command === PullCommand.DELETE_SECTION)
			{
				const sectionId = BX.prop.getNumber(fields, 'ID', 0);

				if (this.sections[sectionId])
				{
					this.deleteSectionHandler(sectionId);
				}
			}
			else if (command === PullCommand.EDIT_SECTION)
			{
				this.editSectionHandler(fields);
			}
		}

		deleteSectionHandler(sectionId)
		{
			delete this.sections[sectionId];
		}

		editSectionHandler(fields)
		{
			const section = new SectionModel(fields);
			this.sections[section.getId()] = section;
		}

		async refresh(ownerId, calType, force = false)
		{
			if (ownerId !== Number(env.userId) && calType !== CalendarType.USER)
			{
				return;
			}

			if (this.isRefreshing && !force)
			{
				return;
			}

			this.isRefreshing = true;

			try
			{
				const { data } = await EventAjax.getSectionList({ ownerId, calType });

				if (data && data.sections)
				{
					this.setSections(data.sections);
				}
			}
			catch (e)
			{
				console.error('ERROR on SectionManager.refresh', e);
			}

			this.isRefreshing = false;
		}

		compareByName(first, second)
		{
			return first.getName().localeCompare(second.getName());
		}

		belongsToView(section, ownerId, calType)
		{
			return section.getOwnerId() === ownerId && section.getType() === calType;
		}
	}

	module.exports = { SectionManager: new SectionManager() };
});
