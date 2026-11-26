---
title: "Poker EV Calculator: Expected Value of a Bet, Bluff or Call"
slug: poker-ev-calculator
excerpt: "A free poker EV calculator. Enter the pot, your bet, how often villain folds or raises and your equity to see the expected value of betting, checking or calling, plus the break-even numbers behind each decision."
meta_title: "Poker EV Calculator — Expected Value of a Bet, Bluff or Call"
meta_description: "Free poker EV calculator: compare the expected value of betting, bluffing, checking or calling. See break-even fold %, required equity, pot odds and implied odds, with the formulas explained."
published: true
published_at: "2026-11-27T09:00:00+00:00"
---

Every decision in poker has an **expected value (EV)**: what the play wins or loses on average if you made it thousands of times. This calculator works it out for the two spots that come up most: **betting (or bluffing)** into a pot, and **calling** a bet. Change any number and the result updates as you type.

[calculator:ev]

## How to use the EV calculator

**Bet or bluff** compares betting with checking:

- **Pot:** the pot before you bet.
- **Your bet:** how much you put in.
- **Villain folds / raises:** how often you expect the opponent to fold or raise. The rest of the time they call. When they raise, the calculator assumes you fold.
- **Your equity when called:** how often you win at showdown against the hands that call. For a pure bluff this is close to 0%.
- **Your equity if you check:** how much of the current pot you expect to win by checking instead.

**Call** works out whether calling a bet is profitable:

- **Pot:** everything in the middle, the bet you face included.
- **Amount to call** and **your equity** against the hands that bet.

Use big blinds or money, as long as every amount is in the same unit.

## The EV formula for a bet

When you bet, three things can happen, and the EV is the sum of each result times how often it happens:

```
EV(bet) = Fold% × Pot
        + Call% × (Equity × (Pot + 2 × Bet) − Bet)
        − Raise% × Bet
```

If villain folds you win the pot. If they call, the pot grows by both bets and you win your equity share of it, minus the bet you put in. If they raise and you fold, you lose your bet.

**Example.** The pot is 100 and you bet 75. Villain folds 40% of the time, raises 10% and calls 50%, and you have 30% equity when called:

- Folds: 0.40 × 100 = **+40**
- Calls: 0.50 × (0.30 × 250 − 75) = **0**
- Raises: 0.10 × −75 = **−7.5**

EV(bet) = **+32.5**. If checking wins you 35% of the 100 pot, EV(check) = **+35**, so here checking is slightly better. Keep the same frequencies but bet 66 instead, and betting edges ahead at **+35.2**. Small changes in sizing and frequencies matter, which is why it helps to put the numbers in.

## How often does a bluff need to work?

A pure bluff (no equity, never raised) breaks even when villain folds:

```
Break-even fold % = Bet / (Pot + Bet)
```

| Bet size | Folds needed |
|---|---|
| 1/3 pot | 25% |
| 1/2 pot | 33% |
| 2/3 pot | 40% |
| Pot | 50% |
| 2× pot | 67% |

Compare these numbers with what your opponents actually do. A player with a high [fold to c-bet](/blog/fold-to-cbet-poker-stat-explained) gives your small bluffs a big edge; one who rarely folds turns the same bluffs into losing plays. The [c-bet percentage guide](/blog/cbet-percentage-poker-stat-explained) covers the other side of that duel.

## The EV of a call and pot odds

When you face a bet, the EV of calling is:

```
EV(call) = Equity × (Pot + Call) − Call
```

It breaks even when your equity equals the **pot odds**:

```
Equity needed = Call / (Pot + Call)
```

**Example.** The pot is 100 and villain bets 50, so the pot is 150 and you must call 50. You need 50 / 200 = **25%** equity. With a flush draw on the turn (9 outs, about 20%), EV(call) = 0.20 × 200 − 50 = **−10**: a fold on direct odds.

### Implied odds

A draw can still be a call if you expect to win more when it hits. The calculator shows the **extra you must win later to break even**: Call / Equity − Pot − Call. In the example that is 50 / 0.20 − 200 = **50** more on the river. If villain will pay off that much often enough, the call becomes profitable.

## EV in practice

- **Estimates beat guesses.** You never know villain's exact fold percentage, but stats such as fold to c-bet or [fold to 3-bet](/blog/fold-to-3bet-poker-stat-explained) give you a real number to start from.
- **EV is long-term.** A +EV bluff still fails often. Results over a few hundred hands say little; the [variance calculator](/blog/poker-variance-calculator) shows how wide the swings are.
- **Track what you actually win.** Your [win rate](/blog/poker-win-rate-calculator) is the sum of all these decisions, so it is the best check that your EV estimates hold up.

## Other poker calculators

- [Poker win rate calculator](/blog/poker-win-rate-calculator): your bb/100, hourly rate and how sure you can be of it.
- [Poker variance calculator](/blog/poker-variance-calculator): downswings, the chance of being down after N hands and risk of ruin.

## FAQ

**What does +EV mean in poker?** A play with positive expected value wins money on average over many repetitions, even if it loses this time.

**Is EV the same as equity?** No. Equity is your share of the pot at showdown; EV also counts the chips you put in and the times villain folds.

**What is a good EV?** Any positive number is profitable, but compare lines: the best play is the one with the highest EV, even if two lines are both positive.

## Keep your tracker reading the right hands

Fold percentages and showdown equities only help if your stats come from accurate hand histories. If you play on CoinPoker, [convert your hands with PokerHandsConverter](/) before importing them into PokerTracker 4 or Hold'em Manager 3, so the numbers you plug into these calculators are the ones you really played.
