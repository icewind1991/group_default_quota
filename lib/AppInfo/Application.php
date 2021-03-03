<?php
/**
 * @copyright Copyright (c) 2020 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\GroupDefaultQuota\AppInfo;

use OCP\User\GetQuotaEvent;
use OCA\GroupDefaultQuota\QuotaManager;
use OCP\AppFramework\App;
use OCP\EventDispatcher\IEventDispatcher;

class Application extends App {
	public function __construct(array $urlParams = []) {
		parent::__construct('group_default_quota', $urlParams);
	}

	private function getQuotaManager(): QuotaManager {
		return $this->getContainer()->query(QuotaManager::class);
	}

	public function register() {
		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $this->getContainer()->query(IEventDispatcher::class);

		$dispatcher->addListener(GetQuotaEvent::class, function (GetQuotaEvent $event) {
			$quota = $this->getQuotaManager()->getDefaultQuotaForUser($event->getUser());
			if ($quota !== 'default') {
				$event->setQuota($quota);
			}
		});
	}
}
