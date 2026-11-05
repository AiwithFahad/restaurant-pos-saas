<?php
// Role sets for RestoPro tenant pages.
define('ROLE_ALL', ['owner','branch_manager','cashier','waiter','kitchen','inventory_manager','accountant']);
define('ROLE_MANAGE', ['owner','branch_manager']);
define('ROLE_POS', ['owner','branch_manager','cashier','waiter']);
define('ROLE_KITCHEN', ['owner','branch_manager','kitchen','cashier']);
define('ROLE_STOCK', ['owner','branch_manager','inventory_manager','accountant']);
define('ROLE_MONEY', ['owner','branch_manager','accountant']);
