---
title: "Poker Variance Calculator: Downswings, Confidence and Risk of Ruin"
slug: poker-variance-calculator
excerpt: "A free poker variance calculator and simulator. Enter your win rate, standard deviation and number of hands to see the range of results you can expect, the chance of being down, the hands you need to be sure you win, and your risk of ruin."
meta_title: "Poker Variance Calculator — Simulate Downswings and Risk of Ruin"
meta_description: "Free poker variance calculator and simulator: see the 68% and 95% ranges of your results, the chance of losing over N hands, how many hands prove you are a winner, and your risk of ruin."
published: true
published_at: "2026-11-27T09:20:00+00:00"
---

A winning player can lose for 50,000 hands, and a losing player can run well for just as long. This **poker variance calculator** shows how wide the range of results really is for your **win rate** and **standard deviation**, draws five random runs so you can see what that looks like, and works out your **risk of ruin** for a given bankroll.

[calculator:variance]

## How to use the variance calculator

- **Win rate (bb/100):** your expected win rate. Use the [win rate calculator](/blog/poker-win-rate-calculator) if you only have a result and a hand count.
- **Standard deviation (bb/100):** shown in PokerTracker 4 and Hold'em Manager 3. Around 80–100 is typical for 6-max no-limit hold'em, more for heads-up or PLO.
- **Hands:** how far ahead to look.
- **Bankroll (bb):** for the risk of ruin. 100 bb is one buy-in, so 3,000 bb is 30 buy-ins.

Press **Simulate again** to draw five new runs with the same numbers. The shaded bands do not change: they show where 68% and 95% of all possible runs end up.

## What the numbers mean

With a win rate of 5 bb/100 and a standard deviation of 90 over 100,000 hands:

- **Expected result:** 5 × 1,000 = **+5,000 bb**.
- **Spread:** one standard deviation of the result is 90 × √1,000 ≈ **2,846 bb**.
- **68% of the time** you finish between +2,154 and +7,846 bb.
- **95% of the time** between **−578 and +10,578 bb**. A solid winner still has a real chance of a losing year.
- **Chance of being down** after 100,000 hands: about **4%**.

Drop the win rate to 2 bb/100 and the chance of being down after the same 100,000 hands jumps to about **24%**. Over only 10,000 hands, even the 5 bb/100 player is down about **29%** of the time.

The formulas:

```
Expected result = Win rate × Hands / 100
Standard deviation of the result = SD × √(Hands / 100)
Chance of being down = Φ(−Expected / SD of the result)
```

## How many hands to know you are a winner

The calculator shows how many hands you need before you can be 95% sure your true win rate is above zero:

```
Hands = (1.645 × SD / Win rate)² × 100
```

At 5 bb/100 and SD 90 that is about **88,000 hands**; at 2 bb/100 it is about **548,000**. That is why the [sample size guide](/blog/sample-size-reliable-poker-stats) says win rate is the slowest stat of all to trust.

## Risk of ruin

Risk of ruin is the chance of ever losing your whole bankroll if you keep playing at the same win rate:

```
Risk of ruin = e^(−2 × Win rate × Bankroll / SD²)
```

With 5 bb/100, SD 90 and 3,000 bb (30 buy-ins), the risk of ruin is about **2.5%**. For a 5% risk you need about **2,400 bb**, or 24 buy-ins. At 2 bb/100 the same 30 buy-ins carry a **23%** risk. A lower win rate needs a much bigger bankroll, which is the core idea behind [bankroll management for online cash games](/blog/bankroll-management-online-cash-games).

The formula assumes you never move down in stakes. In practice, dropping a level after a downswing cuts your real risk a lot.

## Living with variance

- **Judge decisions, not results.** Use the [EV calculator](/blog/poker-ev-calculator) to check whether a play was right; the outcome of one hand tells you almost nothing.
- **Expect downswings.** A 20 or 30 buy-in downswing is normal for a small winner over a big sample. Our guide to [tilt and downswings](/blog/poker-tilt-and-downswings) covers how to handle them.
- **Watch the luck you can measure.** All-in adjusted EV removes the luck of all-in showdowns, so comparing it with your actual result shows part of how lucky you ran.

## Other poker calculators

- [Poker EV calculator](/blog/poker-ev-calculator): the expected value of a bet, bluff or call.
- [Poker win rate calculator](/blog/poker-win-rate-calculator): your bb/100, hourly rate and 95% confidence range.

## FAQ

**What is a normal standard deviation in poker?** Around 80–100 bb/100 in 6-max no-limit hold'em, lower at full ring and higher in heads-up and pot-limit Omaha.

**Can a winning player lose over 100,000 hands?** Yes. With 2 bb/100 and SD 90, about one in four 100,000-hand samples ends below zero.

**Does variance ever go away?** No, but it grows more slowly than your expected win. The win grows with the number of hands, the spread only with its square root, so over enough hands skill always shows.

## Keep your tracker reading the right hands

Your win rate and standard deviation are only as accurate as the hand histories behind them. If you play on CoinPoker, [convert your hands with PokerHandsConverter](/) before importing them into PokerTracker 4 or Hold'em Manager 3, so these numbers come from the hands you really played.
