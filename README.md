# prestashopheaven/runtime-mail

Module mails in the shop's mail theme: layouts for PrestaShop's mail theme generator (actionListMailThemes), and sending a module template for one shop and language with every failure logged.

## Use when

A module sends mails that should look like the shop's own mails, or sends any module template per shop and language without breaking the request on a mail error.

## Install

```
composer require prestashopheaven/runtime-mail
```

## Usage

Sending a module template (`mails/<iso>/<template>.html|txt`) from a shop, in a language:

```php
use PrestaShopHeaven\Runtime\Mail\MailVars;
use PrestaShopHeaven\Runtime\Mail\ModuleMail;
use PrestaShopHeaven\Runtime\Mail\ModuleMailer;

$sent = (new ModuleMailer())->send(new ModuleMail(
    'heavenfoo', 'cart_reminder', $subject,
    ['{firstname}' => MailVars::html($customer->firstname), '{cart_url}' => $url],
    $customer->email, $customer->firstname . ' ' . $customer->lastname,
    (int) $customer->id_lang, (int) $cart->id_shop, $this->getLocalPath() . 'mails/'
)); // false (and a PrestaShop log line) on any failure; never throws
```

The shop's mail styling: hook `actionListMailThemes` and register the module's layouts. Put
`mails/layouts/<template>_modern.html.twig` (and `_classic`) in the module, extending
`@MailThemes/modern/components/layout.html.twig` with a `content` block; texts with
`{{ 'Text'|trans({}, 'Modules.Heavenfoo.Emails', locale) }}`.

```php
public function hookActionListMailThemes(array $params): void
{
    MailThemeLayouts::register($params['mailThemes'] ?? [], $this->name, $this->getLocalPath(), ['cart_reminder']);
}
```

"Design > Email theme > Generate emails" then writes `modules/<module>/mails/<iso>/cart_reminder.html|txt` in the shop's
theme and languages. Ship generated files for every language as well, so the mail works before the merchant generates.

---

Read-only split of [`prestashopheaven/mono-runtime-communication`](https://github.com/prestashopheaven/mono-runtime-communication): changes,
issues and pull requests go there. MIT License.
