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
 * Sends a module mail with PrestaShop's Mail::send(), from the mail's shop and in its language, with the module's
 * template folder (a theme can override it in themes/<theme>/modules/<module>/mails/; the core looks there itself).
 * Never throws: a failure is logged in PrestaShop's log (object type = the module name) and gives false, so a hook or
 * a cron run goes on.
 */
final class ModuleMailer
{
    /**
     * Sends the mail; true when PrestaShop accepted it (with "never send emails" set, PrestaShop also answers true).
     */
    public function send(ModuleMail $mail): bool
    {
        try {
            $sent = \Mail::send(
                $mail->langId,
                $mail->template,
                $mail->subject,
                $mail->vars,
                $mail->toEmail,
                $mail->toName === '' ? null : $mail->toName,
                null,
                null,
                null,
                null,
                $mail->templateDir,
                false,
                $mail->shopId
            );
        } catch (\Throwable $e) {
            return $this->fail($mail, $e->getMessage());
        }

        return (bool) $sent || $this->fail($mail, 'PrestaShop did not send it, see the email log');
    }

    /**
     * Logs the failure, without the address (the log is not the place for personal data).
     *
     * @return bool always false
     */
    private function fail(ModuleMail $mail, string $reason): bool
    {
        \PrestaShopLogger::addLog(
            sprintf('[%s] Sending mail "%s" from shop %d failed: %s', $mail->moduleName, $mail->template, $mail->shopId, $reason),
            \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
            null,
            $mail->moduleName,
            0,
            true
        );

        return false;
    }
}
