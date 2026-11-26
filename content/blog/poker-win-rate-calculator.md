---
title: "Poker Win Rate Calculator: bb/100, Hourly Rate and Confidence Interval"
slug: poker-win-rate-calculator
excerpt: "A free poker win rate calculator. Enter your result and the hands you played to get your bb/100, your money per 100 hands and per hour, and a 95% confidence range that tells you whether your sample is big enough."
meta_title: "Poker Win Rate Calculator — bb/100, Hourly Rate and Confidence"
meta_description: "Free poker win rate calculator: turn your results into bb/100 and an hourly rate, and see the 95% confidence interval that shows whether you are really a winning player or just running well."
published: true
published_at: "2026-11-27T09:10:00+00:00"
---

Your **win rate** is the clearest single number for how well you play cash games. This calculator turns a result and a hand count into **bb/100**, money per 100 hands and an **hourly rate**, and it adds the part most players skip: a **95% confidence range** that tells you how much of that number is skill and how much is still luck.

[calculator:winrate]

## How to use the win rate calculator

- **Net result:** what you won or lost. Choose **Big blinds** if your tracker already shows it in bb, or **Money** and enter the big blind of your stake (0.50 at NL50, 1 at NL100).
- **Hands played:** the hands in the sample.
- **Standard deviation:** shown by PokerTracker 4 and Hold'em Manager 3 in bb/100. If you do not know it, 80–100 is typical for 6-max no-limit hold'em; heads-up and PLO are higher.
- **Hands per hour (optional):** all tables together, for example 4 tables × 75 hands = 300.

## How win rate is calculated

```
bb/100 = Net result in big blinds / Hands × 100
```

**Example.** You won 1,500 bb over 40,000 hands: 1,500 / 40,000 × 100 = **3.75 bb/100**. At NL50 that is $750, or $1.88 per 100 hands. At 300 hands an hour, 3.75 × 3 = **11.25 bb (about $5.60) per hour**.

For a deeper look at what bb/100 means and how it compares across stakes, see [bb/100 explained](/blog/bb-per-100-poker-win-rate-explained).

## The confidence range: are you really a winner?

A win rate measured over a sample is only an estimate of your true win rate. The calculator puts a 95% range around it:

```
95% range = bb/100 ± 1.96 × SD / √(Hands / 100)
```

In the example, with a standard deviation of 90: 1.96 × 90 / √400 = 8.8, so the range runs from **−5.1 to +12.6 bb/100**. It crosses zero, which means 40,000 hands cannot yet prove you beat the game. With the same win rate you would need roughly 220,000 hands before the whole range sits above zero.

That is not bad news; it is normal. The [sample size guide](/blog/sample-size-reliable-poker-stats) shows how slowly win rate converges compared with stats like VPIP, and the [variance calculator](/blog/poker-variance-calculator) lets you see how far apart two players with the same skill can end up.

## What is a good win rate?

As a rough guide for online no-limit hold'em cash games:

| bb/100 | What it usually means |
|---|---|
| Below 0 | Losing, often to rake as much as to opponents |
| 0–2 | Break-even to small winner |
| 2–5 | Solid winner |
| 5–10 | Strong winner, often at lower stakes |
| Above 10 | Exceptional, or a sample that is still too small |

Win rates drop as stakes rise, and [rake](/blog/what-is-rake-in-online-poker) takes a larger share at micro stakes. Check your rake and [rakeback](/blog/rakeback-and-your-bankroll) before comparing your number with someone else's.

## Turning win rate into decisions

- **Bankroll:** the higher your win rate relative to your standard deviation, the smaller the bankroll you need. The [variance calculator](/blog/poker-variance-calculator) gives the risk of ruin for your numbers, and our [bankroll management guide](/blog/bankroll-management-online-cash-games) covers moving up and down.
- **Spot by spot:** a win rate is the sum of thousands of decisions. The [EV calculator](/blog/poker-ev-calculator) helps you check single bets, bluffs and calls.
- **Game selection:** your hourly rate, not your bb/100, decides whether adding tables or moving up is worth it.

## Other poker calculators

- [Poker EV calculator](/blog/poker-ev-calculator): the expected value of a bet, bluff or call.
- [Poker variance calculator](/blog/poker-variance-calculator): downswings, confidence and risk of ruin.

## FAQ

**How many hands do I need for an accurate win rate?** Far more than most people think: with a standard deviation of 90, even 100,000 hands leaves a range of about ±5.6 bb/100.

**Should I use all-in adjusted win rate?** It removes the luck of all-in showdowns, so it is less noisy than your actual result, but the rest of the variance stays.

**Why is my hourly rate so different from my bb/100?** Hourly depends on how many hands you play. Adding tables raises it as long as your bb/100 does not drop too much.

## Keep your tracker reading the right hands

A win rate is only as accurate as the hand histories behind it. If you play on CoinPoker, [convert your hands with PokerHandsConverter](/) before importing them into PokerTracker 4 or Hold'em Manager 3, so your bb/100 and standard deviation come from the hands you really played.
