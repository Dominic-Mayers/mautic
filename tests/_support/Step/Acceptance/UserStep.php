<?php

namespace Step\Acceptance;

use Facebook\WebDriver\WebDriverKeys;
use Page\Acceptance\UserPage;

class UserStep extends \AcceptanceTester
{
    /**
     * Fill out the contact form with the provided details.
     *
     * @param string $username
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $password
     */
    public function fillContactForm($username, $firstName, $lastName, $email, $password): void
    {
        $I = $this;
        // Wait for the first name field to be visible
        $I->waitForElementVisible(UserPage::$firstNameField, 10);
        // Fill in the form fields
        $I->fillField(UserPage::$usernameField, $username);
        $I->fillField(UserPage::$firstNameField, $firstName);
        $I->fillField(UserPage::$lastNameField, $lastName);
        $I->fillField(UserPage::$emailField, $email);
        $I->fillField(UserPage::$passwordField, $password);
        $I->fillField(UserPage::$passwordConfirmField, $password);
    }
}
