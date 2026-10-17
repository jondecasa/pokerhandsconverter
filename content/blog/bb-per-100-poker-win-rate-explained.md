---
title: "What Is bb/100? How to Measure Your Poker Win Rate"
slug: bb-per-100-poker-win-rate-explained
excerpt: "bb/100 is the standard way to measure a cash game win rate. Here's how it's calculated, why it swings so much, and what range your results could really sit in."
meta_title: "What Is bb/100? Measuring Your Poker Win Rate"
meta_description: "What bb/100 means, how to calculate your poker win rate, why it needs tens of thousands of hands, and how big the margin of error really is."
published: true
published_at: "2026-11-10T09:00:00+00:00"
---

## What bb/100 means

**bb/100** is the number of big blinds you win (or lose) per 100 hands. It's the standard measure of win rate in cash games because it works across stakes: 5 bb/100 means the same thing at $0.02 blinds as at $2 blinds.

```
bb/100 = (total result in big blinds / hands played) × 100
```

If you win $90 over 3,000 hands at $0.50 big blinds, that's 180 big blinds. Then bb/100 = (180 / 3,000) × 100 = 6 bb/100.

## Result before and after rake

Check which result your tracker is showing. A win rate **before rake** counts what you won at the table. **After rake** subtracts what the room kept, which is what you actually earned. At low stakes the difference can be large, so compare like with like. [What rake is and how it affects your win rate](/blog/what-is-rake-in-online-poker) explains why, and [rakeback](/blog/rakeback-and-your-bankroll) adds some of it back.

## Why one number is misleading

bb/100 is an average, and poker has a huge amount of variance. Standard deviation is the usual way to measure how much results swing. For 6-max no-limit cash games a commonly quoted figure is somewhere around 80 to 100 bb per 100 hands, though yours depends on your style.

Here's what that does to your margin of error. Using 90 as an example:

```
standard error = 90 / √(hands / 100)
```

| Hands played | Standard error | 95% range (± 1.96 × error) |
|---|---|---|
| 10,000 | 9 bb/100 | about ± 18 bb/100 |
| 50,000 | 4 bb/100 | about ± 8 bb/100 |
| 100,000 | 2.8 bb/100 | about ± 5.6 bb/100 |

So a 5 bb/100 result over 10,000 hands is compatible with a true win rate anywhere from about −13 to +23. That is why a short sample says almost nothing. [How many hands you need before your stats are reliable](/blog/sample-size-reliable-poker-stats) takes that idea further.

## Use it, but carefully

- **Track it over big samples.** Look at months, not sessions.
- **Separate game types.** [6-max and full ring](/blog/6max-vs-full-ring-cash-games) have different win rates and variance.
- **Don't plan around it.** An estimate with a wide range is a poor basis for deciding when to move up. [Bankroll management for online cash game players](/blog/bankroll-management-online-cash-games) gives a framework that doesn't rely on knowing your true win rate.

## Also check the all-in adjusted figure

Many trackers show a second result that replaces your all-in outcomes with their expected value. It strips out some of the luck, which makes it a steadier measure of your play than raw results.
