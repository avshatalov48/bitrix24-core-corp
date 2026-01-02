<?php

/* ORMENTITYANNOTATION:Bitrix\Baas\Model\ServiceInPurchasedPackageTable:baas/lib/Model/ServiceInPurchasedPackageTable.php */
namespace Bitrix\Baas\Model {
	/**
	 * EO_ServiceInPurchasedPackage
	 * @see \Bitrix\Baas\Model\ServiceInPurchasedPackageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int remindActualId()
	 * @method \int requireId()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage resetId()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage unsetId()
	 * @method \int fillId()
	 * @method \string getPurchasedPackageCode()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage setPurchasedPackageCode(\string|\Bitrix\Main\DB\SqlExpression $purchasedPackageCode)
	 * @method bool hasPurchasedPackageCode()
	 * @method bool isPurchasedPackageCodeFilled()
	 * @method bool isPurchasedPackageCodeChanged()
	 * @method \string getServiceCode()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage setServiceCode(\string|\Bitrix\Main\DB\SqlExpression $serviceCode)
	 * @method bool hasServiceCode()
	 * @method bool isServiceCodeFilled()
	 * @method bool isServiceCodeChanged()
	 * @method \int getCurrentValue()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage setCurrentValue(\int|\Bitrix\Main\DB\SqlExpression $currentValue)
	 * @method bool hasCurrentValue()
	 * @method bool isCurrentValueFilled()
	 * @method bool isCurrentValueChanged()
	 * @method \int remindActualCurrentValue()
	 * @method \int requireCurrentValue()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage resetCurrentValue()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage unsetCurrentValue()
	 * @method \int fillCurrentValue()
	 * @method \int getStateNumber()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage setStateNumber(\int|\Bitrix\Main\DB\SqlExpression $stateNumber)
	 * @method bool hasStateNumber()
	 * @method bool isStateNumberFilled()
	 * @method bool isStateNumberChanged()
	 * @method \int remindActualStateNumber()
	 * @method \int requireStateNumber()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage resetStateNumber()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage unsetStateNumber()
	 * @method \int fillStateNumber()
	 * @method \Bitrix\Baas\Model\EO_Service getService()
	 * @method \Bitrix\Baas\Model\EO_Service remindActualService()
	 * @method \Bitrix\Baas\Model\EO_Service requireService()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage setService(\Bitrix\Baas\Model\EO_Service $object)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage resetService()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage unsetService()
	 * @method bool hasService()
	 * @method bool isServiceFilled()
	 * @method bool isServiceChanged()
	 * @method \Bitrix\Baas\Model\EO_Service fillService()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage getPurchasedPackage()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage remindActualPurchasedPackage()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage requirePurchasedPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage setPurchasedPackage(\Bitrix\Baas\Model\EO_PurchasedPackage $object)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage resetPurchasedPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage unsetPurchasedPackage()
	 * @method bool hasPurchasedPackage()
	 * @method bool isPurchasedPackageFilled()
	 * @method bool isPurchasedPackageChanged()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage fillPurchasedPackage()
	 * @method \Bitrix\Baas\Model\EO_Package getPackage()
	 * @method \Bitrix\Baas\Model\EO_Package remindActualPackage()
	 * @method \Bitrix\Baas\Model\EO_Package requirePackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage setPackage(\Bitrix\Baas\Model\EO_Package $object)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage resetPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage unsetPackage()
	 * @method bool hasPackage()
	 * @method bool isPackageFilled()
	 * @method bool isPackageChanged()
	 * @method \Bitrix\Baas\Model\EO_Package fillPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage getServicesInPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage remindActualServicesInPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage requireServicesInPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage setServicesInPackage(\Bitrix\Baas\Model\EO_ServiceInPackage $object)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage resetServicesInPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage unsetServicesInPackage()
	 * @method bool hasServicesInPackage()
	 * @method bool isServicesInPackageFilled()
	 * @method bool isServicesInPackageChanged()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage fillServicesInPackage()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage set($fieldName, $value)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage reset($fieldName)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage wakeUp($data)
	 */
	class EO_ServiceInPurchasedPackage {
		/* @var \Bitrix\Baas\Model\ServiceInPurchasedPackageTable */
		static public $dataClass = '\Bitrix\Baas\Model\ServiceInPurchasedPackageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Baas\Model {
	/**
	 * EO_ServiceInPurchasedPackage_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] fillId()
	 * @method \string[] getPurchasedPackageCodeList()
	 * @method \string[] getServiceCodeList()
	 * @method \int[] getCurrentValueList()
	 * @method \int[] fillCurrentValue()
	 * @method \int[] getStateNumberList()
	 * @method \int[] fillStateNumber()
	 * @method \Bitrix\Baas\Model\EO_Service[] getServiceList()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage_Collection getServiceCollection()
	 * @method \Bitrix\Baas\Model\EO_Service_Collection fillService()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage[] getPurchasedPackageList()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage_Collection getPurchasedPackageCollection()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage_Collection fillPurchasedPackage()
	 * @method \Bitrix\Baas\Model\EO_Package[] getPackageList()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage_Collection getPackageCollection()
	 * @method \Bitrix\Baas\Model\EO_Package_Collection fillPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage[] getServicesInPackageList()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage_Collection getServicesInPackageCollection()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage_Collection fillServicesInPackage()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Baas\Model\EO_ServiceInPurchasedPackage $object)
	 * @method bool has(\Bitrix\Baas\Model\EO_ServiceInPurchasedPackage $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage getByPrimary($primary)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage[] getAll()
	 * @method bool remove(\Bitrix\Baas\Model\EO_ServiceInPurchasedPackage $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage_Collection merge(?\Bitrix\Baas\Model\EO_ServiceInPurchasedPackage_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ServiceInPurchasedPackage_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Baas\Model\ServiceInPurchasedPackageTable */
		static public $dataClass = '\Bitrix\Baas\Model\ServiceInPurchasedPackageTable';
	}
}
namespace Bitrix\Baas\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ServiceInPurchasedPackage_Result exec()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage fetchObject()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage_Collection fetchCollection()
	 */
	class EO_ServiceInPurchasedPackage_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage fetchObject()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage_Collection fetchCollection()
	 */
	class EO_ServiceInPurchasedPackage_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage createObject($setDefaultValues = true)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage_Collection createCollection()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage wakeUpObject($row)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage_Collection wakeUpCollection($rows)
	 */
	class EO_ServiceInPurchasedPackage_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Baas\Model\PackageTable:baas/lib/Model/PackageTable.php */
