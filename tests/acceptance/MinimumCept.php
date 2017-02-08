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

$I->wantTo('ensure that publish works');

$I->wait(2);


$I->see("Hello ximdex, first time here?", "#tourcontrols");
$I->click("#canceltour");


$I->doubleClick("//span[contains(text(),'Picasso')]", "#angular-tree");

$I->see("Picasso_Server", "#angular-tree");

$I->doubleClick("//span[contains(text(),'Picasso_Server')]", "#angular-tree");
$I->see("documents", "#angular-tree");

$I->doubleClick("//span[contains(text(),'documents')]", "#angular-tree");
$I->see("picasso", "#angular-tree");

$I->doubleClick("//span[contains(text(),'picasso')]", "#angular-tree");
$I->see("picasso-iden", "#angular-tree");

// Open picasso-iden menu
$I->click("//*[@id=\"angular-tree\"]/div[1]/div[2]/div/div[1]/div[2]/xim-tree/div/div[2]/ul/li/tree-node/span/ul/li/span/ul/li[4]/span/ul/li[3]/span/ul/li[1]/span/ul/li/span/div/span[2]");

$I->see("Publish", "body > div.xim-actions-menu.destroy-on-click.noselect.xim-actions-menu-list");

$I->click("body > div.xim-actions-menu.destroy-on-click.noselect.xim-actions-menu-list > div.button-container-list.icon.workflow_forward");

$I->wait(1);

$I->click("Publish", "#angular-content");

$I->wait(1);

$I->see("State has been successfully changed", "#angular-content");

$I->wait(60);

$I->seeFileFound('default.css','data/previos/css');
$I->seeFileFound('picasso-iden-idhtml.html','data/previos');

$I->amOnPage("/data/previos/picasso-iden-idhtml.html");

$I->see("Picasso", ".header");
$I->see("Cubism", ".header");

