<?php

namespace Bitrix\Crm\AutomatedSolution\Entity;

use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

/**
 * Class AutomatedSolutionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_AutomatedSolution_Query query()
 * @method static EO_AutomatedSolution_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_AutomatedSolution_Result getById($id)
 * @method static EO_AutomatedSolution_Result getList(array $parameters = [])
 * @method static EO_AutomatedSolution_Entity getEntity()
 * @method static \Bitrix\Crm\AutomatedSolution\Entity\EO_AutomatedSolution createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\AutomatedSolution\Entity\EO_AutomatedSolution_Collection createCollection()
 * @method static \Bitrix\Crm\AutomatedSolution\Entity\EO_AutomatedSolution wakeUpObject($row)
 * @method static \Bitrix\Crm\AutomatedSolution\Entity\EO_AutomatedSolution_Collection wakeUpCollection($rows)
 */
class AutomatedSolutionTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_automated_solution';
	}

	public static function getMap(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,

			(new IntegerField('INTRANET_CUSTOM_SECTION_ID'))
				->configureRequired()
			,

			(new StringField('TITLE'))
				->configureTitle(Loc::getMessage('CRM_COMMON_TITLE'))
				->configureSize(255)
				->configureRequired()
			,

			(new StringField('CODE'))
				->configureTitle(Loc::getMessage('CRM_COMMON_CODE'))
				->configureSize(255)
			,

			(new IntegerField('SORT'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_SORT'))
				->configureRequired()
				->configureDefaultValue(100)
			,

			(new DatetimeField('CREATED_TIME'))
				->configureTitle(Loc::getMessage('CRM_COMMON_CREATED_TIME'))
				->configureRequired()
				->configureDefaultValue(static fn() => new DateTime())
			,

			(new DatetimeField('UPDATED_TIME'))
				->configureTitle(Loc::getMessage('CRM_COMMON_MODIFY_DATE'))
				->configureRequired()
				->configureDefaultValue(static fn() => new DateTime())
			,

			(new IntegerField('CREATED_BY'))
				->configureTitle(Loc::getMessage('CRM_AUTOMATED_SOLUTION_TABLE_FIELD_NAME_CREATED_BY'))
				->configureRequired()
				->configureDefaultValue(static fn() => Container::getInstance()->getContext()->getUserId())
			,

			(new IntegerField('UPDATED_BY'))
				->configureTitle(Loc::getMessage('CRM_AUTOMATED_SOLUTION_TABLE_FIELD_NAME_UPDATED_BY'))
				->configureRequired()
				->configureDefaultValue(static fn() => Container::getInstance()->getContext()->getUserId())
			,

			(new OneToMany('TYPES', TypeTable::class, 'AUTOMATED_SOLUTION'))
				->configureCascadeDeletePolicy(CascadePolicy::SET_NULL)
			,
		];
	}

	final public static function onBeforeAdd(Event $event): EventResult
	{
		$result = new EventResult();
		self::trimTitle($event, $result);

		return $result;
	}

	final public static function onBeforeUpdate(Event $event): EventResult
	{
		$result = new EventResult();
		self::trimTitle($event, $result);

		return $result;
	}

	private static function trimTitle(Event $event, EventResult $result): void
	{
		$title = self::extractFieldFromOrmEvent($event, $result, 'TITLE');
		if (!is_string($title))
		{
			return;
		}

		$trimmedTitle = trim($title);
		if ($trimmedTitle !== $title)
		{
			$result->modifyFields(
				$result->getModified() + ['TITLE' => $trimmedTitle],
			);
		}
	}

	private static function extractFieldFromOrmEvent(Event $event, EventResult $result, string $fieldName): mixed
	{
		$fields = $event->getParameter('fields') ?? [];

		return $result->getModified()[$fieldName] ?? $fields[$fieldName] ?? null;
	}
}
