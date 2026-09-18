# PokerHandsConverter

**PokerHandsConverter** converts **CoinPoker** hand-history `.txt` files into the
hand-history format that **PokerTracker 4** imports cleanly (Hold'em Manager 3
and similar trackers read it too). Access to the converter is gated behind a
paid **Stripe subscription**.

Built on the Laravel framework (see `composer.json` for the dependency list).

---

## What it does

Verified against real CoinPoker exports. Per hand:

**Header** — `CoinPoker Hand #130114200045: NLH (₮0.01/₮0.02) 2026/09/09 12:01:21 CEST`
becomes `CoinPoker Hand #130114200045:  Hold'em No Limit ($0.01/$0.02 USD) - 2026/09/09 12:01:21 CET [2026/09/09 6:01:21 ET]`:

1. The `CoinPoker Hand #` prefix is **kept** — PokerTracker 4 / Hold'em Manager 3
   import CoinPoker natively (change `config('pokerhandsconverter.converter.room_name')`
   only if your tracker needs a different site name).
2. Game code expanded: `NLH → Hold'em No Limit`, `PLO → Omaha Pot Limit`, etc.
3. Tether sign `₮ → $`, currency code (`USD`) added inside the parenthesis, ` - `
   inserted before the date.
4. Timezone rendered per `timezone_mode`: `dual` (default) keeps the local time
   and appends `[<Eastern time> ET]`; `et` emits a single Eastern stamp; `keep`
   leaves it as-is.

**Body**

5. `₮ → $` everywhere (bare amounts get a `$` as a safety net for exports that
   omit the sign); tournament chip counts stay bare.
6. The per-player `Dealt to <name>` lines are dropped — only
   `Dealt to Hero [Xx Yy]` is kept. If the account has a **CoinPoker ID** set
   (profile / registration, or `--hero=` on the CLI), every `Hero` token is
   replaced with it.
7. `<player>: RETURN <amt>` → `Uncalled bet ($amt) returned to <player>`.
8. `*** SHOWDOWN ***` → `*** SHOW DOWN ***`, or dropped when nobody shows.

**Summary**

9. CoinPoker-only lines removed: `Hand was run once`, empty `Board [  ]`,
   `Game ended:` / `Game started:`.
10. `Seat N: NAME won (amt)` → `Seat N: NAME (position) collected ($amt)`;
    `(button)` / `(small blind)` / `(big blind)` tags inserted; the misleading
    `(didn't bet)` stripped from blind posters.

**Run it twice** (`run_it_twice_mode`)

11. `keep` (default) — **one hand**, run-it-twice markers normalised to the
    format PT4 / HM3 import natively (they split the pot themselves for EV):
    `*** FIRST/SECOND SHOWDOWN ***` → `*** FIRST/SECOND SHOW DOWN ***`,
    `Hand was run with two boards` → `Hand was run twice`,
    `FIRST Board [ … ]` → `Board [ … ]` (ordinal dropped, padding trimmed).
    CoinPoker's `Total pot` is the grand total of every board's payout, so the
    single summary line is **divided by the board count** to state the pot per
    board (what the tracker cross-checks against the action). A warning is
    emitted.
12. `split` (opt-in) — a hand run twice becomes **one hand per board** (ids
    `<id>-1`, `<id>-2`, …), each with the shared action duplicated, its own
    board/showdown and pot (`that run's collected + rake ÷ N`). Handy for
    separate review, but the tracker's own pot check may not agree with the
    duplicated action.

**Per-file stats** — the result screen (and the `conversions` row) report the
hand count plus **splash pots** (a `SPLASH dropped ₮…` / `MEGA SPLASH dropped ₮…`
line — dropped from the output, kept for the count) and **bomb pots** (CoinPoker
tags the game code `NLH BombPot` in the header; failing that, no blinds + everyone
antes + straight to a flop). `NLH BombPot` is normalised to `Hold'em No Limit`
(the bomb pot is expressed by the antes/no-blinds structure, as trackers expect).

**Stake gate** — each package can set a **stake cap** (`stakes_cap`, e.g. `NL50`).
On upload the file's highest cash big blind is compared to the cap
(`NL50` → `$0.50`); anything above it is refused with a "upgrade your package"
message and nothing is stored. A package with no cap converts any stake;
tournaments are never gated. `samples/coinpoker-nl50-example.txt` is a 4-hand
NL50 file in CoinPoker's raw format for trying this.

Warnings (never exceptions) also cover unknown game codes, unknown timezones and
blocks that are not hands.

