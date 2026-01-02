import { WidgetPackageDataType } from './types/widget-package-data-type';
import { PackageItem } from './package-item';

export class PackageItemFactory
{
	create(data: WidgetPackageDataType): PackageItem
	{
		return new PackageItem(data);
	}
}
