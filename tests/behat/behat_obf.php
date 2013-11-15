<?php

use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Context\Step\Given;

class behat_obf extends behat_base {

    /**
     * @Given /^I enter a valid request token to "([^"]*)"$/
     */
    public function iEnterAValidRequestTokenTo($fieldname) {
        $session = $this->getSession();
        $seleniumsession = new \Behat\Mink\Session(new \Behat\Mink\Driver\Selenium2Driver());
        $seleniumsession->start();

        $seleniumsession->visit('https://elvis.discendum.com/obf/');
        $seleniumsession->getPage()->fillField('username', 'test');
        $seleniumsession->getPage()->fillField('password', 'test');
        $seleniumsession->getPage()->pressButton('Login');
        $seleniumsession->getPage()->clickLink('Admin tools');
        $seleniumsession->wait(1000);
        $seleniumsession->getPage()->clickLink('Edit Organisation Details');
        $seleniumsession->wait(1000);
        $seleniumsession->getPage()->clickLink('More settings');
        $seleniumsession->wait(1000);
        $seleniumsession->getPage()->clickLink('Generate certificate signing request token');

        $seleniumsession->wait(5000, "$('#csrtoken-out textarea').length > 0");

        $textarea = $seleniumsession->getPage()->find('css', '#csrtoken-out textarea');
        $token = $textarea->getValue();
        $seleniumsession->stop();

        $session->getPage()->fillField($fieldname, $token);
    }

    /**
     * @Given /^the following badges exist:$/
     */
    public function theFollowingBadgesExist(TableNode $badgetable) {
        $steps = array();

        foreach ($badgetable->getHash() as $hash) {

            $name = $hash['Name'];
            $desc = $hash['Description'];
            $issuer = $hash['issuername'];
            $table = new TableNode(<<<TABLE
                | Name        | $name   |
                | Description | $desc   |
                | issuername  | $issuer |
TABLE
            );

            $steps[] = new Given('I expand "Site administration" node');
            $steps[] = new Given('I expand "Badges" node');
            $steps[] = new Given('I follow "Add a new badge"');
            $steps[] = new Given('I fill the moodle form with:', $table);
            $steps[] = new Given('I upload "' . $hash['image'] . '" file to "Image" filepicker');
            $steps[] = new Given('I press "Create badge"');
        }

        $steps[] = new Given('I expand "Open Badges" node');
        $steps[] = new Given('I follow "Settings"');
        $steps[] = new Given('I enter a valid request token to "obftoken"');
        $steps[] = new Given('I press "Save changes"');

        foreach ($badgetable->getHash() as $hash) {
            $steps[] = new Given('I check "' . $hash['Name'] . '"');
        }

        $steps[] = new Given('I check "Make exported badges visible by default"');
        $steps[] = new Given('I press "Continue"');

        return $steps;
    }

    /**
     * This step triggers cron like a user would do going to admin/cron.php.
     *
     * @Given /^I trigger cron$/
     */
    public function iTriggerCron() {
        $this->getSession()->visit($this->locate_path('/admin/cron.php'));
    }

    /**
     * @Given /^I go to badge list$/
     */
    public function iGoToBadgeList() {
        return array(
            new Given('I am on homepage'),
            new Given('I expand "Site administration" node'),
            new Given('I expand "Open Badges" node'),
            new Given('I follow "Badge list"')
        );
    }

    /**
     * @Given /^I set "([^"]*)" to be completed when assignment "([^"]*)" is completed$/
     */
    public function iSetToBeCompletedWhenAssignmentIsCompleted($course, $assignment) {
        return array(
            new Given('I am on homepage'),
            new Given('I follow "' . $course . '"'),
            new Given('I follow "Edit settings"'),
            new Given('I fill the moodle form with:',
                    new TableNode(<<<TABLE
                | Enable completion tracking | Yes |
TABLE
                    )),
            new Given('I press "Save changes"'),
            new Given('I turn editing mode on'),
            new Given('I add a "Assignment" to section "1" and I fill the form with:',
                    new TableNode(<<<TABLE
                | Assignment name                     | $assignment            |
                | Description                         | Assignment description |
                | assignsubmission_onlinetext_enabled | 1                      |
TABLE
                    )),
            new Given('I follow "Course completion"'),
            new Given('I select "2" from "id_overall_aggregation"'),
            new Given('I click on "Condition: Activity completion" "link"'),
            new Given('I check "Assign - ' . $assignment . '"'),
            new Given('I press "Save changes"'));
    }

    /**
     * @Given /^I mark "([^"]*)" of "([^"]*)" completed by "([^"]*)"$/
     */
    public function iMarkOfCompletedBy($assignment, $course, $user)
    {
        return array(
            new Given('I log in as "' . $user . '"'),
            new Given('I follow "' . $course . '"'),
            new Given('I press "Mark as complete: ' . $assignment . '"'),
            new Given('I wait "3" seconds'),
            new Given('I log out')
        );
    }
}
