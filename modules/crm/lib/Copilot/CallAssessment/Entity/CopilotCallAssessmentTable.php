<?php

namespace Bitrix\Crm\Copilot\CallAssessment\Entity;

use Bitrix\Crm\Copilot\CallAssessment\Entity\Fields\Validators\PromptLengthValidator;
use Bitrix\Crm\Copilot\CallAssessment\Enum\AvailabilityType;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Validators\RangeValidator;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Emoji;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class CopilotCallAssessmentTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CopilotCallAssessment_Query query()
 * @method static EO_CopilotCallAssessment_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CopilotCallAssessment_Result getById($id)
 * @method static EO_CopilotCallAssessment_Result getList(array $parameters = [])
 * @method static EO_CopilotCallAssessment_Entity getEntity()
 * @method static \Bitrix\Crm\Copilot\CallAssessment\Entity\CopilotCallAssessment createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Copilot\CallAssessment\Entity\EO_CopilotCallAssessment_Collection createCollection()
 * @method static \Bitrix\Crm\Copilot\CallAssessment\Entity\CopilotCallAssessment wakeUpObject($row)
 * @method static \Bitrix\Crm\Copilot\CallAssessment\Entity\EO_CopilotCallAssessment_Collection wakeUpCollection($rows)
 */
class CopilotCallAssessmentTable extends Main\ORM\Data\DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_copilot_call_assessment';
	}

	public static function getObjectClass(): string
	{
		return CopilotCallAssessment::class;
	}

	/**
	 * @throws SystemException
	 * @throws ObjectNotFoundException
	 * @throws NotFoundExceptionInterface
	 */
	public static function getMap(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			$fieldRepository->getId(),
			$fieldRepository->getTitle()
				->configureDefaultValue('')
			,
			(new Main\ORM\Fields\StringField('PROMPT'))
				->addValidator(new PromptLengthValidator())
				->addSaveDataModifier([Emoji::class, 'encode'])
				->addFetchDataModifier([Emoji::class, 'decode'])
				->configureRequired()
			,
			(new Main\ORM\Fields\StringField('GIST')),
			(new Main\ORM\Fields\IntegerField('CALL_TYPE'))
				->configureSize(1)
				->configureRequired()
			,
			(new Main\ORM\Fields\IntegerField('AUTO_CHECK_TYPE'))
				->configureSize(1)
				->configureRequired()
			,
			(new Main\ORM\Fields\BooleanField('IS_ENABLED'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('Y')
				->configureRequired()
			,
			(new Main\ORM\Fields\BooleanField('IS_DEFAULT'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N')
			,
			(new Main\ORM\Fields\IntegerField('JOB_ID'))
				->configureRequired()
				->configureDefaultValue(0)
			,
			(new Main\ORM\Fields\StringField('STATUS'))
				->configureRequired()
				->configureSize(100)
				->configureDefaultValue(QueueTable::EXECUTION_STATUS_PENDING)
			,
			(new Main\ORM\Fields\StringField('CODE'))
				->configureSize(30)
				->configureNullable()
			,
			(new Main\ORM\Fields\IntegerField('LOW_BORDER'))
				->configureRequired()
				->configureDefaultValue(0)
				->addValidator(new RangeValidator(min: 0, max: 100))
			,
			(new Main\ORM\Fields\IntegerField('HIGH_BORDER'))
				->configureRequired()
				->configureDefaultValue(100)
				->addValidator(new RangeValidator(min: 0, max: 100))
			,
			(new Main\ORM\Fields\EnumField('AVAILABILITY_TYPE'))
				->configureRequired()
				->configureValues(AvailabilityType::values())
				->configureDefaultValue(AvailabilityType::ALWAYS_ACTIVE->value)
			,
			$fieldRepository->getCreatedTime('CREATED_AT'),
			$fieldRepository->getUpdatedTime('UPDATED_AT'),
			$fieldRepository
				->getCreatedBy('CREATED_BY_ID')
				->configureDefaultValue(static fn() => Container::getInstance()->getContext()->getUserId())
			,
			$fieldRepository
				->getUpdatedBy('UPDATED_BY_ID')
				->configureDefaultValue(static fn() => Container::getInstance()->getContext()->getUserId())
			,
			(new OneToMany('CLIENT_TYPES', CopilotCallAssessmentClientTypeTable::class, 'ASSESSMENT')),
			(new OneToMany('AVAILABILITY_DATA', CopilotCallAssessmentAvailabilityTable::class, 'ASSESSMENT')),
		];
	}
}
