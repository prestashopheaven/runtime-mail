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
 * Escaping of the values a module puts into the HTML version of a mail: Mail::send() replaces {variables} as they
 * are, so a customer name or product name has to be escaped first (TPL-09).
 */
final class MailVars
{
    /**
     * The text safe for HTML (quotes too, UTF-8; invalid UTF-8 is replaced, not dropped).
     */
    public static function html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
