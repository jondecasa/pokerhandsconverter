---
title: "Aggression Frequency (AFq) vs Aggression Factor (AF) in Poker"
slug: aggression-frequency-afq-poker-stat-explained
excerpt: "Aggression frequency (AFq) shows how often a player bets or raises when they act. Here is the formula, how it differs from aggression factor, and when to use each one."
meta_title: "Aggression Frequency (AFq) vs Aggression Factor (AF) in Poker"
meta_description: "What aggression frequency (AFq) is, the formula, a worked example, the difference with aggression factor (AF), typical numbers and how to read both stats together."
published: true
published_at: "2026-11-30T09:00:00+00:00"
---

## Two ways to measure aggression

Poker trackers show two numbers that both say "how aggressive is this player after the flop", and they are easy to mix up:

- **Aggression factor (AF)** compares aggressive actions to calls.
- **Aggression frequency (AFq)** is the share of all decisions that were aggressive.

## The formulas

```
AF  = (bets + raises) / calls
AFq = (bets + raises) / (bets + raises + calls + folds) × 100
```

AF is a ratio, so it can be 0.5, 2 or 8. AFq is a percentage between 0 and 100. You can see the first one explained in [aggression factor in poker](/blog/aggression-factor-poker-stat-explained).

## A worked example

Over a stretch of hands, a player bets 18 times, raises 6 times, calls 20 times and folds 16 times after the flop.

- AF = (18 + 6) / 20 = 1.2
- AFq = (18 + 6) / (18 + 6 + 20 + 16) = 24 / 60 = 40%

## Why AFq is often the better number

AF ignores folds, and that is its weak point. Two players can have the same AF and play completely differently:

- A player who folds a lot has few calls, so a handful of bets gives a high AF while they spend most of their time folding.
- A player who never calls has a division by zero: AF explodes or is undefined.
- AF is also hard to compare between streets, because the number of calls changes so much from the flop to the river.

AFq does not break in those cases. It always stays between 0 and 100, it includes folding, and it answers a simple question: in what share of the spots did this player take the initiative?

## How to read it

As a rough guide in 6-max cash games, many regulars show a flop AFq somewhere around 40 to 55%, and it tends to be lower on the river than on the flop. Treat these as orientation only: the right numbers depend on stakes and on how you segment the data.

- **Low AFq.** A passive player. Their bets and raises are usually strong, so respect them. You can bet more thinly for value because they call a lot.
- **High AFq.** An aggressive player who bets and raises often. Their range includes many bluffs, so you can call down lighter and let them bluff.
- **AFq that falls on the turn and river.** A player who bets the flop and then slows down is giving up. That is the profile to attack with floats and raises.

## Use both numbers together

AF tells you how many aggressive actions a player takes per call, AFq how often they take the initiative. When the two disagree, look at how often the player folds. Combine them with [c-bet%](/blog/cbet-percentage-poker-stat-explained) and [fold to c-bet](/blog/fold-to-cbet-poker-stat-explained) to see where a player is aggressive and where they give up.

## One caution

Postflop stats need many hands to settle. [How many hands you need before your stats are reliable](/blog/sample-size-reliable-poker-stats) explains how much to trust a number that is based on a few dozen decisions.
