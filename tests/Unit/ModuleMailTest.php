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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrestaShopHeaven\Runtime\Mail\MailException;
use PrestaShopHeaven\Runtime\Mail\ModuleMail;

/**
 * ModuleMail keeps checked values (variables as text, the folder with one trailing slash) and refuses every value that
 * could break a mail or its headers.
 */
final class ModuleMailTest extends TestCase
{
    public function testItKeepsTheValues(): void
    {
        $mail = $this->mail();

        self::assertSame('heavenfoo', $mail->moduleName);
        self::assertSame('cart_reminder', $mail->template);
        self::assertSame('Your cart is waiting', $mail->subject);
        self::assertSame(['{firstname}' => 'Anna', '{count}' => '3', '{total}' => '12.5'], $mail->vars);
        self::assertSame('anna@example.com', $mail->toEmail);
        self::assertSame('Anna Smith', $mail->toName);
        self::assertSame(2, $mail->langId);
        self::assertSame(3, $mail->shopId);
        self::assertSame('/var/www/modules/heavenfoo/mails/', $mail->templateDir);
    }

    public function testTheFolderGetsOneSlashAndAnEmptyNameIsAllowed(): void
    {
        self::assertSame('C:\mails/', $this->mail(['templateDir' => 'C:\mails\\'])->templateDir);
        self::assertSame('', $this->mail(['toName' => ''])->toName);
        self::assertSame([], $this->mail(['vars' => []])->vars);
    }

    public function testItIsImmutable(): void
    {
        foreach (['moduleName', 'template', 'subject', 'vars', 'toEmail', 'toName', 'langId', 'shopId', 'templateDir'] as $property) {
            self::assertTrue((new \ReflectionProperty(ModuleMail::class, $property))->isReadOnly(), $property);
        }
    }

    /**
     * Values that are refused.
     *
     * @return iterable<string, array{0: array<string, mixed>}>
     */
    public static function wrongValues(): iterable
    {
        yield 'module name with a capital' => [['moduleName' => 'HeavenFoo']];
        yield 'empty module name' => [['moduleName' => '']];
        yield 'template with a slash' => [['template' => '../order_conf']];
        yield 'template with a dot' => [['template' => 'cart.html']];
        yield 'empty subject' => [['subject' => '  ']];
        yield 'subject with a line break' => [['subject' => "Hi\r\nBcc: x@example.com"]];
        yield 'subject with NUL' => [['subject' => "Hi\0"]];
        yield 'subject too long' => [['subject' => str_repeat('a', ModuleMail::MAX_TEXT_LENGTH + 1)]];
        yield 'name with a line break' => [['toName' => "Anna\nSmith"]];
        yield 'not an address' => [['toEmail' => 'anna']];
        yield 'two addresses' => [['toEmail' => 'a@example.com, b@example.com']];
        yield 'language 0' => [['langId' => 0]];
        yield 'negative shop' => [['shopId' => -1]];
        yield 'no folder' => [['templateDir' => ' ']];
        yield 'variable without braces' => [['vars' => ['firstname' => 'Anna']]];
        yield 'variable with a capital' => [['vars' => ['{Firstname}' => 'Anna']]];
        yield 'variable with a number key' => [['vars' => [0 => 'Anna']]];
        yield 'variable that is an array' => [['vars' => ['{list}' => ['a']]]];
        yield 'variable that is null' => [['vars' => ['{name}' => null]]];
    }

    /**
     * @param array<string, mixed> $change
     */
    #[DataProvider('wrongValues')]
    public function testAWrongValueIsRefused(array $change): void
    {
        $this->expectException(MailException::class);

        $this->mail($change);
    }

    public function testASubjectOfTheMaximumLengthIsKept(): void
    {
        $subject = str_repeat('é', ModuleMail::MAX_TEXT_LENGTH);

        self::assertSame($subject, $this->mail(['subject' => $subject])->subject);
    }

    /**
     * A valid mail with some values changed.
     *
     * @param array<string, mixed> $change
     */
    private function mail(array $change = []): ModuleMail
    {
        $values = array_merge([
            'moduleName' => 'heavenfoo',
            'template' => 'cart_reminder',
            'subject' => 'Your cart is waiting',
            'vars' => ['{firstname}' => 'Anna', '{count}' => 3, '{total}' => 12.5],
            'toEmail' => 'anna@example.com',
            'toName' => 'Anna Smith',
            'langId' => 2,
            'shopId' => 3,
            'templateDir' => '/var/www/modules/heavenfoo/mails//',
        ], $change);

        return new ModuleMail(...$values);
    }
}
