<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 Robin Appelman <robin@icewind.nl>
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

namespace OCA\GroupDefaultQuota;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\GetQuotaEvent;

class QuotaListener implements IEventListener {
	/** @var QuotaManager */
	private $quotaManager;

	public function __construct(QuotaManager $quotaManager) {
		$this->quotaManager = $quotaManager;
	}

	public function handle(Event $event): void {
		if ($event instanceof GetQuotaEvent) {
			$quota = $this->quotaManager->getDefaultQuotaForUser($event->getUser());
			if ($quota !== 'default') {
				$event->setQuota($quota);
			}
		}
	}
}
