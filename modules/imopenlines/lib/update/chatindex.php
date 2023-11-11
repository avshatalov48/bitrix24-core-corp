<?
namespace Bitrix\Imopenlines\Update;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;


Loc::loadMessages(__FILE__);

final class ChatIndex extends Stepper
{
	const OPTION_NAME = "im_index_chat";
	protected static $moduleId = "imopenlines";
	protected const LIMIT = 500;
	protected const DELETE_BATCH_SIZE = 50;

	/**
	 * @inheritdoc
	 */
	public function execute(array &$result)
	{
		if (!Loader::includeModule(self::$moduleId) || !Loader::includeModule('im'))
		{
			return self::FINISH_EXECUTION;
		}

		if (!isset($result['lastId']))
		{
			$queryResult = \Bitrix\Im\Model\ChatTable::query()
				->setSelect([new ExpressionField('MAX_ID', 'MAX(%s)', ['ID'])])
				->where('TYPE', \Bitrix\Im\V2\Chat::IM_TYPE_OPEN_LINE)
				->fetch() ?: []
			;
			$maxId = (int)($queryResult['MAX_ID'] ?? 0);
			$result['lastId'] = $maxId + 1;
		}

		$openLineChatIdsWithTitles = $this->getOpenLineChatIdsWithTitles($result['lastId'] ?? 0);

		if (empty($openLineChatIdsWithTitles))
		{
			return self::FINISH_EXECUTION;
		}

		$ids = array_keys($openLineChatIdsWithTitles);
		$this->deleteFromCommonIndex($ids);
		$this->recordOpenLineIndex($openLineChatIdsWithTitles);
		$result['lastId'] = min($ids);

		return self::CONTINUE_EXECUTION;
	}

	public function getOpenLineChatIdsWithTitles(int $lastId): array
	{
		$raw = \Bitrix\Im\Model\ChatTable::query()
			->setSelect(['ID', 'TITLE'])
			->where('TYPE', \Bitrix\Im\V2\Chat::IM_TYPE_OPEN_LINE)
			->where('ID', '<', $lastId)
			->setLimit(self::LIMIT)
			->setOrder(['ID' => 'DESC'])
			->fetchAll()
		;
		$result = [];

		foreach ($raw as $row)
		{
			$result[(int)$row['ID']] = $row['TITLE'];
		}

		return $result;
	}

	public function deleteFromCommonIndex(array $ids): void
	{
		$batch = [];
		$count = 0;
		foreach ($ids as $id)
		{
			if ($count === self::DELETE_BATCH_SIZE)
			{
				\Bitrix\Im\Model\ChatIndexTable::deleteByFilter(['=CHAT_ID' => $batch]);
				$batch = [];
				$count = 0;
			}
			$batch[] = $id;
			$count++;
		}

		if (!empty($batch))
		{
			\Bitrix\Im\Model\ChatIndexTable::deleteByFilter(['=CHAT_ID' => $batch]);
		}
	}

	public function recordOpenLineIndex(array $idsWithTitles): void
	{
		foreach ($idsWithTitles as $id => $title)
		{
			\Bitrix\ImOpenLines\Model\ChatIndexTable::mergeIndex($id, $title);
		}
	}
}
?>