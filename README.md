# Hazelchat for Statamic

Adds your [Hazelchat](https://hazelchat.co/?utm_source=statamic&utm_medium=addon&utm_campaign=readme) AI chat agent to a Statamic site from one pasted installation code. The agent answers visitors from the pages you approved, shows its sources and passes on their enquiries.

- Statamic 5 (PHP 8.2+) and Statamic 6 (PHP 8.3+).
- Needs a Hazelchat account (hosted service). The Free plan covers one website and stays free; paid plans add websites and answers ([pricing](https://hazelchat.co/pricing/?utm_source=statamic&utm_medium=addon&utm_campaign=readme-pricing)).
- Support: hello@hazelchat.co or [GitHub issues](https://github.com/hazelchat-co/statamic-hazelchat/issues).
- Hosted service: visitors' browsers load the chat from `app.hazelchat.co`, and Hazelchat processes their questions and any enquiry details they send ([privacy notice](https://hazelchat.co/privacy/), [terms](https://hazelchat.co/terms/)). The addon itself sends nothing.

## Install

1. In Hazelchat, add your website and copy its installation code ([create a free account](https://app.hazelchat.co/signup?utm_source=statamic&utm_medium=addon&utm_campaign=readme-signup)).
2. `composer require hazelchat/statamic-hazelchat`
3. Statamic 6: in the Control Panel, open **Tools → Addons → Hazelchat**, paste the code into **Installation code** and save. Statamic 5 (or if you keep settings in Git): add the key to `.env`:

   ```
   HAZELCHAT_INSTALLATION_CODE=<your 64-character key>
   ```

4. If the site uses static caching, run `php please static:clear`.
5. Back in Hazelchat, click **Publish and switch on**. Hazelchat finds the chat on your live page and verifies the website.

## What it does

- Every front-end HTML page gets one tag before `</body>`, including pages from Statamic's static cache (half and full):
  `<script id="hazelchat-statamic" data-agent="<key>" src="https://app.hazelchat.co/widget.js" defer></script>`
- **Installation code** takes the pasted code or just its 64-character key, and only the key is saved. Anything else (an API key, upper case, two keys, a commented-out code) is refused on the settings page and prints nothing from `.env`.
- **Wait for my consent manager** (off) adds `data-require-consent="true"`: the chat starts only once your consent manager sends `hazelchat-consent`. Usercentrics, CCM19 and consentmanager can also allow it through a service named "Hazelchat". See [Visitor consent](https://hazelchat.co/integrations/statamic/#consent).
- Never: the Control Panel, live preview, redirects, downloads, JSON or other non-HTML responses, POST requests, server errors, or a page that already has the tag.
- If the site sets a CSP nonce through Laravel (`Vite::useCspNonce()`), the tag carries it.
- No database tables, no migrations, no scheduled tasks, no requests from the site to Hazelchat, no telemetry.

## More

| Want | Do |
| --- | --- |
| The chat only on some pages | `HAZELCHAT_AUTO_INJECT=false`, then `{{ hazelchat }}` (Antlers) or `<s:hazelchat />` (Blade) where it should load. A page with the tag never gets a second one. |
| Settings in `.env` | `HAZELCHAT_INSTALLATION_CODE`, `HAZELCHAT_WAIT_FOR_CONSENT=true`. On Statamic 6 a code saved in the Control Panel wins. Publish the config with `php artisan vendor:publish --tag=hazelchat-config`. |
| Another code (or none) on one site of a multi-site install | Add the attribute `hazelchat` to that site under **Configure → Sites** or in `resources/sites.yaml`. An empty value turns the chat off there. |

## Remove

`composer remove hazelchat/statamic-hazelchat`, then clear the static cache. The settings file `resources/addons/hazelchat.yaml` (Statamic 6) can be deleted.

## Deutsch

Hazelchat bringt Ihren KI-Chat-Agenten mit einem einzigen Installationscode auf Ihre Statamic-Website (Statamic 5 und 6). Installieren Sie das Addon (`composer require hazelchat/statamic-hazelchat`), fügen Sie in Statamic 6 unter **Werkzeuge → Add-ons → Hazelchat** den Code aus Hazelchat ein (oder in Statamic 5 den Schlüssel als `HAZELCHAT_INSTALLATION_CODE` in die `.env`) und leeren Sie gegebenenfalls den statischen Cache. Die Browser Ihrer Besucher laden den Chat von `app.hazelchat.co`; deren Fragen und Anfragedaten verarbeitet Hazelchat. Sie brauchen ein Hazelchat-Konto; der Free-Tarif gilt für eine Website und bleibt kostenlos.

## Licence

MIT. Hazelchat is an independent addon and is not affiliated with or endorsed by Statamic.
