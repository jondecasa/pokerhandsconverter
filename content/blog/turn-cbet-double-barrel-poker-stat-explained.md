---
title: "Turn C-Bet and Double Barrel Percentage: What the Stat Tells You"
slug: turn-cbet-double-barrel-poker-stat-explained
excerpt: "Turn c-bet% shows how often a player keeps betting after c-betting the flop. Here is how it is counted, what a normal number looks like, and how to exploit players who fire too much or give up too soon."
meta_title: "Turn C-Bet% (Double Barrel) in Poker Explained"
meta_description: "What the turn c-bet stat (double barrel) measures, how to calculate it, typical ranges in 6-max cash games, and how to use it against players who give up or keep firing."
published: true
published_at: "2026-12-06T09:00:00+00:00"
---

## What turn c-bet% measures

A **double barrel** is a second bet on the turn after you bet the flop. **Turn c-bet%** is how often a player makes that second bet when they had the chance.

```
turn c-bet% = times you bet the turn after c-betting the flop / times you had the chance
```

The chance exists only when you c-bet the flop, saw the turn, and it was your turn to bet first. If the flop c-bet got raised and the hand ended, or you checked the flop, the hand does not count. That is why this number is always read next to the [flop c-bet%](/blog/cbet-percentage-poker-stat-explained).

## A worked example

You c-bet the flop in 40 hands and saw the turn in 25 of them with the first chance to bet (the others ended on the flop). You bet the turn in 11 of those 25. Your turn c-bet% is 11 / 25 = 44%.

## What a normal number looks like

As a rough guide, many winning regulars in 6-max cash games double barrel somewhere between 40% and 55% of the time. Treat that as a direction, not a target: the right number depends on position, the board, and how the turn card changes who has the stronger range.

- **Good turn cards to keep betting.** Cards that help your range more than the caller's, such as an ace or a king on a flop that favoured you, or a card that completes a draw you can represent.
- **Bad turn cards to slow down.** Cards that help the caller, such as a card that connects the board or pairs a draw, call for fewer bets.
- **Heads-up vs multiway.** Against one opponent you can keep barreling more often. Against several, a bet on the turn is stronger and needs a better hand.

## Reading a high or low number

- **Very high turn c-bet%.** A player who fires almost every turn has bluffs in their range. Call down lighter, and consider raising the turn with draws, especially if their [fold to c-bet](/blog/fold-to-cbet-poker-stat-explained) on later streets is low.
- **Very low turn c-bet%.** A player who bets the flop and almost never bets again is giving up. Float the flop in position and take the pot when they check the turn.
- **A big drop from flop to turn.** A flop c-bet% of 70% with a turn c-bet% of 25% is a very exploitable pattern: the flop bets are often just bluffs that are given up.

## Using it on yourself

If you fire the flop a lot but almost never the turn, good players will call the flop wide and take the pot away on the turn. If you barrel every turn, they will call you down. Break your stat down by position and by flop and turn texture before you change anything. Read it together with your [aggression frequency](/blog/aggression-frequency-afq-poker-stat-explained) and your [WWSF](/blog/wwsf-won-when-saw-flop-poker-stat-explained).

## One caution

Turn c-bet% only counts the hands where you c-bet the flop and then saw the turn, so the sample is much smaller than for flop stats. [How many hands you need before your stats are reliable](/blog/sample-size-reliable-poker-stats) explains how little a number built on a few dozen opportunities can tell you.

## Keep your tracker reading the right hands

Every stat on this page is only as good as the hand histories behind it. If you play on CoinPoker, [convert your hands with PokerHandsConverter](/) before importing them into PokerTracker 4 or Hold'em Manager 3, so the numbers your tracker shows are the ones you actually played.
