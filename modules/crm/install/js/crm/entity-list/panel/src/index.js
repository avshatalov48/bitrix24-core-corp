import { createCallList, createCallListAndShowAlertOnErrors } from './event/handlers/call-list/internals/functions';
import { LoadEnumsAndEditSelected } from './event/handlers/load-enums-and-edit-selected';
import { init } from './init';

/**
 * @memberof BX.Crm.EntityList.Panel
 */
function loadEnumsGridEditData(grid: BX.Main.grid, entityTypeId: number, categoryId: ?number): Promise<void>
{
	return LoadEnumsAndEditSelected.loadEnums(grid, entityTypeId, categoryId);
}

export {
	init,
	loadEnumsGridEditData,
	createCallList,
	createCallListAndShowAlertOnErrors,
};
