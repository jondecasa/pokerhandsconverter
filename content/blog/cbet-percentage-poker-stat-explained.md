---
title: "C-Bet Percentage in Poker: How to Read the Continuation Bet Stat"
slug: cbet-percentage-poker-stat-explained
excerpt: "C-bet% shows how often the preflop raiser bets the flop. Here is how it is counted, what a healthy number looks like, and how to use it on yourself and on opponents."
meta_title: "C-Bet Percentage (Flop C-Bet%) in Poker Explained"
meta_description: "What flop c-bet% measures, how to calculate it, typical ranges in 6-max cash games, and how position, board texture and number of opponents change the right frequency."
published: true
published_at: "2026-11-24T09:00:00+00:00"
---

## What c-bet% measures

A **continuation bet** (c-bet) is a bet on the flop by the player who raised before the flop, whether or not the flop helped them. **C-bet%** is the share of the times you *could* have made that bet where you actually did.

```
flop c-bet% = times you c-bet the flop / times you had the chance to c-bet
```

The chance only exists when you were the last preflop aggressor, you saw the flop, and it was your turn to bet first (either you were first to act or everyone checked to you). Hands where you raised and the flop never came, or where someone bet before you, are not counted.

## A worked example

You raise before the flop and see the flop 60 times as the aggressor with the first chance to bet. You bet 38 of them and check the other 22. Your flop c-bet% is 38 / 60 = 63%.

Trackers show the same stat for the turn (a "double barrel") and for the river. They behave very differently from the flop, so read them separately.

## What a healthy number looks like

There is no single right answer, but in 6-max cash games most winning regulars c-bet the flop somewhere between about half and two thirds of the time overall. Treat that as a rough guide, because the useful number depends on position, board and how many players are in the pot.

- **Heads-up vs multiway.** One opponent folds far more often than three, so a good c-bet% heads-up is much higher than in a multiway pot.
- **In position vs out of position.** In position you can c-bet more often because you have the last word on later streets.
- **Board texture.** Dry boards such as K-7-2 rainbow favour the raiser and deserve a lot of bets. Connected, two-tone boards such as 9-8-6 hit the caller's range, so a smaller frequency is right.

## Reading a high or a low number

- **Very high c-bet%** (for example over 80%) often means the player bets automatically. Against them, call or raise flops with hands that have equity and punish the bluffs, especially if their [fold to c-bet](/blog/fold-to-cbet-poker-stat-explained) is not high.
- **Very low c-bet%** means a passive or very straightforward player. When they do bet, their range is usually strong, so fold more of your marginal hands.
- **A big gap between the flop and the turn.** A player who c-bets the flop often and almost never fires again is giving up on the turn. That is a spot for floating the flop and betting when they check.

## Using it on yourself

If your c-bet% is very high, your opponents can call or raise much wider. If it is very low, you are passing up easy wins on boards that favour you. Split your stat by position and by number of opponents before you draw conclusions, because an overall number mixes spots that should be played very differently. Read it with your [VPIP and PFR](/blog/understanding-vpip-pfr-3bet-hud-stats) and your [aggression factor](/blog/aggression-factor-poker-stat-explained) to see the whole picture.

## One caution

C-bet% needs a good sample, because it only counts the hands where you were the aggressor and could bet first. [How many hands you need before your stats are reliable](/blog/sample-size-reliable-poker-stats) explains how much to trust a number built on a few dozen opportunities.