namespace Bitrix\Baas\Model {
	/**
	 * EO_Package
	 * @see \Bitrix\Baas\Model\PackageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Baas\Model\EO_Package setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int remindActualId()
	 * @method \int requireId()
	 * @method \Bitrix\Baas\Model\EO_Package resetId()
	 * @method \Bitrix\Baas\Model\EO_Package unsetId()
	 * @method \int fillId()
	 * @method \string getCode()
	 * @method \Bitrix\Baas\Model\EO_Package setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method array getIconStyle()
	 * @method \Bitrix\Baas\Model\EO_Package setIconStyle(array|\Bitrix\Main\DB\SqlExpression $iconStyle)
	 * @method bool hasIconStyle()
	 * @method bool isIconStyleFilled()
	 * @method bool isIconStyleChanged()
	 * @method array remindActualIconStyle()
	 * @method array requireIconStyle()
	 * @method \Bitrix\Baas\Model\EO_Package resetIconStyle()
	 * @method \Bitrix\Baas\Model\EO_Package unsetIconStyle()
	 * @method array fillIconStyle()
	 * @method \string getIconClass()
	 * @method \Bitrix\Baas\Model\EO_Package setIconClass(\string|\Bitrix\Main\DB\SqlExpression $iconClass)
	 * @method bool hasIconClass()
	 * @method bool isIconClassFilled()
	 * @method bool isIconClassChanged()
	 * @method \string remindActualIconClass()
	 * @method \string requireIconClass()
	 * @method \Bitrix\Baas\Model\EO_Package resetIconClass()
	 * @method \Bitrix\Baas\Model\EO_Package unsetIconClass()
	 * @method \string fillIconClass()
	 * @method \string getIconColor()
	 * @method \Bitrix\Baas\Model\EO_Package setIconColor(\string|\Bitrix\Main\DB\SqlExpression $iconColor)
	 * @method bool hasIconColor()
	 * @method bool isIconColorFilled()
	 * @method bool isIconColorChanged()
	 * @method \string remindActualIconColor()
	 * @method \string requireIconColor()
	 * @method \Bitrix\Baas\Model\EO_Package resetIconColor()
	 * @method \Bitrix\Baas\Model\EO_Package unsetIconColor()
	 * @method \string fillIconColor()
	 * @method \string getPurchaseUrl()
	 * @method \Bitrix\Baas\Model\EO_Package setPurchaseUrl(\string|\Bitrix\Main\DB\SqlExpression $purchaseUrl)
	 * @method bool hasPurchaseUrl()
	 * @method bool isPurchaseUrlFilled()
	 * @method bool isPurchaseUrlChanged()
	 * @method \string remindActualPurchaseUrl()
	 * @method \string requirePurchaseUrl()
	 * @method \Bitrix\Baas\Model\EO_Package resetPurchaseUrl()
	 * @method \Bitrix\Baas\Model\EO_Package unsetPurchaseUrl()
	 * @method \string fillPurchaseUrl()
	 * @method \string getTitle()
	 * @method \Bitrix\Baas\Model\EO_Package setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Baas\Model\EO_Package resetTitle()
	 * @method \Bitrix\Baas\Model\EO_Package unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getDescription()
	 * @method \Bitrix\Baas\Model\EO_Package setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Baas\Model\EO_Package resetDescription()
	 * @method \Bitrix\Baas\Model\EO_Package unsetDescription()
	 * @method \string fillDescription()
	 * @method \float getPriceValue()
	 * @method \Bitrix\Baas\Model\EO_Package setPriceValue(\float|\Bitrix\Main\DB\SqlExpression $priceValue)
	 * @method bool hasPriceValue()
	 * @method bool isPriceValueFilled()
	 * @method bool isPriceValueChanged()
	 * @method \float remindActualPriceValue()
	 * @method \float requirePriceValue()
	 * @method \Bitrix\Baas\Model\EO_Package resetPriceValue()
	 * @method \Bitrix\Baas\Model\EO_Package unsetPriceValue()
	 * @method \float fillPriceValue()
	 * @method \string getPriceCurrencyId()
	 * @method \Bitrix\Baas\Model\EO_Package setPriceCurrencyId(\string|\Bitrix\Main\DB\SqlExpression $priceCurrencyId)
	 * @method bool hasPriceCurrencyId()
	 * @method bool isPriceCurrencyIdFilled()
	 * @method bool isPriceCurrencyIdChanged()
	 * @method \string remindActualPriceCurrencyId()
	 * @method \string requirePriceCurrencyId()
	 * @method \Bitrix\Baas\Model\EO_Package resetPriceCurrencyId()
	 * @method \Bitrix\Baas\Model\EO_Package unsetPriceCurrencyId()
	 * @method \string fillPriceCurrencyId()
	 * @method \string getPriceDescription()
	 * @method \Bitrix\Baas\Model\EO_Package setPriceDescription(\string|\Bitrix\Main\DB\SqlExpression $priceDescription)
	 * @method bool hasPriceDescription()
	 * @method bool isPriceDescriptionFilled()
	 * @method bool isPriceDescriptionChanged()
	 * @method \string remindActualPriceDescription()
	 * @method \string requirePriceDescription()
	 * @method \Bitrix\Baas\Model\EO_Package resetPriceDescription()
	 * @method \Bitrix\Baas\Model\EO_Package unsetPriceDescription()
	 * @method \string fillPriceDescription()
	 * @method \string getActive()
	 * @method \Bitrix\Baas\Model\EO_Package setActive(\string|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \string remindActualActive()
	 * @method \string requireActive()
	 * @method \Bitrix\Baas\Model\EO_Package resetActive()
	 * @method \Bitrix\Baas\Model\EO_Package unsetActive()
	 * @method \string fillActive()
	 * @method \string getFeaturePromotionCode()
	 * @method \Bitrix\Baas\Model\EO_Package setFeaturePromotionCode(\string|\Bitrix\Main\DB\SqlExpression $featurePromotionCode)
	 * @method bool hasFeaturePromotionCode()
	 * @method bool isFeaturePromotionCodeFilled()
	 * @method bool isFeaturePromotionCodeChanged()
	 * @method \string remindActualFeaturePromotionCode()
	 * @method \string requireFeaturePromotionCode()
	 * @method \Bitrix\Baas\Model\EO_Package resetFeaturePromotionCode()
	 * @method \Bitrix\Baas\Model\EO_Package unsetFeaturePromotionCode()
	 * @method \string fillFeaturePromotionCode()
	 * @method \string getHelperCode()
	 * @method \Bitrix\Baas\Model\EO_Package setHelperCode(\string|\Bitrix\Main\DB\SqlExpression $helperCode)
	 * @method bool hasHelperCode()
	 * @method bool isHelperCodeFilled()
	 * @method bool isHelperCodeChanged()
	 * @method \string remindActualHelperCode()
	 * @method \string requireHelperCode()
	 * @method \Bitrix\Baas\Model\EO_Package resetHelperCode()
	 * @method \Bitrix\Baas\Model\EO_Package unsetHelperCode()
	 * @method \string fillHelperCode()
	 * @method \string getDistributionStrategy()
	 * @method \Bitrix\Baas\Model\EO_Package setDistributionStrategy(\string|\Bitrix\Main\DB\SqlExpression $distributionStrategy)
	 * @method bool hasDistributionStrategy()
	 * @method bool isDistributionStrategyFilled()
	 * @method bool isDistributionStrategyChanged()
	 * @method \string remindActualDistributionStrategy()
	 * @method \string requireDistributionStrategy()
	 * @method \Bitrix\Baas\Model\EO_Package resetDistributionStrategy()
	 * @method \Bitrix\Baas\Model\EO_Package unsetDistributionStrategy()
	 * @method \string fillDistributionStrategy()
	 * @method \int getSort()
	 * @method \Bitrix\Baas\Model\EO_Package setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\Baas\Model\EO_Package resetSort()
	 * @method \Bitrix\Baas\Model\EO_Package unsetSort()
	 * @method \int fillSort()
	 * @method array getLanguageInfo()
	 * @method \Bitrix\Baas\Model\EO_Package setLanguageInfo(array|\Bitrix\Main\DB\SqlExpression $languageInfo)
	 * @method bool hasLanguageInfo()
	 * @method bool isLanguageInfoFilled()
	 * @method bool isLanguageInfoChanged()
	 * @method array remindActualLanguageInfo()
	 * @method array requireLanguageInfo()
	 * @method \Bitrix\Baas\Model\EO_Package resetLanguageInfo()
	 * @method \Bitrix\Baas\Model\EO_Package unsetLanguageInfo()
	 * @method array fillLanguageInfo()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage_Collection getServiceInPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage_Collection requireServiceInPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage_Collection fillServiceInPackage()
	 * @method bool hasServiceInPackage()
	 * @method bool isServiceInPackageFilled()
	 * @method bool isServiceInPackageChanged()
	 * @method void addToServiceInPackage(\Bitrix\Baas\Model\EO_ServiceInPackage $serviceInPackage)
	 * @method void removeFromServiceInPackage(\Bitrix\Baas\Model\EO_ServiceInPackage $serviceInPackage)
	 * @method void removeAllServiceInPackage()
	 * @method \Bitrix\Baas\Model\EO_Package resetServiceInPackage()
	 * @method \Bitrix\Baas\Model\EO_Package unsetServiceInPackage()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Baas\Model\EO_Package set($fieldName, $value)
	 * @method \Bitrix\Baas\Model\EO_Package reset($fieldName)
	 * @method \Bitrix\Baas\Model\EO_Package unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Baas\Model\EO_Package wakeUp($data)
	 */
	class EO_Package {
		/* @var \Bitrix\Baas\Model\PackageTable */
		static public $dataClass = '\Bitrix\Baas\Model\PackageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Baas\Model {
	/**
	 * EO_Package_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] fillId()
	 * @method \string[] getCodeList()
	 * @method array[] getIconStyleList()
	 * @method array[] fillIconStyle()
	 * @method \string[] getIconClassList()
	 * @method \string[] fillIconClass()
	 * @method \string[] getIconColorList()
	 * @method \string[] fillIconColor()
	 * @method \string[] getPurchaseUrlList()
	 * @method \string[] fillPurchaseUrl()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \float[] getPriceValueList()
	 * @method \float[] fillPriceValue()
	 * @method \string[] getPriceCurrencyIdList()
	 * @method \string[] fillPriceCurrencyId()
	 * @method \string[] getPriceDescriptionList()
	 * @method \string[] fillPriceDescription()
	 * @method \string[] getActiveList()
	 * @method \string[] fillActive()
	 * @method \string[] getFeaturePromotionCodeList()
	 * @method \string[] fillFeaturePromotionCode()
	 * @method \string[] getHelperCodeList()
	 * @method \string[] fillHelperCode()
	 * @method \string[] getDistributionStrategyList()
	 * @method \string[] fillDistributionStrategy()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method array[] getLanguageInfoList()
	 * @method array[] fillLanguageInfo()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage_Collection[] getServiceInPackageList()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage_Collection getServiceInPackageCollection()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage_Collection fillServiceInPackage()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Baas\Model\EO_Package $object)
	 * @method bool has(\Bitrix\Baas\Model\EO_Package $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Baas\Model\EO_Package getByPrimary($primary)
	 * @method \Bitrix\Baas\Model\EO_Package[] getAll()
	 * @method bool remove(\Bitrix\Baas\Model\EO_Package $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Baas\Model\EO_Package_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Baas\Model\EO_Package current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Baas\Model\EO_Package_Collection merge(?\Bitrix\Baas\Model\EO_Package_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Package_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Baas\Model\PackageTable */
		static public $dataClass = '\Bitrix\Baas\Model\PackageTable';
	}
}
namespace Bitrix\Baas\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Package_Result exec()
	 * @method \Bitrix\Baas\Model\EO_Package fetchObject()
	 * @method \Bitrix\Baas\Model\EO_Package_Collection fetchCollection()
	 */
	class EO_Package_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Baas\Model\EO_Package fetchObject()
	 * @method \Bitrix\Baas\Model\EO_Package_Collection fetchCollection()
	 */
	class EO_Package_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Baas\Model\EO_Package createObject($setDefaultValues = true)
	 * @method \Bitrix\Baas\Model\EO_Package_Collection createCollection()
	 * @method \Bitrix\Baas\Model\EO_Package wakeUpObject($row)
	 * @method \Bitrix\Baas\Model\EO_Package_Collection wakeUpCollection($rows)
	 */
	class EO_Package_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Baas\Model\ServiceTable:baas/lib/Model/ServiceTable.php */
