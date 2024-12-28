/**
 * @module calendar/data-managers/section-manager
 */
jn.define('calendar/data-managers/section-manager', (require, exports, module) => {
	const { SectionModel } = require('calendar/model/section');
	const { SectionPermissionActions } = require('calendar/enums');
	const { EventAjax } = require('calendar/ajax');
	const { PullCommand } = require('calendar/enums');

	/**
	 * @class SectionManager
	 */
	class SectionManager
	{
		constructor()
		{
			this.sections = [];
			this.collabSections = [];

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

		handlePull(data)
		{
			const command = BX.prop.getString(data, 'command', '');
			const params = BX.prop.getObject(data, 'params', {});

			if (command === PullCommand.DELETE_SECTION)
			{
				const fields = BX.prop.getObject(params, 'fields', {});
				const sectionId = BX.prop.getNumber(fields, 'ID', 0);

				if (this.sections[sectionId])
				{
					this.deleteSectionHandler(sectionId);
				}
			}
			else if (command === PullCommand.EDIT_SECTION)
			{
				const isNewSection = BX.prop.getBoolean(params, 'newSection', false);

				if (isNewSection)
				{
					this.refresh();
				}
			}
		}

		deleteSectionHandler(sectionId)
		{
			delete this.sections[sectionId];
		}

		refresh(ownerId, calType, force = false)
		{
			if (this.isRefreshing && !force)
			{
				return;
			}

			this.isRefreshing = true;

			// eslint-disable-next-line promise/catch-or-return
			EventAjax.getSectionList({ ownerId, calType }).then((response) => {
				if (response.data && response.data.sections)
				{
					this.setSections(response.data.sections);
					this.isRefreshing = false;
				}
			});
		}
	}

	module.exports = { SectionManager: new SectionManager() };
});
