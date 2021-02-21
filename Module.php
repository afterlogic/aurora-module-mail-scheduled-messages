<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailScheduledMessages;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
	public $oManager = null;

	public $sScheduledFolderName = 'Scheduled';

	public $oMailModuleAccountsManager = null;

	public $oMailModuleMailManager = null;

	public function init()
	{
		$this->oManager = new Manager($this);

		$this->subscribeEvent('Mail::GetFolders::before', array($this, 'onBeforeGetFolders'));
		$this->subscribeEvent('Core::CreateTables::after', array($this, 'onAfterCreateTables'));

		$oMailModule = \Aurora\Modules\Mail\Module::getInstance();
		$this->oMailModuleAccountsManager = $oMailModule->getAccountsManager();
		$this->oMailModuleMailManager = $oMailModule->getMailManager();
	}

	protected function getScheduledFolderFullName($oAccount)
	{
		$oNamespace = \Aurora\Modules\Mail\Module::getInstance()->getMailManager()->getFoldersNamespace($oAccount);
		$sNamespace = $oNamespace ? $oNamespace->GetPersonalNamespace() : '';
		return $sNamespace . $this->sScheduledFolderName;
	}

	public function onBeforeGetFolders(&$aArgs, &$mResult)
	{
		$iAccountID = $aArgs['AccountID'];
		$oAccount = $this->oMailModuleAccountsManager->getAccountById($iAccountID);
		if ($oAccount)
		{
			$sScheduledFolderFullName = $this->getScheduledFolderFullName($oAccount);
			$aResult = $this->oMailModuleMailManager->getFolderListInformation($oAccount, array($sScheduledFolderFullName), false);
			if (empty($aResult))
			{
				try
				{
					\Aurora\Modules\Mail\Module::Decorator()->CreateFolder($iAccountID, $sScheduledFolderFullName, '', '/');
				}
				catch (\Exception $oException) {}
			}
		}
	}

	/**
	 * Creates tables required for module work. Called by event subscribe.
	 *
	 * @ignore
	 * @param array $aParams Parameters
	 */
	public function onAfterCreateTables($aParams, &$mResult)
	{
		if ($mResult)
		{
			$mResult = $this->oManager->createTablesFromFile();
			if ($mResult)
			{
				$mResult = $this->oManager->updateTables();
			}
		}
	}

	public function GetSettings()
	{
		return [
			'PredefinedSchedule' => $this->getConfig('PredefinedSchedule', [])
		];
	}

	public function SaveScheduledMessage(
		$AccountID,
		$Fetcher = null,
		$Alias = null,
		$IdentityID = 0,
		$To = "",
		$Cc = "",
		$Bcc = "",
		$Recipients = array(),
		$Subject = "",
		$Text = "",
		$IsHtml = false,
		$Importance = \MailSo\Mime\Enumerations\MessagePriority::NORMAL,
		$SendReadingConfirmation = false,
		$Attachments = array(),
		$InReplyTo = "",
		$References = "",
		$Sensitivity = \MailSo\Mime\Enumerations\Sensitivity::NOTHING,
		$CustomHeaders = [],
		$ScheduleDateTime = null)
	{
		$iNewUid = 0;
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

		$oAccount = $this->oMailModuleAccountsManager->getAccountById($AccountID);

		\Aurora\Modules\Mail\Module::checkAccess($oAccount);

		$oIdentity = $IdentityID !== 0 ? \Aurora\Modules\Mail\Module::getInstance()->getIdentitiesManager()->getIdentity($IdentityID) : null;
		$oMessage = \Aurora\Modules\Mail\Module::Decorator()->BuildMessage(
			$oAccount,
			$To,
			$Cc,
			$Bcc,
			$Subject,
			$IsHtml,
			$Text,
			$Attachments,
			null,
			$InReplyTo,
			$References,
			$Importance,
			$Sensitivity,
			$SendReadingConfirmation,
			$Fetcher,
			$Alias,
			false,
			$oIdentity,
			$CustomHeaders);
		if ($oMessage)
		{
			$oMessage->SetDate($ScheduleDateTime);
			$rMessageStream = \MailSo\Base\ResourceRegistry::CreateMemoryResource();
			$iMessageStreamSize = \MailSo\Base\Utils::MultipleStreamWriter($oMessage->ToStream(true), array($rMessageStream), 8192, true, true, true);

			$FolderFullName = $this->getScheduledFolderFullName($oAccount);
			$this->oMailModuleMailManager->appendMessageFromStream($oAccount, $rMessageStream, $FolderFullName, $iMessageStreamSize, $iNewUid);
			$this->oMailModuleMailManager->setMessageFlag($oAccount, $FolderFullName, [$iNewUid], \MailSo\Imap\Enumerations\MessageFlag::SEEN, \Aurora\Modules\Mail\Enums\MessageStoreAction::Add);

			$this->oManager->removeMessage($oAccount->EntityId, $FolderFullName, $iNewUid);
			$this->oManager->addMessage($oAccount->EntityId, $FolderFullName, $iNewUid, $ScheduleDateTime);
		}

		return $iNewUid;
	}

	public function GetMessagesForSend($ScheduledTimestamp)
	{
		return $this->oManager->getMessagesForSend($ScheduledTimestamp);
	}

	public function RemoveMessage($AccountID, $FolderFullName, $MessageUid)
	{
		return $this->oManager->removeMessage($AccountID, $FolderFullName, $MessageUid);
	}
}
