# PokerCoinverter

A Laravel web app that converts **CoinPoker** hand-history `.txt` files into
**PokerStars-formatted** `.txt` files so they import cleanly into trackers/HUDs
(Hold'em Manager 3, PokerTracker 4, DriveHUD, …). Access to the converter is
gated behind a paid **Stripe subscription** (via Laravel Cashier).

---

## What it does

Given a CoinPoker export, the converter:

1. Rewrites the header `CoinPoker Hand #… → PokerStars Hand #…`.
2. Normalises the currency code in the header (`USDT → USD`, configurable).
3. Relabels or shifts the trailing timezone token on the date (`UTC → ET`).
4. **Cash games only:** prefixes a currency symbol to the bare decimal amounts
   CoinPoker prints without one — blinds/antes, `(5 in chips)`, bets/raises,
   `Uncalled bet (…)`, `collected … from pot`, `Total pot … | Rake …`, and the
   per-seat summary `collected (…)` / `won (…)`.
   Tournament chip amounts stay bare, exactly like a real PokerStars tournament.
5. Replaces the USDT tether sign `₮` with the configured symbol.
6. Records a warning (never throws) for run-it-twice boards, unparseable
   timestamps, and blocks that are not hands.

The conversion logic lives in [`app/Poker/`](app/Poker) and is fully unit-tested
against fixtures in [`tests/Fixtures/`](tests/Fixtures). It is intentionally a
**resilient line transformation**, not a full parse-and-rebuild, because
CoinPoker's layout already mirrors PokerStars.

> ⚠️ **Tune against your own files.** The regex rules are built from the
> documented CoinPoker format. Run a few of *your* real exports through the CLI
> command below and, if a line does not import, adjust the rules in
> `App\Poker\CoinPokerConverter::addCurrencySymbols()` / `convertHeader()` and
> add a fixture + test.

---

## Requirements

- PHP 8.2+
- Composer 2
- Node 18+ (for the Vite asset build)
- MySQL / MariaDB (app DB). The test suite runs on in-memory SQLite, so
  `pdo_sqlite` must also be enabled.
- A Stripe account (test mode is fine to start)

## Setup

```bash
composer install
npm install && npm run build      # or: npm run dev

cp .env.example .env              # if you don't already have .env
php artisan key:generate
```

Create the MySQL database and point `.env` at it (defaults assume local
XAMPP/MariaDB — `root`, no password):

```sql
CREATE DATABASE pokercoinverter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pokercoinverter
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate
php artisan serve
```

> Tests do not touch MySQL — `phpunit.xml` forces `DB_CONNECTION=sqlite` /
> `DB_DATABASE=:memory:`.

### Stripe / Cashier configuration

1. In the Stripe dashboard create a **product** with two recurring **prices**
   (monthly + yearly).
2. Fill in `.env`:

   ```dotenv
   STRIPE_KEY=pk_test_xxx
   STRIPE_SECRET=sk_test_xxx
   STRIPE_WEBHOOK_SECRET=whsec_xxx
   CASHIER_CURRENCY=usd

   STRIPE_PRICE_MONTHLY=price_xxx
   STRIPE_PRICE_YEARLY=price_xxx

   PLAN_MONTHLY_AMOUNT=9
   PLAN_YEARLY_AMOUNT=90
   PLAN_TRIAL_DAYS=7
   ```

3. Point a Stripe webhook at `https://your-app.test/stripe/webhook` and select at
   least these events (or run `php artisan cashier:webhook` to create it):
   `customer.subscription.created`, `customer.subscription.updated`,
   `customer.subscription.deleted`, `customer.updated`, `customer.deleted`,
   `invoice.payment_action_required`, `invoice.payment_succeeded`.

   Local testing: `stripe listen --forward-to localhost:8000/stripe/webhook`.

4. Enable the Stripe **Billing customer portal** (Settings → Billing → Customer
   portal) so `/billing` works.

Plans, trial length, subscription name and every converter knob are in
[`config/pokercoinverter.php`](config/pokercoinverter.php), all env-overridable.

---

## Using it

### Web

**Public marketing site** (no auth — sells the product, explains the conversion):

| Route | Purpose |
|---|---|
| `/` | Landing page: hero with before/after, problem, how-it-works, features, pricing, FAQ, CTAs |
| `/pricing` | Public pricing + billing FAQ |
| `/terms`, `/privacy` | Legal (template wording — replace placeholders) |
| `/register`, `/login` | Auth (Laravel Breeze) |

Marketing pages render through the `<x-marketing-layout>` anonymous component
(`resources/views/components/marketing-layout.blade.php`); page content lives in
`resources/views/welcome.blade.php` and `resources/views/marketing/`.

**The app ("the back") — requires login:**

| Route | Purpose |
|---|---|
| `/dashboard` | Subscription status, usage stats, recent conversions |
| `/account/plans` | Pick a plan → Stripe Checkout |
| `/billing` | Redirect to the Stripe customer portal |
| `/convert` | Upload a CoinPoker `.txt`, get a PokerStars `.txt` — **requires an active subscription** |
| `/conversions/{id}` | Result page: preview, warnings, download |

The `subscribed` middleware (`App\Http\Middleware\EnsureSubscribed`) protects the
converter routes and redirects non-subscribers to `/account/plans`.

### CLI (no subscription needed — handy for tuning)

```bash
php artisan pokerhh:convert path/to/coinpoker.txt path/to/output.txt
php artisan pokerhh:convert coinpoker.txt --timezone-mode=convert --force
```

---

## Tests

```bash
php artisan test
```

- `tests/Unit/CoinPokerConverterTest.php` — conversion rules (cash, tournament,
  timezone modes, idempotency, warnings).
- `tests/Feature/ConverterFlowTest.php` — auth + subscription gating, upload →
  convert → download, per-user ownership.

---

## Notes / limitations

- Storage: converted files are written to the `local` disk under
  `storage/app/conversions/{userId}/`. Add a scheduled cleanup if you don't want
  to keep them forever.
- The converter does **not** currently re-order or recompute pots; it trusts
  CoinPoker's math and only reformats.
- Run-it-twice hands are passed through with a warning — verify how your tracker
  handles them.
- Not affiliated with CoinPoker or PokerStars. "PokerStars format" refers only to
  the text layout that trackers expect.
