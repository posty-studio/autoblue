<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

class ActivationCest {

	public function test_it_deactivates_activates_correctly( EndToEndTester $I ): void {
		$I->loginAsAdmin();
		$I->amOnPluginsPage();

		$I->seePluginActivated( 'autoblue' );

		$I->deactivatePlugin( 'autoblue' );

		$I->wait( 1 );

		$I->seePluginDeactivated( 'autoblue' );

		$I->activatePlugin( 'autoblue' );

		$I->seePluginActivated( 'autoblue' );
	}
}
