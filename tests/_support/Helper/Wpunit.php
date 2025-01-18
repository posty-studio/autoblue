<?php

declare(strict_types=1);

namespace Helper;

class Wpunit extends \Codeception\Module {
	public function _after(\Codeception\TestInterface $test) {
		// Remove all logs after each test.
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i',
				$wpdb->prefix . \Autoblue\Logging\DatabaseHandler::TABLE_NAME
			)
    );
	}
}