namespace Bitrix\Baas\Model {
	/**
	 * EO_Service
	 * @see \Bitrix\Baas\Model\ServiceTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Baas\Model\EO_Service setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int remindActualId()
	 * @method \int requireId()
	 * @method \Bitrix\Baas\Model\EO_Service resetId()
	 * @method \Bitrix\Baas\Model\EO_Service unsetId()
	 * @method \int fillId()
	 * @method \string getCode()
	 * @method \Bitrix\Baas\Model\EO_Service setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method array getIconStyle()
	 * @method \Bitrix\Baas\Model\EO_Service setIconStyle(array|\Bitrix\Main\DB\SqlExpression $iconStyle)
	 * @method bool hasIconStyle()
	 * @method bool isIconStyleFilled()
	 * @method bool isIconStyleChanged()
	 * @method array remindActualIconStyle()
	 * @method array requireIconStyle()
	 * @method \Bitrix\Baas\Model\EO_Service resetIconStyle()
	 * @method \Bitrix\Baas\Model\EO_Service unsetIconStyle()
	 * @method array fillIconStyle()
	 * @method \string getIconClass()
	 * @method \Bitrix\Baas\Model\EO_Service setIconClass(\string|\Bitrix\Main\DB\SqlExpression $iconClass)
	 * @method bool hasIconClass()
	 * @method bool isIconClassFilled()
	 * @method bool isIconClassChanged()
	 * @method \string remindActualIconClass()
	 * @method \string requireIconClass()
	 * @method \Bitrix\Baas\Model\EO_Service resetIconClass()
	 * @method \Bitrix\Baas\Model\EO_Service unsetIconClass()
	 * @method \string fillIconClass()
	 * @method \string getIconColor()
	 * @method \Bitrix\Baas\Model\EO_Service setIconColor(\string|\Bitrix\Main\DB\SqlExpression $iconColor)
	 * @method bool hasIconColor()
	 * @method bool isIconColorFilled()
	 * @method bool isIconColorChanged()
	 * @method \string remindActualIconColor()
	 * @method \string requireIconColor()
	 * @method \Bitrix\Baas\Model\EO_Service resetIconColor()
	 * @method \Bitrix\Baas\Model\EO_Service unsetIconColor()
	 * @method \string fillIconColor()
	 * @method \string getTitle()
	 * @method \Bitrix\Baas\Model\EO_Service setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Baas\Model\EO_Service resetTitle()
	 * @method \Bitrix\Baas\Model\EO_Service unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getActiveSubtitle()
	 * @method \Bitrix\Baas\Model\EO_Service setActiveSubtitle(\string|\Bitrix\Main\DB\SqlExpression $activeSubtitle)
	 * @method bool hasActiveSubtitle()
	 * @method bool isActiveSubtitleFilled()
	 * @method bool isActiveSubtitleChanged()
	 * @method \string remindActualActiveSubtitle()
	 * @method \string requireActiveSubtitle()
	 * @method \Bitrix\Baas\Model\EO_Service resetActiveSubtitle()
	 * @method \Bitrix\Baas\Model\EO_Service unsetActiveSubtitle()
	 * @method \string fillActiveSubtitle()
	 * @method \string getInactiveSubtitle()
	 * @method \Bitrix\Baas\Model\EO_Service setInactiveSubtitle(\string|\Bitrix\Main\DB\SqlExpression $inactiveSubtitle)
	 * @method bool hasInactiveSubtitle()
	 * @method bool isInactiveSubtitleFilled()
	 * @method bool isInactiveSubtitleChanged()
	 * @method \string remindActualInactiveSubtitle()
	 * @method \string requireInactiveSubtitle()
	 * @method \Bitrix\Baas\Model\EO_Service resetInactiveSubtitle()
	 * @method \Bitrix\Baas\Model\EO_Service unsetInactiveSubtitle()
	 * @method \string fillInactiveSubtitle()
	 * @method \string getDescription()
	 * @method \Bitrix\Baas\Model\EO_Service setDescription(\string|\Bitrix\Main\DB\SqlExpression $description)
	 * @method bool hasDescription()
	 * @method bool isDescriptionFilled()
	 * @method bool isDescriptionChanged()
	 * @method \string remindActualDescription()
	 * @method \string requireDescription()
	 * @method \Bitrix\Baas\Model\EO_Service resetDescription()
	 * @method \Bitrix\Baas\Model\EO_Service unsetDescription()
	 * @method \string fillDescription()
	 * @method \string getFeaturePromotionCode()
	 * @method \Bitrix\Baas\Model\EO_Service setFeaturePromotionCode(\string|\Bitrix\Main\DB\SqlExpression $featurePromotionCode)
	 * @method bool hasFeaturePromotionCode()
	 * @method bool isFeaturePromotionCodeFilled()
	 * @method bool isFeaturePromotionCodeChanged()
	 * @method \string remindActualFeaturePromotionCode()
	 * @method \string requireFeaturePromotionCode()
	 * @method \Bitrix\Baas\Model\EO_Service resetFeaturePromotionCode()
	 * @method \Bitrix\Baas\Model\EO_Service unsetFeaturePromotionCode()
	 * @method \string fillFeaturePromotionCode()
	 * @method \string getHelperCode()
	 * @method \Bitrix\Baas\Model\EO_Service setHelperCode(\string|\Bitrix\Main\DB\SqlExpression $helperCode)
	 * @method bool hasHelperCode()
	 * @method bool isHelperCodeFilled()
	 * @method bool isHelperCodeChanged()
	 * @method \string remindActualHelperCode()
	 * @method \string requireHelperCode()
	 * @method \Bitrix\Baas\Model\EO_Service resetHelperCode()
	 * @method \Bitrix\Baas\Model\EO_Service unsetHelperCode()
	 * @method \string fillHelperCode()
	 * @method \string getAdvertisingStrategy()
	 * @method \Bitrix\Baas\Model\EO_Service setAdvertisingStrategy(\string|\Bitrix\Main\DB\SqlExpression $advertisingStrategy)
	 * @method bool hasAdvertisingStrategy()
	 * @method bool isAdvertisingStrategyFilled()
	 * @method bool isAdvertisingStrategyChanged()
	 * @method \string remindActualAdvertisingStrategy()
	 * @method \string requireAdvertisingStrategy()
	 * @method \Bitrix\Baas\Model\EO_Service resetAdvertisingStrategy()
	 * @method \Bitrix\Baas\Model\EO_Service unsetAdvertisingStrategy()
	 * @method \string fillAdvertisingStrategy()
	 * @method \int getCurrentValue()
	 * @method \Bitrix\Baas\Model\EO_Service setCurrentValue(\int|\Bitrix\Main\DB\SqlExpression $currentValue)
	 * @method bool hasCurrentValue()
	 * @method bool isCurrentValueFilled()
	 * @method bool isCurrentValueChanged()
	 * @method \int remindActualCurrentValue()
	 * @method \int requireCurrentValue()
	 * @method \Bitrix\Baas\Model\EO_Service resetCurrentValue()
	 * @method \Bitrix\Baas\Model\EO_Service unsetCurrentValue()
	 * @method \int fillCurrentValue()
	 * @method \int getMinimalValue()
	 * @method \Bitrix\Baas\Model\EO_Service setMinimalValue(\int|\Bitrix\Main\DB\SqlExpression $minimalValue)
	 * @method bool hasMinimalValue()
	 * @method bool isMinimalValueFilled()
	 * @method bool isMinimalValueChanged()
	 * @method \int remindActualMinimalValue()
	 * @method \int requireMinimalValue()
	 * @method \Bitrix\Baas\Model\EO_Service resetMinimalValue()
	 * @method \Bitrix\Baas\Model\EO_Service unsetMinimalValue()
	 * @method \int fillMinimalValue()
	 * @method \int getMaximalValue()
	 * @method \Bitrix\Baas\Model\EO_Service setMaximalValue(\int|\Bitrix\Main\DB\SqlExpression $maximalValue)
	 * @method bool hasMaximalValue()
	 * @method bool isMaximalValueFilled()
	 * @method bool isMaximalValueChanged()
	 * @method \int remindActualMaximalValue()
	 * @method \int requireMaximalValue()
	 * @method \Bitrix\Baas\Model\EO_Service resetMaximalValue()
	 * @method \Bitrix\Baas\Model\EO_Service unsetMaximalValue()
	 * @method \int fillMaximalValue()
	 * @method \Bitrix\Main\Type\Date getExpirationDate()
	 * @method \Bitrix\Baas\Model\EO_Service setExpirationDate(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $expirationDate)
	 * @method bool hasExpirationDate()
	 * @method bool isExpirationDateFilled()
	 * @method bool isExpirationDateChanged()
	 * @method \Bitrix\Main\Type\Date remindActualExpirationDate()
	 * @method \Bitrix\Main\Type\Date requireExpirationDate()
	 * @method \Bitrix\Baas\Model\EO_Service resetExpirationDate()
	 * @method \Bitrix\Baas\Model\EO_Service unsetExpirationDate()
	 * @method \Bitrix\Main\Type\Date fillExpirationDate()
	 * @method \int getSort()
	 * @method \Bitrix\Baas\Model\EO_Service setSort(\int|\Bitrix\Main\DB\SqlExpression $sort)
	 * @method bool hasSort()
	 * @method bool isSortFilled()
	 * @method bool isSortChanged()
	 * @method \int remindActualSort()
	 * @method \int requireSort()
	 * @method \Bitrix\Baas\Model\EO_Service resetSort()
	 * @method \Bitrix\Baas\Model\EO_Service unsetSort()
	 * @method \int fillSort()
	 * @method \boolean getRenewable()
	 * @method \Bitrix\Baas\Model\EO_Service setRenewable(\boolean|\Bitrix\Main\DB\SqlExpression $renewable)
	 * @method bool hasRenewable()
	 * @method bool isRenewableFilled()
	 * @method bool isRenewableChanged()
	 * @method \boolean remindActualRenewable()
	 * @method \boolean requireRenewable()
	 * @method \Bitrix\Baas\Model\EO_Service resetRenewable()
	 * @method \Bitrix\Baas\Model\EO_Service unsetRenewable()
	 * @method \boolean fillRenewable()
	 * @method array getLanguageInfo()
	 * @method \Bitrix\Baas\Model\EO_Service setLanguageInfo(array|\Bitrix\Main\DB\SqlExpression $languageInfo)
	 * @method bool hasLanguageInfo()
	 * @method bool isLanguageInfoFilled()
	 * @method bool isLanguageInfoChanged()
	 * @method array remindActualLanguageInfo()
	 * @method array requireLanguageInfo()
	 * @method \Bitrix\Baas\Model\EO_Service resetLanguageInfo()
	 * @method \Bitrix\Baas\Model\EO_Service unsetLanguageInfo()
	 * @method array fillLanguageInfo()
	 * @method \int getStateNumber()
	 * @method \Bitrix\Baas\Model\EO_Service setStateNumber(\int|\Bitrix\Main\DB\SqlExpression $stateNumber)
	 * @method bool hasStateNumber()
	 * @method bool isStateNumberFilled()
	 * @method bool isStateNumberChanged()
	 * @method \int remindActualStateNumber()
	 * @method \int requireStateNumber()
	 * @method \Bitrix\Baas\Model\EO_Service resetStateNumber()
	 * @method \Bitrix\Baas\Model\EO_Service unsetStateNumber()
	 * @method \int fillStateNumber()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Baas\Model\EO_Service set($fieldName, $value)
	 * @method \Bitrix\Baas\Model\EO_Service reset($fieldName)
	 * @method \Bitrix\Baas\Model\EO_Service unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Baas\Model\EO_Service wakeUp($data)
	 */
	class EO_Service {
		/* @var \Bitrix\Baas\Model\ServiceTable */
		static public $dataClass = '\Bitrix\Baas\Model\ServiceTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Baas\Model {
	/**
	 * EO_Service_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] fillId()
	 * @method \string[] getCodeList()
	 * @method array[] getIconStyleList()
	 * @method array[] fillIconStyle()
	 * @method \string[] getIconClassList()
	 * @method \string[] fillIconClass()
	 * @method \string[] getIconColorList()
	 * @method \string[] fillIconColor()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getActiveSubtitleList()
	 * @method \string[] fillActiveSubtitle()
	 * @method \string[] getInactiveSubtitleList()
	 * @method \string[] fillInactiveSubtitle()
	 * @method \string[] getDescriptionList()
	 * @method \string[] fillDescription()
	 * @method \string[] getFeaturePromotionCodeList()
	 * @method \string[] fillFeaturePromotionCode()
	 * @method \string[] getHelperCodeList()
	 * @method \string[] fillHelperCode()
	 * @method \string[] getAdvertisingStrategyList()
	 * @method \string[] fillAdvertisingStrategy()
	 * @method \int[] getCurrentValueList()
	 * @method \int[] fillCurrentValue()
	 * @method \int[] getMinimalValueList()
	 * @method \int[] fillMinimalValue()
	 * @method \int[] getMaximalValueList()
	 * @method \int[] fillMaximalValue()
	 * @method \Bitrix\Main\Type\Date[] getExpirationDateList()
	 * @method \Bitrix\Main\Type\Date[] fillExpirationDate()
	 * @method \int[] getSortList()
	 * @method \int[] fillSort()
	 * @method \boolean[] getRenewableList()
	 * @method \boolean[] fillRenewable()
	 * @method array[] getLanguageInfoList()
	 * @method array[] fillLanguageInfo()
	 * @method \int[] getStateNumberList()
	 * @method \int[] fillStateNumber()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Baas\Model\EO_Service $object)
	 * @method bool has(\Bitrix\Baas\Model\EO_Service $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Baas\Model\EO_Service getByPrimary($primary)
	 * @method \Bitrix\Baas\Model\EO_Service[] getAll()
	 * @method bool remove(\Bitrix\Baas\Model\EO_Service $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Baas\Model\EO_Service_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Baas\Model\EO_Service current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Baas\Model\EO_Service_Collection merge(?\Bitrix\Baas\Model\EO_Service_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Service_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Baas\Model\ServiceTable */
		static public $dataClass = '\Bitrix\Baas\Model\ServiceTable';
	}
}
namespace Bitrix\Baas\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Service_Result exec()
	 * @method \Bitrix\Baas\Model\EO_Service fetchObject()
	 * @method \Bitrix\Baas\Model\EO_Service_Collection fetchCollection()
	 */
	class EO_Service_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Baas\Model\EO_Service fetchObject()
	 * @method \Bitrix\Baas\Model\EO_Service_Collection fetchCollection()
	 */
	class EO_Service_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Baas\Model\EO_Service createObject($setDefaultValues = true)
	 * @method \Bitrix\Baas\Model\EO_Service_Collection createCollection()
	 * @method \Bitrix\Baas\Model\EO_Service wakeUpObject($row)
	 * @method \Bitrix\Baas\Model\EO_Service_Collection wakeUpCollection($rows)
	 */
	class EO_Service_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Baas\Model\PurchaseTable:baas/lib/Model/PurchaseTable.php */
