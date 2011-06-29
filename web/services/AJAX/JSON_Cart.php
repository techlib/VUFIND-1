<?php
/**
 * AJAX functions for the Cart module using JSON as output format.
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
 * @package  Controller_AJAX
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */

require_once 'JSON.php';
require_once 'sys/Cart_Model.php';

/**
 * AJAX functions for the Cart module using JSON as output format.
 *
 * @category VuFind
 * @package  Controller_AJAX
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class JSON_Cart extends JSON
{
    private $_cart;

    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();
        $this->_cart = Cart_Model::getInstance();
    }

    /**
     * Get the contents of the cart formatted as HTML
     * and send the output as a JSON object.
     *
     * @return void
     * @access public
     */
    function getCartAsHTML()
    {
        include_once 'services/Cart/Cart.php';
        $cartService = new Cart();
        $html = $cartService->getCartAsHTML();
        $this->output($html, JSON::STATUS_OK);
    }

    /**
     * Output the contents of the cart as a JSON object.
     *
     * @return void
     * @access public
     */
    function getItems()
    {
        $this->output($this->_cart->getItems(), JSON::STATUS_OK);
    }

    /**
     * Add an item to the cart, then output the contents
     * of the cart as a JSON object.
     *
     * @return void
     * @access public
     */
    function addItems()
    {
        $this->_cart->addItems($_REQUEST['id']);
        $this->getItems();
    }

    /**
     * Remove an item from the cart, then output the contents
     * of the cart as a JSON object.
     *
     * @return void
     * @access public
     */
    function removeItem()
    {
        $this->_cart->removeItem($_REQUEST['id']);
        $this->getItems();
    }

    /**
     * Empty the cart, then output the contents
     * of the cart as a JSON object.
     *
     * @return void
     * @access public
     */
    function emptyCart()
    {
        $this->_cart->emptyCart();
        $this->getItems();
    }
}
?>
