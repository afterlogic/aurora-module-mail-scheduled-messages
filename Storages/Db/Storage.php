<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailScheduledMessages\Storages\Db;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2021, Afterlogic Corp.
 */
class Storage extends \Aurora\Modules\MailScheduledMessages\Storages\Storage
{
	/**
	 * @var CDbStorage $oConnection
	 */
	protected $oConnection;

	/**
	 * @var CApiMinCommandCreatorMySQL
	 */
	protected $oCommandCreator;

	/**
	 *
	 * @param \Aurora\System\Managers\AbstractManager $oManager
	 */
	public function __construct(\Aurora\System\Managers\AbstractManager &$oManager)
	{
		parent::__construct($oManager);

		$this->oConnection =& $oManager->GetConnection();
		$this->oCommandCreator = new CommandCreator\MySQL();
	}

	/**
	 * @return array|bool
	 */
	public function getMessagesForSend($iTimestamp)
	{
		$mResult = [];

		if ($this->oConnection->Execute($this->oCommandCreator->getMessagesForSend($iTimestamp)))
		{
			$oRow = null;
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$mResult[] = [
					'AccountId' => (int) $oRow->account_id,
					'FolderFullName' => $oRow->folder_full_name,
					'MessageUid' => $oRow->message_uid,
					'ScheduleTimestamp' => (int) $oRow->schedule_timestamp
				];
			}
			$this->oConnection->FreeResult();
		}

		return $mResult;
	}

	public function getMessage($iAccountID, $sFolderFullName, $sMessageUid)
    {
		$mResult = false;
        if ($this->oConnection->Execute($this->oCommandCreator->getMessage($iAccountID, $sFolderFullName, $sMessageUid)))
		{
			$oRow = $this->oConnection->GetNextRecord();
			if ($oRow !== false)
			{
				$mResult = [
					'AccountId' => (int) $oRow->account_id,
					'FolderFullName' => $oRow->folder_full_name,
					'MessageUid' => $oRow->message_uid,
					'ScheduleTimestamp' => (int) $oRow->schedule_timestamp
				];
			}
			$this->oConnection->FreeResult();
		}

		return $mResult;
    }

    public function addMessage($iAccountID, $sFolderFullName, $sMessageUid, $iTimestamp)
    {
        return $this->oConnection->Execute($this->oCommandCreator->addMessage($iAccountID, $sFolderFullName, $sMessageUid, $iTimestamp));
    }

    public function updateMessageScheduleTimestamp($iAccountID, $sFolderFullName, $sMessageUid, $iTimestamp)
    {
        return $this->oConnection->Execute($this->oCommandCreator->updateMessageScheduleTimestamp($iAccountID, $sFolderFullName, $sMessageUid, $iTimestamp));
    }

    public function removeMessage($iAccountID, $sFolderFullName, $sMessageUid)
    {
        return $this->oConnection->Execute($this->oCommandCreator->removeMessage($iAccountID, $sFolderFullName, $sMessageUid));
    }
}
