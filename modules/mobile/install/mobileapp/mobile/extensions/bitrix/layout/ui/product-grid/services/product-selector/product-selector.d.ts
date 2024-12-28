// eslint-disable-next-line no-unused-vars
type ProductSelectorProps = {
	iblockId: number,
	basePriceId: number,
	currency: string,
	enableCreation: boolean,
	onCreate: () => void,
	onSelect: () => void,
	isCatalogHidden: boolean,
	isOnecRestrictedByPlan: boolean,
	analyticsSection: string,
}