namespace Bitrix\Baas\Model {
	/**
	 * EO_Purchase
	 * @see \Bitrix\Baas\Model\PurchaseTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Baas\Model\EO_Purchase setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int remindActualId()
	 * @method \int requireId()
	 * @method \Bitrix\Baas\Model\EO_Purchase resetId()
	 * @method \Bitrix\Baas\Model\EO_Purchase unsetId()
	 * @method \int fillId()
	 * @method \string getCode()
	 * @method \Bitrix\Baas\Model\EO_Purchase setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string getPurchaseUrl()
	 * @method \Bitrix\Baas\Model\EO_Purchase setPurchaseUrl(\string|\Bitrix\Main\DB\SqlExpression $purchaseUrl)
	 * @method bool hasPurchaseUrl()
	 * @method bool isPurchaseUrlFilled()
	 * @method bool isPurchaseUrlChanged()
	 * @method \string remindActualPurchaseUrl()
	 * @method \string requirePurchaseUrl()
	 * @method \Bitrix\Baas\Model\EO_Purchase resetPurchaseUrl()
	 * @method \Bitrix\Baas\Model\EO_Purchase unsetPurchaseUrl()
	 * @method \string fillPurchaseUrl()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage getPurchasedPackage()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage remindActualPurchasedPackage()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage requirePurchasedPackage()
	 * @method \Bitrix\Baas\Model\EO_Purchase setPurchasedPackage(\Bitrix\Baas\Model\EO_PurchasedPackage $object)
	 * @method \Bitrix\Baas\Model\EO_Purchase resetPurchasedPackage()
	 * @method \Bitrix\Baas\Model\EO_Purchase unsetPurchasedPackage()
	 * @method bool hasPurchasedPackage()
	 * @method bool isPurchasedPackageFilled()
	 * @method bool isPurchasedPackageChanged()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage fillPurchasedPackage()
	 * @method \boolean getPurged()
	 * @method \Bitrix\Baas\Model\EO_Purchase setPurged(\boolean|\Bitrix\Main\DB\SqlExpression $purged)
	 * @method bool hasPurged()
	 * @method bool isPurgedFilled()
	 * @method bool isPurgedChanged()
	 * @method \boolean remindActualPurged()
	 * @method \boolean requirePurged()
	 * @method \Bitrix\Baas\Model\EO_Purchase resetPurged()
	 * @method \Bitrix\Baas\Model\EO_Purchase unsetPurged()
	 * @method \boolean fillPurged()
	 * @method \boolean getNotified()
	 * @method \Bitrix\Baas\Model\EO_Purchase setNotified(\boolean|\Bitrix\Main\DB\SqlExpression $notified)
	 * @method bool hasNotified()
	 * @method bool isNotifiedFilled()
	 * @method bool isNotifiedChanged()
	 * @method \boolean remindActualNotified()
	 * @method \boolean requireNotified()
	 * @method \Bitrix\Baas\Model\EO_Purchase resetNotified()
	 * @method \Bitrix\Baas\Model\EO_Purchase unsetNotified()
	 * @method \boolean fillNotified()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Baas\Model\EO_Purchase set($fieldName, $value)
	 * @method \Bitrix\Baas\Model\EO_Purchase reset($fieldName)
	 * @method \Bitrix\Baas\Model\EO_Purchase unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Baas\Model\EO_Purchase wakeUp($data)
	 */
	class EO_Purchase {
		/* @var \Bitrix\Baas\Model\PurchaseTable */
		static public $dataClass = '\Bitrix\Baas\Model\PurchaseTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Baas\Model {
	/**
	 * EO_Purchase_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] fillId()
	 * @method \string[] getCodeList()
	 * @method \string[] getPurchaseUrlList()
	 * @method \string[] fillPurchaseUrl()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage[] getPurchasedPackageList()
	 * @method \Bitrix\Baas\Model\EO_Purchase_Collection getPurchasedPackageCollection()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage_Collection fillPurchasedPackage()
	 * @method \boolean[] getPurgedList()
	 * @method \boolean[] fillPurged()
	 * @method \boolean[] getNotifiedList()
	 * @method \boolean[] fillNotified()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Baas\Model\EO_Purchase $object)
	 * @method bool has(\Bitrix\Baas\Model\EO_Purchase $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Baas\Model\EO_Purchase getByPrimary($primary)
	 * @method \Bitrix\Baas\Model\EO_Purchase[] getAll()
	 * @method bool remove(\Bitrix\Baas\Model\EO_Purchase $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Baas\Model\EO_Purchase_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Baas\Model\EO_Purchase current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Baas\Model\EO_Purchase_Collection merge(?\Bitrix\Baas\Model\EO_Purchase_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_Purchase_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Baas\Model\PurchaseTable */
		static public $dataClass = '\Bitrix\Baas\Model\PurchaseTable';
	}
}
namespace Bitrix\Baas\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Purchase_Result exec()
	 * @method \Bitrix\Baas\Model\EO_Purchase fetchObject()
	 * @method \Bitrix\Baas\Model\EO_Purchase_Collection fetchCollection()
	 */
	class EO_Purchase_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Baas\Model\EO_Purchase fetchObject()
	 * @method \Bitrix\Baas\Model\EO_Purchase_Collection fetchCollection()
	 */
	class EO_Purchase_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Baas\Model\EO_Purchase createObject($setDefaultValues = true)
	 * @method \Bitrix\Baas\Model\EO_Purchase_Collection createCollection()
	 * @method \Bitrix\Baas\Model\EO_Purchase wakeUpObject($row)
	 * @method \Bitrix\Baas\Model\EO_Purchase_Collection wakeUpCollection($rows)
	 */
	class EO_Purchase_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Baas\Model\ServiceInPackageTable:baas/lib/Model/ServiceInPackageTable.php */
