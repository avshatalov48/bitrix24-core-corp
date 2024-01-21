/**
 * @module bizproc/fields/types/base
 */
jn.define('bizproc/fields/types/base', (require, exports, module) => {

	const {
		BooleanType,
		DateTimeType,
		NumberType,
		FileType,
		SelectType,
		StringType,
		TextAreaType,
		UserType,
	} = require('layout/ui/fields');
	const { isObjectLike } = require('utils/object');

	const BaseTypeMap = {
		bool: {
			type: BooleanType,
		},
		date: {
			type: DateTimeType,
			config: {
				enableTime: false,
			},
		},
		datetime: {
			type: DateTimeType,
		},
		double: {
			type: NumberType,
			config: {
				type: 'double',
				precision: 2,
			},
		},
		file: {
			type: FileType,
			config: (property) => {
				if (property.ParentType === 'task' && property.ParentId)
				{
					return {
						fileInfo: {},
						mediaType: 'file',
						controller: {
							endpoint: 'bizproc.FileUploader.TaskUploaderController',
							options: {
								taskId: property.ParentId,
								fieldId: property.Id,
							},
						},
					};
				}

				return {};
			},
		},
		int: {
			type: NumberType,
			config: {
				type: 'integer',
			},
		},
		select: {
			type: SelectType,
			config: (property) => {
				return { items: Array.isArray(property.Options) ? property.Options : [] };
			},
		},
		string: {
			type: StringType,
		},
		text: {
			type: TextAreaType,
		},
		user: {
			type: UserType,
			config: (property) => {
				return {
					entityList: isObjectLike(property.Settings) && Array.isArray(property.Settings.entityList)
						? property.Settings.entityList
						: []
					,
				};
			},
		},
		// internalselect: { // converted to 'select' type on backend
		// 	type: SelectType,
		// },
		time: {
			type: DateTimeType,
			config: {
				datePickerType: 'time',
			},
		},
	};

	module.exports = { BaseTypeMap };
});
