<?php

use Facebook\WebDriver\WebDriverKeys;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Page\Acceptance\UserPage;
use Step\Acceptance\UserStep;
use PHPUnit\Framework\Assert;

class UserManagementCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login('admin', 'Maut1cR0cks!');
    }

    public function createUserFromForm(
        AcceptanceTester $I,
        UserStep $user,
    ): void {
        $I->amOnPage(UserPage::$URL);
        // Click on "+New" button
        $I->waitForElementClickable(UserPage::$newUserButton, 30);
        $I->click(UserPage::$newUserButton);
        $I->waitForText('New User', 30);

        // Fill out the contact form
        $user->fillContactForm('username', 'FirstName', 'LastName', 'email@example.com', 'Maut1cR0cks!');

        // Scroll back to the top of the page
        $I->executeJS('window.scrollTo(0, 0);');

        // Click the save and close button
        $I->waitForElementClickable(UserPage::$saveAndCloseButton, 30);
        $I->click(UserPage::$saveAndCloseButton);

        // Confirm the user is created
        $I->waitForElementVisible('table#userTable tbody tr:first-child td:nth-child(3) a', 30);
        $I->see('LastName, FirstName', 'table#userTable tbody tr:first-child td:nth-child(3) a');

        // Check the database for the created contact
        $I->seeInDatabase('test_users', ['first_name' => 'FirstName', 'email' => 'email@example.com']);
    }
}