namespace Bitrix\Baas\Model {
	/**
	 * EO_ServiceInPackage
	 * @see \Bitrix\Baas\Model\ServiceInPackageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int remindActualId()
	 * @method \int requireId()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage resetId()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage unsetId()
	 * @method \int fillId()
	 * @method \string getPackageCode()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage setPackageCode(\string|\Bitrix\Main\DB\SqlExpression $packageCode)
	 * @method bool hasPackageCode()
	 * @method bool isPackageCodeFilled()
	 * @method bool isPackageCodeChanged()
	 * @method \string getServiceCode()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage setServiceCode(\string|\Bitrix\Main\DB\SqlExpression $serviceCode)
	 * @method bool hasServiceCode()
	 * @method bool isServiceCodeFilled()
	 * @method bool isServiceCodeChanged()
	 * @method \int getValue()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage setValue(\int|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \int remindActualValue()
	 * @method \int requireValue()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage resetValue()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage unsetValue()
	 * @method \int fillValue()
	 * @method \Bitrix\Baas\Model\EO_Service getService()
	 * @method \Bitrix\Baas\Model\EO_Service remindActualService()
	 * @method \Bitrix\Baas\Model\EO_Service requireService()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage setService(\Bitrix\Baas\Model\EO_Service $object)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage resetService()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage unsetService()
	 * @method bool hasService()
	 * @method bool isServiceFilled()
	 * @method bool isServiceChanged()
	 * @method \Bitrix\Baas\Model\EO_Service fillService()
	 * @method \Bitrix\Baas\Model\EO_Package getPackage()
	 * @method \Bitrix\Baas\Model\EO_Package remindActualPackage()
	 * @method \Bitrix\Baas\Model\EO_Package requirePackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage setPackage(\Bitrix\Baas\Model\EO_Package $object)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage resetPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage unsetPackage()
	 * @method bool hasPackage()
	 * @method bool isPackageFilled()
	 * @method bool isPackageChanged()
	 * @method \Bitrix\Baas\Model\EO_Package fillPackage()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage set($fieldName, $value)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage reset($fieldName)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Baas\Model\EO_ServiceInPackage wakeUp($data)
	 */
	class EO_ServiceInPackage {
		/* @var \Bitrix\Baas\Model\ServiceInPackageTable */
		static public $dataClass = '\Bitrix\Baas\Model\ServiceInPackageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Baas\Model {
	/**
	 * EO_ServiceInPackage_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] fillId()
	 * @method \string[] getPackageCodeList()
	 * @method \string[] getServiceCodeList()
	 * @method \int[] getValueList()
	 * @method \int[] fillValue()
	 * @method \Bitrix\Baas\Model\EO_Service[] getServiceList()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage_Collection getServiceCollection()
	 * @method \Bitrix\Baas\Model\EO_Service_Collection fillService()
	 * @method \Bitrix\Baas\Model\EO_Package[] getPackageList()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage_Collection getPackageCollection()
	 * @method \Bitrix\Baas\Model\EO_Package_Collection fillPackage()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Baas\Model\EO_ServiceInPackage $object)
	 * @method bool has(\Bitrix\Baas\Model\EO_ServiceInPackage $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage getByPrimary($primary)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage[] getAll()
	 * @method bool remove(\Bitrix\Baas\Model\EO_ServiceInPackage $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Baas\Model\EO_ServiceInPackage_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage_Collection merge(?\Bitrix\Baas\Model\EO_ServiceInPackage_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ServiceInPackage_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Baas\Model\ServiceInPackageTable */
		static public $dataClass = '\Bitrix\Baas\Model\ServiceInPackageTable';
	}
}
namespace Bitrix\Baas\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ServiceInPackage_Result exec()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage fetchObject()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage_Collection fetchCollection()
	 */
	class EO_ServiceInPackage_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage fetchObject()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage_Collection fetchCollection()
	 */
	class EO_ServiceInPackage_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage createObject($setDefaultValues = true)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage_Collection createCollection()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage wakeUpObject($row)
	 * @method \Bitrix\Baas\Model\EO_ServiceInPackage_Collection wakeUpCollection($rows)
	 */
	class EO_ServiceInPackage_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Baas\Model\PurchasedPackageTable:baas/lib/Model/PurchasedPackageTable.php */
