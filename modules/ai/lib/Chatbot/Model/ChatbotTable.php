<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Model;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class ChatbotTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Chatbot_Query query()
 * @method static EO_Chatbot_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Chatbot_Result getById($id)
 * @method static EO_Chatbot_Result getList(array $parameters = [])
 * @method static EO_Chatbot_Entity getEntity()
 * @method static \Bitrix\AI\Chatbot\Model\Chatbot createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Chatbot\Model\EO_Chatbot_Collection createCollection()
 * @method static \Bitrix\AI\Chatbot\Model\Chatbot wakeUpObject($row)
 * @method static \Bitrix\AI\Chatbot\Model\EO_Chatbot_Collection wakeUpCollection($rows)
 */
class ChatbotTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_ai_chatbot_chatbot';
	}

	public static function getObjectClass(): string
	{
		return Chatbot::class;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configureAutocomplete()
				->configurePrimary(),
			(new StringField('MODULE_ID'))
				->configureRequired(),
			(new StringField('CODE'))
				->configureRequired(),
			(new StringField('CLASS'))
				->configureRequired(),
			(new DatetimeField('DATE_CREATE')),
		];
	}
}