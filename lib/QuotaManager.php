<?php

declare(strict_types=1);
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

namespace OCA\GroupDefaultQuota;

use OCP\Config\IUserConfig;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Util;

class QuotaManager {
	public function __construct(
		private IAppConfig $appConfig,
		private IUserConfig $userConfig,
		private IGroupManager $groupManager,
	) {
	}

	public function setGroupDefault(string $groupId, string $quota) {
		if ($quota === 'default') {
			$this->appConfig->deleteKey('group_default_quota', 'default_quota_' . $groupId);
		} else {
			$this->appConfig->setValueString('group_default_quota', 'default_quota_' . $groupId, $quota);
		}
	}

	public function getGroupDefault(string $groupId): string {
		return $this->appConfig->getValueString('group_default_quota', 'default_quota_' . $groupId, 'default');
	}

	public function getDefaultQuotaForUser(IUser $user): string {
		$quota = $this->userConfig->getValueString($user->getUID(), 'files', 'quota', 'default');
		if ($quota !== 'default') {
			return $quota;
		}
		$groups = $this->groupManager->getUserGroupIds($user);
		if (!$groups) {
			return $quota;
		}
		$groupQuotas = array_map(function (string $groupId) {
			$quota = $this->getGroupDefault($groupId);
			return ($quota === 'default') ? 0 : Util::computerFileSize($quota);
		}, $groups);
		$quota = max($groupQuotas);
		return ($quota == 0) ? 'default' : Util::humanFileSize($quota);
	}

	public function getQuotaList(): array {
		$appKeys = $this->appConfig->getKeys('group_default_quota');
		$quotas = [];
		foreach ($appKeys as $appKeyValue) {
			$appKeyValueArray = explode('_', $appKeyValue, 3);

			if (sizeof($appKeyValueArray) != 3) {
				continue;
			}
			if ($appKeyValueArray[0] != 'default' && $appKeyValueArray[1] != 'quota') {
				continue;
			}

			$groupId = $appKeyValueArray[2];

			$quotas[$groupId] = $this->getGroupDefault($groupId);
		}
		return $quotas;
	}
}
