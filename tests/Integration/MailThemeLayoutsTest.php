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
use PrestaShop\PrestaShop\Core\MailTemplate\Layout\LayoutCollection;
use PrestaShop\PrestaShop\Core\MailTemplate\Theme;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeInterface;
use PrestaShopHeaven\Runtime\Mail\MailException;
use PrestaShopHeaven\Runtime\Mail\MailThemeLayouts;

/**
 * MailThemeLayouts in a real shop, with the fixture module (HEAVEN_FIXTURE_MODULE), whose hook actionListMailThemes
 * registers mails/layouts/fixture_mail_<theme>.html.twig: PrestaShop's theme catalog lists the layout in modern and
 * classic, and PrestaShop's generator renders it in the theme's styling into the module's mail folder.
 */
final class MailThemeLayoutsTest extends TestCase
{
    public function testTheCatalogOfPrestaShopListsTheLayoutInEveryTheme(): void
    {
        foreach (['modern', 'classic'] as $themeName) {
            $layout = $this->theme($themeName)->getLayouts()->getLayout('fixture_mail', HEAVEN_FIXTURE_MODULE);

            self::assertNotNull($layout, "layout in $themeName");
            self::assertSame('@Modules/' . HEAVEN_FIXTURE_MODULE . '/mails/layouts/fixture_mail_' . $themeName . '.html.twig', $layout->getHtmlPath());
            self::assertSame('', $layout->getTxtPath(), 'no txt layout: PrestaShop makes the text from the HTML');
            self::assertSame(HEAVEN_FIXTURE_MODULE, $layout->getModuleName());
        }
    }

    public function testTheGeneratorOfPrestaShopRendersTheLayoutInTheThemesStyling(): void
    {
        $container = $this->container();
        $languageId = (int) \Configuration::get('PS_LANG_DEFAULT');
        $iso = (string) \Language::getIsoById($languageId);
        $language = $container->get('prestashop.core.admin.lang.repository')->getOneByLocaleOrIsoCode($iso);
        $folder = sys_get_temp_dir() . '/heavenmonocommunication-' . bin2hex(random_bytes(4));
        mkdir($folder . '/core', 0777, true);
        mkdir($folder . '/modules', 0777, true);
        try {
            $container->get('prestashop.core.mail_template.generator')
                ->generateTemplates($this->theme('modern'), $language, $folder . '/core', $folder . '/modules', true);

            $html = (string) file_get_contents($folder . '/modules/' . HEAVEN_FIXTURE_MODULE . '/mails/' . $iso . '/fixture_mail.html');
            $txt = (string) file_get_contents($folder . '/modules/' . HEAVEN_FIXTURE_MODULE . '/mails/' . $iso . '/fixture_mail.txt');
            self::assertStringContainsString('HEAVENMONOCOMMUNICATION-MODERN', $html);
            self::assertStringContainsString('{shop_logo}', $html, 'the header of the modern theme');
            self::assertStringContainsString('{firstname}', $html, 'the variables stay for Mail::send()');
            self::assertStringContainsString('HEAVENMONOCOMMUNICATION-MODERN', $txt);
        } finally {
            $this->removeTree($folder);
        }
    }

    public function testAThemeWithoutAFileKeepsItsLayoutsAndOtherValuesAreSkipped(): void
    {
        $custom = new Theme('custom');
        $custom->setLayouts(new LayoutCollection());

        MailThemeLayouts::register([$custom, 'not a theme', null], HEAVEN_FIXTURE_MODULE, $this->moduleDir(), ['fixture_mail']);

        self::assertCount(0, $custom->getLayouts());
    }

    public function testRegisteringTwiceReplacesTheLayout(): void
    {
        $modern = new Theme('modern');
        $modern->setLayouts(new LayoutCollection());

        MailThemeLayouts::register([$modern], HEAVEN_FIXTURE_MODULE, $this->moduleDir(), ['fixture_mail']);
        MailThemeLayouts::register([$modern], HEAVEN_FIXTURE_MODULE, $this->moduleDir() . '/', ['fixture_mail']);

        self::assertCount(1, $modern->getLayouts());
    }

    public function testAWrongModuleNameIsRefused(): void
    {
        $this->expectException(MailException::class);

        MailThemeLayouts::register([], 'Heaven Foo', $this->moduleDir(), ['fixture_mail']);
    }

    public function testAWrongLayoutNameIsRefused(): void
    {
        $this->expectException(MailException::class);

        MailThemeLayouts::register([], HEAVEN_FIXTURE_MODULE, $this->moduleDir(), ['../order_conf']);
    }

    public function testALayoutNameThatIsNotTextIsRefused(): void
    {
        $this->expectException(MailException::class);

        MailThemeLayouts::register([], HEAVEN_FIXTURE_MODULE, $this->moduleDir(), [12]);
    }

    /**
     * A mail theme of PrestaShop's catalog (it runs the hook actionListMailThemes).
     */
    private function theme(string $name): ThemeInterface
    {
        $container = $this->container();
        $catalog = $container->has('PrestaShop\PrestaShop\Core\MailTemplate\FolderThemeCatalog')
            ? $container->get('PrestaShop\PrestaShop\Core\MailTemplate\FolderThemeCatalog')
            : $container->get('prestashop.core.mail_template.theme_catalog');

        return $catalog->getByName($name);
    }

    /**
     * The container of the kernel the bootstrap booted.
     */
    private function container(): \Symfony\Component\DependencyInjection\ContainerInterface
    {
        global $kernel;
        self::assertIsObject($kernel);

        return $kernel->getContainer();
    }

    /**
     * The folder of the fixture module.
     */
    private function moduleDir(): string
    {
        return _PS_MODULE_DIR_ . HEAVEN_FIXTURE_MODULE;
    }

    /**
     * Removes a folder with everything in it.
     */
    private function removeTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }
}
