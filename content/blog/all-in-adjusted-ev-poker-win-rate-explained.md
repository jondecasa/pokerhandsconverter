---
title: "All-In Adjusted EV in Poker: Win Rate Without the Luck"
slug: all-in-adjusted-ev-poker-win-rate-explained
excerpt: "All-in adjusted EV replaces the result of every all-in with what you were expected to win. Here is how it works, how to read the gap between actual and EV, and why it is not a perfect measure."
meta_title: "All-In Adjusted EV in Poker: Win Rate Without Luck"
meta_description: "What all-in adjusted EV is, how trackers calculate it, how to read the gap between your actual win rate and your EV win rate, and the limits of the number."
published: true
published_at: "2026-12-16T09:00:00+00:00"
---

## What all-in adjusted EV is

Your results include a lot of luck, and the biggest source is all-in hands: you get your chips in as a favourite and still lose the pot, or the other way around. **All-in adjusted EV** (often shown as "EV bb/100") replaces what actually happened in those hands with what you were *expected* to win, based on your equity at the moment the money went in.

```
all-in EV for a hand = your equity at the all-in x the pot - what you put in
```

Trackers then add it up the way they do for your normal [bb/100](/blog/bb-per-100-poker-win-rate-explained), so you can compare two numbers: your **actual** win rate and your **EV** win rate.

## A worked example

You get 100 big blinds all-in preflop with a hand that has 80% equity against your opponent's range at that moment. The pot is 200 big blinds, of which 100 is yours.

- Expected result: 0.80 x 200 - 100 = +60 big blinds.
- Actual result if you win: +100. If you lose: -100.

Whatever happened, the adjusted number records +60. Over many all-ins, the actual and the adjusted numbers get closer.

## How to read the gap

- **Actual above EV.** You ran above expectation in all-in spots. Your real win rate is probably lower than the one you see.
- **Actual below EV.** You ran below expectation. Your real win rate is probably higher than the number you see.
- **Both close.** The luck in your all-in spots was small, so your raw result is a fair summary of those hands.

Seeing a large gap is normal over a few thousand hands. It is exactly the [variance](/blog/poker-variance-standard-deviation-explained) that makes small samples unreliable.

## The limits of the number

All-in adjusted EV removes the luck from all-in spots, but only those. It does not remove luck from hands that were decided later, such as a bluff that worked because the board happened to help you. It also depends on seeing the cards: it only works when both players' hands are known at the all-in. In many cash game hands, a player who folds never shows, so the tracker cannot calculate equity against hands it cannot see. Treat EV as a useful second opinion, not the truth.

## How to use it

1. Compare your actual and EV win rates over a large sample, not over a session.
2. Use the EV number to avoid panic during a downswing. See [tilt and downswings](/blog/poker-tilt-and-downswings).
3. Keep studying your decisions instead of results, as in [reviewing hands without a HUD](/blog/review-poker-hands-without-a-hud).

## One caution

Even the EV win rate has a wide margin of error on a small sample. [How many hands you need before your stats are reliable](/blog/sample-size-reliable-poker-stats) shows how much a number can move.
