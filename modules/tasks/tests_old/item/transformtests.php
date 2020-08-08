<?
CModule::IncludeModule('tasks');

include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/tasks/dev/util/testcase.php");
$beforeClasses = get_declared_classes();
$beforeClassesCount = count($beforeClasses);

use \Bitrix\Tasks\Item;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\UI;

class TransformTests extends \Bitrix\Tasks\Dev\Util\TestCase
{
	public function testTransformTemplateToTask()
	{
		//static::disableDataCleanUp();

		$staticUsers = static::$users;
		$users = static::getExistingDemoUsers(2);

		$t11 = static::makeDemoTask();
		$this->assertTrue($t11 > 0);

		$t12 = static::makeDemoTask();
		$this->assertTrue($t12 > 0);

		$ufDiskCode = \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode();
		$diskInstalled = \Bitrix\Tasks\Integration\Disk::isInstalled();
		$attachments = array();
		$description = '';
		if($diskInstalled)
		{
			$a1 = static::makeDemoAttachment();
			$a2 = static::makeDemoAttachment();

			$description = '[DISK FILE ID='.$a1.'] [DISK FILE ID='.$a2.']';
			$attachments = array($a1, $a2);
		}

		$t1 = static::makeDemoTemplateLegacy(array(
			'override' => array(
				'ACCOMPLICES' => serialize($users),
				'AUDITORS' => serialize($users),
				'DEPENDS_ON' => serialize(array($t11, $t12)),
				'PRIORITY' => 2,

				'GROUP_ID' => 1,
				'PARENT_ID' => $t11,

				'DEADLINE_AFTER' => 180, // 3 minutes
				'START_DATE_PLAN_AFTER' => 3600, // 1 hour
				'END_DATE_PLAN_AFTER' => 867600, // 10 days + 1 hour

				$ufDiskCode => $attachments,
				'DESCRIPTION' => $description,
			)
		));
		$this->assertTrue($t1 > 0);

		$template = new \Bitrix\Tasks\Item\Task\Template($t1);
		$converter = new \Bitrix\Tasks\Item\Converter\Task\Template\ToTask();

		// set fixed moment to be able to check result dates: 1/13/2017, 6:00:00 PM
		$nowStamp = 1484323200;
		$ctx = $converter->getContext()->spawn();
		$ctx->setNow(Util\Type\DateTime::createFromTimestamp($nowStamp));
		$converter->setContext($ctx);

		$result = $template->transform($converter);

		$this->assertTrue($result->isSuccess());
		$task = $result->getInstance();

		// check all fields:

		$this->assertEquals(2, $task['PRIORITY']);
		$this->assertEquals(1, $task['GROUP_ID']);
		$this->assertEquals($t11, $task['PARENT_ID']);

		// dates
		$this->assertTrue($task['DEADLINE']->getTimeStamp() - $nowStamp == 180);
		$this->assertTrue($task['START_DATE_PLAN']->getTimeStamp() - $nowStamp == 3600);
		$this->assertTrue($task['END_DATE_PLAN']->getTimeStamp() - $nowStamp == 867600);

		// DEPENDS_ON
		$this->assertItemsEqual(array($t11, $t12), $task['DEPENDS_ON']->export());

		// SE_CHECKLIST
		$checkList = $task['SE_CHECKLIST'];

		$this->assertEquals(3, $checkList->count());

		$this->assertEquals('One', $checkList->first()['TITLE']);
		$this->assertEquals('Two', $checkList->nth(1)['TITLE']);
		$this->assertEquals('Three', $checkList->last()['TITLE']);

		$this->assertTrue(!$checkList->first()->isCompleted());
		$this->assertEquals('N', $checkList->nth(1)['IS_COMPLETE']);
		$this->assertTrue($checkList->last()->isCompleted());

		// SE_MEMBER
		$member = $task['SE_MEMBER'];

		$this->assertEquals(6, $member->count());
		$this->assertItemsEqual($users, $task['ACCOMPLICES']->export());
		$this->assertItemsEqual($users, $task['AUDITORS']->export());
		$this->assertEquals($staticUsers[1], $task['RESPONSIBLE_ID']);
		$this->assertEquals($staticUsers[0], $task['CREATED_BY']);

		// SE_TAG
		$tags = $task['SE_TAG'];
		$this->assertEquals(2, $tags->count());
		$this->assertEquals('tag one', $task['TAGS'][0]);
		$this->assertEquals('tag two', $task['TAGS'][1]);

		// UF_TASK_WEBDAV_FILES & inline attaches
		if($diskInstalled)
		{
			$taskAttachments = $task[$ufDiskCode];
			$description = trim($task->description);

			$this->assertEquals(2, $taskAttachments->count());

			$this->assertTrue(mb_strpos($description, '[DISK FILE ID='.$taskAttachments[0].']') >= 0);
			$this->assertTrue(mb_strpos($description, '[DISK FILE ID='.$taskAttachments[1].']') >= 0);
		}

		$saveResult = $task->save();
		$this->assertTrue($saveResult->isSuccess());
		$this->assertTrue($task->getId() > 0);
	}
}