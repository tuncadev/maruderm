<?php
/**
 * Canonical WooCommerce login/register template.
 *
 * @package Maruderm
 * @version 9.9.0
 */

defined('ABSPATH') || exit;

(new \Maruderm\Auth\LoginRenderer())->render();
