/**
 * @module calendar/section-manager
 */
jn.define('calendar/section-manager', (require, exports, module) => {
	const { SectionModel } = require('calendar/model/section');
	const { EventAjax } = require('calendar/ajax');

	/**
	 * @class SectionManager
	 */
	class SectionManager
	{
		constructor(props)
		{
			this.props = props;
			this.sections = [];
			this.sectionIndex = {};

			this.isRefreshing = false;

			this.setSections(this.props.sectionInfo);
		}

		setSections(sectionInfo)
		{
			this.sections = [];

			sectionInfo.forEach((sectionRaw) => {
				const section = new SectionModel(sectionRaw);
				this.sections.push(section);
			});

			this.generateSectionIndex();
		}

		generateSectionIndex()
		{
			this.sectionIndex = {};

			this.sections.forEach((section, index) => {
				this.sectionIndex[section.getId()] = index;
			});
		}

		getSection(id)
		{
			return this.sections[this.sectionIndex[id]] || {};
		}

		getSectionsIds()
		{
			return this.sections.map((section) => section.getId());
		}

		getActiveSectionsIds()
		{
			const sectionsId = [];

			this.sections.forEach((section) => {
				if (section.isActive())
				{
					sectionsId.push(section.getId());
				}
			});

			return sectionsId;
		}

		handlePull(data)
		{
			const command = BX.prop.getString(data, 'command', '');
			const params = BX.prop.getObject(data, 'params', {});

			if (command === 'delete_section')
			{
				const fields = BX.prop.getObject(params, 'fields', {});
				const sectionId = BX.prop.getNumber(fields, 'ID', 0);

				if (this.sectionIndex[sectionId])
				{
					this.deleteSectionHandler(sectionId);
				}
			}
			else if (command === 'edit_section')
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
			const sectionIndex = this.sectionIndex[sectionId];
			if (sectionIndex)
			{
				this.sections = this.sections.slice(0, sectionIndex).concat(this.sections.slice(sectionIndex + 1));
				this.generateSectionIndex();
			}
		}

		refresh(force = false)
		{
			if (this.isRefreshing && !force)
			{
				return;
			}

			this.isRefreshing = true;

			EventAjax.getSectionList().then((response) => {
				if (response.data && response.data.sections)
				{
					this.setSections(response.data.sections);
					this.isRefreshing = false;
				}

				if (force && this.props.onSectionsForceRefresh)
				{
					this.props.onSectionsForceRefresh();
				}
			});
		}
	}

	module.exports = { SectionManager };
});