The logic lives in [`app/Poker/`](app/Poker) and is locked down by a full
input→output fixture in [`tests/Fixtures/`](tests/Fixtures) (`coinpoker-cash.txt`
→ `expected/cash-pt4.txt`). It is a **resilient line transformation**, not
a full parse-and-rebuild.

> ⚠️ **Tune against your own files.** Rules are built from real samples but
> CoinPoker has many game types / layouts. Run your exports through the CLI
> command below; if a line does not import, adjust
> `App\Poker\CoinPokerConverter` and add a fixture + test.

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
CREATE DATABASE pokerhandsconverter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pokerhandsconverter
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate
php artisan serve
```

> Tests do not touch MySQL — `phpunit.xml` forces `DB_CONNECTION=sqlite` /
> `DB_DATABASE=:memory:`.

### Test user (local development only)

A ready-to-use account exists in the local database:

| Field | Value |
|---|---|
| Email | `preview-admin@example.com` |
| Password | `password` |

It is an **admin** and has a fake active subscription, so it can open the
converter, the ranges and `/admin/*` without going through Stripe.

This user is not created by `php artisan db:seed` (that only makes
`test@example.com` / `password`, with no subscription and no admin rights), so
on a fresh database create it in `php artisan tinker`:

```php
$u = App\Models\User::create(['name' => 'Preview Admin', 'email' => 'preview-admin@example.com', 'password' => bcrypt('password'), 'email_verified_at' => now()]);
$u->forceFill(['is_admin' => true])->save();
$s = $u->subscriptions()->create(['type' => 'default', 'stripe_id' => 'sub_preview', 'stripe_status' => 'active', 'stripe_price' => 'price_monthly', 'quantity' => 1]);
$s->items()->create(['stripe_id' => 'si_preview', 'stripe_product' => 'prod_preview', 'stripe_price' => 'price_monthly', 'quantity' => 1]);
```

> ⚠️ Local development only. Never create these users on production — the
> password is public. There, run `php artisan db:seed --class=PlanSeeder` (or
> the specific seeder you need) instead of a bare `db:seed`.

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

`STRIPE_PRICE_*` / `PLAN_*` env vars only seed the two starter packages on a
fresh `db:seed`. After that, packages live in the DB — manage them in the admin
UI (below). Subscription name, default trial length, the stake ladder and every
converter knob stay in [`config/pokerhandsconverter.php`](config/pokerhandsconverter.php).

### Packages (pricing) & the admin panel

Packages ("plans") are rows in the `plans` table, edited by admins at
**`/admin/plans`** (`App\Http\Controllers\Admin\PlanController`, gated by the
`admin` middleware = `users.is_admin`). Per package:

- name, slug, description, price + currency + billing interval
- **Stripe Price ID** (`price_…`) — required before anyone can subscribe
- **stake covered** — pick a cap from the ladder in config (`Covers up to NL100`)
  or set a free-text stake label
- feature bullet list, trial-day override, sort order
- **Visible** — listed on the public pricing page and the in-app picker
- **Hidden** (not visible) — not listed, but still subscribable via its direct
  link `/subscribe/<slug>` (share with one customer / grandfathered deals)
- **Inactive** — retired; blocks new sign-ups entirely (existing subs untouched)

Make yourself an admin:

```bash
php artisan pokerhandsconverter:make-admin you@example.com
php artisan pokerhandsconverter:make-admin you@example.com --revoke   # undo
```

`php artisan db:seed --class=PlanSeeder` (re)creates the Monthly/Yearly starters.

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
| `/convert` | Upload a CoinPoker `.txt`, get a PokerTracker 4-ready `.txt` — **requires an active subscription** |
| `/conversions/{id}` | Result screen: hands / splash pots / bomb pots counts, warnings, preview, download |
| `/admin/plans` | Package & pricing maintenance — **admins only** (`users.is_admin`) |

The `subscribed` middleware (`App\Http\Middleware\EnsureSubscribed`) protects the
converter routes and redirects non-subscribers to `/account/plans`. The `admin`
middleware (`App\Http\Middleware\EnsureAdmin`) 403s everyone who is not an admin.

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
- Run-it-twice hands are kept as one native multi-board hand by default
  (`CONVERTER_RUN_IT_TWICE_MODE=split` to emit one hand per board instead).
- Not affiliated with CoinPoker, PokerTracker or Hold'em Manager. Product names
  describe compatibility only. The `CoinPoker Hand #` header prefix is preserved
  so trackers import the file with their native CoinPoker profile
  (`App\Poker\ConverterOptions::$roomName`).
