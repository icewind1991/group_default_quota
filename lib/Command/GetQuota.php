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
use OCP\User\GetQuotaEvent;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetQuota extends Base {
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
			->setName('group_default_quota:get')
			->setDescription('Get the default quota for a group')
			->addArgument('name', InputArgument::REQUIRED, 'Name of the group');
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if (!class_exists(GetQuotaEvent::class)) {
			$output->writeln("<error>App not supported on your Nextcloud version, please update to the latest maintenance release.</error>");
			return -1;
		}

		$groupId = $input->getArgument('name');
		$group = $this->groupManager->get($groupId);
		if (!$group) {
			$output->writeln("<error>Group not found: $groupId</error>");
			return -1;
		}
		$quota = $this->quotaManager->getGroupDefault($groupId);
		if ($quota === 0) {
			$output = 'default';
		}
		$output->writeln($quota);

		return 0;
	}
}
