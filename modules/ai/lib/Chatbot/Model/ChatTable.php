<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Model;

use Bitrix\AI\Chatbot\Enum\ChatInputStatus;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

/**
 * Class ChatTable
 *
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Chat_Query query()
 * @method static EO_Chat_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Chat_Result getById($id)
 * @method static EO_Chat_Result getList(array $parameters = [])
 * @method static EO_Chat_Entity getEntity()
 * @method static \Bitrix\AI\Chatbot\Model\Chat createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Chatbot\Model\EO_Chat_Collection createCollection()
 * @method static \Bitrix\AI\Chatbot\Model\Chat wakeUpObject($row)
 * @method static \Bitrix\AI\Chatbot\Model\EO_Chat_Collection wakeUpCollection($rows)
 */
class ChatTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_chatbot_chat';
	}

	public static function getObjectClass(): string
	{
		return Chat::class;
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configureAutocomplete()
				->configurePrimary(),
			(new StringField('CODE'))
				->configureRequired(),
			(new IntegerField('AUTHOR_ID'))
				->configureRequired(),
			(new IntegerField('CHATBOT_ID'))
				->configureRequired(),
			(new ArrayField('PARAMS'))
				->configureDefaultValue([])
				->configureSerializationJson(),
			(new EnumField('INPUT_STATUS'))
				->configureDefaultValue(ChatInputStatus::Unlock->value)
				->configureValues(array_column(ChatInputStatus::cases(), 'value')),
			(new DatetimeField('DATE_CREATE'))
				->configureDefaultValue(fn() => new DateTime()),
		];
	}

}
