---
title: "Aggression Factor (AF) in Poker: What It Is and How to Read It"
slug: aggression-factor-poker-stat-explained
excerpt: "Aggression factor compares how often you bet and raise with how often you call. Here's the formula, how it differs from aggression frequency, and what to aim for."
meta_title: "Aggression Factor (AF) in Poker Explained"
meta_description: "What aggression factor means, the AF formula, how it differs from aggression frequency (AFq), rough ranges by street, and how to use it to find leaks."
published: true
published_at: "2026-10-27T09:00:00+00:00"
---

## What aggression factor measures

**Aggression factor (AF)** compares your active plays with your passive ones after the flop. PokerTracker defines it as the number of times you bet plus the number of times you raise, divided by the number of times you call.

```
AF = (bets + raises) / calls
```

An AF of 2 means you bet or raised twice as often as you called. An AF of 0.5 means you called twice as often as you bet or raised.

## A worked example

Over a stretch of hands you bet 120 times, raised 40 times and called 80 times:

```
AF = (120 + 40) / 80 = 2.0
```

Folds and checks don't count. That is both the strength and the weakness of the stat.

## AF vs aggression frequency (AFq)

AF is a **ratio** and AFq is a **percentage**. Aggression frequency adds folds into the denominator: bets plus raises, divided by bets plus raises plus calls plus folds. Because folds are included, AFq tells you how often you take an aggressive action when you could have done something else, which often reflects your real style better than AF.

| | Counts folds? | Reads as |
|---|---|---|
| AF | No | A ratio, e.g. 2.0 |
| AFq | Yes | A percentage, e.g. 45% |

Trackers differ slightly in exactly which actions they count, so check the stat's definition in your own tracker (in PokerTracker 4 you can see it under Configure > Statistics) before comparing numbers across programs.

## Per street

Trackers split AF by street: flop, turn and river. Players tend to be most aggressive on the flop and to bet less often on the river, so a lower river AF isn't automatically a problem.

## Rough ranges

For 6-max cash games, many players aim for an overall postflop AF somewhere around 2 to 3. Much lower can mean too passive, with too many calls. Much higher can mean heavy aggression with too few calls. These are rough guides that change with stakes and style, not targets.

## What AF can't tell you

- **It ignores quality.** A high AF doesn't say you're betting the right hands.
- **It ignores folds.** A player who folds a lot can have a high AF with few actions.
- **It needs a sample.** Small samples swing wildly. [How many hands for reliable stats](/blog/sample-size-reliable-poker-stats) gives a feel for when to trust a number.

## Use it with the showdown stats

AF is most useful next to [WTSD and W$SD](/blog/wtsd-and-wsd-poker-stats-explained). For example, a low AF with a high WTSD is the classic profile of someone who calls too much. If you're just starting, [VPIP, PFR and 3-bet%](/blog/understanding-vpip-pfr-3bet-hud-stats) cover the preflop side.
