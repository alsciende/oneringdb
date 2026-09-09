# TwilightDB (lotrtcgdb)

Card database for the *Lord of the Rings* Trading Card Game (LOTR TCG). Symfony 8.1 app, PHP >=8.5, Doctrine ORM ^3.2 / DBAL ^4, PostgreSQL 16.

## Dev environment

Docker Compose stack at `docker/dev/compose.yaml`: `symfony` (app + built-in web server on :8080), `postgres` (:5432), `pgweb` (:8081, DB browser UI).

The `Makefile` wraps everything through `docker-compose -f docker/dev/compose.yaml exec symfony`:

- `make up` / `make down` — start/stop the stack
- `make install` — composer install
- `make shell` — shell into the symfony container
- `make lint` — `lint:container` + ecs --fix + rector + phpstan
- `make test` — `bin/phpunit`
- `make ci` — lint + test
- `make db` — drop/recreate schema and load fixtures

## Testing

- PHPUnit 11, config in `phpunit.dist.xml`, tests under `tests/`, mirroring `src/` (`Controller/`, `Repository/`, `Service/`, `Enum/`, `SearchQueryBuilder/`).
- Functional/controller tests extend `WebTestCase`; repository/service tests extend `KernelTestCase`.
- `framework.test: true` is enabled in the test env, so `static::getContainer()` exposes private services too.
- Web profiler is installed in test env but `framework.profiler.collect` is `false` by default — call `$client->enableProfiler()` **before** a request to collect data (e.g. to read query counts from the `db` collector afterwards). It must be called again before each request you want profiled; it resets after each request.
- `tests/DoctrineCollector.php` (trait) — reuse `assertDoctrineQueryCount(int)` instead of re-deriving query-count assertions from the profiler's `db` collector. Used by `CardControllerTest`, `HomeControllerTest`, `CultureControllerTest`, `PublishedSetControllerTest`.
- `tests/TranslationCollector.php` (trait) — `assertNoMissingTranslations()` reads the profiler's `translation` collector. Every functional page test (`CardControllerTest`, `HomeControllerTest`, `CultureControllerTest`, `PublishedSetControllerTest`, `SearchControllerTest`) has a `testNoMissingTranslationsOn...Page` test using it — add one for any new page controller.

## Architecture notes

- Routes: `app_home` (`/`), `app_card` (`/card/{id}`), `app_published_set` (`/set/{set}`), `app_culture` (`/culture/{culture}`), `app_search_form` (`/search`), `app_search_cards` (`/cards`). Set and culture pages both forward into `SearchController::search`.
- `PublishedSet` was renamed from `Pack`, and the `Card`↔`PublishedSet` relation was changed from many-to-many to many-to-one (one set per card). See git history (`ce00c77`, `b5ed854`) for the migration.
- Doctrine second-level cache (L2C) is enabled globally (not just prod) via `doctrine.second_level_cache_pool`, backed by `cache.app` (filesystem — persists across requests/kernel reboots, including between two functional-test requests hitting the same var/cache dir).
  - `Card` and `PublishedSet` entities are marked `#[Cache(usage: 'READ_ONLY')]`.
  - `CardRepository::getCard()` (used by `/card/{id}`) explicitly sets `->setCacheable(true)` on its query — on a cache hit this avoids hitting the DB entirely.
  - `CardRepository::search()` (used by `/cards`, `/set/{set}`, `/culture/{culture}`) explicitly sets `->setCacheable(false)` — search results are never L2C-cached, so these pages always issue live queries.
  - `PublishedSetService::all()` (backs the "Sets" dropdown, including on the homepage) uses a plain `findBy()` — not cacheable either.
- The homepage (`hide_menu`/`hide_search` = true) skips the navbar's menu/search form but renders its own culture/set dropdowns directly in `home/index.html.twig`, calling the same `cultures.all()` / `sets.all()` Twig globals.
- `Card::$position` (nullable int, unique per `(published_set_id, position)`) holds the card's rank within its `PublishedSet`, populated from the fixture JSON's `card_number` (`CardFixtures`, via the snake_case→camelCase name converter: `card_number` → `DtoCard::$cardNumber`).
  - `CardRepository::findPreviousCard()` / `findNextCard()` use it to find the adjacent card in the same set (return `null` at a set boundary or when `position` is `null`); both are `->setCacheable(true)` (same L2C pattern as `getCard()`) so they add zero queries on a cached second visit.
  - `CardController` passes `previousCard`/`nextCard` to `card/index.html.twig`, which renders the prev/next links (absent when `null`).
- `Card::getFullTitle()` prefixes the title with `Card::UNIQUE_SYMBOL` (`•`) when `isUnique()` is true — there is no `getFullName()` on `Card`, `getFullTitle()` (title + optional subtitle) is the method to extend for title-rendering changes.
- `src/Twig/CardTextExtension.php` provides the `card_markup` Twig filter (auto-registered via `autoconfigure: true`, no manual service wiring needed): transforms `<keyword>`/`<phase>` tags in `card.text` into `<span class="keyword">`/`<span class="phase">` (styled bold in `assets/styles/app.css`) and applies `nl2br`. Used in `templates/component/_card_effect.html.twig` instead of the plain `nl2br` filter. No HTML-escaping is done — card text is trusted fixture data. Extend the `str_replace` pairs in `markup()` to support more tags later.
