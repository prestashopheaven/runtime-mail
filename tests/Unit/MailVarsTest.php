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

namespace PrestaShopHeaven\Runtime\Mail\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PrestaShopHeaven\Runtime\Mail\MailVars;

/**
 * MailVars::html() makes customer and product texts safe for the HTML version of a mail.
 */
final class MailVarsTest extends TestCase
{
    public function testItEscapesHtmlAndQuotes(): void
    {
        self::assertSame('&lt;img src=x onerror=&quot;a()&quot;&gt; &amp; O&#039;Brien', MailVars::html('<img src=x onerror="a()"> & O\'Brien'));
    }

    public function testItKeepsPlainTextAndAccents(): void
    {
        self::assertSame('Crème brûlée', MailVars::html('Crème brûlée'));
        self::assertSame('', MailVars::html(''));
    }

    public function testInvalidUtf8IsReplacedNotDropped(): void
    {
        self::assertSame("a\u{FFFD}b", MailVars::html("a\xC3b"));
    }
}
