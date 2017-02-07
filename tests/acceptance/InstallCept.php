<?php 
$I = new AcceptanceTester($scenario);

if(file_exists('conf/_STATUSFILE')){
    $I->deleteFile('conf/_STATUSFILE');
}

$I->wantTo('ensure that installation works');

$I->amOnPage('/setup/index.php');

$I->see("Welcome to Ximdex CMS");

$I->click("Start Installation");

$I->see("System Requirements");

$I->click("Start Installation");

$I->fillField("dbhost", "db");
$I->fillField("dbuser", "ximdex");
$I->fillField("dbpass", "ximdex");
$I->fillField("dbname", "ximdex");

$I->click("Check");

$I->wait(2);

$I->click("Continue: Create tables");

$I->wait(10);

$I->see("Tables and default data created");

$I->click("Continue: Modules");

$I->wait(5);

$I->see("Install Modules");

$I->click("Next: Settings");

$I->see("Set the password for the user Ximdex (Administrator)");

$I->fillField("password", "ximdex");
$I->fillField("repeatpassword", "ximdex");

$I->click("Check");

$I->click("Enjoy Ximdex !");

$I->see("User");
$I->see("Password");

$I->fillField("user", "ximdex");
$I->fillField("password", "ximdex");

$I->click("Sign in");

$I->wait(5);

$I->see("Welcome to Ximdex CMS, ximdex!");

