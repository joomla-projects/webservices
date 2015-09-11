<?php
/**
 * @package     Joomla
 * @subpackage  webservices
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

class AdministratorContacts1Cest
{
	/**
	 * Set up the contact stub
	 */
	public function __construct()
	{
		$this->name = 'contact' . rand(0,1000);
		$this->id = 0;
	}

	public function WebserviceIsAvailable(ApiTester $I)
	{
		$I->wantTo("check the availability of the webservice");
		$I->amHttpAuthenticated('admin', 'admin');
		$I->sendGET('index.php'
			. '?option=contact'
			. '&api=Hal'
			. '&webserviceClient=administrator'
			. '&webserviceVersion=1.0.0'
		);

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJson();
		$I->seeHttpHeader('Webservice name', 'contact');
		$I->seeHttpHeader('Webservice version', '1.0.0');
	}

	/**
	 * @depends WebserviceIsAvailable
	 */
	public function create(ApiTester $I)
	{
		$I->wantTo('POST a new Contact in com_contacts');
		$I->amHttpAuthenticated('admin', 'admin');
		$I->sendPOST('index.php'
			. '?option=contact'
			. '&api=Hal'
			. '&webserviceClient=administrator'
			. '&webserviceVersion=1.0.0'
			. "&name=$this->name"
			// Uncategorised default category
			. '&catid=4'
		);

		$I->seeResponseCodeIs(201);
		$I->seeResponseIsJson();

		$links = $I->grabDataFromResponseByJsonPath('$._links');
		$I->sendGET($links[0]['contact:self']['href']);
		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJson();
		$ids = $I->grabDataFromResponseByJsonPath('$.id');
		$this->id = $ids[0];
		$I->comment("The id of the new created contact with name '$this->name' is: $this->id");
	}

	/**
	 * @depends create
	 */
	public function readItem(ApiTester $I)
	{
		$I->wantTo("GET an existing Contact");
		$I->amHttpAuthenticated('admin', 'admin');
		$I->sendGET('index.php'
			. '?option=contact'
			. '&api=Hal'
			. '&webserviceClient=administrator'
			. '&webserviceVersion=1.0.0'
			. "&id=$this->id"
		);

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJson();
		$I->seeResponseContains('"name":"'. $this->name.'"');
	}

	/**
	 * @depends readItem
	 */
	public function update(ApiTester $I)
	{
		$I->wantTo('Update a new Contact in com_contacts using PUT');
		$I->amHttpAuthenticated('admin', 'admin');

		$this->name = 'new_' . $this->name;
		$I->sendPUT('index.php'
			. '?option=contact'
			. '&api=Hal'
			. '&webserviceClient=administrator'
			. '&webserviceVersion=1.0.0'
			. "&id=$this->id"
			. "&name=$this->name"
			// Uncategorised default category
			. '&catid=4'
		);

		$I->seeResponseCodeIs(200);

		$I->sendGET('index.php'
			. '?option=contact'
			. '&api=Hal'
			. '&webserviceClient=administrator'
			. '&webserviceVersion=1.0.0'
			. "&id=$this->id"
		);

		$I->seeResponseCodeIs(200);
		$I->seeResponseIsJson();
		$I->seeResponseContains('"name":"' . $this->name . '"');
		$I->comment("The contact name has been modified to: $this->name");
	}

	/**
	 * @todo delete depends on https://github.com/joomla-projects/webservices/issues/16
	 */
}