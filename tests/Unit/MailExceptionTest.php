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
use PrestaShopHeaven\Runtime\Mail\MailException;

/**
 * MailException is an InvalidArgumentException with its message.
 */
final class MailExceptionTest extends TestCase
{
    public function testItIsAnInvalidArgument(): void
    {
        $exception = new MailException('bad address');

        self::assertInstanceOf(\InvalidArgumentException::class, $exception);
        self::assertSame('bad address', $exception->getMessage());
    }
}