namespace Bitrix\Baas\Model {
	/**
	 * EO_PurchasedPackage
	 * @see \Bitrix\Baas\Model\PurchasedPackageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int remindActualId()
	 * @method \int requireId()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage resetId()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage unsetId()
	 * @method \int fillId()
	 * @method \string getCode()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage setCode(\string|\Bitrix\Main\DB\SqlExpression $code)
	 * @method bool hasCode()
	 * @method bool isCodeFilled()
	 * @method bool isCodeChanged()
	 * @method \string getPackageCode()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage setPackageCode(\string|\Bitrix\Main\DB\SqlExpression $packageCode)
	 * @method bool hasPackageCode()
	 * @method bool isPackageCodeFilled()
	 * @method bool isPackageCodeChanged()
	 * @method \string getPurchaseCode()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage setPurchaseCode(\string|\Bitrix\Main\DB\SqlExpression $purchaseCode)
	 * @method bool hasPurchaseCode()
	 * @method bool isPurchaseCodeFilled()
	 * @method bool isPurchaseCodeChanged()
	 * @method \string remindActualPurchaseCode()
	 * @method \string requirePurchaseCode()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage resetPurchaseCode()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage unsetPurchaseCode()
	 * @method \string fillPurchaseCode()
	 * @method \string getActive()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage setActive(\string|\Bitrix\Main\DB\SqlExpression $active)
	 * @method bool hasActive()
	 * @method bool isActiveFilled()
	 * @method bool isActiveChanged()
	 * @method \string remindActualActive()
	 * @method \string requireActive()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage resetActive()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage unsetActive()
	 * @method \string fillActive()
	 * @method \Bitrix\Main\Type\Date getStartDate()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage setStartDate(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $startDate)
	 * @method bool hasStartDate()
	 * @method bool isStartDateFilled()
	 * @method bool isStartDateChanged()
	 * @method \Bitrix\Main\Type\Date remindActualStartDate()
	 * @method \Bitrix\Main\Type\Date requireStartDate()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage resetStartDate()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage unsetStartDate()
	 * @method \Bitrix\Main\Type\Date fillStartDate()
	 * @method \Bitrix\Main\Type\Date getExpirationDate()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage setExpirationDate(\Bitrix\Main\Type\Date|\Bitrix\Main\DB\SqlExpression $expirationDate)
	 * @method bool hasExpirationDate()
	 * @method bool isExpirationDateFilled()
	 * @method bool isExpirationDateChanged()
	 * @method \Bitrix\Main\Type\Date remindActualExpirationDate()
	 * @method \Bitrix\Main\Type\Date requireExpirationDate()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage resetExpirationDate()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage unsetExpirationDate()
	 * @method \Bitrix\Main\Type\Date fillExpirationDate()
	 * @method \Bitrix\Baas\Model\EO_Package getPackage()
	 * @method \Bitrix\Baas\Model\EO_Package remindActualPackage()
	 * @method \Bitrix\Baas\Model\EO_Package requirePackage()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage setPackage(\Bitrix\Baas\Model\EO_Package $object)
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage resetPackage()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage unsetPackage()
	 * @method bool hasPackage()
	 * @method bool isPackageFilled()
	 * @method bool isPackageChanged()
	 * @method \Bitrix\Baas\Model\EO_Package fillPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage getServiceInPurchasedPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage remindActualServiceInPurchasedPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage requireServiceInPurchasedPackage()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage setServiceInPurchasedPackage(\Bitrix\Baas\Model\EO_ServiceInPurchasedPackage $object)
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage resetServiceInPurchasedPackage()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage unsetServiceInPurchasedPackage()
	 * @method bool hasServiceInPurchasedPackage()
	 * @method bool isServiceInPurchasedPackageFilled()
	 * @method bool isServiceInPurchasedPackageChanged()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage fillServiceInPurchasedPackage()
	 * @method \boolean getActual()
	 * @method \boolean remindActualActual()
	 * @method \boolean requireActual()
	 * @method bool hasActual()
	 * @method bool isActualFilled()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage unsetActual()
	 * @method \boolean fillActual()
	 * @method \boolean getExpired()
	 * @method \boolean remindActualExpired()
	 * @method \boolean requireExpired()
	 * @method bool hasExpired()
	 * @method bool isExpiredFilled()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage unsetExpired()
	 * @method \boolean fillExpired()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage set($fieldName, $value)
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage reset($fieldName)
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Baas\Model\EO_PurchasedPackage wakeUp($data)
	 */
	class EO_PurchasedPackage {
		/* @var \Bitrix\Baas\Model\PurchasedPackageTable */
		static public $dataClass = '\Bitrix\Baas\Model\PurchasedPackageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Baas\Model {
	/**
	 * EO_PurchasedPackage_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] fillId()
	 * @method \string[] getCodeList()
	 * @method \string[] getPackageCodeList()
	 * @method \string[] getPurchaseCodeList()
	 * @method \string[] fillPurchaseCode()
	 * @method \string[] getActiveList()
	 * @method \string[] fillActive()
	 * @method \Bitrix\Main\Type\Date[] getStartDateList()
	 * @method \Bitrix\Main\Type\Date[] fillStartDate()
	 * @method \Bitrix\Main\Type\Date[] getExpirationDateList()
	 * @method \Bitrix\Main\Type\Date[] fillExpirationDate()
	 * @method \Bitrix\Baas\Model\EO_Package[] getPackageList()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage_Collection getPackageCollection()
	 * @method \Bitrix\Baas\Model\EO_Package_Collection fillPackage()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage[] getServiceInPurchasedPackageList()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage_Collection getServiceInPurchasedPackageCollection()
	 * @method \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage_Collection fillServiceInPurchasedPackage()
	 * @method \boolean[] getActualList()
	 * @method \boolean[] fillActual()
	 * @method \boolean[] getExpiredList()
	 * @method \boolean[] fillExpired()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Baas\Model\EO_PurchasedPackage $object)
	 * @method bool has(\Bitrix\Baas\Model\EO_PurchasedPackage $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage getByPrimary($primary)
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage[] getAll()
	 * @method bool remove(\Bitrix\Baas\Model\EO_PurchasedPackage $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Baas\Model\EO_PurchasedPackage_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage_Collection merge(?\Bitrix\Baas\Model\EO_PurchasedPackage_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_PurchasedPackage_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Baas\Model\PurchasedPackageTable */
		static public $dataClass = '\Bitrix\Baas\Model\PurchasedPackageTable';
	}
}
namespace Bitrix\Baas\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_PurchasedPackage_Result exec()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage fetchObject()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage_Collection fetchCollection()
	 */
	class EO_PurchasedPackage_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage fetchObject()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage_Collection fetchCollection()
	 */
	class EO_PurchasedPackage_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage createObject($setDefaultValues = true)
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage_Collection createCollection()
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage wakeUpObject($row)
	 * @method \Bitrix\Baas\Model\EO_PurchasedPackage_Collection wakeUpCollection($rows)
	 */
	class EO_PurchasedPackage_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Bitrix\Baas\Model\ServiceAdsTable:baas/lib/Model/ServiceAdsTable.php */
