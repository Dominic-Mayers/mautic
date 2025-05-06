<?php

namespace Page\Acceptance;

class UserPage
{
    public static $URL = '/s/users';

    // Form fields
    public static $firstNameField        = '#user_firstName';
    public static $lastNameField         = '#user_lastName';
    public static $roleOption            = '#user_role_chosen > div > ul > li';
    public static $positionField         = '#user_position';
    public static $signature             = '#user_signature';
    public static $usernameField         = '#user_username';
    public static $emailField            = '#user_email';
    public static $passwordField         = '#user_plainPassword_password';
    public static $passwordConfirmField  = '#user_plainPassword_confirm';
        
    // Form buttons (+New, Edit)
    public static $cancelButton       = '#user_buttons_cancel_toolbar';
    public static $saveAndCloseButton = '#user_buttons_save_toolbar';

    // Delete User alert
    public static $ConfirmDelete = 'button.btn.btn-danger';

    // User Page
    public static $newUserButton   = '#new';

    // Search bar
    public static $searchBar   = '#list-search';
    public static $clearSearch = '#btn-filter';

    // Clear all selection button
    public static $clearAllUsersSelection = '#form-cancel';

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');.
     */
    public static function route($param)
    {
        return static::$URL.$param;
    }

    /**
     * @var \AcceptanceTester;
     */
    protected $acceptanceTester;

    public function __construct(\AcceptanceTester $I)
    {
        $this->acceptanceTester = $I;
    }
}
