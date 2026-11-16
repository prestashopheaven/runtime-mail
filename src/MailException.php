<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author    prestahaven.com
 * @copyright Since 2026 George van Engers
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace PrestaShopHeaven\Runtime\Mail;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * A mail value is not valid (an address, a template name, a variable name, a subject with a line break, ...). The
 * message is English and meant for the log.
 */
final class MailException extends \InvalidArgumentException
{
}
