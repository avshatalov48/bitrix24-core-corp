/**
 * @module disk/analytics
 */
jn.define('disk/analytics', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { FileType } = require('disk/enum');

	class DiskAnalyticsEvent extends AnalyticsEvent
	{
		static get Event()
		{
			return {
				UPLOAD_FILE: 'upload_file',
				ADD_FOLDER: 'add_folder',
				CREATE_FILE: 'create_file',
			};
		}

		static get Type()
		{
			return {
				IMAGE: 'image',
				VIDEO: 'video',
				DOCUMENT: 'document',
				ARCHIVE: 'archive',
				SCRIPT: 'script',
				UNKNOWN: 'unknown',
				PDF: 'pdf',
				AUDIO: 'audio',
				KNOWN: 'known',
				VECTOR_IMAGE: 'vector_image',
			};
		}

		static get Section()
		{
			return {
				FILES: 'files',
				PROJECT: 'project',
				COLLAB: 'collab',
			};
		}

		static get Subsection()
		{
			return {
				RECENT_FILES: 'recent_files',
				MY_FILES: 'my_files',
				COMPANY_FILES: 'bitrix24_files',
				PROJECT_FILES: 'project_files',
				COLLAB_FILES: 'collab_files',
			};
		}

		/**
		 * @param {FileReduxModel} file
		 * @return {DiskAnalyticsEvent}
		 */
		static createFromFile(file)
		{
			return new DiskAnalyticsEvent({
				type: mapFileType(file.typeFile),
				p1: `size_${file.size}`,
				p5: `ext_${getExt(file.name)}`,
			});
		}

		getDefaults()
		{
			return {
				tool: 'files',
				category: 'files_operations',
				event: null,
				type: null,
				c_section: null,
				c_sub_section: null,
				c_element: null,
				status: null,
				p1: null, // size_{number}
				p2: UserType.get(),
				p3: null,
				p4: null, // collabId_{number}
				p5: null, // ext_{string}
			};
		}

		/**
		 * @param {number} id
		 * @return {DiskAnalyticsEvent}
		 */
		setCollabId(id)
		{
			return this.setP4(id ? `collabId_${id}` : '');
		}
	}

	const UserType = {
		COLLABER: env.isCollaber ? 'user_collaber' : false,
		EXTRANET: env.extranet ? 'user_extranet' : false,
		INTRANET: 'user_intranet',
		get()
		{
			return String(this.COLLABER || this.EXTRANET || this.INTRANET);
		},
	};

	/**
	 * @param {number} reduxValue
	 * @return {string}
	 */
	const mapFileType = (reduxValue) => {
		const key = Object.entries(FileType).find(([_, value]) => value === reduxValue)?.[0];

		return DiskAnalyticsEvent.Type[key] ?? DiskAnalyticsEvent.Type.UNKNOWN;
	};

	/**
	 * @param {string} name
	 * @return {string}
	 */
	const getExt = (name) => ((name && name.includes('.'))
		? name.split('.').pop().toLowerCase()
		: '');

	module.exports = {
		DiskAnalyticsEvent,
	};
});
