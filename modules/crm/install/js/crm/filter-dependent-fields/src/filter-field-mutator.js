export interface FilterFieldMutator
{
	/**
	 * @param fields
	 * @param oldFields
	 * @return array The first value is the changed filter, the second is a flag indicating the presence of
	 * changes in the filter
	 */
	mutate(fields: {[k: string]: any}, oldFields: {[k: string]: any}): [{[k: string]: any}, boolean];
}
