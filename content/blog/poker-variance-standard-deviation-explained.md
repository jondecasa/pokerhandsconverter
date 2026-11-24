---
title: "Poker Variance and Standard Deviation: How Lucky or Unlucky Are You?"
slug: poker-variance-standard-deviation-explained
excerpt: "Variance explains why a winning player can lose for months. Here is how standard deviation works in poker, how to build a confidence interval for your win rate, and what to expect from a downswing."
meta_title: "Poker Variance and Standard Deviation Explained (With Examples)"
meta_description: "What variance and standard deviation mean in poker, typical bb/100 standard deviations, how to compute a 95% confidence interval for your win rate and how long a downswing can last."
published: true
published_at: "2026-12-04T09:00:00+00:00"
---

## Why variance matters

Poker has a lot of randomness. A player who beats the game can still lose for thousands of hands, and a break-even player can look like a star for a month. **Variance** is the measure of that swing, and **standard deviation** is the number you use to put a size on it.

The point is simple: your results over a small number of hands say very little about how good you are.

## What standard deviation means

Standard deviation (SD) describes how much your results scatter around your average. In poker it is usually quoted in **bb/100**, the same unit as your [win rate](/blog/bb-per-100-poker-win-rate-explained).

As a rough guide, no-limit hold'em cash games have a standard deviation of about 80 to 100 bb per 100 hands in 6-max, a little less at full ring and more in short-handed formats. Your own number is shown in most trackers, and it grows with how aggressive and loose your style is. Treat these figures as orientation only.

## The standard error: how sure can you be?

The more hands you have, the closer your observed win rate is to your true one. The uncertainty is the **standard error**:

```
standard error = SD / √(hands / 100)
```

A **95% confidence interval** is roughly your observed win rate plus or minus 1.96 times the standard error.

### A worked example

Say you have played 100,000 hands with an observed win rate of 5 bb/100 and a standard deviation of 90 bb/100.

- Blocks of 100 hands: 100,000 / 100 = 1,000, and √1,000 ≈ 31.6
- Standard error: 90 / 31.6 ≈ 2.85 bb/100
- 95% interval: 5 ± 1.96 × 2.85 ≈ 5 ± 5.6, that is from about −0.6 to +10.6 bb/100

After 100,000 hands, a win rate of 5 bb/100 is compatible with a player who is break-even and with one who wins 10 bb/100. That is how wide the uncertainty is.

## What that means for you

- **Small samples prove nothing.** After 10,000 hands the same calculation gives an interval of roughly ±18 bb/100 around your result.
- **Downswings are normal.** A winning player can go many thousands of hands below zero. It does not mean the strategy is wrong. See [poker tilt and downswings](/blog/poker-tilt-and-downswings) for how to deal with it.
- **Bankroll should reflect variance.** The higher your standard deviation relative to your win rate, the more buy-ins you need. [Bankroll management for online cash games](/blog/bankroll-management-online-cash-games) explains how to size it.
- **Judge decisions, not results.** A hand that lost can still have been the right play, and a hand that won can have been a mistake.

## How to use it in practice

1. Read your standard deviation in your tracker, along with your hand count.
2. Compute the interval for your win rate, as above, before you celebrate or panic.
3. Use [how many hands you need before your stats are reliable](/blog/sample-size-reliable-poker-stats) to judge your other numbers, not only your win rate.
4. Review the hands, not only the results, as in [reviewing your hands without a HUD](/blog/review-poker-hands-without-a-hud).

## One caution

These formulas assume your results behave like independent samples, which is approximately true for hands but not perfect: your play changes, your opponents change and the games change. Use the interval as a sense of scale, not as an exact answer.

## Keep your tracker reading the right hands

Every stat on this page is only as good as the hand histories behind it. If you play on CoinPoker, [convert your hands with PokerHandsConverter](/) before importing them into PokerTracker 4 or Hold'em Manager 3, so the numbers your tracker shows are the ones you actually played.