namespace Bitrix\Baas\Model {
	/**
	 * EO_ServiceAds
	 * @see \Bitrix\Baas\Model\ServiceAdsTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getServiceCode()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds setServiceCode(\string|\Bitrix\Main\DB\SqlExpression $serviceCode)
	 * @method bool hasServiceCode()
	 * @method bool isServiceCodeFilled()
	 * @method bool isServiceCodeChanged()
	 * @method \string getLanguageId()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds setLanguageId(\string|\Bitrix\Main\DB\SqlExpression $languageId)
	 * @method bool hasLanguageId()
	 * @method bool isLanguageIdFilled()
	 * @method bool isLanguageIdChanged()
	 * @method \string getTitle()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds resetTitle()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getSubtitle()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds setSubtitle(\string|\Bitrix\Main\DB\SqlExpression $subtitle)
	 * @method bool hasSubtitle()
	 * @method bool isSubtitleFilled()
	 * @method bool isSubtitleChanged()
	 * @method \string remindActualSubtitle()
	 * @method \string requireSubtitle()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds resetSubtitle()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds unsetSubtitle()
	 * @method \string fillSubtitle()
	 * @method \string getSubtitleDescription()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds setSubtitleDescription(\string|\Bitrix\Main\DB\SqlExpression $subtitleDescription)
	 * @method bool hasSubtitleDescription()
	 * @method bool isSubtitleDescriptionFilled()
	 * @method bool isSubtitleDescriptionChanged()
	 * @method \string remindActualSubtitleDescription()
	 * @method \string requireSubtitleDescription()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds resetSubtitleDescription()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds unsetSubtitleDescription()
	 * @method \string fillSubtitleDescription()
	 * @method \string getIconUrl()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds setIconUrl(\string|\Bitrix\Main\DB\SqlExpression $iconUrl)
	 * @method bool hasIconUrl()
	 * @method bool isIconUrlFilled()
	 * @method bool isIconUrlChanged()
	 * @method \string remindActualIconUrl()
	 * @method \string requireIconUrl()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds resetIconUrl()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds unsetIconUrl()
	 * @method \string fillIconUrl()
	 * @method \string getIconFileType()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds setIconFileType(\string|\Bitrix\Main\DB\SqlExpression $iconFileType)
	 * @method bool hasIconFileType()
	 * @method bool isIconFileTypeFilled()
	 * @method bool isIconFileTypeChanged()
	 * @method \string remindActualIconFileType()
	 * @method \string requireIconFileType()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds resetIconFileType()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds unsetIconFileType()
	 * @method \string fillIconFileType()
	 * @method \string getVideoUrl()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds setVideoUrl(\string|\Bitrix\Main\DB\SqlExpression $videoUrl)
	 * @method bool hasVideoUrl()
	 * @method bool isVideoUrlFilled()
	 * @method bool isVideoUrlChanged()
	 * @method \string remindActualVideoUrl()
	 * @method \string requireVideoUrl()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds resetVideoUrl()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds unsetVideoUrl()
	 * @method \string fillVideoUrl()
	 * @method \string getVideoFileType()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds setVideoFileType(\string|\Bitrix\Main\DB\SqlExpression $videoFileType)
	 * @method bool hasVideoFileType()
	 * @method bool isVideoFileTypeFilled()
	 * @method bool isVideoFileTypeChanged()
	 * @method \string remindActualVideoFileType()
	 * @method \string requireVideoFileType()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds resetVideoFileType()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds unsetVideoFileType()
	 * @method \string fillVideoFileType()
	 * @method \string getFeaturePromotionCode()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds setFeaturePromotionCode(\string|\Bitrix\Main\DB\SqlExpression $featurePromotionCode)
	 * @method bool hasFeaturePromotionCode()
	 * @method bool isFeaturePromotionCodeFilled()
	 * @method bool isFeaturePromotionCodeChanged()
	 * @method \string remindActualFeaturePromotionCode()
	 * @method \string requireFeaturePromotionCode()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds resetFeaturePromotionCode()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds unsetFeaturePromotionCode()
	 * @method \string fillFeaturePromotionCode()
	 * @method \string getHelperCode()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds setHelperCode(\string|\Bitrix\Main\DB\SqlExpression $helperCode)
	 * @method bool hasHelperCode()
	 * @method bool isHelperCodeFilled()
	 * @method bool isHelperCodeChanged()
	 * @method \string remindActualHelperCode()
	 * @method \string requireHelperCode()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds resetHelperCode()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds unsetHelperCode()
	 * @method \string fillHelperCode()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Bitrix\Baas\Model\EO_ServiceAds set($fieldName, $value)
	 * @method \Bitrix\Baas\Model\EO_ServiceAds reset($fieldName)
	 * @method \Bitrix\Baas\Model\EO_ServiceAds unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method mixed fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Bitrix\Baas\Model\EO_ServiceAds wakeUp($data)
	 */
	class EO_ServiceAds {
		/* @var \Bitrix\Baas\Model\ServiceAdsTable */
		static public $dataClass = '\Bitrix\Baas\Model\ServiceAdsTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Bitrix\Baas\Model {
	/**
	 * EO_ServiceAds_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getServiceCodeList()
	 * @method \string[] getLanguageIdList()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getSubtitleList()
	 * @method \string[] fillSubtitle()
	 * @method \string[] getSubtitleDescriptionList()
	 * @method \string[] fillSubtitleDescription()
	 * @method \string[] getIconUrlList()
	 * @method \string[] fillIconUrl()
	 * @method \string[] getIconFileTypeList()
	 * @method \string[] fillIconFileType()
	 * @method \string[] getVideoUrlList()
	 * @method \string[] fillVideoUrl()
	 * @method \string[] getVideoFileTypeList()
	 * @method \string[] fillVideoFileType()
	 * @method \string[] getFeaturePromotionCodeList()
	 * @method \string[] fillFeaturePromotionCode()
	 * @method \string[] getHelperCodeList()
	 * @method \string[] fillHelperCode()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Bitrix\Baas\Model\EO_ServiceAds $object)
	 * @method bool has(\Bitrix\Baas\Model\EO_ServiceAds $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Bitrix\Baas\Model\EO_ServiceAds getByPrimary($primary)
	 * @method \Bitrix\Baas\Model\EO_ServiceAds[] getAll()
	 * @method bool remove(\Bitrix\Baas\Model\EO_ServiceAds $object)
	 * @method void removeByPrimary($primary)
	 * @method array|\Bitrix\Main\ORM\Objectify\Collection|null fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Bitrix\Baas\Model\EO_ServiceAds_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Bitrix\Baas\Model\EO_ServiceAds current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method \Bitrix\Baas\Model\EO_ServiceAds_Collection merge(?\Bitrix\Baas\Model\EO_ServiceAds_Collection $collection)
	 * @method bool isEmpty()
	 * @method array collectValues(int $valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, int $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL, bool $recursive = false)
	 */
	class EO_ServiceAds_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Bitrix\Baas\Model\ServiceAdsTable */
		static public $dataClass = '\Bitrix\Baas\Model\ServiceAdsTable';
	}
}
namespace Bitrix\Baas\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_ServiceAds_Result exec()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds fetchObject()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds_Collection fetchCollection()
	 */
	class EO_ServiceAds_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Bitrix\Baas\Model\EO_ServiceAds fetchObject()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds_Collection fetchCollection()
	 */
	class EO_ServiceAds_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Bitrix\Baas\Model\EO_ServiceAds createObject($setDefaultValues = true)
	 * @method \Bitrix\Baas\Model\EO_ServiceAds_Collection createCollection()
	 * @method \Bitrix\Baas\Model\EO_ServiceAds wakeUpObject($row)
	 * @method \Bitrix\Baas\Model\EO_ServiceAds_Collection wakeUpCollection($rows)
	 */
	class EO_ServiceAds_Entity extends \Bitrix\Main\ORM\Entity {}
}