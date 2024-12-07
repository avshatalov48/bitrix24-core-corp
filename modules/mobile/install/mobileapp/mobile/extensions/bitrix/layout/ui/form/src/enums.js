/**
 * @module layout/ui/form/src/enums
 */
jn.define('layout/ui/form/src/enums', (require, exports, module) => {
	const SubmitFailureReason = {
		VALIDATION: 'VALIDATION',
		FILES_NOT_UPLOADED: 'FILES_NOT_UPLOADED',
	};

	const CompactMode = {
		NONE: 'NONE',
		ONLY: 'ONLY',
		BOTH: 'BOTH',
		FILL_COMPACT_AND_HIDE: 'FILL_COMPACT_AND_HIDE',
		FILL_COMPACT_AND_KEEP: 'FILL_COMPACT_AND_KEEP',
		default: 'NONE',
		has(value)
		{
			return this[value] === value;
		},
	};

	module.exports = {
		SubmitFailureReason,
		CompactMode,
	};
});
