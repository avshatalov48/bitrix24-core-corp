/**
 * @module layout/ui/kanban/settings/stage
 */
jn.define('layout/ui/kanban/settings/stage', (require, exports, module) => {
	const STAGE_TYPE = {
		PROCESS_STAGES: 'processStages',
		SUCCESS_STAGES: 'successStages',
		FAILED_STAGES: 'failedStages',
	};

	const SEMANTICS = {
		PROCESS: 'P',
		SUCCESS: 'S',
		FAILED: 'F',
	};
	const AppTheme = require('apptheme');
	const DEFAULT_PROCESS_STAGE_COLOR = AppTheme.colors.accentSoftBlue1;
	const DEFAULT_FAILED_STAGE_COLOR = AppTheme.colors.accentMainAlert;

	/**
	 * @class Stage
	 */
	class Stage
	{
		constructor({ id, name, sort, color, semantics, type, tunnels, statusId, total, currency, count })
		{
			this.id = id;
			this.name = name || BX.message('CATEGORY_DETAIL_DEFAULT_STAGE_NAME2');
			this.sort = sort || 0;
			this.semantics = semantics || SEMANTICS.PROCESS;
			this.color = this.getColorByProps(color) || this.getColorBySemantics(semantics);
			this.type = type === undefined ? Stage.getType(this.semantics) : type;
			this.tunnels = tunnels || [];
			this.statusId = statusId || null;
			this.total = total;
			this.currency = currency || null;
			this.count = count;
		}

		static getType(semantics)
		{
			if (semantics === SEMANTICS.FAILED)
			{
				return STAGE_TYPE.FAILED_STAGES;
			}

			if (semantics === SEMANTICS.SUCCESS)
			{
				return STAGE_TYPE.SUCCESS_STAGES;
			}

			return STAGE_TYPE.PROCESS_STAGES;
		}

		getColorByProps(color)
		{
			return color && color.length > 0 ? color : null;
		}

		getColorBySemantics(semantics)
		{
			return semantics === SEMANTICS.PROCESS ? DEFAULT_PROCESS_STAGE_COLOR : DEFAULT_FAILED_STAGE_COLOR;
		}

		toStorageJson()
		{
			return {
				id: this.id,
				name: this.name,
				semantics: this.semantics,
				color: this.color,
				statusId: this.statusId,
				sort: this.sort,
				count: this.count,
				total: this.total,
				currency: this.currency,
				tunnels: this.tunnels,
			};
		}
	}

	module.exports = {
		Stage,
		STAGE_TYPE,
		SEMANTICS,
		DEFAULT_PROCESS_STAGE_COLOR,
		DEFAULT_FAILED_STAGE_COLOR,
	};
});
