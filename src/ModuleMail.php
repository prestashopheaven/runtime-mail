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
 * One mail of a module: which template (mails/<iso>/<template>.html|txt in the template folder), its subject and
 * variables, to whom, in which language and from which shop (MS-10). Immutable; the constructor checks every value.
 */
final class ModuleMail
{
    /** The longest subject or name that is accepted. */
    public const MAX_TEXT_LENGTH = 255;

    /** @var array<string, string> the variables with their braces, e.g. ['{firstname}' => 'Anna'] */
    public readonly array $vars;

    /** The template folder, always ending with "/". */
    public readonly string $templateDir;

    /**
     * Checks and keeps the values.
     *
     * @param string $moduleName the technical name of the module (for the log)
     * @param string $template the template name: a-z, 0-9, "_" and "-"
     * @param string $subject the subject, one line
     * @param array<mixed> $vars the variables: keys with braces ("{name}", a-z, 0-9, "_"), values text or numbers
     * @param string $toEmail the address
     * @param string $toName the name of the addressee ('' = none), one line
     * @param int $langId the language of the template and the shop's texts
     * @param int $shopId the shop the mail comes from
     * @param string $templateDir the folder with <iso>/<template>.html, e.g. _PS_MODULE_DIR_ . 'heavenfoo/mails/'
     *
     * @throws MailException when a value is not valid
     */
    public function __construct(
        public readonly string $moduleName,
        public readonly string $template,
        public readonly string $subject,
        array $vars,
        public readonly string $toEmail,
        public readonly string $toName,
        public readonly int $langId,
        public readonly int $shopId,
        string $templateDir,
    ) {
        if (!preg_match('/^[a-z][a-z0-9_]*$/D', $moduleName)) {
            throw new MailException(sprintf('"%s" is not a module name.', $moduleName));
        }
        if (!preg_match('/^[a-z0-9_-]+$/D', $template)) {
            throw new MailException(sprintf('"%s" is not a mail template name (a-z, 0-9, "_" and "-").', $template));
        }
        self::assertLine($subject, 'subject', false);
        self::assertLine($toName, 'name', true);
        if (filter_var($toEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new MailException('The address of a mail is not an email address.');
        }
        if ($langId <= 0 || $shopId <= 0) {
            throw new MailException('A mail needs a language and a shop.');
        }
        if (trim($templateDir, " \t\n\r") === '') {
            throw new MailException('A mail needs the folder of its templates.');
        }
        $this->vars = self::checkedVars($vars);
        $this->templateDir = rtrim($templateDir, '/\\') . '/';
    }

    /**
     * Refuses an empty text (unless allowed), a line break or NUL, or a text that is too long.
     *
     * @throws MailException
     */
    private static function assertLine(string $text, string $what, bool $mayBeEmpty): void
    {
        if ((!$mayBeEmpty && trim($text, " \t") === '') || preg_match('/[\r\n\0]/', $text) || mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            throw new MailException(sprintf('The %s of a mail is one line of at most %d characters.', $what, self::MAX_TEXT_LENGTH));
        }
    }

    /**
     * The variables with their values as text.
     *
     * @param array<mixed> $vars
     *
     * @return array<string, string>
     *
     * @throws MailException when a key has no braces or a value is not text or a number
     */
    private static function checkedVars(array $vars): array
    {
        $checked = [];
        foreach ($vars as $key => $value) {
            if (!is_string($key) || !preg_match('/^\{[a-z0-9_]+\}$/D', $key)) {
                throw new MailException(sprintf('The mail variable "%s" needs braces and only a-z, 0-9 and "_".', (string) $key));
            }
            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                throw new MailException(sprintf('The mail variable %s is not text or a number.', $key));
            }
            $checked[$key] = (string) $value;
        }

        return $checked;
    }
}
