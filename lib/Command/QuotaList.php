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

namespace OCA\GroupDefaultQuota\Command;

use OC\Core\Command\Base;
use OCA\GroupDefaultQuota\QuotaManager;
use OCP\IGroupManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QuotaList extends Base {
	private $quotaManager;
	private $groupManager;

	public function __construct(
		IGroupManager $groupManager,
		QuotaManager $quotaManager
	) {
		parent::__construct();
		$this->groupManager = $groupManager;
		$this->quotaManager = $quotaManager;
	}

	protected function configure() {
		$this
			->setName('group_default_quota:list')
			->setDescription('Lists all configured quotas');
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$quotas = $this->quotaManager->getQuotaList();
		$output->writeln("Group : Quota");
		foreach ($quotas as $groupId => $quota) {
			$output->writeln($groupId . ": " . $quota);
		}
		return 0;
	}
}
