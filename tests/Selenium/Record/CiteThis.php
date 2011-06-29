<?php
/**
 * Integration testing of CiteThis function in Record module.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Tests
 * @author   Preetha Rao <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */

/**
 * Set up test environment.
 */
require_once dirname(__FILE__) . '/../../web/prepend.inc.php';

/**
 * Load base class.
 */
require_once dirname(__FILE__) . '/../lib/SeleniumTestCase.php';

/**
 * Load Selenium driver code.
 */
require_once 'Testing/Selenium.php';

/**
 * Integration testing of CiteThis function in Record module.
 *
 * @category VuFind
 * @package  Tests
 * @author   Preetha Rao <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/unit_tests Wiki
 */
class CiteThis extends SeleniumTestCase
{
    /**
     * Test the CiteThis lightbox.
     *
     * @return void
     * @access public
     */
    public function testCiteThis()
    {
        //print "\n". __METHOD__ . "\n";
        $this->selenium->open($this->baseUrl);
        $this->assertRegExp("/Search Home/", $this->selenium->getTitle());
        $this->selenium->click("link=A - General Works");
        $this->selenium->waitForPageToLoad("10000");
        $this->selenium->click(
            "link=Journal of rational emotive therapy : " .
            "the journal of the Institute for Rational-Emotive Therapy."
        );
        $this->selenium->waitForPageToLoad("10000");
        $this->selenium->click("link=Cite this");
        //echo "\nAsserting if Lightbox was opened\n";
        $this->assertTrue($this->selenium->isElementPresent("id=lightbox"));
    }
}
?>