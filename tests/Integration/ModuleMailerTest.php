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

namespace PrestaShopHeaven\Runtime\Mail\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PrestaShopHeaven\Runtime\Mail\ModuleMail;
use PrestaShopHeaven\Runtime\Mail\ModuleMailer;

/**
 * ModuleMailer in a real shop, with the fixture module's template mails/en/fixture_mail.html|txt: "never send" is a
 * success, an SMTP server that refuses gives false with a log line, a module that throws inside Mail::send() gives false
 * with a log line, and (when a Mailpit container is on the shop's network as "mailpit") the mail really arrives with its
 * variables and the shop's name.
 */
final class ModuleMailerTest extends TestCase
{
    /** The mail settings this test changes, with their values before. */
    private const KEYS = ['PS_MAIL_METHOD', 'PS_MAIL_SERVER', 'PS_MAIL_SMTP_PORT', 'PS_MAIL_SMTP_ENCRYPTION', 'PS_MAIL_USER', 'PS_MAIL_PASSWD'];

    /** @var array<string, string> */
    private array $saved = [];

    /**
     * Remembers the mail settings.
     */
    protected function setUp(): void
    {
        foreach (self::KEYS as $key) {
            $this->saved[$key] = (string) \Configuration::getGlobalValue($key);
        }
    }

    /**
     * Puts the mail settings back.
     */
    protected function tearDown(): void
    {
        foreach ($this->saved as $key => $value) {
            \Configuration::updateGlobalValue($key, $value);
        }
    }

    public function testNeverSendIsASuccess(): void
    {
        \Configuration::updateGlobalValue('PS_MAIL_METHOD', \Mail::METHOD_DISABLE);

        self::assertTrue((new ModuleMailer())->send($this->mail('Fixture mail')));
    }

    public function testARefusingSmtpServerGivesFalseAndALogLine(): void
    {
        $this->smtp('127.0.0.1', 1);
        $before = $this->lastLogId();

        self::assertFalse((new ModuleMailer())->send($this->mail('Fixture mail')));
        self::assertStringContainsString('Sending mail "fixture_mail" from shop', $this->logSince($before));
    }

    public function testAModuleThatThrowsInsideMailSendGivesFalseAndALogLine(): void
    {
        $before = $this->lastLogId();

        self::assertFalse((new ModuleMailer())->send($this->mail('HEAVENMONOCOMMUNICATION-THROW')));
        $log = $this->logSince($before);
        self::assertStringContainsString('The fixture breaks this mail.', $log);
        self::assertStringNotContainsString('fixture@example.com', $log, 'no address in the log');
    }

    public function testTheMailArrivesWithItsVariables(): void
    {
        if (gethostbyname('mailpit') === 'mailpit') {
            self::markTestSkipped('No Mailpit container named "mailpit" on the shop network (see memory mail-testing-mailpit).');
        }
        $this->smtp('mailpit', 1025);
        $subject = 'Fixture mail ' . bin2hex(random_bytes(4));

        self::assertTrue((new ModuleMailer())->send($this->mail($subject)));

        $search = json_decode((string) \Tools::file_get_contents('http://mailpit:8025/api/v1/search?query=' . rawurlencode('subject:"' . $subject . '"')), true);
        self::assertIsArray($search);
        self::assertSame(1, $search['messages_count'] ?? null, 'one mail with the subject');
        $message = json_decode((string) \Tools::file_get_contents('http://mailpit:8025/api/v1/message/' . $search['messages'][0]['ID']), true);
        self::assertIsArray($message);
        self::assertSame('fixture@example.com', $message['To'][0]['Address'] ?? null);
        self::assertStringContainsString('Fixture mail for Anna &amp; Co from ' . htmlspecialchars((string) \Configuration::get('PS_SHOP_NAME'), ENT_QUOTES), (string) $message['HTML']);
        self::assertStringContainsString('HEAVENMONOCOMMUNICATION-SENT', (string) $message['Text']);
    }

    /**
     * Points the shop at an SMTP server without encryption (PS 8: '', PS 9: 'off').
     */
    private function smtp(string $server, int $port): void
    {
        \Configuration::updateGlobalValue('PS_MAIL_METHOD', \Mail::METHOD_SMTP);
        \Configuration::updateGlobalValue('PS_MAIL_SERVER', $server);
        \Configuration::updateGlobalValue('PS_MAIL_SMTP_PORT', $port);
        \Configuration::updateGlobalValue('PS_MAIL_SMTP_ENCRYPTION', version_compare(_PS_VERSION_, '9.0.0', '>=') ? 'off' : '');
        \Configuration::updateGlobalValue('PS_MAIL_USER', '');
        \Configuration::updateGlobalValue('PS_MAIL_PASSWD', '');
    }

    /**
     * The fixture's mail to a test address, with an HTML-escaped variable.
     */
    private function mail(string $subject): ModuleMail
    {
        $shopId = (int) \Configuration::get('PS_SHOP_DEFAULT');

        return new ModuleMail(
            HEAVEN_FIXTURE_MODULE,
            'fixture_mail',
            $subject,
            ['{firstname}' => htmlspecialchars('Anna & Co', ENT_QUOTES)],
            'fixture@example.com',
            'Anna',
            (int) \Language::getIdByIso('en'),
            $shopId,
            _PS_MODULE_DIR_ . HEAVEN_FIXTURE_MODULE . '/mails'
        );
    }

    /**
     * The id of the newest log line.
     */
    private function lastLogId(): int
    {
        return (int) \Db::getInstance()->getValue('SELECT MAX(id_log) FROM `' . _DB_PREFIX_ . 'log`');
    }

    /**
     * The messages of the log lines after an id, as one text.
     */
    private function logSince(int $id): string
    {
        $rows = \Db::getInstance()->executeS('SELECT message FROM `' . _DB_PREFIX_ . 'log` WHERE id_log > ' . $id);

        return implode("\n", array_column(is_array($rows) ? $rows : [], 'message'));
    }
}
