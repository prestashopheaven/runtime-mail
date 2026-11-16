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

use PrestaShop\PrestaShop\Core\MailTemplate\Layout\Layout;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Puts a module's mails into PrestaShop's mail themes, so they look like the shop's own mails. Call it from the hook
 * actionListMailThemes with $params['mailThemes']. For every theme (modern, classic or a custom one) and every layout
 * name, the file <module>/mails/layouts/<layout>_<theme>.html.twig (and an optional .txt.twig; without it PrestaShop
 * makes the text version from the HTML) is added as a layout of the module. "Design > Email theme > Generate emails"
 * then writes modules/<module>/mails/<iso>/<layout>.html|txt in the shop's theme and languages. A theme without a file
 * for the layout is left alone. The layouts are given to PrestaShop as "@Modules/<module>/mails/layouts/..." (its Twig
 * loads no absolute paths), so the module folder must be named after the module, as PrestaShop requires anyway.
 * A layout file extends the theme's layout, e.g. "@MailThemes/modern/components/layout.html.twig".
 */
final class MailThemeLayouts
{
    /**
     * Adds the module's layouts to the themes (an existing layout of the module with the same name is replaced).
     *
     * @param iterable<mixed> $themes the mail themes of the hook (ThemeCollectionInterface); other values are skipped
     * @param string $moduleName the technical name of the module
     * @param string $moduleDir the module folder ($module->getLocalPath())
     * @param array<mixed> $layoutNames the template names, e.g. ['cart_reminder']
     *
     * @throws MailException when a name is not valid
     */
    public static function register(iterable $themes, string $moduleName, string $moduleDir, array $layoutNames): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/D', $moduleName)) {
            throw new MailException(sprintf('"%s" is not a module name.', $moduleName));
        }
        $names = [];
        foreach ($layoutNames as $layoutName) {
            if (!is_string($layoutName) || !preg_match('/^[a-z0-9_-]+$/D', $layoutName)) {
                throw new MailException('A mail layout name has only a-z, 0-9, "_" and "-".');
            }
            $names[] = $layoutName;
        }
        $folder = rtrim($moduleDir, '/\\') . '/mails/layouts/';
        foreach ($themes as $theme) {
            if (!$theme instanceof ThemeInterface || !preg_match('/^[A-Za-z0-9_-]+$/D', (string) $theme->getName())) {
                continue;
            }
            foreach ($names as $layoutName) {
                $html = $folder . $layoutName . '_' . $theme->getName() . '.html.twig';
                if (!is_file($html)) {
                    continue;
                }
                $txt = $folder . $layoutName . '_' . $theme->getName() . '.txt.twig';
                // PrestaShop's Twig only loads namespaced paths: "@Modules" is the modules folder (8.1 and 9).
                $twig = '@Modules/' . $moduleName . '/mails/layouts/' . $layoutName . '_' . $theme->getName();
                $layout = new Layout($layoutName, $twig . '.html.twig', is_file($txt) ? $twig . '.txt.twig' : '', $moduleName);
                $layouts = $theme->getLayouts();
                $existing = $layouts->getLayout($layoutName, $moduleName);
                if ($existing !== null) {
                    $layouts->replace($existing, $layout);
                } else {
                    $layouts->add($layout);
                }
            }
        }
    }
}
