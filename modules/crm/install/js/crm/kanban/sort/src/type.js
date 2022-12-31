/**
 * @memberOf BX.Crm.Kanban.Sort
 */
const Type = {
	BY_ID: 'BY_ID',
	BY_LAST_ACTIVITY_TIME: 'BY_LAST_ACTIVITY_TIME',

	isDefined(type: string): boolean
	{
		return (
			type === this.BY_ID
			|| type === this.BY_LAST_ACTIVITY_TIME
		);
	},

	getAll(): string[]
	{
		return [
			this.BY_ID,
			this.BY_LAST_ACTIVITY_TIME,
		];
	},
};

Object.freeze(Type);

export { Type };
